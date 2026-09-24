<?php
/**
 * Votes: storage, who is voting, and counts.
 *
 * @package LikeDislike
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every vote is a row in {prefix}likedislikewp. Counts are kept in post and comment
 * meta so pages, columns and "most liked" lists never count rows.
 *
 * One vote per person per item: logged-in users by account, visitors by a random
 * browser id kept in a cookie, or by a keyed hash of their IP address when the site
 * asks for that. IP addresses themselves are never stored.
 */
class LDFW_Votes {

	const COOKIE   = 'ldfw_visitor';
	const LIKES    = '_ldfw_likes';
	const DISLIKES = '_ldfw_dislikes';
	const HIDE     = '_ldfw_hide';

	/**
	 * The logged-in user's votes, loaded in batches while a page renders.
	 *
	 * @var array<string,string> "type:id" => status.
	 */
	private static $mine = array();

	/**
	 * The votes table.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'likedislikewp';
	}

	/**
	 * Creates the table, or adds what 2.x did not have.
	 */
	public static function install() {
		global $wpdb;
		$table   = esc_sql( self::table() );
		$charset = $wpdb->get_charset_collate();

		// Columns 2.x created keep their exact definitions, so dbDelta leaves them alone.
		$sql = "CREATE TABLE {$table} (
id bigint(20) NOT NULL AUTO_INCREMENT,
post_id bigint(20) NOT NULL,
object_type varchar(20) NOT NULL DEFAULT 'post',
date_time datetime NOT NULL,
ip varchar(100) NOT NULL,
user_id varchar(100) NOT NULL,
token varchar(64) NOT NULL DEFAULT '',
status varchar(30) NOT NULL,
feedback text NULL,
PRIMARY KEY  (id),
KEY post_id (post_id),
KEY object_vote (object_type,post_id,user_id),
KEY date_time (date_time),
KEY user_id (user_id),
KEY status (status),
KEY token (token)
) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/* Who is voting ------------------------------------------------------------------------ */

	/**
	 * The visitor's IP address, as PHP sees it. Sites behind a proxy or CDN can pass the
	 * real address through the ldfw_visitor_ip filter.
	 *
	 * @return string
	 */
	private static function ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		/**
		 * Filters the IP address used to limit visitors to one vote.
		 *
		 * @param string $ip IP address from REMOTE_ADDR.
		 */
		$ip = (string) apply_filters( 'ldfw_visitor_ip', $ip );
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * A keyed hash of the visitor's IP address. The key is the site's own secret, so the
	 * hash can't be reversed by trying every address.
	 *
	 * @return string
	 */
	private static function ip_hash() {
		$ip = self::ip();
		return '' === $ip ? '' : hash_hmac( 'sha256', 'ldfw|' . $ip, wp_salt( 'auth' ) );
	}

	/**
	 * The person voting: a user ID, or for visitors a hashed browser id and IP.
	 *
	 * @param bool $create Give a visitor without a browser id a new one.
	 * @return array{user:int,token:string,ip:string}
	 */
	public static function visitor( $create = false ) {
		$user = get_current_user_id();
		if ( $user ) {
			return array(
				'user'  => $user,
				'token' => '',
				'ip'    => '',
			);
		}

		$raw = isset( $_COOKIE[ self::COOKIE ] ) ? preg_replace( '/[^a-f0-9]/', '', sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) ) : '';
		if ( 32 !== strlen( $raw ) && $create ) {
			$raw = md5( wp_generate_uuid4() . wp_rand() );
			if ( ! headers_sent() ) {
				setcookie(
					self::COOKIE,
					$raw,
					array(
						'expires'  => time() + YEAR_IN_SECONDS,
						'path'     => COOKIEPATH ? COOKIEPATH : '/',
						'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
						'secure'   => is_ssl(),
						'httponly' => true,
						'samesite' => 'Lax',
					)
				);
			}
			$_COOKIE[ self::COOKIE ] = $raw;
		}

		return array(
			'user'  => 0,
			'token' => 32 === strlen( $raw ) ? hash( 'sha256', $raw ) : '',
			'ip'    => self::ip_hash(),
		);
	}

	/**
	 * The visitor's vote on an item.
	 *
	 * @param string $type    'post' or 'comment'.
	 * @param int    $id      Item ID.
	 * @param array  $visitor From visitor().
	 * @return object|null Row with id and status.
	 */
	private static function find( $type, $id, $visitor ) {
		global $wpdb;
		$table = esc_sql( self::table() );

		if ( $visitor['user'] ) {
			$where = $wpdb->prepare( 'user_id = %s', (string) $visitor['user'] );
		} elseif ( 'ip' === LDFW_Settings::get( 'guests' ) ) {
			if ( '' === $visitor['ip'] ) {
				return null;
			}
			$where = $wpdb->prepare( "user_id = '0' AND ip = %s", $visitor['ip'] );
		} else {
			if ( '' === $visitor['token'] ) {
				return null;
			}
			$where = $wpdb->prepare( "user_id = '0' AND token = %s", $visitor['token'] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table; $where is prepared above.
		return $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM {$table} WHERE object_type = %s AND post_id = %d AND {$where} ORDER BY id DESC LIMIT 1", $type, $id ) );
	}

	/* Voting --------------------------------------------------------------------------------- */

	/**
	 * Whether an item can be voted on by the current visitor.
	 *
	 * @param string $type 'post' or 'comment'.
	 * @param int    $id   Item ID.
	 * @return true|WP_Error
	 */
	public static function can_vote( $type, $id ) {
		if ( 'members' === LDFW_Settings::get( 'who' ) && ! is_user_logged_in() ) {
			return new WP_Error( 'ldfw_login', __( 'Please log in to vote.', 'like-dislike-for-wp' ), array( 'status' => 401 ) );
		}

		if ( 'comment' === $type ) {
			$comment = get_comment( $id );
			if ( ! LDFW_Settings::get( 'comments' ) || ! $comment || '1' !== (string) $comment->comment_approved ) {
				return new WP_Error( 'ldfw_missing', __( 'This comment can no longer be voted on.', 'like-dislike-for-wp' ), array( 'status' => 404 ) );
			}
			$id = (int) $comment->comment_post_ID;
		}

		$post = get_post( $id );
		$open = $post && ( is_post_publicly_viewable( $post ) || current_user_can( 'read_post', $post->ID ) ) && ! post_password_required( $post );
		if ( ! $open ) {
			return new WP_Error( 'ldfw_missing', __( 'This item can no longer be voted on.', 'like-dislike-for-wp' ), array( 'status' => 404 ) );
		}
		return true;
	}

	/**
	 * Records a click: a new vote, a switch from like to dislike or back, or taking the
	 * vote back by clicking the same button again.
	 *
	 * @param string $type   'post' or 'comment'.
	 * @param int    $id     Item ID.
	 * @param string $choice 'like' or 'dislike'.
	 * @return array|WP_Error State and counts.
	 */
	public static function vote( $type, $id, $choice ) {
		global $wpdb;
		$table   = esc_sql( self::table() );
		$visitor = self::visitor( true );

		if ( ! $visitor['user'] && '' === ( 'ip' === LDFW_Settings::get( 'guests' ) ? $visitor['ip'] : $visitor['token'] ) ) {
			return new WP_Error( 'ldfw_unknown', __( 'Your vote could not be recorded. Please allow cookies and try again.', 'like-dislike-for-wp' ), array( 'status' => 400 ) );
		}

		// One click at a time per person and item, so double clicks can't double count.
		$lock = 'ldfw_' . md5( $type . '|' . $id . '|' . $visitor['user'] . '|' . $visitor['token'] . '|' . $visitor['ip'] );
		$wpdb->query( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		$existing = self::find( $type, $id, $visitor );
		$now      = current_time( 'mysql', true );
		$result   = null;

		if ( $existing && $existing->status === $choice ) {
			$wpdb->delete( $table, array( 'id' => (int) $existing->id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$state = '';
		} elseif ( $existing ) {
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table,
				array(
					'status'    => $choice,
					'date_time' => $now,
					'feedback'  => null,
				),
				array( 'id' => (int) $existing->id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$state = $choice;
		} else {
			if ( ! $visitor['user'] && '' !== $visitor['ip'] && self::too_many( $type, $id, $visitor['ip'] ) ) {
				$result = new WP_Error( 'ldfw_flood', __( 'Too many votes from your network. Please try again later.', 'like-dislike-for-wp' ), array( 'status' => 429 ) );
			} else {
				$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$table,
					array(
						'post_id'     => $id,
						'object_type' => $type,
						'date_time'   => $now,
						'ip'          => $visitor['ip'],
						'user_id'     => (string) $visitor['user'],
						'token'       => $visitor['token'],
						'status'      => $choice,
					),
					array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
				);
			}
			$state = $choice;
		}

		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$counts = self::recount( $type, $id );

		/**
		 * Fires after a vote is added, changed or taken back.
		 *
		 * @param string $type    'post' or 'comment'.
		 * @param int    $id      Item ID.
		 * @param string $state   'like', 'dislike', or '' when the vote was taken back.
		 * @param int    $user_id Voter, 0 for visitors.
		 */
		do_action( 'ldfw_voted', $type, $id, $state, $visitor['user'] );

		return array(
			'state'  => $state,
			'counts' => $counts,
		);
	}

	/**
	 * Whether one network has voted on an item suspiciously often today.
	 *
	 * @param string $type 'post' or 'comment'.
	 * @param int    $id   Item ID.
	 * @param string $ip   IP hash.
	 * @return bool
	 */
	private static function too_many( $type, $id, $ip ) {
		global $wpdb;
		$table = esc_sql( self::table() );

		/**
		 * Filters how many visitor votes one IP address can add to an item in a day.
		 *
		 * @param int $limit Votes.
		 */
		$limit = (int) apply_filters( 'ldfw_votes_per_ip', 25 );
		$count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE object_type = %s AND post_id = %d AND ip = %s AND date_time > %s", $type, $id, $ip, gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		return $limit > 0 && $count >= $limit;
	}

	/**
	 * Saves the visitor's answer to "what could be better?" on their dislike.
	 *
	 * @param string $type 'post' or 'comment'.
	 * @param int    $id   Item ID.
	 * @param string $text Feedback.
	 * @return true|WP_Error
	 */
	public static function feedback( $type, $id, $text ) {
		global $wpdb;
		$existing = self::find( $type, $id, self::visitor() );
		if ( ! $existing || 'dislike' !== $existing->status ) {
			return new WP_Error( 'ldfw_no_vote', __( 'Your vote could not be found. Please try again.', 'like-dislike-for-wp' ), array( 'status' => 400 ) );
		}
		$wpdb->update( self::table(), array( 'feedback' => $text ), array( 'id' => (int) $existing->id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		/**
		 * Fires after a visitor says what could be better.
		 *
		 * @param string $type 'post' or 'comment'.
		 * @param int    $id   Item ID.
		 * @param string $text Feedback.
		 */
		do_action( 'ldfw_feedback', $type, $id, $text );
		return true;
	}

	/* Counts ----------------------------------------------------------------------------------- */

	/**
	 * Counts an item's votes again and stores the totals.
	 *
	 * @param string $type 'post' or 'comment'.
	 * @param int    $id   Item ID.
	 * @return array{like:int,dislike:int}
	 */
	public static function recount( $type, $id ) {
		global $wpdb;
		$table  = esc_sql( self::table() );
		$rows   = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT status, COUNT(*) AS n FROM {$table} WHERE object_type = %s AND post_id = %d GROUP BY status", $type, $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		$counts = array(
			'like'    => 0,
			'dislike' => 0,
		);
		foreach ( $rows as $row ) {
			if ( isset( $counts[ $row->status ] ) ) {
				$counts[ $row->status ] = (int) $row->n;
			}
		}
		$meta = 'comment' === $type ? 'comment' : 'post';
		update_metadata( $meta, $id, self::LIKES, $counts['like'] );
		update_metadata( $meta, $id, self::DISLIKES, $counts['dislike'] );
		return $counts;
	}

	/**
	 * An item's stored counts.
	 *
	 * @param string $type 'post' or 'comment'.
	 * @param int    $id   Item ID.
	 * @return array{like:int,dislike:int}
	 */
	public static function counts( $type, $id ) {
		$meta = 'comment' === $type ? 'comment' : 'post';
		return array(
			'like'    => (int) get_metadata( $meta, $id, self::LIKES, true ),
			'dislike' => (int) get_metadata( $meta, $id, self::DISLIKES, true ),
		);
	}

	/**
	 * Loads the logged-in user's votes on several items at once.
	 *
	 * @param string $type 'post' or 'comment'.
	 * @param int[]  $ids  Item IDs.
	 */
	public static function preload( $type, $ids ) {
		global $wpdb;
		$user = get_current_user_id();
		$ids  = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
		if ( ! $user || ! $ids ) {
			return;
		}
		foreach ( $ids as $id ) {
			self::$mine[ $type . ':' . $id ] = '';
		}
		$table = esc_sql( self::table() );
		$in    = implode( ',', $ids );
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT post_id, status FROM {$table} WHERE object_type = %s AND user_id = %s AND post_id IN ({$in})", $type, (string) $user ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $in holds integers only.
		);
		foreach ( $rows as $row ) {
			self::$mine[ $type . ':' . (int) $row->post_id ] = (string) $row->status;
		}
	}

	/**
	 * The logged-in user's vote on an item. Visitors' votes are never put in the page,
	 * which may be cached; the browser remembers them instead.
	 *
	 * @param string $type 'post' or 'comment'.
	 * @param int    $id   Item ID.
	 * @return string 'like', 'dislike' or ''.
	 */
	public static function mine( $type, $id ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		$key = $type . ':' . (int) $id;
		if ( ! isset( self::$mine[ $key ] ) ) {
			self::preload( $type, array( $id ) );
		}
		return isset( self::$mine[ $key ] ) ? self::$mine[ $key ] : '';
	}

	/**
	 * Removes every vote on an item.
	 *
	 * @param string $type 'post' or 'comment'.
	 * @param int    $id   Item ID.
	 */
	public static function reset( $type, $id ) {
		global $wpdb;
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'object_type' => $type,
				'post_id'     => (int) $id,
			),
			array( '%s', '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		self::recount( $type, $id );
	}
}

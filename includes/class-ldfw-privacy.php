<?php
/**
 * Privacy: suggested policy text, and export and erasure of a user's votes.
 *
 * @package LikeDislike
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hooks into Tools → Export Personal Data and Erase Personal Data.
 */
class LDFW_Privacy {

	const PER_PAGE = 100;

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'policy' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
	}

	/**
	 * Suggested text for the site's privacy policy.
	 */
	public static function policy() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$text = __( 'When you like or dislike a post or a comment, we store your vote and the date. If you are logged in, the vote is linked to your account. If you are not, we store a random ID in a cookie named ldfw_visitor and a one-way hash of your IP address, so that each person votes once; your IP address itself is not stored. If you tell us what could be better after a dislike, we store your answer with your vote.', 'like-dislike-for-wp' );
		wp_add_privacy_policy_content( 'Like Dislike', wp_kses_post( wpautop( $text ) ) );
	}

	/**
	 * Registers the exporter.
	 *
	 * @param array $exporters Exporters.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['like-dislike-for-wp'] = array(
			'exporter_friendly_name' => __( 'Likes and dislikes', 'like-dislike-for-wp' ),
			'callback'               => array( __CLASS__, 'export' ),
		);
		return $exporters;
	}

	/**
	 * Registers the eraser.
	 *
	 * @param array $erasers Erasers.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['like-dislike-for-wp'] = array(
			'eraser_friendly_name' => __( 'Likes and dislikes', 'like-dislike-for-wp' ),
			'callback'             => array( __CLASS__, 'erase' ),
		);
		return $erasers;
	}

	/**
	 * A user's votes, one page at a time.
	 *
	 * @param string $email Email address.
	 * @param int    $page  Page, from 1.
	 * @return array
	 */
	public static function export( $email, $page = 1 ) {
		global $wpdb;
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}
		$table = esc_sql( LDFW_Votes::table() );
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT id, post_id, object_type, status, date_time, feedback FROM {$table} WHERE user_id = %s ORDER BY id ASC LIMIT %d OFFSET %d", (string) $user->ID, self::PER_PAGE, ( max( 1, (int) $page ) - 1 ) * self::PER_PAGE ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$data = array();
		foreach ( $rows as $row ) {
			if ( 'comment' === $row->object_type ) {
				$comment = get_comment( (int) $row->post_id );
				$item    = $comment ? sprintf( /* translators: %s: post title. */ __( 'A comment on “%s”', 'like-dislike-for-wp' ), get_the_title( (int) $comment->comment_post_ID ) ) : __( 'A deleted comment', 'like-dislike-for-wp' );
			} else {
				$item = get_post( (int) $row->post_id ) ? get_the_title( (int) $row->post_id ) : __( 'A deleted post', 'like-dislike-for-wp' );
			}
			$fields = array(
				array(
					'name'  => __( 'Item', 'like-dislike-for-wp' ),
					'value' => $item,
				),
				array(
					'name'  => __( 'Vote', 'like-dislike-for-wp' ),
					'value' => 'dislike' === $row->status ? __( 'Dislike', 'like-dislike-for-wp' ) : __( 'Like', 'like-dislike-for-wp' ),
				),
				array(
					'name'  => __( 'Date', 'like-dislike-for-wp' ),
					'value' => get_date_from_gmt( $row->date_time ),
				),
			);
			if ( ! empty( $row->feedback ) ) {
				$fields[] = array(
					'name'  => __( 'What could be better', 'like-dislike-for-wp' ),
					'value' => $row->feedback,
				);
			}
			$data[] = array(
				'group_id'    => 'like-dislike-for-wp',
				'group_label' => __( 'Likes and dislikes', 'like-dislike-for-wp' ),
				'item_id'     => 'ldfw-vote-' . (int) $row->id,
				'data'        => $fields,
			);
		}

		return array(
			'data' => $data,
			'done' => count( $rows ) < self::PER_PAGE,
		);
	}

	/**
	 * Removes a user's votes and updates the counts they were part of.
	 *
	 * @param string $email Email address.
	 * @param int    $page  Page, from 1.
	 * @return array
	 */
	public static function erase( $email, $page = 1 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress passes the page; one pass removes everything.
		global $wpdb;
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}
		$table   = esc_sql( LDFW_Votes::table() );
		$items   = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT DISTINCT object_type, post_id FROM {$table} WHERE user_id = %s", (string) $user->ID ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		$removed = (int) $wpdb->delete( $table, array( 'user_id' => (string) $user->ID ), array( '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( $items as $item ) {
			LDFW_Votes::recount( $item->object_type, (int) $item->post_id );
		}
		return array(
			'items_removed'  => $removed > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}

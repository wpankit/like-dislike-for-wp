<?php
/**
 * Stats: totals, trends, top posts and feedback.
 *
 * @package LikeDislike
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read-only queries over the votes table.
 */
class LDFW_Stats {

	/**
	 * The start of a period, in GMT, or '' for all time.
	 *
	 * @param int $days Days back, 0 for all time.
	 * @return string
	 */
	private static function since( $days ) {
		return $days > 0 ? gmdate( 'Y-m-d 00:00:00', time() - ( $days - 1 ) * DAY_IN_SECONDS ) : '';
	}

	/**
	 * Likes and dislikes in a period, on posts and comments.
	 *
	 * @param int $days Days back, 0 for all time.
	 * @return array{like:int,dislike:int}
	 */
	public static function totals( $days ) {
		global $wpdb;
		$table = esc_sql( LDFW_Votes::table() );
		$since = self::since( $days );
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table; all time uses an always-true date.
			$wpdb->prepare( "SELECT status, COUNT(*) AS n FROM {$table} WHERE date_time >= %s GROUP BY status", $since ? $since : '1000-01-01 00:00:00' )
		);
		$out = array(
			'like'    => 0,
			'dislike' => 0,
		);
		foreach ( $rows as $row ) {
			if ( isset( $out[ $row->status ] ) ) {
				$out[ $row->status ] = (int) $row->n;
			}
		}
		return $out;
	}

	/**
	 * Likes and dislikes per day, oldest first.
	 *
	 * @param int $days Days.
	 * @return array[] Each with date (Y-m-d), like and dislike.
	 */
	public static function daily( $days ) {
		global $wpdb;
		$table = esc_sql( LDFW_Votes::table() );
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT DATE(date_time) AS d, status, COUNT(*) AS n FROM {$table} WHERE date_time >= %s GROUP BY d, status", self::since( $days ) ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		$out   = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$day         = gmdate( 'Y-m-d', time() - $i * DAY_IN_SECONDS );
			$out[ $day ] = array(
				'date'    => $day,
				'like'    => 0,
				'dislike' => 0,
			);
		}
		foreach ( $rows as $row ) {
			if ( isset( $out[ $row->d ] ) && isset( $out[ $row->d ][ $row->status ] ) ) {
				$out[ $row->d ][ $row->status ] = (int) $row->n;
			}
		}
		return array_values( $out );
	}

	/**
	 * Posts with the most likes or dislikes in a period.
	 *
	 * @param string $by    'like' or 'dislike'.
	 * @param int    $days  Days back, 0 for all time.
	 * @param int    $limit How many.
	 * @return array[] Each with id, like, dislike and last (GMT).
	 */
	public static function top( $by, $days, $limit = 10 ) {
		global $wpdb;
		$table = esc_sql( LDFW_Votes::table() );
		$since = self::since( $days );
		$order = 'dislike' === $by ? 'dislikes' : 'likes';
		$where = "object_type = 'post'" . ( $since ? $wpdb->prepare( ' AND date_time >= %s', $since ) : '' );
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table; $where is prepared above and $order is one of two fixed words.
			$wpdb->prepare( "SELECT post_id, SUM(status = 'like') AS likes, SUM(status = 'dislike') AS dislikes, MAX(date_time) AS last FROM {$table} WHERE {$where} GROUP BY post_id HAVING {$order} > 0 ORDER BY {$order} DESC, last DESC LIMIT %d", $limit * 2 )
		);
		$out = array();
		foreach ( $rows as $row ) {
			$post = get_post( (int) $row->post_id );
			if ( ! $post || 'trash' === $post->post_status ) {
				continue;
			}
			$out[] = array(
				'id'      => (int) $row->post_id,
				'like'    => (int) $row->likes,
				'dislike' => (int) $row->dislikes,
				'last'    => (string) $row->last,
			);
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * The latest answers to "what could be better?".
	 *
	 * @param int $limit How many.
	 * @return object[] Rows with post_id, object_type, feedback and date_time.
	 */
	public static function feedback( $limit = 10 ) {
		global $wpdb;
		$table = esc_sql( LDFW_Votes::table() );
		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT post_id, object_type, feedback, date_time FROM {$table} WHERE feedback IS NOT NULL AND feedback <> '' ORDER BY id DESC LIMIT %d", $limit ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	/**
	 * The most liked posts of a type, for the block and shortcode.
	 *
	 * @param int    $number    How many.
	 * @param string $post_type Post type.
	 * @param string $period    'all', 'year', 'month' or 'week'.
	 * @return array[] Each with id and likes.
	 */
	public static function most_liked( $number, $post_type, $period ) {
		$days = array(
			'week'  => 7,
			'month' => 30,
			'year'  => 365,
		);

		if ( ! isset( $days[ $period ] ) ) {
			$query = new WP_Query(
				array(
					'post_type'           => $post_type,
					'post_status'         => 'publish',
					'posts_per_page'      => $number,
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
					'fields'              => 'ids',
					'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'likes' => array(
							'key'     => LDFW_Votes::LIKES,
							'value'   => 0,
							'compare' => '>',
							'type'    => 'NUMERIC',
						),
					),
					'orderby'             => array(
						'likes' => 'DESC',
						'date'  => 'DESC',
					),
				)
			);
			return array_map(
				function ( $id ) {
					return array(
						'id'    => (int) $id,
						'likes' => LDFW_Votes::counts( 'post', (int) $id )['like'],
					);
				},
				$query->posts
			);
		}

		$key   = 'ldfw_most_liked_' . md5( $number . '|' . $post_type . '|' . $period );
		$items = get_transient( $key );
		if ( is_array( $items ) ) {
			return $items;
		}

		global $wpdb;
		$table = esc_sql( LDFW_Votes::table() );
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT v.post_id, COUNT(*) AS likes FROM {$table} v INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id WHERE v.object_type = 'post' AND v.status = 'like' AND v.date_time >= %s AND p.post_type = %s AND p.post_status = 'publish' GROUP BY v.post_id ORDER BY likes DESC, MAX(v.date_time) DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				self::since( $days[ $period ] ),
				$post_type,
				$number
			)
		);
		$items = array();
		foreach ( $rows as $row ) {
			$items[] = array(
				'id'    => (int) $row->post_id,
				'likes' => (int) $row->likes,
			);
		}
		set_transient( $key, $items, 5 * MINUTE_IN_SECONDS );
		return $items;
	}

	/**
	 * A bar chart of daily likes and dislikes.
	 *
	 * @param array[] $days From daily().
	 * @return string
	 */
	public static function chart( $days ) {
		$max   = 1;
		$likes = 0;
		$dis   = 0;
		foreach ( $days as $day ) {
			$max    = max( $max, $day['like'] + $day['dislike'] );
			$likes += $day['like'];
			$dis   += $day['dislike'];
		}
		$summary = sprintf(
			/* translators: 1: number of days, 2: likes, 3: dislikes. */
			__( 'Votes per day over %1$s days: %2$s likes and %3$s dislikes in total.', 'like-dislike-for-wp' ),
			number_format_i18n( count( $days ) ),
			number_format_i18n( $likes ),
			number_format_i18n( $dis )
		);
		$html = '<div class="ldfw-chart" role="img" aria-label="' . esc_attr( $summary ) . '">';
		foreach ( $days as $day ) {
			$label = sprintf(
				/* translators: 1: date, 2: number of likes, 3: number of dislikes. */
				__( '%1$s: %2$s likes, %3$s dislikes', 'like-dislike-for-wp' ),
				date_i18n( get_option( 'date_format' ), strtotime( $day['date'] . ' 12:00:00' ) ),
				number_format_i18n( $day['like'] ),
				number_format_i18n( $day['dislike'] )
			);
			$html .= '<span class="ldfw-bar" title="' . esc_attr( $label ) . '">';
			$html .= '<span class="ldfw-bar-like" style="height:' . esc_attr( round( $day['like'] / $max * 100, 2 ) ) . '%"></span>';
			$html .= '<span class="ldfw-bar-dislike" style="height:' . esc_attr( round( $day['dislike'] / $max * 100, 2 ) ) . '%"></span>';
			$html .= '</span>';
		}
		return $html . '</div>';
	}
}

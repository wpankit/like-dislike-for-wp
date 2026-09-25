<?php
/**
 * Demo content for Like Dislike screenshots: a small coffee blog with votes,
 * comments and feedback. From the plugin folder, run: wp eval-file tools/wporg-assets/demo.php
 *
 * The posts are drafts. The screenshot script publishes them while it runs and
 * returns them to drafts afterwards. Running this again replaces the earlier demo.
 *
 * @package LikeDislike
 */

$ldfw_people = array();
foreach ( array( 'priya', 'maria', 'rahul', 'sofia' ) as $ldfw_login ) {
	$ldfw_user = get_user_by( 'login', $ldfw_login );
	if ( ! $ldfw_user ) {
		fwrite( STDERR, "Missing demo user {$ldfw_login}\n" );
		exit( 1 );
	}
	$ldfw_people[ $ldfw_login ] = $ldfw_user;
}

global $wpdb;
$ldfw_table = $wpdb->prefix . 'likedislikewp';

// Remove the previous demo, its votes and comments.
$ldfw_old = get_option( 'ldfw_demo', array() );
foreach ( (array) ( $ldfw_old['posts'] ?? array() ) as $ldfw_id ) {
	foreach ( get_comments( array( 'post_id' => (int) $ldfw_id, 'fields' => 'ids' ) ) as $ldfw_comment ) {
		$wpdb->delete( $ldfw_table, array( 'object_type' => 'comment', 'post_id' => (int) $ldfw_comment ) );
	}
	$wpdb->delete( $ldfw_table, array( 'object_type' => 'post', 'post_id' => (int) $ldfw_id ) );
	wp_delete_post( (int) $ldfw_id, true );
}

/** Paragraph and heading blocks. */
function ldfw_demo_blocks( $parts ) {
	$out = array();
	foreach ( $parts as $part ) {
		if ( 0 === strpos( $part, '## ' ) ) {
			$out[] = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . substr( $part, 3 ) . "</h2>\n<!-- /wp:heading -->";
		} else {
			$out[] = "<!-- wp:paragraph -->\n<p>" . $part . "</p>\n<!-- /wp:paragraph -->";
		}
	}
	return implode( "\n\n", $out );
}

/** A demo post, dated days ago. */
function ldfw_demo_post( $author, $title, $parts, $days_ago, $type = 'post' ) {
	$date = gmdate( 'Y-m-d H:i:s', time() - $days_ago * DAY_IN_SECONDS - 3 * HOUR_IN_SECONDS );
	return wp_insert_post(
		array(
			'post_type'      => $type,
			'post_status'    => 'draft',
			'post_author'    => $author,
			'post_title'     => $title,
			'post_name'      => sanitize_title( $title ),
			'post_content'   => wp_slash( ldfw_demo_blocks( $parts ) ),
			'post_date'      => get_date_from_gmt( $date ),
			'post_date_gmt'  => $date,
			'comment_status' => 'open',
		)
	);
}

/** Visitor votes spread over the last 30 days, some with feedback. */
function ldfw_demo_votes( $type, $id, $likes, $dislikes, $feedback = array() ) {
	global $wpdb;
	$table = $wpdb->prefix . 'likedislikewp';
	$rows  = array();
	foreach ( array( 'like' => $likes, 'dislike' => $dislikes ) as $status => $count ) {
		for ( $i = 0; $i < $count; $i++ ) {
			// Weighted towards recent days, so the chart rises.
			$days   = (int) floor( pow( wp_rand( 0, 1000 ) / 1000, 1.6 ) * 29 );
			$rows[] = array( $status, time() - $days * DAY_IN_SECONDS - wp_rand( 0, 80000 ) );
		}
	}
	foreach ( $rows as $i => $row ) {
		$text = null;
		if ( 'dislike' === $row[0] && $feedback ) {
			$text = array_shift( $feedback );
		}
		$wpdb->insert(
			$table,
			array(
				'post_id'     => $id,
				'object_type' => $type,
				'date_time'   => gmdate( 'Y-m-d H:i:s', $row[1] ),
				'ip'          => hash( 'sha256', 'demo-ip-' . $type . $id . '-' . $i ),
				'user_id'     => '0',
				'token'       => hash( 'sha256', 'demo-token-' . $type . $id . '-' . $i ),
				'status'      => $row[0],
				'feedback'    => $text,
			)
		);
	}
	LDFW_Votes::recount( $type, $id );
}

$ldfw_p = $ldfw_people['priya']->ID;
$ldfw_m = $ldfw_people['maria']->ID;
$ldfw_r = $ldfw_people['rahul']->ID;
$ldfw_s = $ldfw_people['sofia']->ID;

$ldfw_lisbon = ldfw_demo_post(
	$ldfw_p,
	'The best coffee shops in Lisbon',
	array(
		'We spent a week in Lisbon drinking far too much coffee, so you don\'t have to. These are the five places we would go back to tomorrow.',
		'## 1. A tiny bar in Alfama',
		'No seats, no menu, just a counter and the best espresso of the trip. Go early, before the tour groups find it.',
		'## 2. The roastery by the river',
		'They roast in the back and pour filter coffee at the front. Ask for whatever came out of the roaster that morning.',
		'## 3. A bakery that happens to make great coffee',
		'Come for the pastel de nata, stay for a second flat white. The window seat is worth the wait.',
	),
	2
);
$ldfw_delivery = ldfw_demo_post(
	$ldfw_m,
	'How to change your delivery day',
	array(
		'You can move your coffee delivery to any weekday, as often as you like. Changes made before Thursday at noon apply to the next delivery.',
		'## In your account',
		'Go to My subscription, choose Delivery day, pick a new day and press Save. You will get an email to confirm the change.',
		'## Skipping a week',
		'If you only need to skip one delivery, choose Skip next delivery instead. Your usual day stays the same.',
	),
	9
);
$ldfw_porto    = ldfw_demo_post( $ldfw_r, 'A weekend in Porto on a budget', array( 'Porto is one of the best value cities in Europe for good coffee and better views. Here is how we spent a weekend there.' ), 5 );
$ldfw_mistakes = ldfw_demo_post( $ldfw_s, 'Five coffee brewing mistakes', array( 'Most bitter coffee at home comes from the same five mistakes. The good news: each one takes a minute to fix.' ), 7 );
$ldfw_oat      = ldfw_demo_post( $ldfw_m, 'Why we switched to oat milk', array( 'After a year of blind tastings, oat milk won. Not everyone agrees, and that is fine.' ), 12 );
$ldfw_beans    = ldfw_demo_post( $ldfw_p, 'Our favourite beans this month', array( 'A washed Ethiopian that tastes like blueberries, and a Brazilian for everyone who likes chocolate.' ), 16 );
$ldfw_popular  = ldfw_demo_post( $ldfw_p, 'Popular this month', array( 'The posts our readers liked most over the last 30 days.' ), 1, 'page' );
wp_update_post(
	array(
		'ID'           => $ldfw_popular,
		'post_content' => wp_slash( ldfw_demo_blocks( array( 'The posts our readers liked most over the last 30 days.' ) ) . "\n\n<!-- wp:like-dislike-for-wp/most-liked {\"number\":5,\"period\":\"month\"} /-->" ),
	)
);

update_post_meta( $ldfw_popular, '_ldfw_hide', 1 );

ldfw_demo_votes( 'post', $ldfw_lisbon, 128, 9 );
ldfw_demo_votes(
	'post',
	$ldfw_delivery,
	212,
	17,
	array(
		'The steps don\'t mention the mobile app.',
		'I couldn\'t find Delivery day in my account. It is under Settings on my screen.',
		'Can I change it for just one week without skipping?',
		'A screenshot of the page would help.',
	)
);
ldfw_demo_votes( 'post', $ldfw_porto, 86, 4 );
ldfw_demo_votes( 'post', $ldfw_mistakes, 64, 12 );
ldfw_demo_votes( 'post', $ldfw_oat, 41, 23 );
ldfw_demo_votes( 'post', $ldfw_beans, 23, 2 );

// Comments on the Lisbon post, with their own votes.
$ldfw_comments = array(
	array( $ldfw_people['maria'], 'The riverside roastery was our favourite too. Their Friday filter was incredible.', 20, 14, 1 ),
	array( $ldfw_people['rahul'], 'Adding the bakery to my list for next month. Thanks for the tip about the window seat!', 12, 6, 0 ),
	array( $ldfw_people['sofia'], 'No mention of the place near the castle? It has the best view in the city.', 5, 3, 2 ),
);
foreach ( $ldfw_comments as $ldfw_c ) {
	$ldfw_cid = wp_insert_comment(
		array(
			'comment_post_ID'      => $ldfw_lisbon,
			'comment_author'       => $ldfw_c[0]->display_name,
			'comment_author_email' => $ldfw_c[0]->user_email,
			'user_id'              => $ldfw_c[0]->ID,
			'comment_content'      => $ldfw_c[1],
			'comment_approved'     => 1,
			'comment_date'         => get_date_from_gmt( gmdate( 'Y-m-d H:i:s', time() - $ldfw_c[2] * HOUR_IN_SECONDS ) ),
			'comment_date_gmt'     => gmdate( 'Y-m-d H:i:s', time() - $ldfw_c[2] * HOUR_IN_SECONDS ),
		)
	);
	ldfw_demo_votes( 'comment', $ldfw_cid, $ldfw_c[3], $ldfw_c[4] );
}

delete_transient( 'ldfw_vote_total' );
update_option(
	'ldfw_demo',
	array(
		'posts'    => array( $ldfw_lisbon, $ldfw_delivery, $ldfw_porto, $ldfw_mistakes, $ldfw_oat, $ldfw_beans, $ldfw_popular ),
		'lisbon'   => $ldfw_lisbon,
		'delivery' => $ldfw_delivery,
		'popular'  => $ldfw_popular,
	),
	false
);
echo wp_json_encode( get_option( 'ldfw_demo' ) ), "\n";

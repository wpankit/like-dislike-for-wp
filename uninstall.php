<?php
/**
 * Removes Like Dislike's data when the plugin is deleted, if the site asked for that
 * in Settings. Otherwise votes and settings stay, in case it is installed again.
 *
 * @package LikeDislike
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Deletes one site's data.
 */
function ldfw_uninstall_site() {
	global $wpdb;
	$settings = get_option( 'ldfw_settings' );
	if ( ! is_array( $settings ) || empty( $settings['delete_data'] ) ) {
		return;
	}

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}likedislikewp" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange

	foreach ( array( 'ldfw_settings', 'ldfw_version', 'ldfw_installed', 'ldfw_notice' ) as $option ) {
		delete_option( $option );
	}
	delete_transient( 'ldfw_vote_total' );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_ldfw\\_most\\_liked\\_%' OR option_name LIKE '\\_transient\\_timeout\\_ldfw\\_most\\_liked\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	foreach ( array( '_ldfw_likes', '_ldfw_dislikes', '_ldfw_hide' ) as $key ) {
		delete_metadata( 'post', 0, $key, '', true );
		delete_metadata( 'comment', 0, $key, '', true );
	}
	delete_metadata( 'user', 0, $wpdb->get_blog_prefix() . 'ldfw_review', '', true );
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $ldfw_site ) {
		switch_to_blog( $ldfw_site );
		ldfw_uninstall_site();
		restore_current_blog();
	}
} else {
	ldfw_uninstall_site();
}

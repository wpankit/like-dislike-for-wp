<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wplikedislike.pro
 * @since      1.0.0
 *
 * @package    Likedislike_For_Wp
 * @subpackage Likedislike_For_Wp/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Likedislike_For_Wp
 * @subpackage Likedislike_For_Wp/includes
 * @author     Ankit Panchal <wptoolsdev@gmail.com>
 */
class Likedislike_For_Wp_Activator {
	/**
	 * Other variables
	 */
	protected $tables, $database;

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;

		$main_table_name = $wpdb->prefix . 'likedislikewp';
		
		// SQL to create your table
		$sql = "CREATE TABLE IF NOT EXISTS $main_table_name (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				`post_id` bigint(20) NOT NULL,
				`date_time` datetime NOT NULL,
				`ip` varchar(100) NOT NULL,
				`user_id` varchar(100) NOT NULL,
				`status` varchar(30) NOT NULL,
				PRIMARY KEY (`id`),
				KEY `post_id` (`post_id`),
				KEY `date_time` (`date_time`),
				KEY `user_id` (`user_id`),
				KEY `status` (`status`)
			);";

		// Include the WordPress upgrade library
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// Execute the SQL
		dbDelta( $sql );

		// Update db version
		if( get_option( 'like_dislike_for_wp_db_version' ) === false ){
			update_option( 'like_dislike_for_wp_db_version', LIKEDISLIKE_FOR_WP_DB_VERSION );
		}
	}

}

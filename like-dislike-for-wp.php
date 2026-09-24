<?php
/**
 * Plugin Name:       Like Dislike – Like Buttons, Helpful Votes & Most Liked Posts
 * Plugin URI:        https://wordpress.org/plugins/like-dislike-for-wp/
 * Description:       Like and dislike buttons with live counts for posts, pages, products and comments, plus a "Was this helpful?" mode, most liked posts and stats.
 * Version:           3.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            WPAnkit
 * Author URI:        https://wpankit.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       like-dislike-for-wp
 * Domain Path:       /languages
 *
 * @package LikeDislike
 */

defined( 'ABSPATH' ) || exit;

define( 'LDFW_VERSION', '3.0.0' );
define( 'LDFW_FILE', __FILE__ );
define( 'LDFW_DIR', plugin_dir_path( __FILE__ ) );
define( 'LDFW_URL', plugin_dir_url( __FILE__ ) );

require_once LDFW_DIR . 'includes/class-ldfw-plugin.php';

register_activation_hook( __FILE__, array( 'LDFW_Plugin', 'activate' ) );

LDFW_Plugin::instance();

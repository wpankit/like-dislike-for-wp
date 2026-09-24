<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://pluginstack.dev
 * @since             1.0.0
 * @package           Likedislike_For_Wp
 *
 * @wordpress-plugin
 * Plugin Name:       Like Dislike For WP
 * Plugin URI:        https://pluginstack.dev
 * Description:       Add like and dislike buttons to your WordPress posts/pages, allowing visitors to express their opinion with a simple click.
 * Version:           2.1.0
 * Author:            PluginStackDev
 * Author URI:        https://pluginstack.dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       like-dislike-for-wp-pro
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LIKEDISLIKE_FOR_WP_VERSION', '2.1.0' );

define( 'LIKEDISLIKE_FOR_WP_LOGO', plugins_url( 'admin/img/logo-wp-like-dislike.svg', __FILE__ ) );
define( 'LIKEDISLIKE_FOR_WP_ICON', plugins_url( 'admin/img/icon-wp-like-dislike.png', __FILE__ ) );
define( 'LIKEDISLIKE_FOR_WP_PATH', plugin_dir_path( __FILE__ ) );
define( 'LIKEDISLIKE_FOR_WP_DASHBOARD', 'wp-like-dislike-settings' );

if (isset($_GET['page'])) {
    // Remove magic quotes and unnecessary slashes, then sanitize the text as a single operation.
    $page = sanitize_text_field(wp_unslash($_GET['page']));

    // Check if the sanitized value is not empty to proceed with defining the constant.
    if (!empty($page)) {
        define('LIKEDISLIKE_FOR_WP_CURRENT_PAGE', $page);
    } else {
        define('LIKEDISLIKE_FOR_WP_CURRENT_PAGE', '/');
    }
} else {
    define('LIKEDISLIKE_FOR_WP_CURRENT_PAGE', '/');
}


define( 'LIKEDISLIKE_FOR_WP_DB_VERSION', 1 );

define( 'LIKEDISLIKE_FOR_WP_ALLOWED_PAGES', apply_filters( 'like_dislike_for_wp_pages_for_assets', array( 'wp-like-dislike-settings', 'wp-like-dislike-posts-counts', 'wp-like-dislike-pages-counts' ) ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-like-dislike-for-wp-activator.php
 */
function like_dislike_for_wp_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-like-dislike-for-wp-activator.php';
	Likedislike_For_Wp_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-like-dislike-for-wp-deactivator.php
 */
function like_dislike_for_wp_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-like-dislike-for-wp-deactivator.php';
	Likedislike_For_Wp_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'like_dislike_for_wp_activate' );
register_deactivation_hook( __FILE__, 'like_dislike_for_wp_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-like-dislike-for-wp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function like_dislike_for_wp_run() {

	$plugin = new Likedislike_For_Wp();
	$plugin->run();

}
like_dislike_for_wp_run();



/* Show a small promotional notice for PluginStack bundle. */
add_action( 'admin_notices', 'ldfw_pluginstack_promo_notice' );
function ldfw_pluginstack_promo_notice() {
	$dismissed = get_option( 'ldfw_pluginstack_promo_dismissed' );
	if ( $dismissed ) {
		return;
	}
	?>
	<div class="notice ldfw-promo-notice" style="border-left-color:#6c47ff;padding:8px 12px;display:flex;align-items:center;gap:10px;">
		<span style="font-size:18px;">⚡</span>
		<p style="margin:0;font-size:13px;">
			<strong>Enjoying this plugin?</strong> Get the <a href="https://pluginstack.dev/?utm_source=like-dislike-for-wp&utm_medium=admin_notice&utm_campaign=pluginstack_bundle" target="_blank" rel="noopener noreferrer" style="color:#6c47ff;font-weight:600;">PluginStack Bundle</a> — AI, WooCommerce, Gravity Forms, Analytics &amp; more. All current + upcoming plugins. <strong>One-time payment, no subscription.</strong>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'ldfw_dismiss_promo', '1' ), 'ldfw_dismiss_promo' ) ); ?>" style="margin-left:10px;color:#999;font-size:12px;text-decoration:none;"><?php esc_html_e( 'Dismiss', 'like-dislike-for-wp-pro' ); ?></a>
		</p>
	</div>
	<?php
}

/* Handle dismiss action for PluginStack promo notice. */
add_action( 'admin_init', 'ldfw_handle_promo_dismiss' );
function ldfw_handle_promo_dismiss() {
	if ( isset( $_GET['ldfw_dismiss_promo'] ) && check_admin_referer( 'ldfw_dismiss_promo' ) ) {
		update_option( 'ldfw_pluginstack_promo_dismissed', true );
		wp_safe_redirect( remove_query_arg( array( 'ldfw_dismiss_promo', '_wpnonce' ) ) );
		exit;
	}
}
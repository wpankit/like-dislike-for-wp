<?php
/**
 * Installs and upgrades: the votes table, settings from 2.x, and stored counts.
 *
 * @package LikeDislike
 */

defined( 'ABSPATH' ) || exit;

/**
 * Runs once per version, on the first request after an install or update.
 */
class LDFW_Upgrade {

	const VERSION   = 'ldfw_version';
	const INSTALLED = 'ldfw_installed';
	const NOTICE    = 'ldfw_notice';
	const DISMISS   = 'ldfw_dismiss_notice';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 1 );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'admin_post_' . self::DISMISS, array( __CLASS__, 'dismiss' ) );
	}

	/**
	 * Brings the database up to date with this version.
	 */
	public static function maybe_upgrade() {
		$current = (string) get_option( self::VERSION, '' );
		if ( LDFW_VERSION === $current ) {
			return;
		}

		LDFW_Votes::install();
		if ( '' === $current ) {
			self::first_run();
		}
		update_option( self::VERSION, LDFW_VERSION );
		if ( ! get_option( self::INSTALLED ) ) {
			add_option( self::INSTALLED, time(), '', false );
		}
	}

	/**
	 * First run of 3.x: a new site, or an update from 2.x.
	 */
	private static function first_run() {
		$legacy = false !== get_option( 'like_dislike_for_wp_db_version' ) || false !== get_option( 'like_dislike_vote_tracking_enabled' );

		if ( false === get_option( LDFW_Settings::OPTION ) ) {
			$settings = LDFW_Settings::defaults();
			if ( $legacy ) {
				// 2.x showed nothing until "Enable Tracking" was on, and one button unless
				// "Show Dislike Button" was on. Keep the site looking the way it did.
				$settings['enabled']    = 'yes' === get_option( 'like_dislike_vote_tracking_enabled' );
				$settings['dislike']    = 'yes' === get_option( 'like_dislike_hide_dislike_btn' );
				$settings['post_types'] = array( 'post', 'page' );
			}
			add_option( LDFW_Settings::OPTION, $settings );
			LDFW_Settings::flush();
		}

		self::recount_all();

		foreach ( array( 'like_dislike_vote_tracking_enabled', 'like_dislike_hide_dislike_btn', 'like_dislike_for_wp_db_version', 'ldfw_pluginstack_promo_dismissed' ) as $old ) {
			delete_option( $old );
		}
		update_option( self::NOTICE, $legacy ? 'upgraded' : 'new', false );
	}

	/**
	 * Stores the counts of every item that has votes.
	 */
	public static function recount_all() {
		global $wpdb;
		$table = esc_sql( LDFW_Votes::table() );
		$rows  = $wpdb->get_results( "SELECT object_type, post_id, SUM(status = 'like') AS likes, SUM(status = 'dislike') AS dislikes FROM {$table} GROUP BY object_type, post_id" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table, no input.
		foreach ( (array) $rows as $row ) {
			$meta = 'comment' === $row->object_type ? 'comment' : 'post';
			update_metadata( $meta, (int) $row->post_id, LDFW_Votes::LIKES, (int) $row->likes );
			update_metadata( $meta, (int) $row->post_id, LDFW_Votes::DISLIKES, (int) $row->dislikes );
		}
	}

	/**
	 * A one-time welcome after installing or updating to 3.0.
	 */
	public static function notice() {
		$kind   = get_option( self::NOTICE );
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $kind || ! current_user_can( 'manage_options' ) || ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) ) {
			return;
		}
		$settings = admin_url( 'admin.php?page=' . LDFW_Settings::PAGE );
		$dismiss  = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DISMISS ), self::DISMISS );
		?>
		<div class="notice notice-info ldfw-notice">
			<p>
				<strong><?php esc_html_e( 'Like Dislike 3.0 is ready.', 'like-dislike-for-wp' ); ?></strong>
				<?php
				if ( 'upgraded' === $kind ) {
					esc_html_e( 'The buttons now show live counts and remember each vote, visitors can change their mind, and there are new styles, a "Was this helpful?" mode, likes on comments and a Stats screen. Your votes and settings were kept.', 'like-dislike-for-wp' );
				} elseif ( LDFW_Settings::get( 'enabled' ) ) {
					esc_html_e( 'Like and dislike buttons now appear after your posts. Choose their look, where they appear and more in Settings.', 'like-dislike-for-wp' );
				} else {
					esc_html_e( 'Choose where the buttons appear and how they look in Settings.', 'like-dislike-for-wp' );
				}
				?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $settings ); ?>"><?php esc_html_e( 'Open Settings', 'like-dislike-for-wp' ); ?></a>
				<a class="button-link" href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Dismiss', 'like-dislike-for-wp' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Hides the welcome notice.
	 */
	public static function dismiss() {
		check_admin_referer( self::DISMISS );
		if ( current_user_can( 'manage_options' ) ) {
			delete_option( self::NOTICE );
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}
}

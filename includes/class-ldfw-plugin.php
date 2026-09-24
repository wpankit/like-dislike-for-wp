<?php
/**
 * Loads Like Dislike and wires up its parts.
 *
 * @package LikeDislike
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin bootstrap.
 */
final class LDFW_Plugin {

	/**
	 * The single instance.
	 *
	 * @var LDFW_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Returns the single instance, creating it on first use.
	 *
	 * @return LDFW_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Loads the classes and hooks them up.
	 */
	private function __construct() {
		$dir = LDFW_DIR . 'includes/';
		require_once $dir . 'class-ldfw-settings.php';
		require_once $dir . 'class-ldfw-votes.php';
		require_once $dir . 'class-ldfw-render.php';
		require_once $dir . 'class-ldfw-rest.php';
		require_once $dir . 'class-ldfw-stats.php';
		require_once $dir . 'class-ldfw-admin.php';
		require_once $dir . 'class-ldfw-upgrade.php';
		require_once $dir . 'class-ldfw-privacy.php';
		require_once $dir . 'class-ldfw-review.php';

		LDFW_Upgrade::init();
		LDFW_Settings::init();
		LDFW_Render::init();
		LDFW_REST::init();
		LDFW_Admin::init();
		LDFW_Privacy::init();
		LDFW_Review::init();
	}

	/**
	 * Activation: create or update the votes table and bring 2.x settings over.
	 */
	public static function activate() {
		LDFW_Upgrade::maybe_upgrade();
	}
}

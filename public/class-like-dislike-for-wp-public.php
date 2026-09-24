<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wplikedislike.pro
 * @since      1.0.0
 *
 * @package    Likedislike_For_Wp
 * @subpackage Likedislike_For_Wp/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Likedislike_For_Wp
 * @subpackage Likedislike_For_Wp/public
 * @author     Ankit Panchal <wptoolsdev@gmail.com>
 */
class Likedislike_For_Wp_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Likedislike_For_Wp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Likedislike_For_Wp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// Enqueue toastr CSS.
		wp_enqueue_style( 'toastr-css', plugin_dir_url( __FILE__ ) . 'css/toastr.min.css', array(), $this->version, 'all' );

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/like-dislike-for-wp-public.css', array(), $this->version, 'all' );
		
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Likedislike_For_Wp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Likedislike_For_Wp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// Enqueue toastr.js.
		wp_enqueue_script( 'toastr-js', plugin_dir_url( __FILE__ ) . 'js/toastr.min.js', array( 'jquery' ), $this->version, true );

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/like-dislike-for-wp-public.js', array( 'jquery' ), $this->version, false );

		wp_localize_script(
			$this->plugin_name,
			'like_dislike_for_wp_ajax',
			array(
				'url'               => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce('like_dislike_nonce'),
			)
		);

	}
	

	public function like_dislike_for_wp_add_buttons( $content ) {
		$enable_tracking = get_option( 'like_dislike_vote_tracking_enabled' );
		if ( 'yes' === $enable_tracking ) {
			$hide_dislike_btn = get_option( 'like_dislike_hide_dislike_btn' );
			if ( 'yes' === $hide_dislike_btn ) {
				$content .= '<div class="likedislike-btn-cnt">
								<button class="like-dislike-for-wp-button like-dislike-for-wp-like-button" data-post-id="' . get_the_ID() . '" data-action="like">Like</button>
								<button class="like-dislike-for-wp-button like-dislike-for-wp-dislike-button" data-post-id="' . get_the_ID() . '" data-action="dislike">Dislike</button>
							</div>';
			} else {
				$content .= '<div class="likedislike-btn-cnt">
								<button class="like-dislike-for-wp-button like-dislike-for-wp-like-button" data-post-id="' . get_the_ID() . '" data-action="like">Like</button>
							</div>';
			}
		}
		return $content;

		
	}

}

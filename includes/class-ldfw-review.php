<?php
/**
 * Asking for a review on WordPress.org.
 *
 * @package LikeDislike
 */

defined( 'ABSPATH' ) || exit;

/**
 * Asks administrators for a review once readers have voted a few times and the
 * plugin has been in use for a few days. "Maybe later" asks again in two weeks;
 * leaving a review or "I already did" stops it for good. The Plugins screen and the
 * footer of our screens carry a quiet rating link all the time.
 */
class LDFW_Review {

	const USER_KEY    = 'ldfw_review';
	const URL         = 'https://wordpress.org/support/plugin/like-dislike-for-wp/reviews/#new-post';
	const MIN_VOTES   = 10;
	const MIN_DAYS    = 3;
	const SNOOZE_DAYS = 14;
	const AJAX        = 'ldfw_review';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_' . self::AJAX, array( __CLASS__, 'answer_ajax' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_text' ) );
	}

	/**
	 * How many votes the site has, cached for a while.
	 *
	 * @return int
	 */
	private static function votes() {
		$votes = get_transient( 'ldfw_vote_total' );
		if ( false === $votes ) {
			global $wpdb;
			$table = esc_sql( LDFW_Votes::table() );
			$votes = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table, no input.
			set_transient( 'ldfw_vote_total', $votes, 12 * HOUR_IN_SECONDS );
		}
		return (int) $votes;
	}

	/**
	 * Whether to ask this person now.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function should_ask( $user_id ) {
		$state = get_user_option( self::USER_KEY, $user_id );
		if ( 'done' === $state || ( $state && time() - (int) $state < self::SNOOZE_DAYS * DAY_IN_SECONDS ) ) {
			return false;
		}
		$installed = (int) get_option( LDFW_Upgrade::INSTALLED );
		$ask       = $installed && time() - $installed >= self::MIN_DAYS * DAY_IN_SECONDS && self::votes() >= self::MIN_VOTES;

		/**
		 * Filters whether Like Dislike asks this person for a review now.
		 *
		 * @param bool $ask     Whether to ask.
		 * @param int  $user_id User ID.
		 */
		return (bool) apply_filters( 'ldfw_ask_for_review', $ask, $user_id );
	}

	/**
	 * Remembers the answer: 'later' asks again in two weeks, anything else never.
	 *
	 * @param int    $user_id User ID.
	 * @param string $answer  'later' or 'done'.
	 */
	public static function answer( $user_id, $answer ) {
		update_user_option( $user_id, self::USER_KEY, 'later' === $answer ? time() : 'done' );
	}

	/**
	 * Records the answer from the notice.
	 */
	public static function answer_ajax() {
		check_ajax_referer( self::AJAX );
		self::answer( get_current_user_id(), isset( $_POST['later'] ) && '1' === $_POST['later'] ? 'later' : 'done' );
		wp_send_json_success();
	}

	/**
	 * Whether our screens are showing.
	 *
	 * @return bool
	 */
	private static function our_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && ( false !== strpos( $screen->id, LDFW_Admin::STATS ) || false !== strpos( $screen->id, LDFW_Settings::PAGE ) );
	}

	/**
	 * Whether the notice belongs on this screen, for this person.
	 *
	 * @return bool
	 */
	private static function notice_here() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen
			&& ( in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) || self::our_screen() )
			&& current_user_can( 'manage_options' )
			&& self::should_ask( get_current_user_id() );
	}

	/**
	 * Styles and the small script that records the answer.
	 */
	public static function enqueue() {
		if ( ! self::notice_here() ) {
			return;
		}
		wp_register_style( 'ldfw-review', false, array(), LDFW_VERSION );
		wp_enqueue_style( 'ldfw-review' );
		wp_add_inline_style(
			'ldfw-review',
			'.ldfw-review-notice{display:flex;gap:14px;align-items:flex-start;padding:14px 38px 14px 14px;border-left-color:#2563eb}' .
			'.ldfw-review-notice img{flex:none;border-radius:10px}' .
			'.ldfw-review-notice p{margin:0 0 10px;font-size:13.5px}' .
			'.ldfw-review-notice .ldfw-stars{color:#f5a300;letter-spacing:1px}' .
			'.ldfw-review-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:0!important}'
		);
		wp_register_script( 'ldfw-review', false, array(), LDFW_VERSION, true );
		wp_enqueue_script( 'ldfw-review' );
		wp_add_inline_script(
			'ldfw-review',
			'document.addEventListener("click",function(e){var el=e.target.closest(".ldfw-review-notice [data-ldfw-review],.ldfw-review-notice .notice-dismiss");if(!el){return;}var notice=el.closest(".ldfw-review-notice");var later=el.classList.contains("notice-dismiss")||"later"===el.getAttribute("data-ldfw-review");var body=new URLSearchParams({action:' . wp_json_encode( self::AJAX ) . ',_ajax_nonce:' . wp_json_encode( wp_create_nonce( self::AJAX ) ) . ',later:later?"1":"0"});fetch(' . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ',{method:"POST",credentials:"same-origin",body:body}).catch(function(){});if("A"!==el.tagName){e.preventDefault();notice.remove();}else{setTimeout(function(){notice.remove();},300);}});'
		);
	}

	/**
	 * The review notice.
	 */
	public static function notice() {
		if ( ! self::notice_here() ) {
			return;
		}
		$votes = self::votes();
		?>
		<div class="notice notice-info is-dismissible ldfw-review-notice">
			<img src="<?php echo esc_url( LDFW_URL . 'assets/images/icon.svg' ); ?>" width="44" height="44" alt="">
			<div>
				<p>
					<strong><?php esc_html_e( 'Enjoying Like Dislike?', 'like-dislike-for-wp' ); ?></strong>
					<?php
					/* translators: %s: number of votes. */
					echo esc_html( sprintf( _n( 'Your readers have voted %s time so far.', 'Your readers have voted %s times so far.', $votes, 'like-dislike-for-wp' ), number_format_i18n( $votes ) ) );
					?>
					<?php esc_html_e( 'A quick review on WordPress.org takes a minute and helps other site owners find it.', 'like-dislike-for-wp' ); ?>
				</p>
				<p class="ldfw-review-actions">
					<a class="button button-primary" href="<?php echo esc_url( self::URL ); ?>" target="_blank" rel="noopener noreferrer" data-ldfw-review="done">
						<span class="ldfw-stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
						<?php esc_html_e( 'Leave a review', 'like-dislike-for-wp' ); ?>
						<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'like-dislike-for-wp' ); ?></span>
					</a>
					<button type="button" class="button" data-ldfw-review="later"><?php esc_html_e( 'Maybe later', 'like-dislike-for-wp' ); ?></button>
					<button type="button" class="button-link" data-ldfw-review="done"><?php esc_html_e( 'I already did', 'like-dislike-for-wp' ); ?></button>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * A rating link in the plugin's row on the Plugins screen.
	 *
	 * @param string[] $links Links under the description.
	 * @param string   $file  Plugin file.
	 * @return string[]
	 */
	public static function row_meta( $links, $file ) {
		if ( plugin_basename( LDFW_FILE ) === $file ) {
			$links[] = '<a href="' . esc_url( self::URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Rate Like Dislike', 'like-dislike-for-wp' ) . ' <span aria-hidden="true" style="color:#f5a300">&#9733;&#9733;&#9733;&#9733;&#9733;</span><span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'like-dislike-for-wp' ) . '</span></a>';
		}
		return $links;
	}

	/**
	 * A thank-you line with a rating link at the bottom of our screens.
	 *
	 * @param string $text Footer text.
	 * @return string
	 */
	public static function footer_text( $text ) {
		if ( ! self::our_screen() ) {
			return $text;
		}
		return sprintf(
			/* translators: %s: five-star rating link. */
			esc_html__( 'If Like Dislike helps your site, please rate it %s on WordPress.org. Thank you!', 'like-dislike-for-wp' ),
			'<a href="' . esc_url( self::URL ) . '" target="_blank" rel="noopener noreferrer" style="color:#f5a300;text-decoration:none">&#9733;&#9733;&#9733;&#9733;&#9733;<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'like-dislike-for-wp' ) . '</span></a>'
		);
	}
}

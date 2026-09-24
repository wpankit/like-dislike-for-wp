<?php
/**
 * The buttons on the site: markup, placement, shortcodes and blocks.
 *
 * @package LikeDislike
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the buttons and the most liked list.
 */
class LDFW_Render {

	/**
	 * Posts that already have their buttons in this request.
	 *
	 * @var array<int,bool>
	 */
	private static $done = array();

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );
		add_filter( 'the_content', array( __CLASS__, 'content' ), 20 );
		add_filter( 'comment_text', array( __CLASS__, 'comment' ), 20, 2 );
		add_filter( 'comments_array', array( __CLASS__, 'preload_comments' ), 20 );
	}

	/* Icons ---------------------------------------------------------------------------------- */

	/**
	 * Icon sets, drawn for this plugin.
	 *
	 * @return array<string,array> Key => label and SVG bodies for like and dislike.
	 */
	public static function icon_sets() {
		$thumb = '<path class="ldfw-fill" d="M7 11v9H4.5A1.5 1.5 0 0 1 3 18.5v-6A1.5 1.5 0 0 1 4.5 11H7Z"/><path class="ldfw-fill" d="M7 11 10.6 3.6a1.9 1.9 0 0 1 3.5 1.3L13.4 9.5h5a2 2 0 0 1 2 2.4l-1.4 6.5A2 2 0 0 1 17 20H7"/>';
		$heart = '<path class="ldfw-fill" d="M12 20.2S3.6 15.1 3.6 9.3A4.4 4.4 0 0 1 12 7.1a4.4 4.4 0 0 1 8.4 2.2c0 5.8-8.4 10.9-8.4 10.9Z"/>';
		$arrow = '<path class="ldfw-fill" d="M12 3.8 19.2 12H15v8.2H9V12H4.8L12 3.8Z"/>';
		return array(
			'thumbs'  => array(
				'label'   => __( 'Thumbs', 'like-dislike-for-wp' ),
				'like'    => $thumb,
				'dislike' => '<g transform="rotate(180 12 12)">' . $thumb . '</g>',
			),
			'hearts'  => array(
				'label'   => __( 'Hearts', 'like-dislike-for-wp' ),
				'like'    => $heart,
				'dislike' => $heart . '<path d="m12.6 7.6-2 3.4 3 1.8-1.8 3.4"/>',
			),
			'arrows'  => array(
				'label'   => __( 'Arrows', 'like-dislike-for-wp' ),
				'like'    => $arrow,
				'dislike' => '<g transform="rotate(180 12 12)">' . $arrow . '</g>',
			),
			'smileys' => array(
				'label'   => __( 'Faces', 'like-dislike-for-wp' ),
				'like'    => '<circle cx="12" cy="12" r="8.6"/><path d="M8.4 13.6a4.2 4.2 0 0 0 7.2 0"/><path d="M9.2 9.6h.01M14.8 9.6h.01" stroke-width="2.6"/>',
				'dislike' => '<circle cx="12" cy="12" r="8.6"/><path d="M8.4 16.4a4.2 4.2 0 0 1 7.2 0"/><path d="M9.2 9.6h.01M14.8 9.6h.01" stroke-width="2.6"/>',
			),
			'none'    => array(
				'label'   => __( 'Text only', 'like-dislike-for-wp' ),
				'like'    => '',
				'dislike' => '',
			),
		);
	}

	/**
	 * An icon as inline SVG.
	 *
	 * @param string $set    Icon set.
	 * @param string $choice 'like' or 'dislike'.
	 * @return string
	 */
	public static function icon( $set, $choice ) {
		$sets = self::icon_sets();
		$body = isset( $sets[ $set ][ $choice ] ) ? $sets[ $set ][ $choice ] : '';
		if ( '' === $body ) {
			return '';
		}
		return '<svg class="ldfw-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
	}

	/* Assets ------------------------------------------------------------------------------------- */

	/**
	 * Registers styles, scripts and blocks.
	 */
	public static function register() {
		wp_register_style( 'ldfw-buttons', LDFW_URL . 'assets/css/buttons.css', array(), LDFW_VERSION );
		// Deferred on WordPress 6.3 and later; older versions load it in the footer.
		wp_register_script(
			'ldfw-buttons',
			LDFW_URL . 'assets/js/buttons.js',
			array(),
			LDFW_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_register_script( 'ldfw-blocks', LDFW_URL . 'assets/js/blocks.js', array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-server-side-render', 'wp-block-editor', 'wp-components' ), LDFW_VERSION, true );
		wp_set_script_translations( 'ldfw-blocks', 'like-dislike-for-wp', LDFW_DIR . 'languages' );

		register_block_type(
			'like-dislike-for-wp/buttons',
			array(
				'api_version'     => 2,
				'editor_script'   => 'ldfw-blocks',
				'style'           => 'ldfw-buttons',
				'render_callback' => array( __CLASS__, 'block_buttons' ),
				'attributes'      => array(),
			)
		);
		register_block_type(
			'like-dislike-for-wp/most-liked',
			array(
				'api_version'     => 2,
				'editor_script'   => 'ldfw-blocks',
				'style'           => 'ldfw-buttons',
				'render_callback' => array( __CLASS__, 'block_most_liked' ),
				'attributes'      => array(
					'number'    => array(
						'type'    => 'number',
						'default' => 5,
					),
					'postType'  => array(
						'type'    => 'string',
						'default' => 'post',
					),
					'period'    => array(
						'type'    => 'string',
						'default' => 'all',
					),
					'showCount' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);

		add_shortcode( 'like_dislike', array( __CLASS__, 'shortcode_buttons' ) );
		add_shortcode( 'most_liked_posts', array( __CLASS__, 'shortcode_most_liked' ) );
	}

	/**
	 * Loads the assets early on pages that will show the buttons, so they are styled
	 * from the first paint. Other pages load them when a button is rendered.
	 */
	public static function maybe_enqueue() {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post ) {
			return;
		}
		$auto = LDFW_Settings::get( 'enabled' ) && LDFW_Settings::type_enabled( $post->post_type ) && ! get_post_meta( $post->ID, LDFW_Votes::HIDE, true );
		if ( $auto || has_shortcode( $post->post_content, 'like_dislike' ) || has_block( 'like-dislike-for-wp/buttons', $post ) ) {
			self::enqueue();
		}
	}

	/**
	 * Enqueues the style, the script and its settings once.
	 */
	public static function enqueue() {
		wp_enqueue_style( 'ldfw-buttons' );
		if ( wp_script_is( 'ldfw-buttons', 'enqueued' ) ) {
			return;
		}
		wp_enqueue_script( 'ldfw-buttons' );

		$data = array(
			'api'      => esc_url_raw( rest_url( 'like-dislike-for-wp/v1/' ) ),
			'nonce'    => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
			'nonceUrl' => is_user_logged_in() ? admin_url( 'admin-ajax.php?action=rest-nonce' ) : '',
			'members'  => 'members' === LDFW_Settings::get( 'who' ) && ! is_user_logged_in(),
			'loginUrl' => wp_login_url(),
			'thanks'   => LDFW_Settings::get( 'thanks' ),
			'feedback' => (bool) LDFW_Settings::get( 'feedback' ),
			'i18n'     => array(
				'login'       => __( 'Please log in to vote.', 'like-dislike-for-wp' ),
				'loginLink'   => __( 'Log in', 'like-dislike-for-wp' ),
				'error'       => __( 'Your vote could not be saved. Please try again.', 'like-dislike-for-wp' ),
				'question'    => __( 'What could be better?', 'like-dislike-for-wp' ),
				'placeholder' => __( 'Tell us what was missing or unclear (optional).', 'like-dislike-for-wp' ),
				'send'        => __( 'Send', 'like-dislike-for-wp' ),
				'sent'        => __( 'Thank you. Your feedback helps us improve.', 'like-dislike-for-wp' ),
			),
		);
		wp_add_inline_script( 'ldfw-buttons', 'window.ldfwData = ' . wp_json_encode( $data ) . ';', 'before' );
	}

	/* Markup --------------------------------------------------------------------------------- */

	/**
	 * The buttons for an item.
	 *
	 * @param string $type 'post' or 'comment'.
	 * @param int    $id   Item ID.
	 * @return string
	 */
	public static function buttons( $type, $id ) {
		$s          = LDFW_Settings::all();
		$is_comment = 'comment' === $type;
		$counts     = LDFW_Votes::counts( $type, $id );
		$state      = LDFW_Votes::mine( $type, $id );
		$choices    = $s['dislike'] ? array( 'like', 'dislike' ) : array( 'like' );
		$icons      = $s['icons'];

		$classes = array(
			'ldfw',
			'ldfw-style-' . $s['style'],
			'ldfw-size-' . ( $is_comment ? 'small' : $s['size'] ),
			'ldfw-align-' . ( $is_comment ? 'left' : $s['align'] ),
			'ldfw-counts-' . $s['counts'],
			'none' === $icons ? 'ldfw-no-icons' : 'ldfw-has-icons',
		);
		if ( $is_comment ) {
			$classes[] = 'ldfw-is-comment';
		}
		if ( $state ) {
			$classes[] = 'ldfw-has-voted';
		}

		$prompt = $is_comment ? '' : trim( (string) $s['prompt'] );
		$group  = '' !== $prompt ? $prompt : ( $is_comment ? __( 'Rate this comment', 'like-dislike-for-wp' ) : __( 'Rate this', 'like-dislike-for-wp' ) );

		$html  = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( '--ldfw-like:' . $s['like_color'] . ';--ldfw-dislike:' . $s['dislike_color'] ) . '" data-ldfw data-type="' . esc_attr( $type ) . '" data-id="' . esc_attr( $id ) . '"' . ( $state ? ' data-state="' . esc_attr( $state ) . '"' : '' ) . '>';
		$html .= '' !== $prompt ? '<span class="ldfw-prompt">' . esc_html( $prompt ) . '</span>' : '';
		$html .= '<span class="ldfw-buttons" role="group" aria-label="' . esc_attr( $group ) . '">';
		foreach ( $choices as $choice ) {
			$active = $state === $choice;
			$label  = LDFW_Settings::label( $choice );
			$html  .= '<button type="button" class="ldfw-button ldfw-' . $choice . ( $active ? ' is-active' : '' ) . '" data-choice="' . $choice . '" aria-pressed="' . ( $active ? 'true' : 'false' ) . '">';
			$html  .= self::icon( $icons, $choice );
			$html  .= '<span class="ldfw-label">' . esc_html( $label ) . '</span>';
			if ( 'never' !== $s['counts'] ) {
				$html .= '<span class="ldfw-count" data-count="' . (int) $counts[ $choice ] . '">' . esc_html( number_format_i18n( $counts[ $choice ] ) ) . '</span>';
			}
			$html .= '</button>';
		}
		$html .= '</span><span class="ldfw-message" role="status" aria-live="polite"></span></div>';

		self::enqueue();

		/**
		 * Filters the buttons' HTML.
		 *
		 * @param string $html HTML.
		 * @param string $type 'post' or 'comment'.
		 * @param int    $id   Item ID.
		 */
		return apply_filters( 'ldfw_buttons_html', $html, $type, $id );
	}

	/**
	 * Adds the buttons to single posts of the chosen types.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function content( $content ) {
		if ( ! LDFW_Settings::get( 'enabled' ) || is_feed() || ! is_singular() || ! in_the_loop() || ! is_main_query()
			|| doing_filter( 'get_the_excerpt' ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $content;
		}
		$post = get_post();
		if ( ! $post || isset( self::$done[ $post->ID ] ) || ! LDFW_Settings::type_enabled( $post->post_type )
			|| get_post_meta( $post->ID, LDFW_Votes::HIDE, true ) || post_password_required( $post ) ) {
			return $content;
		}
		self::$done[ $post->ID ] = true;

		switch ( LDFW_Settings::get( 'position' ) ) {
			case 'before':
				return self::buttons( 'post', $post->ID ) . $content;
			case 'both':
				return self::buttons( 'post', $post->ID ) . $content . self::buttons( 'post', $post->ID );
			default:
				return $content . self::buttons( 'post', $post->ID );
		}
	}

	/**
	 * Loads the logged-in user's votes on a post's comments in one query.
	 *
	 * @param WP_Comment[] $comments Comments.
	 * @return WP_Comment[]
	 */
	public static function preload_comments( $comments ) {
		if ( LDFW_Settings::get( 'comments' ) && is_user_logged_in() && $comments ) {
			LDFW_Votes::preload( 'comment', wp_list_pluck( $comments, 'comment_ID' ) );
		}
		return $comments;
	}

	/**
	 * Adds small buttons under each approved comment.
	 *
	 * @param string          $text    Comment text.
	 * @param WP_Comment|null $comment Comment.
	 * @return string
	 */
	public static function comment( $text, $comment = null ) {
		if ( ! LDFW_Settings::get( 'comments' ) || is_admin() || is_feed() || ! $comment instanceof WP_Comment
			|| '1' !== (string) $comment->comment_approved || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $text;
		}
		$post = get_post( (int) $comment->comment_post_ID );
		if ( ! $post || ! LDFW_Settings::type_enabled( $post->post_type ) ) {
			return $text;
		}
		return $text . self::buttons( 'comment', (int) $comment->comment_ID );
	}

	/* Shortcodes and blocks ----------------------------------------------------------------------- */

	/**
	 * [like_dislike id="123"]: the buttons for a post, the current one by default.
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function shortcode_buttons( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'like_dislike' );
		$id   = $atts['id'] ? absint( $atts['id'] ) : get_the_ID();
		if ( ! $id || ! get_post( $id ) ) {
			return '';
		}
		if ( ! $atts['id'] ) {
			self::$done[ $id ] = true;
		}
		return self::buttons( 'post', $id );
	}

	/**
	 * The Like Dislike Buttons block.
	 *
	 * @return string
	 */
	public static function block_buttons() {
		$id = get_the_ID();
		if ( ! $id ) {
			return '';
		}
		self::$done[ $id ] = true;
		return self::buttons( 'post', $id );
	}

	/**
	 * [most_liked_posts number="5" post_type="post" period="all" show_count="yes"]
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function shortcode_most_liked( $atts ) {
		$atts = shortcode_atts(
			array(
				'number'     => 5,
				'post_type'  => 'post',
				'period'     => 'all',
				'show_count' => 'yes',
			),
			$atts,
			'most_liked_posts'
		);
		return self::most_liked_list( (int) $atts['number'], sanitize_key( $atts['post_type'] ), sanitize_key( $atts['period'] ), ! in_array( strtolower( (string) $atts['show_count'] ), array( 'no', 'false', '0' ), true ) );
	}

	/**
	 * The Most Liked Posts block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function block_most_liked( $attributes ) {
		return self::most_liked_list( (int) $attributes['number'], sanitize_key( $attributes['postType'] ), sanitize_key( $attributes['period'] ), ! empty( $attributes['showCount'] ) );
	}

	/**
	 * The most liked posts as a list.
	 *
	 * @param int    $number     How many.
	 * @param string $post_type  Post type.
	 * @param string $period     'all', 'year', 'month' or 'week'.
	 * @param bool   $show_count Show the like counts.
	 * @return string
	 */
	public static function most_liked_list( $number, $post_type, $period, $show_count ) {
		wp_enqueue_style( 'ldfw-buttons' );
		$items = LDFW_Stats::most_liked( max( 1, min( 50, $number ) ), $post_type ? $post_type : 'post', $period );
		if ( ! $items ) {
			return '<p class="ldfw-most-liked-empty">' . esc_html__( 'No likes yet.', 'like-dislike-for-wp' ) . '</p>';
		}
		$icons = LDFW_Settings::get( 'icons' );
		$html  = '<ol class="ldfw-most-liked" style="' . esc_attr( '--ldfw-like:' . LDFW_Settings::get( 'like_color' ) ) . '">';
		foreach ( $items as $item ) {
			$html .= '<li><a href="' . esc_url( get_permalink( $item['id'] ) ) . '">' . esc_html( get_the_title( $item['id'] ) ) . '</a>';
			if ( $show_count ) {
				/* translators: %s: number of likes. */
				$label = sprintf( _n( '%s like', '%s likes', $item['likes'], 'like-dislike-for-wp' ), number_format_i18n( $item['likes'] ) );
				$html .= ' <span class="ldfw-most-liked-count" title="' . esc_attr( $label ) . '">' . self::icon( 'none' === $icons ? 'hearts' : $icons, 'like' ) . '<span aria-hidden="true">' . esc_html( number_format_i18n( $item['likes'] ) ) . '</span><span class="screen-reader-text">' . esc_html( $label ) . '</span></span>';
			}
			$html .= '</li>';
		}
		return $html . '</ol>';
	}
}

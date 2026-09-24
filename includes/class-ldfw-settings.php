<?php
/**
 * Settings: storage and the Settings screen.
 *
 * @package LikeDislike
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site settings, kept in one option.
 */
class LDFW_Settings {

	const OPTION = 'ldfw_settings';
	const PAGE   = 'ldfw-settings';

	/**
	 * Cached settings for this request.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
		add_action( 'add_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
	}

	/**
	 * Defaults for a new site.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'       => true,
			'post_types'    => array( 'post' ),
			'position'      => 'after',
			'dislike'       => true,
			'counts'        => 'always',
			'icons'         => 'thumbs',
			'style'         => 'pill',
			'size'          => 'medium',
			'align'         => 'left',
			'like_color'    => '#2563eb',
			'dislike_color' => '#dc2626',
			'like_label'    => '',
			'dislike_label' => '',
			'prompt'        => '',
			'thanks'        => '',
			'feedback'      => false,
			'who'           => 'everyone',
			'guests'        => 'browser',
			'comments'      => false,
			'columns'       => true,
			'delete_data'   => false,
		);
	}

	/**
	 * Every setting, with defaults filled in.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		}
		return self::$cache;
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Forgets the cached settings after they change.
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * A button label: the site's own, or the translated default.
	 *
	 * @param string $choice 'like' or 'dislike'.
	 * @return string
	 */
	public static function label( $choice ) {
		$custom = trim( (string) self::get( $choice . '_label' ) );
		if ( '' !== $custom ) {
			return $custom;
		}
		return 'dislike' === $choice ? __( 'Dislike', 'like-dislike-for-wp' ) : __( 'Like', 'like-dislike-for-wp' );
	}

	/**
	 * Post types the buttons can appear on.
	 *
	 * @return array<string,string> Name => label.
	 */
	public static function post_type_choices() {
		$out = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( 'attachment' !== $type->name ) {
				$out[ $type->name ] = $type->labels->name;
			}
		}
		return $out;
	}

	/**
	 * Whether the buttons belong on a post type.
	 *
	 * @param string $post_type Post type.
	 * @return bool
	 */
	public static function type_enabled( $post_type ) {
		return in_array( $post_type, (array) self::get( 'post_types' ), true );
	}

	/**
	 * Registers the option.
	 */
	public static function register() {
		register_setting(
			self::OPTION,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Cleans submitted settings.
	 *
	 * @param mixed $input Submitted values.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$pick     = function ( $key, $allowed ) use ( $input, $defaults ) {
			return isset( $input[ $key ] ) && in_array( $input[ $key ], $allowed, true ) ? $input[ $key ] : $defaults[ $key ];
		};
		$text     = function ( $key, $max ) use ( $input ) {
			$value = isset( $input[ $key ] ) ? trim( sanitize_text_field( $input[ $key ] ) ) : '';
			return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
		};
		$color    = function ( $key ) use ( $input, $defaults ) {
			$value = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
			return $value ? $value : $defaults[ $key ];
		};

		$types = isset( $input['post_types'] ) ? array_map( 'sanitize_key', (array) $input['post_types'] ) : array();

		return array(
			'enabled'       => ! empty( $input['enabled'] ),
			'post_types'    => array_values( array_intersect( $types, array_keys( self::post_type_choices() ) ) ),
			'position'      => $pick( 'position', array( 'after', 'before', 'both' ) ),
			'dislike'       => ! empty( $input['dislike'] ),
			'counts'        => $pick( 'counts', array( 'always', 'after', 'never' ) ),
			'icons'         => $pick( 'icons', array_keys( LDFW_Render::icon_sets() ) ),
			'style'         => $pick( 'style', array( 'pill', 'outline', 'minimal' ) ),
			'size'          => $pick( 'size', array( 'small', 'medium', 'large' ) ),
			'align'         => $pick( 'align', array( 'left', 'center', 'right' ) ),
			'like_color'    => $color( 'like_color' ),
			'dislike_color' => $color( 'dislike_color' ),
			'like_label'    => $text( 'like_label', 40 ),
			'dislike_label' => $text( 'dislike_label', 40 ),
			'prompt'        => $text( 'prompt', 120 ),
			'thanks'        => $text( 'thanks', 160 ),
			'feedback'      => ! empty( $input['feedback'] ),
			'who'           => $pick( 'who', array( 'everyone', 'members' ) ),
			'guests'        => $pick( 'guests', array( 'browser', 'ip' ) ),
			'comments'      => ! empty( $input['comments'] ),
			'columns'       => ! empty( $input['columns'] ),
			'delete_data'   => ! empty( $input['delete_data'] ),
		);
	}

	/* Screen ------------------------------------------------------------------------------ */

	/**
	 * A switch with a title and a description.
	 *
	 * @param string $name        Field name.
	 * @param bool   $checked     Whether it is on.
	 * @param string $title       Title.
	 * @param string $description Description.
	 */
	private static function toggle( $name, $checked, $title, $description = '' ) {
		?>
		<label class="ldfw-toggle">
			<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?>>
			<span class="ldfw-toggle-track" aria-hidden="true"></span>
			<span class="ldfw-toggle-text">
				<strong><?php echo esc_html( $title ); ?></strong>
				<?php if ( $description ) : ?>
					<span><?php echo esc_html( $description ); ?></span>
				<?php endif; ?>
			</span>
		</label>
		<?php
	}

	/**
	 * A row of choices shown as cards.
	 *
	 * @param string $name    Field name.
	 * @param string $current Current value.
	 * @param array  $choices Value => array( label, optional html ).
	 * @param string $label   Group label.
	 * @param string $extra   Extra class.
	 */
	private static function choices( $name, $current, $choices, $label, $extra = '' ) {
		?>
		<fieldset class="ldfw-choices <?php echo esc_attr( $extra ); ?>">
			<legend><?php echo esc_html( $label ); ?></legend>
			<div class="ldfw-choice-row">
				<?php foreach ( $choices as $value => $choice ) : ?>
					<label class="ldfw-choice">
						<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $current, $value ); ?>>
						<span class="ldfw-choice-box">
							<?php
							if ( ! empty( $choice[1] ) ) {
								echo $choice[1]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from the plugin's own SVG icons.
							}
							?>
							<span class="ldfw-choice-label"><?php echo esc_html( $choice[0] ); ?></span>
						</span>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * The Settings screen.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// Seeing Settings is enough to retire the welcome notice.
		delete_option( LDFW_Upgrade::NOTICE );

		$s   = self::all();
		$opt = self::OPTION;

		$icon_choices = array();
		foreach ( LDFW_Render::icon_sets() as $key => $set ) {
			$preview              = 'none' === $key ? '<span class="ldfw-choice-text">Aa</span>' : '<span class="ldfw-choice-icons">' . LDFW_Render::icon( $key, 'like' ) . LDFW_Render::icon( $key, 'dislike' ) . '</span>';
			$icon_choices[ $key ] = array( $set['label'], $preview );
		}
		$style_choices = array(
			'pill'    => array( __( 'Pill', 'like-dislike-for-wp' ), '<span class="ldfw-style-sample is-pill"></span>' ),
			'outline' => array( __( 'Outline', 'like-dislike-for-wp' ), '<span class="ldfw-style-sample is-outline"></span>' ),
			'minimal' => array( __( 'Minimal', 'like-dislike-for-wp' ), '<span class="ldfw-style-sample is-minimal"></span>' ),
		);
		?>
		<div class="wrap ldfw-settings">
			<?php LDFW_Admin::header( __( 'Like Dislike Settings', 'like-dislike-for-wp' ) ); ?>

			<?php settings_errors(); ?>

			<div class="ldfw-settings-layout">
				<form method="post" action="options.php" class="ldfw-settings-main" id="ldfw-settings-form">
					<?php settings_fields( $opt ); ?>

					<section class="ldfw-card">
						<h2><?php esc_html_e( 'Where the buttons appear', 'like-dislike-for-wp' ); ?></h2>
						<div class="ldfw-toggles">
							<?php self::toggle( $opt . '[enabled]', $s['enabled'], __( 'Add the buttons automatically', 'like-dislike-for-wp' ), __( 'Show the buttons on single posts of the types below. Turn this off to place them only with the block or shortcode.', 'like-dislike-for-wp' ) ); ?>
						</div>
						<fieldset class="ldfw-post-types">
							<legend><?php esc_html_e( 'Show them on', 'like-dislike-for-wp' ); ?></legend>
							<?php foreach ( self::post_type_choices() as $type => $label ) : ?>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[post_types][]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, (array) $s['post_types'], true ) ); ?>>
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
						</fieldset>
						<?php
						self::choices(
							$opt . '[position]',
							$s['position'],
							array(
								'after'  => array( __( 'After the content', 'like-dislike-for-wp' ) ),
								'before' => array( __( 'Before the content', 'like-dislike-for-wp' ) ),
								'both'   => array( __( 'Before and after', 'like-dislike-for-wp' ) ),
							),
							__( 'Position', 'like-dislike-for-wp' ),
							'is-compact'
						);
						?>
						<div class="ldfw-toggles">
							<?php
							self::toggle( $opt . '[dislike]', $s['dislike'], __( 'Show the dislike button', 'like-dislike-for-wp' ), __( 'Turn this off for a single like button.', 'like-dislike-for-wp' ) );
							self::toggle( $opt . '[comments]', $s['comments'], __( 'Add buttons to comments', 'like-dislike-for-wp' ), __( 'Readers can like or dislike each comment on the post types above.', 'like-dislike-for-wp' ) );
							?>
						</div>
					</section>

					<section class="ldfw-card">
						<h2><?php esc_html_e( 'Look', 'like-dislike-for-wp' ); ?></h2>
						<div class="ldfw-presets" role="group" aria-label="<?php esc_attr_e( 'Presets', 'like-dislike-for-wp' ); ?>">
							<span><?php esc_html_e( 'Start from:', 'like-dislike-for-wp' ); ?></span>
							<button type="button" class="button" data-preset="classic"><?php esc_html_e( 'Like and dislike', 'like-dislike-for-wp' ); ?></button>
							<button type="button" class="button" data-preset="helpful"><?php esc_html_e( 'Was this helpful?', 'like-dislike-for-wp' ); ?></button>
							<button type="button" class="button" data-preset="hearts"><?php esc_html_e( 'Hearts', 'like-dislike-for-wp' ); ?></button>
							<button type="button" class="button" data-preset="votes"><?php esc_html_e( 'Up and down votes', 'like-dislike-for-wp' ); ?></button>
						</div>
						<?php
						self::choices( $opt . '[icons]', $s['icons'], $icon_choices, __( 'Icons', 'like-dislike-for-wp' ), 'is-icons' );
						self::choices( $opt . '[style]', $s['style'], $style_choices, __( 'Button style', 'like-dislike-for-wp' ), 'is-styles' );
						?>
						<div class="ldfw-fields">
							<label class="ldfw-field">
								<span><?php esc_html_e( 'Like label', 'like-dislike-for-wp' ); ?></span>
								<input type="text" name="<?php echo esc_attr( $opt ); ?>[like_label]" value="<?php echo esc_attr( $s['like_label'] ); ?>" placeholder="<?php esc_attr_e( 'Like', 'like-dislike-for-wp' ); ?>" maxlength="40">
							</label>
							<label class="ldfw-field">
								<span><?php esc_html_e( 'Dislike label', 'like-dislike-for-wp' ); ?></span>
								<input type="text" name="<?php echo esc_attr( $opt ); ?>[dislike_label]" value="<?php echo esc_attr( $s['dislike_label'] ); ?>" placeholder="<?php esc_attr_e( 'Dislike', 'like-dislike-for-wp' ); ?>" maxlength="40">
							</label>
							<label class="ldfw-field is-color">
								<span><?php esc_html_e( 'Like colour', 'like-dislike-for-wp' ); ?></span>
								<input type="color" name="<?php echo esc_attr( $opt ); ?>[like_color]" value="<?php echo esc_attr( $s['like_color'] ); ?>">
							</label>
							<label class="ldfw-field is-color">
								<span><?php esc_html_e( 'Dislike colour', 'like-dislike-for-wp' ); ?></span>
								<input type="color" name="<?php echo esc_attr( $opt ); ?>[dislike_color]" value="<?php echo esc_attr( $s['dislike_color'] ); ?>">
							</label>
							<label class="ldfw-field">
								<span><?php esc_html_e( 'Size', 'like-dislike-for-wp' ); ?></span>
								<select name="<?php echo esc_attr( $opt ); ?>[size]">
									<option value="small" <?php selected( $s['size'], 'small' ); ?>><?php esc_html_e( 'Small', 'like-dislike-for-wp' ); ?></option>
									<option value="medium" <?php selected( $s['size'], 'medium' ); ?>><?php esc_html_e( 'Medium', 'like-dislike-for-wp' ); ?></option>
									<option value="large" <?php selected( $s['size'], 'large' ); ?>><?php esc_html_e( 'Large', 'like-dislike-for-wp' ); ?></option>
								</select>
							</label>
							<label class="ldfw-field">
								<span><?php esc_html_e( 'Alignment', 'like-dislike-for-wp' ); ?></span>
								<select name="<?php echo esc_attr( $opt ); ?>[align]">
									<option value="left" <?php selected( $s['align'], 'left' ); ?>><?php esc_html_e( 'Left', 'like-dislike-for-wp' ); ?></option>
									<option value="center" <?php selected( $s['align'], 'center' ); ?>><?php esc_html_e( 'Centre', 'like-dislike-for-wp' ); ?></option>
									<option value="right" <?php selected( $s['align'], 'right' ); ?>><?php esc_html_e( 'Right', 'like-dislike-for-wp' ); ?></option>
								</select>
							</label>
							<label class="ldfw-field is-wide">
								<span><?php esc_html_e( 'Counts', 'like-dislike-for-wp' ); ?></span>
								<select name="<?php echo esc_attr( $opt ); ?>[counts]">
									<option value="always" <?php selected( $s['counts'], 'always' ); ?>><?php esc_html_e( 'Show them', 'like-dislike-for-wp' ); ?></option>
									<option value="after" <?php selected( $s['counts'], 'after' ); ?>><?php esc_html_e( 'Show them after someone votes', 'like-dislike-for-wp' ); ?></option>
									<option value="never" <?php selected( $s['counts'], 'never' ); ?>><?php esc_html_e( 'Hide them', 'like-dislike-for-wp' ); ?></option>
								</select>
							</label>
						</div>
					</section>

					<section class="ldfw-card">
						<h2><?php esc_html_e( 'Words and feedback', 'like-dislike-for-wp' ); ?></h2>
						<div class="ldfw-fields">
							<label class="ldfw-field is-wide">
								<span><?php esc_html_e( 'Question above the buttons', 'like-dislike-for-wp' ); ?></span>
								<input type="text" name="<?php echo esc_attr( $opt ); ?>[prompt]" value="<?php echo esc_attr( $s['prompt'] ); ?>" placeholder="<?php esc_attr_e( 'For example: Was this article helpful?', 'like-dislike-for-wp' ); ?>" maxlength="120">
							</label>
							<label class="ldfw-field is-wide">
								<span><?php esc_html_e( 'Thank-you message after a vote', 'like-dislike-for-wp' ); ?></span>
								<input type="text" name="<?php echo esc_attr( $opt ); ?>[thanks]" value="<?php echo esc_attr( $s['thanks'] ); ?>" placeholder="<?php esc_attr_e( 'For example: Thanks for letting us know!', 'like-dislike-for-wp' ); ?>" maxlength="160">
							</label>
						</div>
						<div class="ldfw-toggles">
							<?php self::toggle( $opt . '[feedback]', $s['feedback'], __( 'Ask what could be better after a dislike', 'like-dislike-for-wp' ), __( 'A short text box appears after a dislike. Answers show up under Like Dislike → Stats.', 'like-dislike-for-wp' ) ); ?>
						</div>
					</section>

					<section class="ldfw-card">
						<h2><?php esc_html_e( 'Voting', 'like-dislike-for-wp' ); ?></h2>
						<?php
						self::choices(
							$opt . '[who]',
							$s['who'],
							array(
								'everyone' => array( __( 'Everyone', 'like-dislike-for-wp' ) ),
								'members'  => array( __( 'Logged-in users only', 'like-dislike-for-wp' ) ),
							),
							__( 'Who can vote', 'like-dislike-for-wp' ),
							'is-compact'
						);
						self::choices(
							$opt . '[guests]',
							$s['guests'],
							array(
								'browser' => array( __( 'One vote per browser (recommended)', 'like-dislike-for-wp' ) ),
								'ip'      => array( __( 'One vote per IP address', 'like-dislike-for-wp' ) ),
							),
							__( 'Visitors who are not logged in get', 'like-dislike-for-wp' ),
							'is-compact'
						);
						?>
						<p class="description ldfw-note"><?php esc_html_e( 'Logged-in users always get one vote per item. Per browser lets several people on the same network vote; per IP address is stricter but counts a whole office or school as one voter. IP addresses are never stored, only a keyed hash.', 'like-dislike-for-wp' ); ?></p>
					</section>

					<section class="ldfw-card">
						<h2><?php esc_html_e( 'Admin and data', 'like-dislike-for-wp' ); ?></h2>
						<div class="ldfw-toggles">
							<?php
							self::toggle( $opt . '[columns]', $s['columns'], __( 'Likes column in post lists', 'like-dislike-for-wp' ), __( 'Adds a sortable Likes column to the Posts screen and the lists of the other types above.', 'like-dislike-for-wp' ) );
							self::toggle( $opt . '[delete_data]', $s['delete_data'], __( 'Delete all votes and settings when the plugin is deleted', 'like-dislike-for-wp' ), __( 'Deactivating always keeps everything. This cannot be undone.', 'like-dislike-for-wp' ) );
							?>
						</div>
					</section>

					<?php submit_button( __( 'Save Settings', 'like-dislike-for-wp' ) ); ?>
				</form>

				<aside class="ldfw-settings-side">
					<section class="ldfw-card ldfw-preview-card">
						<h2><?php esc_html_e( 'Preview', 'like-dislike-for-wp' ); ?></h2>
						<p class="ldfw-card-intro"><?php esc_html_e( 'Try the buttons. The preview follows your changes before you save.', 'like-dislike-for-wp' ); ?></p>
						<div class="ldfw-preview-page">
							<span class="ldfw-preview-line"></span>
							<span class="ldfw-preview-line is-short"></span>
							<div id="ldfw-preview"></div>
						</div>
					</section>
					<section class="ldfw-card">
						<h2><?php esc_html_e( 'Place them yourself', 'like-dislike-for-wp' ); ?></h2>
						<p class="ldfw-card-intro"><?php esc_html_e( 'Add the Like Dislike Buttons block anywhere in a post or template, or use a shortcode:', 'like-dislike-for-wp' ); ?></p>
						<p><code>[like_dislike]</code></p>
						<p class="ldfw-card-intro"><?php esc_html_e( 'List the most liked posts with the Most Liked Posts block, or:', 'like-dislike-for-wp' ); ?></p>
						<p><code>[most_liked_posts number="5" period="month"]</code></p>
					</section>
					<?php LDFW_Admin::sidebar(); ?>
				</aside>
			</div>
		</div>
		<?php
	}
}

<?php
/**
 * Admin: menus, the Stats screen, list columns and the per-post box.
 *
 * @package LikeDislike
 */

defined( 'ABSPATH' ) || exit;

/**
 * Everything in wp-admin except Settings, which lives in LDFW_Settings.
 */
class LDFW_Admin {

	const STATS = 'ldfw-stats';
	const RESET = 'ldfw_reset_votes';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_by_likes' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ), 10, 2 );
		add_action( 'save_post', array( __CLASS__, 'save_meta_box' ), 10, 2 );
		add_action( 'admin_post_' . self::RESET, array( __CLASS__, 'reset' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( LDFW_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Like Dislike → Stats and Settings.
	 */
	public static function menu() {
		add_menu_page(
			__( 'Like Dislike', 'like-dislike-for-wp' ),
			__( 'Like Dislike', 'like-dislike-for-wp' ),
			'edit_others_posts',
			self::STATS,
			array( __CLASS__, 'render_stats' ),
			'dashicons-thumbs-up',
			26
		);
		add_submenu_page( self::STATS, __( 'Like Dislike Stats', 'like-dislike-for-wp' ), __( 'Stats', 'like-dislike-for-wp' ), 'edit_others_posts', self::STATS, array( __CLASS__, 'render_stats' ) );
		add_submenu_page( self::STATS, __( 'Like Dislike Settings', 'like-dislike-for-wp' ), __( 'Settings', 'like-dislike-for-wp' ), 'manage_options', LDFW_Settings::PAGE, array( 'LDFW_Settings', 'render' ) );
	}

	/**
	 * Settings and Stats links on the Plugins screen.
	 *
	 * @param string[] $links Links.
	 * @return string[]
	 */
	public static function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=' . LDFW_Settings::PAGE ) ) . '">' . esc_html__( 'Settings', 'like-dislike-for-wp' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=' . self::STATS ) ) . '">' . esc_html__( 'Stats', 'like-dislike-for-wp' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Styles for our screens and the list column; the settings preview script.
	 *
	 * @param string $hook Current screen.
	 */
	public static function assets( $hook ) {
		$ours = in_array( $hook, array( 'toplevel_page_' . self::STATS, 'like-dislike_page_' . LDFW_Settings::PAGE ), true )
			|| false !== strpos( $hook, LDFW_Settings::PAGE );
		if ( $ours || in_array( $hook, array( 'edit.php', 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_style( 'ldfw-admin', LDFW_URL . 'assets/css/admin.css', array(), LDFW_VERSION );
			wp_add_inline_style( 'ldfw-admin', ':root{--ldfw-like:' . LDFW_Settings::get( 'like_color' ) . ';--ldfw-dislike:' . LDFW_Settings::get( 'dislike_color' ) . '}' );
		}
		if ( $ours ) {
			wp_enqueue_style( 'ldfw-buttons', LDFW_URL . 'assets/css/buttons.css', array(), LDFW_VERSION );
		}
		if ( false !== strpos( $hook, LDFW_Settings::PAGE ) ) {
			wp_enqueue_script( 'ldfw-admin', LDFW_URL . 'assets/js/admin.js', array( 'wp-i18n' ), LDFW_VERSION, true );
			wp_set_script_translations( 'ldfw-admin', 'like-dislike-for-wp', LDFW_DIR . 'languages' );
			$icons = array();
			foreach ( LDFW_Render::icon_sets() as $key => $set ) {
				$icons[ $key ] = array(
					'like'    => LDFW_Render::icon( $key, 'like' ),
					'dislike' => LDFW_Render::icon( $key, 'dislike' ),
				);
			}
			wp_add_inline_script(
				'ldfw-admin',
				'window.ldfwAdmin = ' . wp_json_encode(
					array(
						'icons'    => $icons,
						'defaults' => array(
							'like'    => __( 'Like', 'like-dislike-for-wp' ),
							'dislike' => __( 'Dislike', 'like-dislike-for-wp' ),
							'yes'     => __( 'Yes', 'like-dislike-for-wp' ),
							'no'      => __( 'No', 'like-dislike-for-wp' ),
							'helpful' => __( 'Was this helpful?', 'like-dislike-for-wp' ),
							'thanks'  => __( 'Thanks for letting us know!', 'like-dislike-for-wp' ),
						),
					)
				) . ';',
				'before'
			);
		}
	}

	/* Shared pieces ---------------------------------------------------------------------------- */

	/**
	 * The header on our screens.
	 *
	 * @param string $title   Screen title.
	 * @param string $current 'stats' or 'settings'.
	 */
	public static function header( $title, $current = 'settings' ) {
		?>
		<header class="ldfw-header">
			<img src="<?php echo esc_url( LDFW_URL . 'assets/images/icon.svg' ); ?>" width="48" height="48" alt="">
			<div>
				<h1><?php echo esc_html( $title ); ?></h1>
				<p>
					<?php
					/* translators: %s: plugin version. */
					echo esc_html( sprintf( __( 'Version %s', 'like-dislike-for-wp' ), LDFW_VERSION ) );
					?>
				</p>
			</div>
			<nav class="ldfw-header-links">
				<?php if ( 'stats' === $current ) : ?>
					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . LDFW_Settings::PAGE ) ); ?>"><?php esc_html_e( 'Settings', 'like-dislike-for-wp' ); ?></a>
					<?php endif; ?>
				<?php else : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::STATS ) ); ?>"><?php esc_html_e( 'Stats', 'like-dislike-for-wp' ); ?></a>
				<?php endif; ?>
			</nav>
		</header>
		<?php
	}

	/**
	 * The review card and our other plugins, in the sidebar of our screens.
	 */
	public static function sidebar() {
		$images   = LDFW_URL . 'assets/images/plugins/';
		$featured = array(
			array(
				'name'        => 'NoteFlow',
				'tagline'     => __( 'Notes and team collaboration in WordPress', 'like-dislike-for-wp' ),
				'description' => __( 'A notes app in your admin, with checklists, reminders, shared notes, and comments and issues on posts.', 'like-dislike-for-wp' ),
				'url'         => 'https://wordpress.org/plugins/noteflow/',
				'icon'        => 'noteflow-icon.svg',
			),
			array(
				'name'        => 'Page Visit Counter',
				'tagline'     => __( 'Privacy-first analytics inside WordPress', 'like-dislike-for-wp' ),
				'description' => __( 'See visitors and page views right in your dashboard, with no cookies and no external scripts.', 'like-dislike-for-wp' ),
				'url'         => 'https://pagevisitcounter.com/',
				'icon'        => 'page-visit-counter-icon.svg',
			),
		);
		$more     = array(
			array(
				'name'    => 'PushRow for Google Sheets',
				'tagline' => __( 'Keep Google Sheets in sync with WordPress', 'like-dislike-for-wp' ),
				'url'     => 'https://getpushrow.com/',
				'icon'    => 'pushrow-icon.svg',
			),
			array(
				'name'    => 'UltimaKit',
				'tagline' => __( 'Admin tools, security and performance in one plugin', 'like-dislike-for-wp' ),
				'url'     => 'https://wordpress.org/plugins/ultimakit-for-wp/',
				'icon'    => 'ultimakit-icon.png',
			),
			array(
				'name'    => 'Hide Admin Bar Based on User Roles',
				'tagline' => __( 'Hide the toolbar for the roles you choose', 'like-dislike-for-wp' ),
				'url'     => 'https://wordpress.org/plugins/hide-admin-bar-based-on-user-roles/',
				'icon'    => 'hab-icon.svg',
			),
		);
		?>
		<section class="ldfw-card ldfw-review">
			<h2><?php esc_html_e( 'Like Dislike is free to use', 'like-dislike-for-wp' ); ?></h2>
			<p><?php esc_html_e( 'Everything in Like Dislike is free, and what\'s free stays free. If it helps your site, a short review on WordPress.org helps other people find it.', 'like-dislike-for-wp' ); ?></p>
			<a class="button" href="<?php echo esc_url( LDFW_Review::URL ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="ldfw-stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
				<?php esc_html_e( 'Leave a review', 'like-dislike-for-wp' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'like-dislike-for-wp' ); ?></span>
			</a>
			<p class="ldfw-support"><a href="https://wordpress.org/support/plugin/like-dislike-for-wp/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get help or suggest a feature', 'like-dislike-for-wp' ); ?></a></p>
		</section>
		<section class="ldfw-card ldfw-promo">
			<h2><?php esc_html_e( 'More from the makers of Like Dislike', 'like-dislike-for-wp' ); ?></h2>
			<?php foreach ( $featured as $plugin ) : ?>
				<a class="ldfw-promo-featured" href="<?php echo esc_url( $plugin['url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="ldfw-promo-head">
						<img src="<?php echo esc_url( $images . $plugin['icon'] ); ?>" width="40" height="40" alt="">
						<span>
							<span class="ldfw-promo-name"><?php echo esc_html( $plugin['name'] ); ?></span>
							<span class="ldfw-promo-tagline"><?php echo esc_html( $plugin['tagline'] ); ?></span>
						</span>
					</span>
					<span class="ldfw-promo-description"><?php echo esc_html( $plugin['description'] ); ?></span>
					<span class="ldfw-promo-cta"><?php esc_html_e( 'Learn more', 'like-dislike-for-wp' ); ?> &rarr;<span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'like-dislike-for-wp' ); ?></span></span>
				</a>
			<?php endforeach; ?>
			<h3><?php esc_html_e( 'More free plugins', 'like-dislike-for-wp' ); ?></h3>
			<ul class="ldfw-promo-list">
				<?php foreach ( $more as $plugin ) : ?>
					<li>
						<a href="<?php echo esc_url( $plugin['url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<img src="<?php echo esc_url( $images . $plugin['icon'] ); ?>" width="32" height="32" alt="">
							<span>
								<span class="ldfw-promo-name"><?php echo esc_html( $plugin['name'] ); ?></span>
								<span class="ldfw-promo-tagline"><?php echo esc_html( $plugin['tagline'] ); ?></span>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	}

	/* Stats -------------------------------------------------------------------------------------------- */

	/**
	 * Periods on the Stats screen.
	 *
	 * @return array<int,string> Days => label; 0 is all time.
	 */
	private static function periods() {
		return array(
			7   => __( '7 days', 'like-dislike-for-wp' ),
			30  => __( '30 days', 'like-dislike-for-wp' ),
			90  => __( '90 days', 'like-dislike-for-wp' ),
			365 => __( '12 months', 'like-dislike-for-wp' ),
			0   => __( 'All time', 'like-dislike-for-wp' ),
		);
	}

	/**
	 * A list of posts with their counts.
	 *
	 * @param array[] $items   From LDFW_Stats::top().
	 * @param string  $nothing Text when there are none.
	 */
	private static function post_table( $items, $nothing ) {
		if ( ! $items ) {
			echo '<p class="ldfw-empty">' . esc_html( $nothing ) . '</p>';
			return;
		}
		echo '<ol class="ldfw-top">';
		foreach ( $items as $item ) {
			$total = $item['like'] + $item['dislike'];
			$share = $total ? round( $item['like'] / $total * 100 ) : 0;
			$edit  = get_edit_post_link( $item['id'] );
			echo '<li><span class="ldfw-top-title">';
			echo $edit ? '<a href="' . esc_url( $edit ) . '">' . esc_html( get_the_title( $item['id'] ) ) . '</a>' : esc_html( get_the_title( $item['id'] ) );
			echo '<span class="ldfw-top-type">' . esc_html( (string) get_post_type_object( get_post_type( $item['id'] ) )->labels->singular_name ) . '</span></span>';
			echo '<span class="ldfw-top-counts"><span class="is-like">' . LDFW_Render::icon( 'thumbs', 'like' ) . esc_html( number_format_i18n( $item['like'] ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin's own SVG.
			echo '<span class="is-dislike">' . LDFW_Render::icon( 'thumbs', 'dislike' ) . esc_html( number_format_i18n( $item['dislike'] ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin's own SVG.
			/* translators: %s: percentage of votes that are likes. */
			echo '<span class="ldfw-meter" title="' . esc_attr( sprintf( __( '%s%% liked it', 'like-dislike-for-wp' ), $share ) ) . '"><span style="width:' . (int) $share . '%"></span></span></span></li>';
		}
		echo '</ol>';
	}

	/**
	 * Like Dislike → Stats.
	 */
	public static function render_stats() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		$periods = self::periods();
		$days    = isset( $_GET['period'] ) ? absint( $_GET['period'] ) : 30; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view switch.
		$days    = isset( $periods[ $days ] ) ? $days : 30;
		$totals  = LDFW_Stats::totals( $days );
		$votes   = $totals['like'] + $totals['dislike'];
		$share   = $votes ? round( $totals['like'] / $votes * 100 ) : 0;
		$chart   = LDFW_Stats::daily( $days && $days <= 90 ? $days : 30 );
		?>
		<div class="wrap ldfw-stats-screen">
			<?php self::header( __( 'Like Dislike Stats', 'like-dislike-for-wp' ), 'stats' ); ?>

			<nav class="ldfw-periods" aria-label="<?php esc_attr_e( 'Period', 'like-dislike-for-wp' ); ?>">
				<?php foreach ( $periods as $value => $label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'period', $value, admin_url( 'admin.php?page=' . self::STATS ) ) ); ?>" <?php echo $value === $days ? 'aria-current="page" class="is-current"' : ''; ?>><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<div class="ldfw-stat-cards">
				<div class="ldfw-stat is-like"><span><?php esc_html_e( 'Likes', 'like-dislike-for-wp' ); ?></span><strong><?php echo esc_html( number_format_i18n( $totals['like'] ) ); ?></strong></div>
				<div class="ldfw-stat is-dislike"><span><?php esc_html_e( 'Dislikes', 'like-dislike-for-wp' ); ?></span><strong><?php echo esc_html( number_format_i18n( $totals['dislike'] ) ); ?></strong></div>
				<div class="ldfw-stat"><span><?php esc_html_e( 'Votes', 'like-dislike-for-wp' ); ?></span><strong><?php echo esc_html( number_format_i18n( $votes ) ); ?></strong></div>
				<div class="ldfw-stat"><span><?php esc_html_e( 'Liked it', 'like-dislike-for-wp' ); ?></span><strong><?php echo esc_html( $votes ? $share . '%' : '—' ); ?></strong></div>
			</div>

			<section class="ldfw-card ldfw-chart-card">
				<h2>
					<?php
					/* translators: %s: number of days. */
					echo esc_html( sprintf( _n( 'Votes per day, last %s day', 'Votes per day, last %s days', count( $chart ), 'like-dislike-for-wp' ), number_format_i18n( count( $chart ) ) ) );
					?>
				</h2>
				<p class="ldfw-legend"><span class="is-like"><?php esc_html_e( 'Likes', 'like-dislike-for-wp' ); ?></span><span class="is-dislike"><?php esc_html_e( 'Dislikes', 'like-dislike-for-wp' ); ?></span></p>
				<?php echo LDFW_Stats::chart( $chart ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from numbers and escaped labels. ?>
			</section>

			<div class="ldfw-columns">
				<section class="ldfw-card">
					<h2><?php esc_html_e( 'Most liked', 'like-dislike-for-wp' ); ?></h2>
					<?php self::post_table( LDFW_Stats::top( 'like', $days ), __( 'No likes in this period.', 'like-dislike-for-wp' ) ); ?>
				</section>
				<section class="ldfw-card">
					<h2><?php esc_html_e( 'Most disliked', 'like-dislike-for-wp' ); ?></h2>
					<p class="ldfw-card-intro"><?php esc_html_e( 'Worth a second look: these got the most dislikes.', 'like-dislike-for-wp' ); ?></p>
					<?php self::post_table( LDFW_Stats::top( 'dislike', $days ), __( 'No dislikes in this period.', 'like-dislike-for-wp' ) ); ?>
				</section>
			</div>

			<section class="ldfw-card">
				<h2><?php esc_html_e( 'What readers said could be better', 'like-dislike-for-wp' ); ?></h2>
				<?php
				$feedback = LDFW_Stats::feedback( 20 );
				if ( ! $feedback ) {
					echo '<p class="ldfw-empty">' . esc_html(
						LDFW_Settings::get( 'feedback' )
							? __( 'No answers yet. They appear here when someone dislikes a post and says why.', 'like-dislike-for-wp' )
							: __( 'Turn on "Ask what could be better after a dislike" in Settings to collect answers here.', 'like-dislike-for-wp' )
					) . '</p>';
				} else {
					echo '<ul class="ldfw-feedback-list">';
					foreach ( $feedback as $row ) {
						$comment = 'comment' === $row->object_type ? get_comment( (int) $row->post_id ) : null;
						$post_id = $comment ? (int) $comment->comment_post_ID : (int) $row->post_id;
						if ( ! get_post( $post_id ) ) {
							continue;
						}
						echo '<li><blockquote>' . esc_html( $row->feedback ) . '</blockquote><p>';
						echo '<a href="' . esc_url( (string) get_permalink( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a> &middot; ';
						/* translators: %s: time since, like "5 mins". */
						echo esc_html( sprintf( __( '%s ago', 'like-dislike-for-wp' ), human_time_diff( strtotime( $row->date_time . ' +0000' ) ) ) );
						echo '</p></li>';
					}
					echo '</ul>';
				}
				?>
			</section>
		</div>
		<?php
	}

	/* List column ------------------------------------------------------------------------------ */

	/**
	 * Adds the Likes column to the chosen post types.
	 */
	public static function columns() {
		if ( ! LDFW_Settings::get( 'columns' ) ) {
			return;
		}
		foreach ( (array) LDFW_Settings::get( 'post_types' ) as $type ) {
			add_filter( "manage_{$type}_posts_columns", array( __CLASS__, 'add_column' ) );
			add_action( "manage_{$type}_posts_custom_column", array( __CLASS__, 'render_column' ), 10, 2 );
			add_filter( "manage_edit-{$type}_sortable_columns", array( __CLASS__, 'sortable_column' ) );
		}
	}

	/**
	 * The column.
	 *
	 * @param string[] $columns Columns.
	 * @return string[]
	 */
	public static function add_column( $columns ) {
		$columns['ldfw_likes'] = esc_html__( 'Likes', 'like-dislike-for-wp' );
		return $columns;
	}

	/**
	 * Makes it sortable.
	 *
	 * @param string[] $columns Sortable columns.
	 * @return string[]
	 */
	public static function sortable_column( $columns ) {
		$columns['ldfw_likes'] = array( 'ldfw_likes', true );
		return $columns;
	}

	/**
	 * A post's counts.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'ldfw_likes' !== $column ) {
			return;
		}
		$counts = LDFW_Votes::counts( 'post', $post_id );
		if ( ! $counts['like'] && ! $counts['dislike'] ) {
			echo '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . esc_html__( 'No votes', 'like-dislike-for-wp' ) . '</span>';
			return;
		}
		echo '<span class="ldfw-col"><span class="is-like">' . LDFW_Render::icon( 'thumbs', 'like' ) . esc_html( number_format_i18n( $counts['like'] ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin's own SVG.
		echo '<span class="is-dislike">' . LDFW_Render::icon( 'thumbs', 'dislike' ) . esc_html( number_format_i18n( $counts['dislike'] ) ) . '</span></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin's own SVG.
	}

	/**
	 * Sorts a list by likes, keeping posts without votes.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function sort_by_likes( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'ldfw_likes' !== $query->get( 'orderby' ) ) {
			return;
		}
		$query->set(
			'meta_query',
			array(
				'relation'   => 'OR',
				'ldfw_likes' => array(
					'key'     => LDFW_Votes::LIKES,
					'compare' => 'EXISTS',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => LDFW_Votes::LIKES,
					'compare' => 'NOT EXISTS',
				),
			)
		);
		$query->set( 'orderby', array( 'ldfw_likes' => 'ASC' === strtoupper( (string) $query->get( 'order' ) ) ? 'ASC' : 'DESC' ) );
	}

	/* Per-post box ---------------------------------------------------------------------------- */

	/**
	 * The Like Dislike box on the chosen post types.
	 *
	 * @param string  $post_type Post type.
	 * @param WP_Post $post      Post.
	 */
	public static function meta_box( $post_type, $post ) {
		if ( ! $post instanceof WP_Post || ! LDFW_Settings::type_enabled( $post_type ) ) {
			return;
		}
		add_meta_box( 'ldfw-box', __( 'Like Dislike', 'like-dislike-for-wp' ), array( __CLASS__, 'render_meta_box' ), $post_type, 'side', 'low' );
	}

	/**
	 * Renders the box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		$counts = LDFW_Votes::counts( 'post', $post->ID );
		wp_nonce_field( 'ldfw_box_' . $post->ID, 'ldfw_box_nonce' );
		?>
		<p class="ldfw-box-counts">
			<span class="is-like"><?php echo LDFW_Render::icon( 'thumbs', 'like' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin's own SVG. ?>
				<?php
				/* translators: %s: number of likes. */
				echo esc_html( sprintf( _n( '%s like', '%s likes', $counts['like'], 'like-dislike-for-wp' ), number_format_i18n( $counts['like'] ) ) );
				?>
			</span>
			<span class="is-dislike"><?php echo LDFW_Render::icon( 'thumbs', 'dislike' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin's own SVG. ?>
				<?php
				/* translators: %s: number of dislikes. */
				echo esc_html( sprintf( _n( '%s dislike', '%s dislikes', $counts['dislike'], 'like-dislike-for-wp' ), number_format_i18n( $counts['dislike'] ) ) );
				?>
			</span>
		</p>
		<p>
			<label>
				<input type="checkbox" name="ldfw_hide" value="1" <?php checked( (bool) get_post_meta( $post->ID, LDFW_Votes::HIDE, true ) ); ?>>
				<?php esc_html_e( 'Hide the buttons on this post', 'like-dislike-for-wp' ); ?>
			</label>
		</p>
		<?php if ( $counts['like'] || $counts['dislike'] ) : ?>
			<p>
				<a class="ldfw-reset" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::RESET . '&post=' . $post->ID ), self::RESET . '_' . $post->ID ) ); ?>" onclick="return window.confirm( this.dataset.confirm );" data-confirm="<?php esc_attr_e( 'Remove every like and dislike on this post? This cannot be undone.', 'like-dislike-for-wp' ); ?>"><?php esc_html_e( 'Reset votes', 'like-dislike-for-wp' ); ?></a>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Saves "hide the buttons".
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_meta_box( $post_id, $post ) {
		if ( ! isset( $_POST['ldfw_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ldfw_box_nonce'] ) ), 'ldfw_box_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! LDFW_Settings::type_enabled( $post->post_type ) ) {
			return;
		}
		if ( ! empty( $_POST['ldfw_hide'] ) ) {
			update_post_meta( $post_id, LDFW_Votes::HIDE, 1 );
		} else {
			delete_post_meta( $post_id, LDFW_Votes::HIDE );
		}
	}

	/**
	 * Removes every vote on a post.
	 */
	public static function reset() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( self::RESET . '_' . $post_id );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You cannot reset the votes on this post.', 'like-dislike-for-wp' ), 403 );
		}
		LDFW_Votes::reset( 'post', $post_id );
		wp_safe_redirect( add_query_arg( 'ldfw_reset', 1, get_edit_post_link( $post_id, 'raw' ) ) );
		exit;
	}
}

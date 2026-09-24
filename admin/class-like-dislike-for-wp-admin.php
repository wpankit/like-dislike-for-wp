<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wplikedislike.pro
 * @since      1.0.0
 *
 * @package    Likedislike_For_Wp
 * @subpackage Likedislike_For_Wp/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Likedislike_For_Wp
 * @subpackage Likedislike_For_Wp/admin
 * @author     Ankit Panchal <wptoolsdev@gmail.com>
 */
class Likedislike_For_Wp_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
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

		if ( in_array( LIKEDISLIKE_FOR_WP_CURRENT_PAGE, LIKEDISLIKE_FOR_WP_ALLOWED_PAGES, true ) ) {
			wp_enqueue_style( 'likedislike_bootstrap_main', plugin_dir_url( __FILE__ ) . 'css/bootstrap.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'likedislike_bootstrap_rtl', plugin_dir_url( __FILE__ ) . 'css/bootstrap.rtl.min.css', array(), $this->version, 'all' );
			// Enqueue toastr CSS.
			wp_enqueue_style( 'toastr-css', plugin_dir_url( __FILE__ ) . 'css/toastr.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/like-dislike-for-wp-admin.css', array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the admin area.
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

		 if ( in_array( LIKEDISLIKE_FOR_WP_CURRENT_PAGE, LIKEDISLIKE_FOR_WP_ALLOWED_PAGES, true ) ) {

			// Enqueue jQuery
			wp_enqueue_script( 'jquery' );
		
			// Enqueue Bootstrap bundle
			wp_enqueue_script( 'like_dislike_for_wp_bootstrap_bundle', plugin_dir_url( __FILE__ ) . 'js/bootstrap.bundle.min.js', array( 'jquery' ), $this->version, false );
		
			// Enqueue toastr.js.
			wp_enqueue_script( 'toastr-js', plugin_dir_url( __FILE__ ) . 'js/toastr.min.js', array( 'jquery' ), $this->version, true );
		
			// Enqueue main plugin script
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/like-dislike-for-wp-admin.js', array( 'jquery' ), $this->version, false );
		
			// Localize your script with the AJAX URL.
			wp_localize_script(
				$this->plugin_name,
				'like_dislike_for_wp_ajax',
				array(
					'url'            => admin_url( 'admin-ajax.php' ),
					'rest_api_url'   => esc_url_raw( rest_url( 'like-dislike-for-wp/v1/update-like-dislike' ) ),
					'nonce'          => wp_create_nonce( 'like_dislike_for_wp_nonce' ),
				)
			);
		}
		
	}

	public function like_dislike_for_wp_admin_menu(){
		// Ensure the current user has the 'manage_options' capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Parameters are: page_title, menu_title, capability, menu_slug, function, icon_url, position.
		add_menu_page(
			__( 'WP Like Dislike Settings', 'like-dislike-for-wp-pro' ), // Page Title.
			__( 'WP Like Dislike', 'like-dislike-for-wp-pro' ),         // Menu Title.
			'manage_options',                                      // Capability (only admins can access).
			'wp-like-dislike-settings',                            // Menu Slug.
			array( $this, 'like_dislike_for_wp_render_settings_page' ),  // The function to render the page content.
			'dashicons-heart',                                     // Icon URL or Slug.
			100                                                    // Position.
		);

		add_submenu_page(
		    'wp-like-dislike-settings',                              // Parent Slug (the top-level menu slug).
		    __( 'Posts Stats', 'like-dislike-for-wp-pro' ),   // Page Title.
		    __( 'Posts Stats', 'like-dislike-for-wp-pro' ),         // Menu Title.
		    'manage_options',                                        // Capability.
		    'wp-like-dislike-posts-counts',                               // Submenu Slug.
		    array( $this, 'like_dislike_for_wp_render_posts_count' ) // The function to render the submenu page content.
		);

		add_submenu_page(
		    'wp-like-dislike-settings',                              // Parent Slug (the top-level menu slug).
		    __( 'Pages Stats', 'like-dislike-for-wp-pro' ),   // Page Title.
		    __( 'Pages Stats', 'like-dislike-for-wp-pro' ),         // Menu Title.
		    'manage_options',                                        // Capability.
		    'wp-like-dislike-pages-counts',                               // Submenu Slug.
		    array( $this, 'like_dislike_for_wp_render_pages_count' ) // The function to render the submenu page content.
		);

	}	

	public function like_dislike_for_wp_render_settings_page(){
		?>
		<div class="wrap">
			<?php $this->like_dislike_for_wp_get_header(); ?>
			<?php $this->like_dislike_for_wp_get_content(); ?>
		</div>
		<?php
	}

	public function like_dislike_for_wp_render_posts_count(){
		$posts = $this->get_like_dislike_counts_with_titles( 'post' );
		?>
		<div class="wrap">
			<?php $this->like_dislike_for_wp_get_header(); ?>
			<div class="container-fluid module-container">
				<div class="row">

					<div class="likedislikewp-posts-stats">
						<div class="likedislikewp-heading">
							<?php echo esc_html_e( 'Posts', 'like-dislike-for-wp-pro' );?>
						</div>
						<div class="likedislikewp-stats container mt-3">
							<table class="table">
								<thead class="thead-dark">
									<tr>
										<th><?php echo esc_html_e( 'ID', 'like-dislike-for-wp-pro' );?></th>
										<th><?php echo esc_html_e( 'Post Title', 'like-dislike-for-wp-pro' );?></th>
										<th><?php echo esc_html_e( '#Link', 'like-dislike-for-wp-pro' );?></th>
										<th><?php echo esc_html_e( 'Date', 'like-dislike-for-wp-pro' );?></th>
										<th><?php echo esc_html_e( 'Likes', 'like-dislike-for-wp-pro' );?></th>
										<th><?php echo esc_html_e( 'Dislikes', 'like-dislike-for-wp-pro' );?></th>
									</tr>
								</thead>
								<tbody>
									<?php 
										if( !empty($posts)) {
											foreach( $posts as $post ){
												?>
												<tr>
												    <td><?php echo esc_html($post->post_id); ?></td>
												    <td><?php echo esc_html($post->post_title); ?></td>
												    <td>
												        <a target="_blank" href="<?php echo esc_url(get_permalink($post->post_id)); ?>">
												            <span class="dashicons dashicons-admin-links"></span>
												        </a>
												    </td>
												    <td><?php echo esc_html($post->date_time); ?></td>
												    <td><?php echo esc_html($post->like_count); ?></td>
												    <td><?php echo esc_html($post->dislike_count); ?></td>
												</tr>

												<?php
											}
										} else {
											echo '<tr><td colspan="6" class="text-center p-5">'.__('No posts found','like-dislike-for-wp').'</td></tr>';
										}
									?>
								</tbody>
							</table>
						</div>
					</div>

				</div>
			</div>
		</div>
		<?php
	}

	public function like_dislike_for_wp_render_pages_count(){
		$pages = $this->get_like_dislike_counts_with_titles( 'page' );
		?>
		<div class="wrap">
			<?php $this->like_dislike_for_wp_get_header(); ?>
			<div class="container-fluid module-container">
				<div class="row">
					<div class="likedislikewp-pages-stats">
						<div class="likedislikewp-heading">
							<?php echo esc_html_e( 'Pages', 'like-dislike-for-wp-pro' );?>
						</div>
						<div class="likedislikewp-stats container mt-3">
							<table class="table">
								<thead class="thead-dark">
									<tr>
										<th><?php echo esc_html_e( 'ID', 'like-dislike-for-wp-pro' );?></th>
										<th><?php echo esc_html_e( 'Page Title', 'like-dislike-for-wp-pro' );?></th>
										<th><?php echo esc_html_e( '#Link', 'like-dislike-for-wp-pro' );?></th>
										<th><?php echo esc_html_e( 'Date', 'like-dislike-for-wp-pro' );?></th>
										<th><?php echo esc_html_e( 'Likes', 'like-dislike-for-wp-pro' );?></th>
										<th><?php echo esc_html_e( 'Dislikes', 'like-dislike-for-wp-pro' );?></th>
									</tr>
								</thead>
								<tbody>
								<?php 
									if( !empty($pages)) {
										foreach( $pages as $page ){
											?>
											<tr>
											    <td><?php echo esc_html($page->post_id); ?></td>
											    <td><?php echo esc_html($page->post_title); ?></td>
											    <td>
											        <a target="_blank" href="<?php echo esc_url(get_permalink($page->post_id)); ?>">
											            <span class="dashicons dashicons-admin-links"></span>
											        </a>
											    </td>
											    <td><?php echo esc_html($page->date_time); ?></td>
											    <td><?php echo esc_html($page->like_count); ?></td>
											    <td><?php echo esc_html($page->dislike_count); ?></td>
											</tr>

											<?php
										}
									} else {
										echo '<tr><td colspan="6" class="text-center p-5">'.__('No pages found','like-dislike-for-wp').'</td></tr>';
									}
								?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Retrieves or displays the custom header content for the theme.
	 *
	 * This function is a custom implementation for fetching or rendering the header content.
	 * It can be used to include custom header templates or dynamic header elements specific
	 * to the theme or plugin. The function can be tailored to support different header styles
	 * or configurations based on context or preferences set in the theme options.
	 */
	public function like_dislike_for_wp_get_header() {
		?>
		<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #6610F2;">
			<div class="container-fluid p-2">
				<a class="navbar-brand" href="javascript:void(0);" target="_blank">
					<img src="<?php echo esc_url( LIKEDISLIKE_FOR_WP_LOGO ); ?>"  class="d-inline-block align-top" alt="like-dislike-for-wp-logo" width="175px">
				</a>
				<div class="navbar-nav ml-auto">
					<a class="nav-item nav-link" href="https://wordpress.org/support/plugin/like-dislike-for-wp/reviews/#new-post" target="_blank" style="color: #ffffff; margin-right: 20px">Leave Feedback</a>
				</div>
			</div>
		</nav>
		<?php
	}

	public function like_dislike_for_wp_get_content(){
		$posts = $this->get_like_dislike_counts_with_titles( 'post' );
		$pages = $this->get_like_dislike_counts_with_titles( 'page' );
		$like_dislike_vote_tracking_enabled = get_option('like_dislike_vote_tracking_enabled') === 'yes';
		$like_dislike_hide_dislike_btn = get_option('like_dislike_hide_dislike_btn') === 'yes';

		?>
		<div class="container-fluid module-container">
			<div class="row">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="likedislikewpTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<a class="nav-link active" id="stats-tab" data-bs-toggle="tab" href="#stats" role="tab" aria-controls="stats" aria-selected="true"><?php echo esc_html_e( 'Stats', 'like-dislike-for-wp-pro' );?></a>
					</li>
					<li class="nav-item" role="presentation">
						<a class="nav-link" id="settings-tab" data-bs-toggle="tab" href="#settings" role="tab" aria-controls="settings" aria-selected="true"><?php echo esc_html_e( 'Settings', 'like-dislike-for-wp-pro' );?></a>
					</li>
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="likedislikewpTabsContent">
					<div class="tab-pane fade show active" id="stats" role="tabpanel" aria-labelledby="stats-tab">
						<!-- Your modules content here -->
						<div class="row d-flex align-items-stretch mb-5">

							<div class="col-md-4 h-100">
					            <div class="card">
					                <div class="card-body">

					                	<div class="row">
					                        <div class="col-4 text-center">
					                            <span class="dashicons dashicons-chart-area wpuk_icon"></span>
					                        </div>
					                        <div class="col-8 text-center">
					                            <div class="wpuk_digits"><?php echo $this->get_like_dislike_total_counts();?></div>
					                            <div><?php echo __('Total','like-dislike-for-wp');?></div>
					                        </div>
					                    </div>

					                </div>
					            </div>
					        </div>
					        <div class="col-md-4">
					            <div class="card">
					                <div class="card-body">
					                    <div class="row">
					                        <div class="col-4 text-center">
					                            <span class="dashicons dashicons-clock wpuk_icon"></span>
					                        </div>
					                        <div class="col-8 text-center">
					                            <div class="wpuk_digits"><?php echo $this->get_like_dislike_total_counts_today();?></div>
					                            <div><?php echo __('Today','like-dislike-for-wp');?></div>
					                        </div>
					                    </div>
					                </div>
					            </div>
					        </div>
					        <div class="col-md-4">
					            <div class="card">
					                <div class="card-body">
					                    <div class="row">
					                        <div class="col-4 text-center">
					                            <span class="dashicons dashicons-chart-bar wpuk_icon"></span>
					                        </div>
					                        <div class="col-8 text-center">
					                            <div class="wpuk_digits"><?php echo $this->get_like_dislike_total_counts_yesterday();?></div>
					                            <div><?php echo __('Yesterday','like-dislike-for-wp');?></div>
					                        </div>
					                    </div>
					                </div>
					            </div>
					        </div>
					    </div>
					    
					    <div class="row">
					    	<div class="col-md-6">
					    		<div class="likedislikewp-pages-stats">
									<div class="likedislikewp-heading">
										<?php echo esc_html_e( 'Top Likers', 'like-dislike-for-wp-pro' );?>
									</div>
									<div class="likedislikewp-stats container mt-3">
										<table class="table">
											<tbody>
											<?php 
												$likers = $this->get_top_likes_users_with_names();
												if( !empty($likers)) {
													$position = 1;
													foreach( $likers as $liker ){
														?>
														<tr>
														    <td style="width: 33%;"><?php echo $position++; ?></td>
														    <td style="width: 50%;"><?php echo esc_html($liker->user_name); ?></td>
														    <td style="width: 17%;"><?php echo esc_html($liker->total_likes); ?><span class="dashicons dashicons-heart"></span></td>
														</tr>
														<?php
													}
												} else {
													echo '<tr><td colspan="3" class="text-center p-3">'.__('No data found','like-dislike-for-wp').'</td></tr>';
												}
											?>
											</tbody>
										</table>
									</div>
								</div>	
					    	</div>
							<div class="col-md-6">
								<div class="likedislikewp-pages-stats">
									<div class="likedislikewp-heading">
										<?php echo esc_html_e( 'Active Posts / Pages', 'like-dislike-for-wp-pro' );?>
									</div>
									<div class="likedislikewp-stats container mt-3">
										<table class="table">
											<tbody>
											<?php 
												$posts_l = $this->get_most_liked_posts();
												if( !empty($posts_l)) {
													foreach( $posts_l as $post_l ){
														?>
														<tr>
														    <td style="width: 33%;"><?php echo esc_html($post_l->post_id); ?></td>
														    <td style="width: 50%;"><?php echo esc_html($post_l->post_title); ?></td>
														    <td style="width: 17%;"><?php echo esc_html($post_l->total_likes); ?><span class="dashicons dashicons-heart"></td>
														</tr>

														<?php
													}
												} else {
													echo '<tr><td colspan="3" class="text-center p-3">'.__('No data found','like-dislike-for-wp').'</td></tr>';
												}
											?>
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>

					</div>

					<div class="tab-pane fade likedislike-settings" id="settings" role="tabpanel" aria-labelledby="settings-tab">
						<!-- Your modules content here -->
						<div class="row">
							<div class="likedislikewp-posts-stats">
								<div class="likedislikewp-heading">
									<?php echo esc_html_e( 'Settings', 'like-dislike-for-wp-pro' );?>
								</div>
								<div class="container-fluid module-container">
									<div class="row">
										<div class="form-check">
											<input class="form-check-input" type="checkbox" value="" <?php checked($like_dislike_vote_tracking_enabled); ?> id="like_dislike_for_wp_enable">
											<label class="form-check-label" for="like_dislike_for_wp_enable">
												<?php esc_html_e('Enable Tracking', 'like-dislike-for-wp-pro'); ?>
											</label>
											<?php esc_html_e('Enable Tracking', 'like-dislike-for-wp-pro'); ?>
										</div>

										<div class="form-check">
											<input class="form-check-input" type="checkbox" value="" <?php checked($like_dislike_hide_dislike_btn); ?> id="like_dislike_for_wp_show_dislike_button">
											<label class="form-check-label" for="like_dislike_for_wp_show_dislike_button">
												<?php esc_html_e('Show Dislike Button', 'like-dislike-for-wp-pro'); ?>
											</label>
											<?php esc_html_e('Show Dislike Button', 'like-dislike-for-wp-pro'); ?>
										</div>
									</div>
								</div>
								
							</div>

						</div>
					</div>

				</div>

				<!-- Duplicate the above block for each module you have -->
			</div>
		</div>
		<?php
	}


	public function like_dislike_for_wp_ajax_handler() {
	    	
	    // Verify the nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'like_dislike_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'like-dislike-for-wp-pro' ) ), 401 );
		}

		// Ensure global access to $wpdb and validate/sanitize incoming data.
		global $wpdb;

		$post_id = isset($_POST['post_id']) ? intval(sanitize_text_field($_POST['post_id'])) : 0;
		$status = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
		$ip = $this->like_dislike_for_wp_generate_guest_user_id();  // Assuming this is a secure method to generate or retrieve a unique IP identifier.
		$date_time = current_time('mysql', 1);
		$table_name = $wpdb->prefix . 'likedislikewp';
		$current_user_id = get_current_user_id() ? intval(get_current_user_id()) : 0;

		// Cache key unique for each post, user, and IP combination.
		$cache_key = "likedislike_pro_vote_{$post_id}_{$current_user_id}_{$ip}";
		$existing = wp_cache_get($cache_key, 'likedislike_pro_votes');

		if (false === $existing) {
		    // Justified use of direct database query due to non-standard table and complex query requirements.
		    $existing = $wpdb->get_row($wpdb->prepare(
			    "SELECT * FROM `{$table_name}` WHERE post_id = %d AND (ip = %s OR user_id = %d) LIMIT 1",
			    $post_id, $ip, $current_user_id
			));
		    wp_cache_set($cache_key, $existing, 'likedislike_pro_votes', 3600); // Cache for 1 hour.
		}

		if ($existing) {
		    if ($existing->status !== $status) {
		        $updated = $wpdb->update(
		            $table_name,
		            array('status' => $status, 'date_time' => $date_time),
		            array('id' => $existing->id),
		            array('%s', '%s'),
		            array('%d')
		        );

		        if ($updated !== false) {
		            wp_cache_delete($cache_key, 'likedislike_pro_votes'); // Clear cache after update.
		            wp_send_json_success(array('message' => 'Vote successfully updated.'));
		        } else {
		            wp_send_json_error(array('message' => 'Failed to update vote.'));
		        }
		    } else {
		        wp_send_json_error(array('message' => 'You have already recorded this vote.'));
		    }
		} else {
		    $data = array(
		        'post_id' => $post_id,
		        'date_time' => $date_time,
		        'ip' => $ip,
		        'user_id' => $current_user_id,
		        'status' => $status
		    );
		    $format = array('%d', '%s', '%s', '%d', '%s');

		    $success = $wpdb->insert($table_name, $data, $format);

		    if ($success !== false) {
		        wp_cache_set($cache_key, $data, 'likedislike_pro_votes', 3600); // Cache the new record.
		        wp_send_json_success(array('message' => 'Vote successfully recorded.'));
		    } else {
		        wp_send_json_error(array('message' => 'Failed to record vote.', 'db_error' => $wpdb->last_error));
		    }
		}



	
		wp_die(); // Make sure to end script execution

	}

	/**
	 * Retrieves the client's IP address using WordPress sanitization functions.
	 *
	 * Checks for the IP address forwarded by a proxy in 'HTTP_X_FORWARDED_FOR'
	 * and falls back to 'REMOTE_ADDR' if not available. The first IP from
	 * 'HTTP_X_FORWARDED_FOR' is used if available since it is the original requester.
	 *
	 * @return string IP address of the client.
	 */
	public function get_client_ip() {
	    // Check if the server has the HTTP_X_FORWARDED_FOR header
	    if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
	        // Explode the forwarded IPs and trim whitespace
	        $ips = explode( ',', sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
	        $ips = array_map('trim', $ips);
	        $ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP ); // Take the first forwarded IP
	    } else {
	        // Fall back to the remote IP
	        $ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP ) ?: '0.0.0.0';
	    }

	    // Sanitize the IP address to ensure it's a valid IP format using WordPress sanitization
	    if ( ! filter_var($ip, FILTER_VALIDATE_IP) ) {
	        $ip = '0.0.0.0'; // Default to '0.0.0.0' if validation fails
	    }

	    return $ip;
	}

	// Usage

	public function like_dislike_for_wp_generate_guest_user_id() {
	
		$ip_address = $this->get_client_ip();

		$salt = '53f0699a1c0c73ff787953ae6aaf5a4568fedb01a26d72345531a47aa61741bef2da4cb7b06de62a7f372c01cf361023d9b8f484d9be96fcdfcdd58ab0c94777'; // Keep this constant
		// Generate the hash
		$unique_id = hash('sha256', $salt . $ip_address);
		return $unique_id;
	}

	public function get_like_dislike_counts_with_titles( $ptype ) {
		global $wpdb;

		$ldw_table = $wpdb->prefix . 'likedislikewp';  // Custom table name, presumed to be safe and hardcoded.
		$posts_table = $wpdb->posts;                  // WordPress defined table name, considered safe.
		$cache_key = 'likedislike_pro_cache_key_' . $ptype;    // Construct a unique cache key with a variable component if needed.

		// Attempt to retrieve the cached query results.
		$results = wp_cache_get($cache_key, 'likedislike_pro_cache_group');

		if ($results === false) {
		    // Cache miss, so run the query.
		    $results = $wpdb->get_results($wpdb->prepare("
		        SELECT ldw.post_id, p.post_title, ldw.date_time, ldw.status, COUNT(ldw.post_id) AS total_votes,
		        SUM(CASE WHEN ldw.status = 'like' THEN 1 ELSE 0 END) AS like_count,
		        SUM(CASE WHEN ldw.status = 'dislike' THEN 1 ELSE 0 END) AS dislike_count
		        FROM `{$ldw_table}` AS ldw
		        JOIN `{$posts_table}` AS p ON ldw.post_id = p.ID
		        WHERE p.post_status = 'publish' AND p.post_type = %s
		        GROUP BY ldw.post_id
		    ", $ptype));

		    // Save the results in the cache for future use.
		    wp_cache_set($cache_key, $results, 'likedislike_pro_cache_group');
		}

		if (!empty($results)) {
		    return $results;
		} else {
		    // Handle the case where no results are found, or error handling as needed.
		    return []; // or null, or an appropriate response.
		}

	}
	
	
	public function save_vote_tracking_setting_handler() {
		// Verify nonce for security
		check_ajax_referer( 'like_dislike_for_wp_nonce', 'nonce' );

		// Sanitize and validate input
		$enabled = isset( $_POST['enabled'] ) && in_array( $_POST['enabled'], array( 'yes', 'no' ) ) ? sanitize_key( $_POST['enabled'] ) : 'no';

		// Update the option in the database
		update_option( 'like_dislike_vote_tracking_enabled', $enabled );

		// Return success response
		wp_send_json_success();

	}
	
	public function save_dislike_btn_setting_handler(){
		// Verify nonce for security
		check_ajax_referer( 'like_dislike_for_wp_nonce', 'nonce' );

		// Sanitize and validate input
		$enabled = isset( $_POST['enabled'] ) && in_array( $_POST['enabled'], array( 'yes', 'no' ) ) ? sanitize_key( $_POST['enabled'] ) : 'no';

		// Update the option in the database
		update_option( 'like_dislike_hide_dislike_btn', $enabled );

		// Return success response
		wp_send_json_success();
	}

	public function get_like_dislike_total_counts() {
		global $wpdb;

		$ldw_table = $wpdb->prefix . 'likedislikewp';  // Custom table name, presumed to be safe and hardcoded.
		$posts_table = $wpdb->posts;                  // WordPress defined table name, considered safe.
		$cache_key = 'likedislike_pro_cache_key_total';    // Construct a unique cache key with a variable component if needed.

		// Attempt to retrieve the cached query results.
		$results = wp_cache_get($cache_key, 'likedislike_pro_cache_group');

		if ($results === false) {
		    // Cache miss, so run the query.
		    $results = $wpdb->get_var($wpdb->prepare("
		        SELECT COUNT(*) FROM `{$ldw_table}`
		    ", $ptype));

		    // Save the results in the cache for future use.
		    wp_cache_set($cache_key, $results, 'likedislike_pro_cache_group');
		}

		if (!empty($results)) {
		    return $results;
		} else {
		    // Handle the case where no results are found, or error handling as needed.
		    return 0; // or null, or an appropriate response.
		}

	}

	public function get_like_dislike_total_counts_today() {
		global $wpdb;

		$ldw_table = $wpdb->prefix . 'likedislikewp';  // Custom table name, presumed to be safe and hardcoded.
		$posts_table = $wpdb->posts;                  // WordPress defined table name, considered safe.
		$cache_key = 'likedislike_pro_cache_key_today_total';    // Construct a unique cache key with a variable component if needed.

		// Attempt to retrieve the cached query results.
		$results = wp_cache_get($cache_key, 'likedislike_pro_cache_group');
		if ($results === false) {
		    // Cache miss, so run the query.
		    $results = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$ldw_table}` WHERE  date_time >= CURDATE() AND date_time < CURDATE() + INTERVAL 1 DAY;"));
		    
		    // Save the results in the cache for future use.
		    wp_cache_set($cache_key, $results, 'likedislike_pro_cache_group');
		}

		if (!empty($results)) {
		    return $results;
		} else {
		    // Handle the case where no results are found, or error handling as needed.
		    return 0; // or null, or an appropriate response.
		}

	}

	public function get_like_dislike_total_counts_yesterday() {
		global $wpdb;

		$ldw_table = $wpdb->prefix . 'likedislikewp';  // Custom table name, presumed to be safe and hardcoded.
		$posts_table = $wpdb->posts;                  // WordPress defined table name, considered safe.
		$cache_key = 'likedislike_pro_cache_key_yesterday_total';    // Construct a unique cache key with a variable component if needed.

		// Attempt to retrieve the cached query results.
		$results = wp_cache_get($cache_key, 'likedislike_pro_cache_group');
		if ($results === false) {
		    // Cache miss, so run the query.
		    $results = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$ldw_table}` WHERE date_time >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND date_time < CURDATE()"));
		    
		    // Save the results in the cache for future use.
		    wp_cache_set($cache_key, $results, 'likedislike_pro_cache_group');
		}

		if (!empty($results)) {
		    return $results;
		} else {
		    // Handle the case where no results are found, or error handling as needed.
		    return 0; // or null, or an appropriate response.
		}

	}


	public function get_top_likes_users_with_names() {
	    global $wpdb;

	    $ldw_table = $wpdb->prefix . 'likedislikewp'; // Adjust the table name as needed
	    $users_table = $wpdb->users;
	    $query = "
	        SELECT ldw.user_id, COUNT(ldw.user_id) AS total_likes, u.user_nicename AS user_name
	        FROM {$ldw_table} ldw
	        LEFT JOIN {$users_table} u ON ldw.user_id = u.ID
	        WHERE ldw.status = 'like'
	        GROUP BY ldw.user_id
	        ORDER BY total_likes DESC;
	    ";

	    $results = $wpdb->get_results($query);

	    if (!empty($results)) {
	        return $results;
	    } else {
	        return array(); // Return an empty array if no results are found
	    }
	}


	public function get_most_liked_posts() {
	    global $wpdb;

	    $ldw_table = $wpdb->prefix . 'likedislikewp'; // Adjust the table name as needed
	    $posts_table = $wpdb->posts;
	    $query = "
	        SELECT ldw.post_id, COUNT(ldw.post_id) AS total_likes, p.post_title
	        FROM {$ldw_table} ldw
	        LEFT JOIN {$posts_table} p ON ldw.post_id = p.ID
	        WHERE ldw.status = 'like'
	        GROUP BY ldw.post_id
	        ORDER BY total_likes DESC;
	    ";

	    $results = $wpdb->get_results($query);

	    if (!empty($results)) {
	        return $results;
	    } else {
	        return array(); // Return an empty array if no results are found
	    }
	}


	
}
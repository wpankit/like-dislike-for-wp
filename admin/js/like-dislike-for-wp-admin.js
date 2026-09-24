(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	const toastConf = {
		timeOut: 1000, // Adjust display time as needed (in milliseconds).
		positionClass: 'toast-bottom-right', // Adjust position as needed.
		progressBar: true, // Show a progress bar.
		closeButton: true,
		preventDuplicates: true,
		iconClasses: {
			success: "toast-success",
	        warning: "toast-warning" // Specify a single CSS class for warning messages.
	    },
	};

	$(document).on('change', '#like_dislike_for_wp_enable', function() {

        var isEnabled = $(this).is(':checked') ? 'yes' : 'no';

        $.ajax({
            url: like_dislike_for_wp_ajax.url, // 'ajaxurl' is automatically defined in the admin area by WordPress
            type: 'POST',
            data: {
                action: 'save_vote_tracking_setting', // The action hook name for wp_ajax_ and wp_ajax_nopriv_
                enabled: isEnabled,
                nonce: like_dislike_for_wp_ajax.nonce // Assuming you've localized this script and included a nonce for security
            },
            success: function(response) {
                if(response.success) {
                	toastr.success( 'Settings saved successfully.', '', toastConf );
                } else {
                	toastr.error( 'Failed to save settings.', '', toastConf );
                }
            }
        });
    });

	$(document).on('change', '#like_dislike_for_wp_show_dislike_button', function() {
        var isEnabled = $(this).is(':checked') ? 'yes' : 'no';
        $.ajax({
            url: like_dislike_for_wp_ajax.url, // 'ajaxurl' is automatically defined in the admin area by WordPress
            type: 'POST',
            data: {
                action: 'save_dislike_btn_setting', // The action hook name for wp_ajax_ and wp_ajax_nopriv_
                enabled: isEnabled,
                nonce: like_dislike_for_wp_ajax.nonce // Assuming you've localized this script and included a nonce for security
            },
            success: function(response) {
                if(response.success) {
                	toastr.success( 'Settings saved successfully.', '', toastConf );
                } else {
                    toastr.error( 'Failed to save settings.', '', toastConf );
                }
            }
        });
    });
	
})( jQuery );

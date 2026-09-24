(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
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

    $(document).on('click', '.like-dislike-for-wp-button', function(e) {
        e.preventDefault();

        var postID = $(this).data('post-id');
        var action = $(this).data('action');

        const toastConf = {
			timeOut: 1000, // Adjust display time as needed (in milliseconds).
			positionClass: 'toast-top-right', // Adjust position as needed.
			progressBar: true, // Show a progress bar.
			closeButton: true,
			preventDuplicates: true,
			iconClasses: {
				success: "toast-success",
		        warning: "toast-warning" // Specify a single CSS class for warning messages.
		    },
		};

        $.ajax({
            url: like_dislike_for_wp_ajax.url,
            type: 'POST',
            data: {
                action: 'like_dislike_action', // This should match the action hook suffix in PHP
                nonce: like_dislike_for_wp_ajax.nonce, // Passed from wp_localize_script
                post_id: postID,
                action_type: action // Use a different key to avoid overriding the action key in the data object
            },
            success: function(response) {
            	toastr.success( response.data.message, '', toastConf );
            },
            error: function(xhr, status, error) {
            	toastr.error( 'AJAX Error:', '', toastConf );
            }
        });

    });


})( jQuery );

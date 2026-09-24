=== Like Dislike For WP ===
Contributors: ankitmaru
Tags: like, dislike, voting, post, button
Requires at least: 4.0
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add like and dislike buttons to your WordPress posts/pages with vote tracking and detailed stats.

== Description ==

WP Like Dislike is a simple yet powerful plugin that allows users to express their opinion by liking or disliking WordPress posts or pages. With just a click of a button, visitors can indicate their preference for content, providing valuable feedback to site owners.

Features include:

- Easily add like and dislike buttons to your WordPress posts or pages.
- Enable or disable vote tracking from the plugin settings page.
- Show or hide the dislike button independently via settings.
- Track votes from both registered users and guests (via IP-based identification).
- View a stats dashboard in the admin area showing total votes, today's votes, and yesterday's votes.
- See a Top Likers leaderboard showing the most active voting users.
- Browse the most liked posts and pages from the Active Posts/Pages widget.
- View detailed per-post and per-page like/dislike counts in dedicated Posts Stats and Pages Stats pages.
- Protect against abuse with nonce verification, caching, and duplicate vote prevention.

== Installation ==

1. Upload the `like-dislike-for-wp` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to WP Like Dislike > Settings in the WordPress admin menu.
4. Enable vote tracking to start displaying like/dislike buttons on your posts and pages.

== Frequently Asked Questions ==

= How do I add like and dislike buttons to my posts/pages? =

After activating the plugin, go to WP Like Dislike > Settings in the admin menu and enable the "Enable Tracking" option. Once enabled, the like and dislike buttons will automatically appear on your posts and pages.

= Why are the buttons not showing after activation? =

The buttons only appear when vote tracking is enabled. Go to WP Like Dislike > Settings and turn on the "Enable Tracking" toggle.

= Can I hide the dislike button? =

Yes. Go to WP Like Dislike > Settings and toggle the "Show Dislike Button" option off to hide the dislike button and show only the like button.

= Can I customize the appearance of the buttons? =

Button styles can be adjusted via your theme's custom CSS. There is no built-in label or style editor in the current version.

= Is there a shortcode available for manual placement of buttons? =

Currently, there is no shortcode available. The buttons are automatically added to posts and pages when vote tracking is enabled.

= Where can I see vote counts for my posts and pages? =

Go to WP Like Dislike > Posts Stats or WP Like Dislike > Pages Stats in the admin menu to view per-post and per-page like and dislike counts.

= Does the plugin track votes from non-logged-in visitors? =

Yes. Guest votes are tracked using a hashed IP address to prevent duplicate voting while preserving visitor privacy.

== Screenshots ==

1. Plugin settings page with Enable Tracking and Show Dislike Button options.
2. Stats dashboard showing total, today, and yesterday vote counts.
3. Top Likers leaderboard in the stats dashboard.
4. Active Posts / Pages widget showing most liked content.
5. Posts Stats page with per-post like and dislike counts.
6. Like and dislike buttons displayed on a post.

== Changelog ==

= 2.1.0 =
* Added Today and Yesterday vote count widgets to the stats dashboard.
* Added Top Likers leaderboard to the stats dashboard.
* Added Active Posts / Pages widget showing most liked content.
* Added dedicated Posts Stats and Pages Stats submenu pages.
* Improved admin UI with tabbed Settings and Stats layout.

= 2.0.2 =
* Bug fixes and stability improvements.

= 2.0.1 =
* Minor fixes and performance improvements.

= 2.0.0 =
* Introduced stats dashboard with total vote counts.
* Added "Show Dislike Button" toggle in settings.
* Added caching layer using wp_cache for improved performance.
* Added REST API endpoint for vote submission.

= 1.3.3 =
* Security improvements and bug fixes.

= 1.3.2 =
* Improved nonce verification for vote requests.
* Bug fixes.

= 1.3.1 =
* Improvements and bug fixes.

= 1.1.0 =
* Added guest vote tracking via hashed IP address.
* Added duplicate vote prevention for registered users and guests.

= 1.0.1 =
* Minor bug fixes.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 2.1.0 =
Adds a full stats dashboard with Top Likers, Active Posts/Pages, and Today/Yesterday vote counts. Update recommended for all users.

= 2.0.0 =
Major update introducing the stats dashboard, dislike button toggle, REST API support, and caching. Update recommended.

= 1.3.2 =
Security improvements including better nonce verification. Update recommended.

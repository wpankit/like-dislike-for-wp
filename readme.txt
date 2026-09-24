=== Like Dislike – Like Buttons, Helpful Votes & Most Liked Posts ===
Contributors: ankitmaru
Donate link: https://wpankit.com/
Tags: like button, like, dislike, voting, feedback
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 3.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Like and dislike buttons with live counts for posts, pages, products and comments, a "Was this helpful?" mode, most liked posts, and stats.

== Description ==

**Like Dislike** adds like and dislike buttons to your posts, pages, products and comments. Readers vote with one click, see the counts change straight away, and can change their mind. You see what they enjoyed, what fell flat, and why.

Switch it on and the buttons appear after your posts. Everything else is optional.

= Buttons that just work =

* **Live counts** that update the moment someone votes.
* **One vote per person.** Logged-in users get one vote per post. Visitors get one per browser, or one per IP address if you prefer.
* **Change of mind:** click the other button to switch, or the same one again to take the vote back.
* **Works with page caching.** Votes and counts keep working on cached pages.
* **Light and fast.** A tiny script without jQuery, loaded only where the buttons are.
* **Accessible:** real buttons that work with the keyboard and screen readers.

= Make them yours =

* Five icon sets: thumbs, hearts, arrows, faces, or text only.
* Pill, outline or minimal buttons, in three sizes, aligned left, centre or right.
* Your own colours and labels, like "Yes" and "No" or "Upvote" and "Downvote".
* Show counts, hide them, or show them only after someone votes.
* Before the content, after it, or both.
* On posts, pages, WooCommerce products and any public custom post type.
* A like button on its own, if you don't want dislikes.
* Presets to start from: Like and dislike, Was this helpful?, Hearts, and Up and down votes.
* A live preview in Settings, so you see every change before you save.

= "Was this helpful?" =

Perfect for documentation, help centres and blogs. Ask a question above the buttons, label them "Yes" and "No", thank readers after they vote, and ask "What could be better?" when someone says no. Their answers appear in Stats, next to the post they are about.

= Likes on comments =

Readers can like or dislike each comment. The buttons are small and quiet, so the conversation stays the focus.

= Most liked posts =

Show your most liked posts anywhere with the **Most Liked Posts** block or the `[most_liked_posts]` shortcode: of all time, or from the last 7 days, 30 days or 12 months.

= Stats =

* Likes, dislikes and total votes for the last 7, 30 or 90 days, 12 months, or all time.
* A chart of votes per day.
* Your most liked and most disliked posts.
* What readers said could be better.
* A sortable **Likes** column in your post lists.
* A box on each post with its counts, to hide the buttons on that post or reset its votes.

= Place them yourself =

Add the **Like Dislike Buttons** block anywhere in a post or a block theme template, or use the `[like_dislike]` shortcode. Turn off automatic placement to show the buttons only where you put them.

= Private by design =

* IP addresses are never stored. Only a keyed one-way hash is kept, to stop repeat votes.
* Visitors get a random ID in a cookie, only after they vote.
* Personal data export and erase tools for your users' votes, in Tools.
* Suggested text for your privacy policy.
* No requests to outside services, no tracking.

= For developers =

* Counts in the REST API, as `like_dislike` on posts of the chosen types.
* Actions: `ldfw_voted` after a vote, `ldfw_feedback` after feedback.
* Filters: `ldfw_buttons_html`, `ldfw_visitor_ip` (for sites behind a proxy or CDN), `ldfw_votes_per_ip`.

= Free to use =

Everything in Like Dislike is free, and what's free stays free. There is no account to create and no tracking.

= More from the makers of Like Dislike =

* [NoteFlow](https://wordpress.org/plugins/noteflow/): notes, checklists and team collaboration in your WordPress admin.
* [Page Visit Counter](https://pagevisitcounter.com/): privacy-first analytics inside WordPress.
* [PushRow for Google Sheets](https://getpushrow.com/): keep Google Sheets in sync with WordPress.

== Installation ==

1. In your admin, go to Plugins → Add New Plugin and search for "Like Dislike".
2. Click Install Now, then Activate.
3. The buttons now appear after your posts. Go to **Like Dislike → Settings** to choose where they appear and how they look.

== Frequently Asked Questions ==

= Can people vote more than once? =

No. Logged-in users get one vote per post or comment. Visitors get one vote per browser by default. For a stricter limit, choose "One vote per IP address" in Settings; that counts everyone on the same network, like an office, as one voter.

= Does it work with caching plugins? =

Yes. Voting works on cached pages, and each visitor sees their own vote. Counts on a cached page are as fresh as the cache and update the moment someone votes.

= How do I show the buttons only on some posts? =

Turn off "Add the buttons automatically" in Settings and add the Like Dislike Buttons block, or the `[like_dislike]` shortcode, where you want them. To hide them on a single post, tick "Hide the buttons on this post" in the Like Dislike box when editing it.

= Can I let only logged-in users vote? =

Yes. Choose "Logged-in users only" under Voting. Visitors still see the buttons and counts, and get a link to log in when they click.

= Does it work with WooCommerce? =

Yes. Choose Products under "Show them on", and the buttons appear with the product description.

= How do I show the most liked posts? =

Add the Most Liked Posts block, or use `[most_liked_posts number="5" post_type="post" period="month"]`. The period can be all, year, month or week.

= My site is behind Cloudflare or a proxy. Does the IP limit still work? =

The per-browser limit works everywhere. For the per-IP limit, use the `ldfw_visitor_ip` filter to pass the visitor's real IP address, from a header your proxy sets.

= I used version 2.x. What happens when I update? =

All votes are kept, and the buttons stay switched on or off the way they were. The counts for every post are worked out once during the update. The new buttons, settings and Stats screen replace the old ones.

= What happens when I delete the plugin? =

Votes and settings are kept, in case you install it again. To remove everything, turn on "Delete all votes and settings when the plugin is deleted" in Settings before deleting the plugin.

== Screenshots ==

1. Like and dislike buttons with live counts after a post.
2. Settings, with a live preview of your buttons.
3. "Was this helpful?" with a question for readers who say no.
4. Stats: votes per day, most liked and most disliked posts, and feedback.
5. The sortable Likes column in the post list.
6. Five icon sets and three button styles.
7. Likes on comments.
8. The Most Liked Posts block.

== Changelog ==

= 3.0.0 - 2026-09-25 =

A complete rebuild.

* New: live counts on the buttons, and a pressed state for your own vote.
* New: switch between like and dislike, or click again to take a vote back.
* New: five icon sets, three button styles and sizes, your own colours and labels, and presets.
* New: a "Was this helpful?" mode, with an optional "What could be better?" question after a dislike.
* New: likes and dislikes on comments.
* New: the Like Dislike Buttons and Most Liked Posts blocks, and the `[like_dislike]` and `[most_liked_posts]` shortcodes.
* New: a Stats screen with a chart, most liked and most disliked posts, and feedback.
* New: a sortable Likes column, and a box on each post to hide the buttons or reset the votes.
* New: choose the post types, the position, and who can vote.
* New: personal data export and erase, and suggested privacy policy text.
* Fixed: visitors could only ever add one vote per post between them, and people sharing an IP address overwrote each other's votes.
* Fixed: voting stopped working on cached pages.
* Fixed: the buttons were added to feeds, excerpts and archive pages.
* Changed: the buttons no longer load jQuery or other libraries on your site, and the admin no longer loads Bootstrap.
* Changed: IP addresses are hashed with your site's secret key.
* Removed: the promotional notice in the admin.

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

= 3.0.0 =
A complete rebuild: live counts, one vote per person, styles, "Was this helpful?", likes on comments, most liked posts and a Stats screen. Your votes and settings are kept.

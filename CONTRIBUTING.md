# Contributing to Like Dislike

Thanks for helping improve Like Dislike. This guide explains how to report problems, suggest features and send code.

## Questions and support

Please ask on the [WordPress.org support forum](https://wordpress.org/support/plugin/like-dislike-for-wp/). GitHub issues are for bugs and feature ideas.

## Reporting a bug

Search the [open issues](https://github.com/wpankit/like-dislike-for-wp/issues) first. If the bug is new, open a [bug report](https://github.com/wpankit/like-dislike-for-wp/issues/new?template=bug_report.yml) with where it happens, the steps to reproduce it, whether you voted as a logged-in user or as a visitor, and your plugin, WordPress and PHP versions.

Found a security issue? Please don't open an issue; follow [SECURITY.md](SECURITY.md) instead.

## Suggesting a feature

Open a [feature request](https://github.com/wpankit/like-dislike-for-wp/issues/new?template=feature_request.yml) and describe the problem it would solve.

## Translating

Translations are managed on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/like-dislike-for-wp/).

## Contributing code

### Set up

1. Run WordPress locally, for example with [Local](https://localwp.com/), [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) or [DDEV](https://ddev.com/).
2. Fork this repository and clone your fork into `wp-content/plugins/like-dislike-for-wp`.
3. Run `composer install` to get the coding standards tools.
4. Activate the plugin. The buttons appear after your posts; the options are under **Like Dislike → Settings** and the results under **Like Dislike → Stats**.

There is no build step: the JavaScript and CSS in `assets/` are loaded as they are.

### How the code is organised

- `like-dislike-for-wp.php` loads `includes/class-ldfw-plugin.php`, which wires up everything else.
- `includes/` has one class per job: `LDFW_Votes` stores votes, works out who is voting and keeps the counts; `LDFW_REST` is the REST API (`like-dislike-for-wp/v1`) for voting, feedback and counts; `LDFW_Render` outputs the buttons, their placement, the shortcodes and the blocks. The others cover settings, stats, the admin screens, privacy tools, and upgrades from 2.x.
- `assets/js/buttons.js` runs the buttons on the site, `admin.js` the live preview in Settings, and `blocks.js` the two blocks.

### Make your change

- Create a branch from `main`. `main` is protected, so every change arrives through a pull request.
- Keep each pull request to one topic.
- Follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/). `composer lint` must pass; `composer format` fixes most issues.
- The code must keep working on PHP 7.4 and WordPress 6.0.
- Prefix new functions, hooks and options with `ldfw_` and new classes with `LDFW_`, and use the `like-dislike-for-wp` text domain. Keep existing names and the stored data (the votes table, the counts in post and comment meta, and the `ldfw_settings` option): sites and other code rely on them.
- Voting must keep working on cached pages: a visitor's own votes are applied in the browser after the page loads, so nothing specific to one visitor may be printed into the HTML.
- Keep it private: never store IP addresses, only the keyed hash, and make no requests to outside services.
- Escape output, sanitize input, and check capabilities and nonces.

### Test it

Before opening the pull request, with `WP_DEBUG` on, try the flows your change touches, for example:

- voting as a logged-in user and as a visitor: like, dislike, switch and take a vote back;
- the same with a page cache turned on;
- "Was this helpful?" with feedback, likes on comments, and the blocks and shortcodes;
- Stats, the Likes column and the box on each post;
- the personal data export and erase tools, if your change touches votes.

### Open the pull request

Fill in the template: what changed, why, and how you tested it. The checks (Coding Standards, PHP Lint and Plugin Check) must pass before the pull request is merged.

## Code of Conduct

This project follows the [Contributor Covenant](CODE_OF_CONDUCT.md). By taking part, you agree to follow it.

## License

By contributing, you agree that your contributions are licensed under the [GPL-2.0-or-later](LICENSE) license.

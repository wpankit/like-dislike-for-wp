/**
 * Builds the wordpress.org listing assets for Like Dislike.
 *
 *   node tools/wporg-assets/build-assets.mjs                         icon PNGs and banners
 *   node tools/wporg-assets/build-assets.mjs --screenshots           also the screenshots
 *   node tools/wporg-assets/build-assets.mjs --screenshots --only=stats,settings   just those
 *
 * Everything is written to .wordpress-org/ at the repository root. The icon PNGs come
 * from .wordpress-org/icon.svg and the banners from banner.html next to this script,
 * rendered in headless Chrome at the exact sizes wordpress.org expects.
 *
 * Screenshots come from the real plugin on a local site with the demo content from
 * demo.php (wp eval-file tools/wporg-assets/demo.php): a small coffee blog
 * with votes, comments and feedback, kept as drafts. While the script runs it
 * publishes the demo posts, applies showcase settings and loads a temporary
 * must-use plugin that shows a made-up site name and limits the Posts screen to the
 * demo posts, only for the script's own browser. Afterwards the posts go back to
 * drafts, the site's settings are restored, and the must-use plugin is removed.
 * Votes clicked in screenshots are answered by the script and never saved.
 *
 * Needs Google Chrome, plus puppeteer-core and the Inter and Manrope fonts from the
 * WPAnkit Product theme's QA tools (set LDFW_QA_DIR if they live elsewhere).
 * Screenshots also need WP-CLI and the site: set LDFW_SITE_PATH, LDFW_SITE_URL and
 * LDFW_DB_SOCKET.
 */

import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync, existsSync, statSync, mkdirSync, unlinkSync, readdirSync, rmdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { homedir } from 'node:os';
import { fileURLToPath } from 'node:url';

const HERE = dirname( fileURLToPath( import.meta.url ) );
const PLUGIN = join( HERE, '../..' );
// The listing assets, deployed to the SVN assets/ directory.
const ASSETS = join( PLUGIN, '.wordpress-org' );
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const QA = process.env.LDFW_QA_DIR || join( homedir(), 'Local Sites/pushrow-lp/app/public/wp-content/themes/wpankit-product/tools/qa' );
const SITE_PATH = process.env.LDFW_SITE_PATH || join( homedir(), 'Local Sites/other-plugin/app/public' );
const SITE_URL = process.env.LDFW_SITE_URL || 'http://other-plugin.local';
const DB_SOCKET = process.env.LDFW_DB_SOCKET || join( homedir(), 'Library/Application Support/Local/run/kdrgZVwMM/mysql/mysqld.sock' );
const DEMO_COOKIE = 'ldfw_demo_shot';

for ( const [ what, path ] of [
	[ 'Google Chrome', CHROME ],
	[ 'puppeteer-core from the theme QA tools', join( QA, 'node_modules/puppeteer-core' ) ],
	[ 'Inter and Manrope from the theme QA tools', join( QA, 'node_modules/@fontsource/manrope' ) ],
] ) {
	if ( ! existsSync( path ) ) {
		console.error( `${ what } not found at ${ path }` );
		process.exit( 1 );
	}
}

const puppeteer = createRequire( join( QA, 'package.json' ) )( 'puppeteer-core' );
const sleep = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

// Fonts are inlined: pages loaded with setContent can't read file:// URLs.
const font = ( family, pkg, weight ) =>
	`@font-face{font-family:${ family };font-weight:${ weight };src:url(data:font/woff2;base64,${ readFileSync( join( QA, 'node_modules/@fontsource', pkg, 'files', `${ pkg }-latin-${ weight }-normal.woff2` ) ).toString( 'base64' ) }) format("woff2")}`;
const FONTS = [ font( 'Inter', 'inter', 500 ), font( 'Inter', 'inter', 600 ), font( 'Inter', 'inter', 700 ), font( 'Manrope', 'manrope', 700 ), font( 'Manrope', 'manrope', 800 ) ].join( '\n' );
const BUTTONS_CSS = readFileSync( join( PLUGIN, 'assets/css/buttons.css' ), 'utf8' );

const dataUri = ( file, type ) => `data:${ type };base64,` + readFileSync( file ).toString( 'base64' );
const iconUri = dataUri( join( ASSETS, 'icon.svg' ), 'image/svg+xml' );

function pngSize( file ) {
	const b = readFileSync( file );
	return [ b.readUInt32BE( 16 ), b.readUInt32BE( 20 ) ];
}

function report( name ) {
	const [ w, h ] = pngSize( join( ASSETS, name ) );
	console.log( `${ name }  ${ w }x${ h }  ${ Math.round( statSync( join( ASSETS, name ) ).size / 1024 ) } KB` );
	return [ w, h ];
}

function check( name, width, height ) {
	const [ w, h ] = report( name );
	if ( w !== width || h !== height ) {
		throw new Error( `${ name } is ${ w }x${ h }, expected ${ width }x${ height }` );
	}
}

/* WP-CLI ------------------------------------------------------------------------------- */

const wp = ( ...args ) =>
	execFileSync( 'php', [ '-d', 'error_reporting=0', '-d', 'display_errors=0', '-d', `mysqli.default_socket=${ DB_SOCKET }`, '/usr/local/bin/wp', `--path=${ SITE_PATH }`, ...args ], { encoding: 'utf8' } );

const sessions = [];

function session( login ) {
	const s = JSON.parse(
		wp(
			'eval',
			`$u = get_user_by( "login", "${ login }" ); $exp = time() + 1800; $t = WP_Session_Tokens::get_instance( $u->ID )->create( $exp ); echo json_encode( array( "uid" => $u->ID, "token" => $t, "cookies" => array( array( "name" => AUTH_COOKIE, "value" => wp_generate_auth_cookie( $u->ID, $exp, "auth", $t ) ), array( "name" => LOGGED_IN_COOKIE, "value" => wp_generate_auth_cookie( $u->ID, $exp, "logged_in", $t ) ) ) ) );`
		)
	);
	sessions.push( s );
	return s;
}

/**
 * A browser page, signed in as someone or a visitor, marked as the script's own so
 * the temporary must-use plugin applies to it and nobody else.
 */
async function pageFor( browser, login, width = 1440, height = 900, scale = 1.5 ) {
	const context = await browser.createBrowserContext();
	const page = await context.newPage();
	const domain = new URL( SITE_URL ).hostname;
	const cookies = [ { name: DEMO_COOKIE, value: '1', domain, path: '/' } ];
	if ( login ) {
		cookies.push( ...session( login ).cookies.map( ( c ) => ( { ...c, domain, path: '/' } ) ) );
	}
	await page.setCookie( ...cookies );
	await page.setViewport( { width, height, deviceScaleFactor: scale } );
	await page.emulateMediaFeatures( [ { name: 'prefers-reduced-motion', value: 'reduce' } ] );
	await page.emulateTimezone( 'UTC' );
	return page;
}

/** Answers vote requests without saving them. */
async function fakeVotes( page, counts ) {
	await page.setRequestInterception( true );
	page.on( 'request', ( request ) => {
		const url = request.url();
		if ( request.method() === 'POST' && url.includes( '/like-dislike-for-wp/v1/vote' ) ) {
			const body = JSON.parse( request.postData() || '{}' );
			const base = counts[ body.type + ':' + body.id ] || { likes: 0, dislikes: 0 };
			const likes = base.likes + ( 'like' === body.choice ? 1 : 0 );
			const dislikes = base.dislikes + ( 'dislike' === body.choice ? 1 : 0 );
			request.respond( { status: 200, contentType: 'application/json', body: JSON.stringify( { state: body.choice, likes, dislikes } ) } );
			return;
		}
		request.continue();
	} );
}

/* Screenshots ------------------------------------------------------------------------------ */

/** Screenshot names, in the order the readme lists them: screenshot-1.png is 'post'. */
const SHOTS = [ 'post', 'settings', 'helpful', 'stats', 'column', 'styles', 'comments', 'most-liked' ];
const fileFor = ( key ) => `screenshot-${ SHOTS.indexOf( key ) + 1 }.png`;
const ONLY = ( process.argv.find( ( arg ) => arg.startsWith( '--only=' ) ) || '' ).slice( 7 ).split( ',' ).filter( Boolean );
const wanted = ( key ) => ! ONLY.length || ONLY.includes( key );

/** Rests the pointer where it hovers nothing. */
const park = ( page ) => page.mouse.move( 1439, 899 );

async function shoot( page, key, clip ) {
	if ( ! SHOTS.includes( key ) ) {
		throw new Error( 'Unknown screenshot ' + key );
	}
	if ( ! wanted( key ) ) {
		return;
	}
	await park( page );
	await sleep( 350 );
	await page.screenshot( { path: join( ASSETS, fileFor( key ) ), ...( clip ? { clip } : {} ) } );
	report( fileFor( key ) );
}

const CLASSIC = {
	enabled: true,
	post_types: [ 'post', 'page' ],
	position: 'after',
	dislike: true,
	counts: 'always',
	icons: 'thumbs',
	style: 'pill',
	size: 'medium',
	align: 'left',
	like_color: '#2563eb',
	dislike_color: '#dc2626',
	like_label: '',
	dislike_label: '',
	prompt: '',
	thanks: '',
	feedback: true,
	who: 'everyone',
	guests: 'browser',
	comments: true,
	columns: true,
	delete_data: false,
};
const HELPFUL = { ...CLASSIC, style: 'outline', counts: 'never', like_label: 'Yes', dislike_label: 'No', prompt: 'Was this article helpful?', thanks: 'Thanks for letting us know!', like_color: '#16a34a' };

const setSettings = ( settings ) => wp( 'eval', `update_option( "ldfw_settings", json_decode( '${ JSON.stringify( settings ) }', true ) );` );

async function screenshots( browser ) {
	const demo = JSON.parse( wp( 'option', 'get', 'ldfw_demo', '--format=json' ) );
	const url = ( id ) => wp( 'eval', `echo get_permalink( ${ id } );` ).trim();
	const counts = JSON.parse(
		wp( 'eval', `$out = array(); foreach ( json_decode( '${ JSON.stringify( demo.posts ) }' ) as $id ) { $c = LDFW_Votes::counts( 'post', $id ); $out[ 'post:' . $id ] = array( 'likes' => $c['like'], 'dislikes' => $c['dislike'] ); foreach ( get_comments( array( 'post_id' => $id ) ) as $cm ) { $c = LDFW_Votes::counts( 'comment', $cm->comment_ID ); $out[ 'comment:' . $cm->comment_ID ] = array( 'likes' => $c['like'], 'dislikes' => $c['dislike'] ); } } echo wp_json_encode( $out );` )
	);

	setSettings( CLASSIC );

	// 1. The buttons after a post, liked.
	// Front-end pages are shot closer, at 1080 pixels wide, and saved at 2160.
	const visitor = await pageFor( browser, null, 1080, 675, 2 );
	await fakeVotes( visitor, counts );
	await visitor.goto( url( demo.lisbon ), { waitUntil: 'networkidle0' } );
	await visitor.evaluate( () => document.querySelector( '.ldfw:not(.ldfw-is-comment)' ).scrollIntoView( { block: 'end' } ) );
	await visitor.evaluate( () => window.scrollBy( 0, 150 ) );
	await visitor.click( '.ldfw:not(.ldfw-is-comment) .ldfw-like' );
	await visitor.evaluate( () => document.activeElement.blur() );
	await sleep( 400 );
	await shoot( visitor, 'post' );

	// 7. Likes on comments.
	await visitor.evaluate( () => ( document.querySelector( '.wp-block-comments' ) || document.querySelector( '.ldfw-is-comment' ) ).scrollIntoView( { block: 'start' } ) );
	await visitor.evaluate( () => window.scrollBy( 0, -24 ) );
	await visitor.click( '.ldfw-is-comment .ldfw-like' );
	await visitor.evaluate( () => document.activeElement.blur() );
	await sleep( 400 );
	await shoot( visitor, 'comments' );

	// 8. The Most Liked Posts block on a page.
	await visitor.goto( url( demo.popular ), { waitUntil: 'networkidle0' } );
	await shoot( visitor, 'most-liked' );
	await visitor.close();

	// 3. "Was this helpful?" after a no.
	setSettings( HELPFUL );
	const reader = await pageFor( browser, null, 1080, 675, 2 );
	await fakeVotes( reader, counts );
	await reader.goto( url( demo.delivery ), { waitUntil: 'networkidle0' } );
	await reader.evaluate( () => document.querySelector( '.ldfw' ).scrollIntoView( { block: 'center' } ) );
	await reader.click( '.ldfw .ldfw-dislike' );
	await reader.waitForSelector( '.ldfw-feedback textarea' );
	await reader.type( '.ldfw-feedback textarea', 'The steps don\'t mention the mobile app.' );
	await reader.evaluate( () => document.querySelector( '.ldfw-feedback' ).scrollIntoView( { block: 'end' } ) );
	await reader.evaluate( () => window.scrollBy( 0, 110 ) );
	await shoot( reader, 'helpful' );
	await reader.close();
	setSettings( CLASSIC );

	// 4. Stats, tall enough for the feedback.
	const priya = await pageFor( browser, 'priya' );
	await priya.goto( SITE_URL + '/wp-admin/admin.php?page=ldfw-stats&period=30', { waitUntil: 'networkidle0' } );
	await priya.evaluate( () => document.querySelectorAll( '.notice' ).forEach( ( n ) => n.remove() ) );
	const statsEnd = await priya.evaluate( () => document.querySelector( '#wpbody-content' ).getBoundingClientRect().bottom );
	await priya.setViewport( { width: 1440, height: Math.min( 1800, Math.ceil( statsEnd ) + 30 ), deviceScaleFactor: 1.5 } );
	await shoot( priya, 'stats' );

	// 5. The Likes column, sorted by likes and cropped below the table.
	await priya.setViewport( { width: 1440, height: 900, deviceScaleFactor: 1.5 } );
	await priya.goto( SITE_URL + '/wp-admin/edit.php?orderby=ldfw_likes&order=desc', { waitUntil: 'networkidle0' } );
	await priya.evaluate( () => document.querySelectorAll( '.subsubsub .count, .notice' ).forEach( ( el ) => el.remove() ) );
	const tableEnd = await priya.evaluate( () => document.querySelector( '.wp-list-table' ).getBoundingClientRect().bottom );
	await priya.setViewport( { width: 1440, height: Math.ceil( tableEnd ) + 6, deviceScaleFactor: 1.5 } );
	await shoot( priya, 'column' );

	// 2. Settings, the whole screen.
	const admin = await pageFor( browser, 'admin' );
	await admin.goto( SITE_URL + '/wp-admin/admin.php?page=ldfw-settings', { waitUntil: 'networkidle0' } );
	await admin.evaluate( () => document.querySelectorAll( '.notice' ).forEach( ( n ) => n.remove() ) );
	await admin.click( '#ldfw-preview .ldfw-like' );
	const settingsEnd = await admin.evaluate( () => document.querySelector( '#wpbody-content' ).getBoundingClientRect().bottom );
	await admin.setViewport( { width: 1440, height: Math.min( 2400, Math.ceil( settingsEnd ) + 30 ), deviceScaleFactor: 1.5 } );
	await shoot( admin, 'settings' );

	// 6. Icon sets and button styles, side by side.
	if ( wanted( 'styles' ) ) {
		const icons = JSON.parse( wp( 'eval', '$o = array(); foreach ( LDFW_Render::icon_sets() as $k => $s ) { $o[ $k ] = array( "like" => LDFW_Render::icon( $k, "like" ), "dislike" => LDFW_Render::icon( $k, "dislike" ) ); } echo wp_json_encode( $o );' ) );
		const button = ( set, choice, label, count, active ) =>
			`<button type="button" class="ldfw-button ldfw-${ choice }${ active ? ' is-active' : '' }">${ icons[ set ][ choice ] }<span class="ldfw-label">${ label }</span>${ null === count ? '' : `<span class="ldfw-count">${ count }</span>` }</button>`;
		const block = ( { set, style, size = 'medium', like, dislike, prompt = '', counts = 'always', voted = false } ) =>
			`<div class="ldfw ldfw-style-${ style } ldfw-size-${ size } ldfw-counts-${ counts } ${ 'none' === set ? 'ldfw-no-icons' : 'ldfw-has-icons' }${ voted ? ' ldfw-has-voted' : '' }" style="--ldfw-like:${ like.color };--ldfw-dislike:${ dislike ? dislike.color : '#dc2626' }">` +
			( prompt ? `<span class="ldfw-prompt">${ prompt }</span>` : '' ) +
			`<span class="ldfw-buttons">${ button( set, 'like', like.label, like.count, like.active ) }${ dislike ? button( set, 'dislike', dislike.label, dislike.count, dislike.active ) : '' }</span></div>`;
		const cards = [
			[ 'Thumbs · Pill', block( { set: 'thumbs', style: 'pill', like: { label: 'Like', count: 128, active: true, color: '#2563eb' }, dislike: { label: 'Dislike', count: 9, color: '#dc2626' }, voted: true } ) ],
			[ 'Hearts · Pill, like only', block( { set: 'hearts', style: 'pill', like: { label: 'Love it', count: 342, active: true, color: '#e11d48' }, voted: true } ) ],
			[ 'Arrows · Minimal', block( { set: 'arrows', style: 'minimal', like: { label: 'Upvote', count: 57, active: true, color: '#ea580c' }, dislike: { label: 'Downvote', count: 3, color: '#4f46e5' }, voted: true } ) ],
			[ 'Faces · Outline', block( { set: 'smileys', style: 'outline', like: { label: 'Loved it', count: 24, color: '#16a34a' }, dislike: { label: 'Not for me', count: 2, color: '#dc2626' } } ) ],
			[ 'Text only · Outline, "Was this helpful?"', block( { set: 'none', style: 'outline', prompt: 'Was this helpful?', counts: 'never', like: { label: 'Yes', count: null, color: '#16a34a' }, dislike: { label: 'No', count: null, color: '#dc2626' } } ) ],
			[ 'Thumbs · Minimal, on comments', block( { set: 'thumbs', style: 'minimal', like: { label: 'Like', count: 14, active: true, color: '#0d9488' }, dislike: { label: 'Dislike', count: 1, color: '#9333ea' }, voted: true } ) ],
		];
		const board = await browser.newPage();
		await board.setViewport( { width: 1440, height: 900, deviceScaleFactor: 1.5 } );
		await board.setContent(
			`<html><head><style>${ FONTS }\n${ BUTTONS_CSS }
			body{margin:0;height:900px;display:grid;place-items:center;--ldfw-font:18px;background:radial-gradient(circle at 85% 10%,rgba(120,140,255,.28),transparent 45%),linear-gradient(135deg,#f7f8ff,#eceffd);font-family:Inter,sans-serif;color:#111827}
			.grid{display:grid;grid-template-columns:repeat(3,440px);gap:28px}
			.card{display:grid;align-content:space-between;gap:22px;min-height:250px;padding:30px 32px;border-radius:16px;background:#fff;box-shadow:0 16px 36px rgba(40,40,140,.12),0 0 0 1px rgba(30,30,90,.06)}
			.card h3{margin:0;color:#6b7280;font:600 14px/1.3 Inter,sans-serif;letter-spacing:.03em;text-transform:uppercase}
			.card .ldfw{--ldfw-font:18px}
			.card .ldfw{margin:0}
			.line{height:10px;border-radius:5px;background:#eef0f5}.line+.line{width:70%;margin-top:10px}
			</style></head><body><div class="grid">${ cards.map( ( [ title, html ] ) => `<div class="card"><h3>${ title }</h3><div><div class="line"></div><div class="line"></div></div>${ html }</div>` ).join( '' ) }</div></body></html>`,
			{ waitUntil: 'load' }
		);
		await board.evaluate( () => document.fonts.ready );
		await board.screenshot( { path: join( ASSETS, fileFor( 'styles' ) ) } );
		report( fileFor( 'styles' ) );
		await board.close();
	}

	for ( const name of readdirSync( ASSETS ) ) {
		const n = /^screenshot-(\d+)\.png$/.exec( name );
		if ( n && Number( n[ 1 ] ) > SHOTS.length ) {
			unlinkSync( join( ASSETS, name ) );
		}
	}
}

/* Build ---------------------------------------------------------------------------------- */

const MU_DIR = join( SITE_PATH, 'wp-content/mu-plugins' );
const MU_FILE = join( MU_DIR, 'ldfw-screenshots.php' );
const MU_PLUGIN = `<?php
// Temporary, written and removed by Like Dislike's build-assets.mjs. Only requests from
// the script's own browser carry the ${ DEMO_COOKIE } cookie.
if ( empty( $_COOKIE['${ DEMO_COOKIE }'] ) ) {
	return;
}
add_filter( 'option_blogname', function () {
	return 'Pinewood Studio';
} );
add_filter( 'manage_post_posts_columns', function ( $columns ) {
	unset( $columns['noteflow'] );
	return $columns;
}, 99 );
add_action( 'pre_get_posts', function ( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$demo   = get_option( 'ldfw_demo' );
	if ( $screen && 'edit-post' === $screen->id && ! empty( $demo['posts'] ) ) {
		$query->set( 'post__in', array_map( 'intval', $demo['posts'] ) );
	}
} );
`;

const browser = await puppeteer.launch( { executablePath: CHROME, headless: 'new' } );
let wroteMu = false;
let published = [];
let savedSettings = null;
const hadMuDir = existsSync( MU_DIR );

try {
	if ( process.argv.includes( '--screenshots' ) ) {
		mkdirSync( MU_DIR, { recursive: true } );
		writeFileSync( MU_FILE, MU_PLUGIN );
		wroteMu = true;
		savedSettings = wp( 'option', 'get', 'ldfw_settings', '--format=json' ).trim();
		published = JSON.parse( wp( 'option', 'get', 'ldfw_demo', '--format=json' ) ).posts;
		wp( 'eval', `global $wpdb; foreach ( json_decode( '${ JSON.stringify( published ) }' ) as $id ) { $wpdb->update( $wpdb->posts, array( 'post_status' => 'publish' ), array( 'ID' => (int) $id ) ); clean_post_cache( (int) $id ); } delete_option( 'ldfw_notice' );` );
		await screenshots( browser );
	}

	const page = await browser.newPage();

	/* Icon */
	for ( const size of [ 128, 256 ] ) {
		await page.setViewport( { width: size, height: size, deviceScaleFactor: 1 } );
		await page.setContent( `<html><body style="margin:0;background:transparent"><img src="${ iconUri }" width="${ size }" height="${ size }" style="display:block"></body></html>` );
		const name = `icon-${ size }x${ size }.png`;
		await page.screenshot( { path: join( ASSETS, name ), omitBackground: true, clip: { x: 0, y: 0, width: size, height: size } } );
		check( name, size, size );
	}

	/* Banners */
	const sets = JSON.parse( wp( 'eval', 'echo wp_json_encode( array( "like" => LDFW_Render::icon( "thumbs", "like" ), "dislike" => LDFW_Render::icon( "thumbs", "dislike" ) ) );' ) );
	const banner = readFileSync( join( HERE, 'banner.html' ), 'utf8' )
		.replace( '/* FONTS: build-assets.mjs injects the Inter and Manrope @font-face rules here. */', FONTS )
		.replace( "/* BUTTONS: build-assets.mjs injects the plugin's buttons.css here. */", BUTTONS_CSS )
		.replaceAll( 'ICON_URI', iconUri )
		.replaceAll( 'ICON_LIKE', sets.like )
		.replaceAll( 'ICON_DISLIKE', sets.dislike );

	for ( const [ width, height, scale ] of [ [ 772, 250, 1 ], [ 1544, 500, 2 ] ] ) {
		await page.setViewport( { width: 772, height: 250, deviceScaleFactor: scale } );
		await page.setContent( banner, { waitUntil: 'load' } );
		await page.evaluate( () => document.fonts.ready );
		const missing = await page.evaluate( () => [ '800 36px Manrope', '500 16px Inter', '600 12px Inter' ].filter( ( f ) => ! document.fonts.check( f ) ) );
		if ( missing.length ) {
			throw new Error( 'Fonts not loaded: ' + missing.join( ', ' ) );
		}
		const name = `banner-${ width }x${ height }.png`;
		await page.screenshot( { path: join( ASSETS, name ), clip: { x: 0, y: 0, width: 772, height: 250 } } );
		check( name, width, height );
	}
} finally {
	await browser.close();
	if ( published.length ) {
		wp( 'eval', `global $wpdb; foreach ( json_decode( '${ JSON.stringify( published ) }' ) as $id ) { $wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => (int) $id ) ); clean_post_cache( (int) $id ); }` );
	}
	if ( savedSettings ) {
		writeFileSync( join( HERE, '.settings-backup.json' ), savedSettings );
		wp( 'eval', `update_option( "ldfw_settings", json_decode( file_get_contents( ${ JSON.stringify( join( HERE, '.settings-backup.json' ) ) } ), true ) );` );
		unlinkSync( join( HERE, '.settings-backup.json' ) );
	}
	for ( const s of sessions ) {
		wp( 'eval', `WP_Session_Tokens::get_instance( ${ s.uid } )->destroy( "${ s.token }" );` );
	}
	if ( wroteMu && existsSync( MU_FILE ) ) {
		unlinkSync( MU_FILE );
		if ( ! hadMuDir && ! readdirSync( MU_DIR ).length ) {
			rmdirSync( MU_DIR );
		}
	}
}

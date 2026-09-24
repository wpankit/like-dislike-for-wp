/**
 * Like Dislike buttons.
 *
 * Votes go to the REST API. The page may be cached, so a visitor's own votes are
 * remembered in the browser and applied after load; logged-in users get theirs from
 * the server. Clicks update the buttons at once and are corrected by the reply.
 */
( function () {
	'use strict';

	const D = window.ldfwData || {};
	const STORE = 'ldfw_votes';
	const MAX_STORED = 500;
	const text = ( key ) => ( D.i18n && D.i18n[ key ] ) || '';
	const keyOf = ( el ) => el.dataset.type + ':' + el.dataset.id;
	const blocksFor = ( key ) => Array.prototype.filter.call( document.querySelectorAll( '[data-ldfw]' ), ( el ) => keyOf( el ) === key );

	/* The visitor's votes, kept in this browser -------------------------------------------- */

	function readStore() {
		try {
			return JSON.parse( window.localStorage.getItem( STORE ) || '{}' ) || {};
		} catch ( e ) {
			return {};
		}
	}

	function remember( key, state ) {
		if ( D.nonce ) {
			return; // Logged-in users' votes come from the server.
		}
		const votes = readStore();
		delete votes[ key ];
		if ( state ) {
			votes[ key ] = state;
		}
		const keys = Object.keys( votes );
		keys.slice( 0, Math.max( 0, keys.length - MAX_STORED ) ).forEach( ( k ) => delete votes[ k ] );
		try {
			window.localStorage.setItem( STORE, JSON.stringify( votes ) );
		} catch ( e ) {}
	}

	/* Showing state ---------------------------------------------------------------------------- */

	function countsOf( el ) {
		const counts = { like: 0, dislike: 0 };
		el.querySelectorAll( '.ldfw-button' ).forEach( ( button ) => {
			const count = button.querySelector( '.ldfw-count' );
			if ( count ) {
				counts[ button.dataset.choice ] = parseInt( count.dataset.count, 10 ) || 0;
			}
		} );
		return counts;
	}

	function render( el, state, counts ) {
		el.dataset.state = state || '';
		el.classList.toggle( 'ldfw-has-voted', !! state );
		el.querySelectorAll( '.ldfw-button' ).forEach( ( button ) => {
			const on = button.dataset.choice === state;
			button.classList.toggle( 'is-active', on );
			button.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			const count = button.querySelector( '.ldfw-count' );
			if ( count && counts ) {
				const n = Math.max( 0, counts[ button.dataset.choice ] || 0 );
				count.dataset.count = String( n );
				count.textContent = n.toLocaleString( document.documentElement.lang || undefined );
			}
		} );
	}

	/** What the counts will be after this click, shown before the server replies. */
	function predict( previous, choice, counts ) {
		const next = { like: counts.like, dislike: counts.dislike };
		if ( previous === choice ) {
			next[ choice ]--;
			return { state: '', counts: next };
		}
		if ( previous ) {
			next[ previous ]--;
		}
		next[ choice ]++;
		return { state: choice, counts: next };
	}

	function message( el, value ) {
		const box = el.querySelector( '.ldfw-message' );
		if ( box ) {
			box.textContent = value || '';
		}
	}

	function showLogin( el ) {
		const box = el.querySelector( '.ldfw-message' );
		if ( ! box ) {
			return;
		}
		box.textContent = text( 'login' ) + ' ';
		const link = document.createElement( 'a' );
		const url = new URL( D.loginUrl || '/wp-login.php', window.location.href );
		url.searchParams.set( 'redirect_to', window.location.href );
		link.href = url.toString();
		link.textContent = text( 'loginLink' );
		box.appendChild( link );
	}

	/* Talking to the site ------------------------------------------------------------------------ */

	async function send( path, body, retried ) {
		const headers = { 'Content-Type': 'application/json' };
		if ( D.nonce ) {
			headers[ 'X-WP-Nonce' ] = D.nonce;
		}
		const response = await window.fetch( D.api + path, {
			method: 'POST',
			credentials: 'same-origin',
			headers,
			body: JSON.stringify( body ),
		} );
		let json = null;
		try {
			json = await response.json();
		} catch ( e ) {}

		// A page kept open for a long time has an expired nonce: get a fresh one once.
		if ( 403 === response.status && json && 'rest_cookie_invalid_nonce' === json.code && ! retried ) {
			let fresh = '';
			if ( D.nonceUrl ) {
				fresh = await window.fetch( D.nonceUrl, { credentials: 'same-origin' } ).then( ( r ) => ( r.ok ? r.text() : '' ) ).catch( () => '' );
			}
			D.nonce = /^[a-f0-9]{6,}$/.test( fresh.trim() ) ? fresh.trim() : '';
			return send( path, body, true );
		}

		if ( ! response.ok ) {
			const error = new Error( ( json && json.message ) || text( 'error' ) );
			error.code = json && json.code;
			throw error;
		}
		return json;
	}

	/* Feedback after a dislike --------------------------------------------------------------------- */

	function removeFeedback( el ) {
		const form = el.querySelector( '.ldfw-feedback' );
		if ( form ) {
			form.remove();
		}
	}

	function askFeedback( el ) {
		if ( el.querySelector( '.ldfw-feedback' ) ) {
			return;
		}
		const id = 'ldfw-feedback-' + el.dataset.type + '-' + el.dataset.id + '-' + Math.random().toString( 36 ).slice( 2, 7 );
		const form = document.createElement( 'form' );
		form.className = 'ldfw-feedback';
		const label = document.createElement( 'label' );
		label.htmlFor = id;
		label.textContent = text( 'question' );
		const field = document.createElement( 'textarea' );
		field.id = id;
		field.rows = 3;
		field.maxLength = 1000;
		field.placeholder = text( 'placeholder' );
		const button = document.createElement( 'button' );
		button.type = 'submit';
		button.className = 'ldfw-send';
		button.textContent = text( 'send' );
		form.append( label, field, button );

		form.addEventListener( 'submit', async ( event ) => {
			event.preventDefault();
			const value = field.value.trim();
			if ( ! value ) {
				field.focus();
				return;
			}
			button.disabled = true;
			try {
				await send( 'feedback', { type: el.dataset.type, id: Number( el.dataset.id ), text: value } );
				form.remove();
				message( el, text( 'sent' ) );
			} catch ( error ) {
				button.disabled = false;
				message( el, error.message );
			}
		} );

		el.appendChild( form );
	}

	/* Clicks ---------------------------------------------------------------------------------------- */

	document.addEventListener( 'click', async ( event ) => {
		const button = event.target.closest( '.ldfw-button' );
		const el = button && button.closest( '[data-ldfw]' );
		if ( ! el ) {
			return;
		}
		event.preventDefault();

		if ( D.members ) {
			showLogin( el );
			return;
		}
		if ( el.dataset.busy ) {
			return;
		}

		const key = keyOf( el );
		const previous = el.dataset.state || '';
		const choice = button.dataset.choice;
		const before = countsOf( el );
		const guess = predict( previous, choice, before );
		const all = blocksFor( key );

		all.forEach( ( block ) => render( block, guess.state, guess.counts ) );
		if ( guess.state ) {
			button.classList.add( 'is-popping' );
			window.setTimeout( () => button.classList.remove( 'is-popping' ), 350 );
		}
		el.dataset.busy = '1';
		message( el, '' );

		try {
			const result = await send( 'vote', { type: el.dataset.type, id: Number( el.dataset.id ), choice } );
			all.forEach( ( block ) => render( block, result.state, { like: result.likes, dislike: result.dislikes } ) );
			remember( key, result.state );
			if ( result.state && D.thanks ) {
				message( el, D.thanks );
			}
			if ( 'dislike' === result.state && D.feedback && 'post' === el.dataset.type ) {
				askFeedback( el );
			} else {
				removeFeedback( el );
			}
		} catch ( error ) {
			all.forEach( ( block ) => render( block, previous, before ) );
			if ( 'ldfw_login' === error.code ) {
				showLogin( el );
			} else {
				message( el, error.message || text( 'error' ) );
			}
		} finally {
			delete el.dataset.busy;
		}
	} );

	/* On load: show the votes this browser remembers ---------------------------------------------- */

	function restore() {
		if ( D.nonce ) {
			return;
		}
		const votes = readStore();
		document.querySelectorAll( '[data-ldfw]' ).forEach( ( el ) => {
			const state = votes[ keyOf( el ) ];
			if ( state && ! el.dataset.state && el.querySelector( '.ldfw-' + state ) ) {
				render( el, state, null );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', restore );
	} else {
		restore();
	}
}() );

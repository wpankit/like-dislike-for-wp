/**
 * Like Dislike → Settings: a live preview of the buttons, and presets.
 */
( function () {
	'use strict';

	const A = window.ldfwAdmin || { icons: {}, defaults: {} };
	const { __ } = window.wp.i18n;
	const form = document.getElementById( 'ldfw-settings-form' );
	const box = document.getElementById( 'ldfw-preview' );
	if ( ! form || ! box ) {
		return;
	}

	const field = ( name ) => form.querySelector( '[name="ldfw_settings[' + name + ']"]' );
	const radio = ( name ) => {
		const checked = form.querySelector( '[name="ldfw_settings[' + name + ']"]:checked' );
		return checked ? checked.value : '';
	};
	const esc = ( value ) =>
		String( value ).replace( /[&<>"']/g, ( c ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ] ) );

	let state = '';
	const counts = { like: 24, dislike: 3 };

	function values() {
		const label = ( name ) => field( name ).value.trim() || field( name ).placeholder;
		return {
			icons: radio( 'icons' ) || 'thumbs',
			style: radio( 'style' ) || 'pill',
			size: field( 'size' ).value,
			align: field( 'align' ).value,
			like: field( 'like_color' ).value,
			dislike: field( 'dislike_color' ).value,
			likeLabel: label( 'like_label' ),
			dislikeLabel: label( 'dislike_label' ),
			counts: field( 'counts' ).value,
			showDislike: field( 'dislike' ).checked,
			prompt: field( 'prompt' ).value.trim(),
			thanks: field( 'thanks' ).value.trim(),
			feedback: field( 'feedback' ).checked,
		};
	}

	function render() {
		const v = values();
		if ( ! v.showDislike && 'dislike' === state ) {
			state = '';
		}
		const choices = v.showDislike ? [ 'like', 'dislike' ] : [ 'like' ];
		const classes = [ 'ldfw', 'ldfw-style-' + v.style, 'ldfw-size-' + v.size, 'ldfw-align-' + v.align, 'ldfw-counts-' + v.counts, 'none' === v.icons ? 'ldfw-no-icons' : 'ldfw-has-icons' ];
		if ( state ) {
			classes.push( 'ldfw-has-voted' );
		}
		const icons = A.icons[ v.icons ] || {};

		let html = '<div class="' + classes.join( ' ' ) + '" style="--ldfw-like:' + esc( v.like ) + ';--ldfw-dislike:' + esc( v.dislike ) + '">';
		if ( v.prompt ) {
			html += '<span class="ldfw-prompt">' + esc( v.prompt ) + '</span>';
		}
		html += '<span class="ldfw-buttons" role="group" aria-label="' + esc( __( 'Preview', 'like-dislike-for-wp' ) ) + '">';
		choices.forEach( ( choice ) => {
			const on = state === choice;
			html += '<button type="button" class="ldfw-button ldfw-' + choice + ( on ? ' is-active' : '' ) + '" data-choice="' + choice + '" aria-pressed="' + ( on ? 'true' : 'false' ) + '">';
			html += icons[ choice ] || '';
			html += '<span class="ldfw-label">' + esc( 'like' === choice ? v.likeLabel : v.dislikeLabel ) + '</span>';
			if ( 'never' !== v.counts ) {
				html += '<span class="ldfw-count">' + counts[ choice ] + '</span>';
			}
			html += '</button>';
		} );
		html += '</span><span class="ldfw-message" role="status">' + esc( state && v.thanks ? v.thanks : '' ) + '</span>';
		if ( 'dislike' === state && v.feedback ) {
			html +=
				'<div class="ldfw-feedback"><label>' + esc( __( 'What could be better?', 'like-dislike-for-wp' ) ) + '</label>' +
				'<textarea rows="3" placeholder="' + esc( __( 'Tell us what was missing or unclear (optional).', 'like-dislike-for-wp' ) ) + '"></textarea>' +
				'<button type="button" class="ldfw-send">' + esc( __( 'Send', 'like-dislike-for-wp' ) ) + '</button></div>';
		}
		box.innerHTML = html + '</div>';
	}

	box.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( '.ldfw-button' );
		if ( ! button ) {
			return;
		}
		const choice = button.dataset.choice;
		if ( state === choice ) {
			counts[ choice ]--;
			state = '';
		} else {
			if ( state ) {
				counts[ state ]--;
			}
			counts[ choice ]++;
			state = choice;
		}
		render();
	} );

	form.addEventListener( 'input', render );
	form.addEventListener( 'change', render );

	/* Presets ------------------------------------------------------------------------------------- */

	const D = A.defaults || {};
	const presets = {
		classic: { icons: 'thumbs', style: 'pill', like_label: '', dislike_label: '', counts: 'always', dislike: true, prompt: '', thanks: '', feedback: false, like_color: '#2563eb', dislike_color: '#dc2626' },
		helpful: { icons: 'thumbs', style: 'outline', like_label: D.yes || 'Yes', dislike_label: D.no || 'No', counts: 'never', dislike: true, prompt: D.helpful || '', thanks: D.thanks || '', feedback: true, like_color: '#16a34a', dislike_color: '#dc2626' },
		hearts: { icons: 'hearts', style: 'pill', like_label: '', dislike_label: '', counts: 'always', dislike: false, prompt: '', thanks: '', feedback: false, like_color: '#e11d48', dislike_color: '#64748b' },
		votes: { icons: 'arrows', style: 'minimal', like_label: '', dislike_label: '', counts: 'always', dislike: true, prompt: '', thanks: '', feedback: false, like_color: '#ea580c', dislike_color: '#4f46e5' },
	};

	form.querySelectorAll( '[data-preset]' ).forEach( ( button ) =>
		button.addEventListener( 'click', () => {
			const preset = presets[ button.dataset.preset ];
			if ( ! preset ) {
				return;
			}
			Object.keys( preset ).forEach( ( name ) => {
				const value = preset[ name ];
				const inputs = form.querySelectorAll( '[name="ldfw_settings[' + name + ']"]' );
				inputs.forEach( ( input ) => {
					if ( 'radio' === input.type ) {
						input.checked = input.value === value;
					} else if ( 'checkbox' === input.type ) {
						input.checked = !! value;
					} else {
						input.value = value;
					}
				} );
			} );
			state = '';
			render();
		} )
	);

	render();
}() );

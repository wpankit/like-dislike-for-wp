/**
 * Blocks: Like Dislike Buttons and Most Liked Posts, rendered by the server.
 */
( function ( wp ) {
	'use strict';

	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, RangeControl, SelectControl, ToggleControl, TextControl } = wp.components;
	const ServerSideRender = wp.serverSideRender;

	const thumb = el(
		'svg',
		{ viewBox: '0 0 24 24', width: 24, height: 24, fill: 'none', stroke: 'currentColor', strokeWidth: 1.8, strokeLinecap: 'round', strokeLinejoin: 'round', 'aria-hidden': true, focusable: false },
		el( 'path', { d: 'M7 11v9H4.5A1.5 1.5 0 0 1 3 18.5v-6A1.5 1.5 0 0 1 4.5 11H7Z' } ),
		el( 'path', { d: 'M7 11 10.6 3.6a1.9 1.9 0 0 1 3.5 1.3L13.4 9.5h5a2 2 0 0 1 2 2.4l-1.4 6.5A2 2 0 0 1 17 20H7' } )
	);

	const list = el(
		'svg',
		{ viewBox: '0 0 24 24', width: 24, height: 24, fill: 'none', stroke: 'currentColor', strokeWidth: 1.8, strokeLinecap: 'round', strokeLinejoin: 'round', 'aria-hidden': true, focusable: false },
		el( 'path', { d: 'M10 6h10M10 12h10M10 18h10' } ),
		el( 'path', { d: 'M4 5.5h1.5v2M4 11l2 2M4 17.5h1.6' } )
	);

	registerBlockType( 'like-dislike-for-wp/buttons', {
		apiVersion: 2,
		title: __( 'Like Dislike Buttons', 'like-dislike-for-wp' ),
		description: __( 'Like and dislike buttons for this post, styled in Like Dislike → Settings.', 'like-dislike-for-wp' ),
		category: 'widgets',
		icon: thumb,
		keywords: [ __( 'like', 'like-dislike-for-wp' ), __( 'vote', 'like-dislike-for-wp' ), __( 'helpful', 'like-dislike-for-wp' ) ],
		supports: { html: false, multiple: false },
		edit: () => el( 'div', useBlockProps(), el( ServerSideRender, { block: 'like-dislike-for-wp/buttons' } ) ),
		save: () => null,
	} );

	registerBlockType( 'like-dislike-for-wp/most-liked', {
		apiVersion: 2,
		title: __( 'Most Liked Posts', 'like-dislike-for-wp' ),
		description: __( 'A list of the posts with the most likes.', 'like-dislike-for-wp' ),
		category: 'widgets',
		icon: list,
		keywords: [ __( 'popular', 'like-dislike-for-wp' ), __( 'top', 'like-dislike-for-wp' ), __( 'likes', 'like-dislike-for-wp' ) ],
		attributes: {
			number: { type: 'number', default: 5 },
			postType: { type: 'string', default: 'post' },
			period: { type: 'string', default: 'all' },
			showCount: { type: 'boolean', default: true },
		},
		supports: { html: false },
		edit: ( props ) => {
			const { attributes, setAttributes } = props;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Settings', 'like-dislike-for-wp' ) },
						el( RangeControl, {
							label: __( 'Number of posts', 'like-dislike-for-wp' ),
							value: attributes.number,
							min: 1,
							max: 20,
							onChange: ( number ) => setAttributes( { number } ),
						} ),
						el( SelectControl, {
							label: __( 'Liked during', 'like-dislike-for-wp' ),
							value: attributes.period,
							options: [
								{ label: __( 'All time', 'like-dislike-for-wp' ), value: 'all' },
								{ label: __( 'The last 12 months', 'like-dislike-for-wp' ), value: 'year' },
								{ label: __( 'The last 30 days', 'like-dislike-for-wp' ), value: 'month' },
								{ label: __( 'The last 7 days', 'like-dislike-for-wp' ), value: 'week' },
							],
							onChange: ( period ) => setAttributes( { period } ),
						} ),
						el( TextControl, {
							label: __( 'Post type', 'like-dislike-for-wp' ),
							help: __( 'For example post, page or product.', 'like-dislike-for-wp' ),
							value: attributes.postType,
							onChange: ( postType ) => setAttributes( { postType } ),
						} ),
						el( ToggleControl, {
							label: __( 'Show like counts', 'like-dislike-for-wp' ),
							checked: attributes.showCount,
							onChange: ( showCount ) => setAttributes( { showCount } ),
						} )
					)
				),
				el( 'div', useBlockProps(), el( ServerSideRender, { block: 'like-dislike-for-wp/most-liked', attributes } ) )
			);
		},
		save: () => null,
	} );
}( window.wp ) );

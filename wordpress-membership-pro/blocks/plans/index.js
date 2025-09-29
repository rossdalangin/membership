( function( blocks, i18n, element ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;

	blocks.registerBlockType( 'wmp/plans', {
		title: __( 'Membership Plans', 'wordpress-membership-pro' ),
		description: __( 'Displays the membership plans grid.', 'wordpress-membership-pro' ),
		category: 'widgets',
		icon: 'groups',
		edit: function() {
			return el(
				'div',
				{ className: 'wmp-plans-block-editor-placeholder' },
				el(
					'p',
					{},
					__( 'Membership Plans Grid - The actual grid will be displayed on the front-end.', 'wordpress-membership-pro' )
				)
			);
		},
		save: function() {
			return null; // Rendered on the server.
		},
	} );
}(
	window.wp.blocks,
	window.wp.i18n,
	window.wp.element
) );
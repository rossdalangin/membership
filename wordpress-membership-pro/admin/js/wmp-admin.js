(function( $ ) {
	'use strict';

	$(function() {
		// Handle the file uploader
		var mediaUploader;

		$( '#wmp_upload_file_button' ).on( 'click', function( e ) {
			e.preventDefault();

			if ( mediaUploader ) {
				mediaUploader.open();
				return;
			}

			mediaUploader = wp.media.frames.file_frame = wp.media({
				title: 'Choose File',
				button: {
					text: 'Choose File'
				},
				multiple: false
			});

			mediaUploader.on( 'select', function() {
				var attachment = mediaUploader.state().get( 'selection' ).first().toJSON();
				$( '#wmp_secure_file_attachment_id' ).val( attachment.id );
				$( '#wmp_file_display_name' ).val( attachment.filename );
			});

			mediaUploader.open();
		});
	});

})( jQuery );
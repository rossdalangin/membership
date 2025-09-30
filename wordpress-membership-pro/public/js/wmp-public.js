(function( $ ) {
	'use strict';

	$( function() {
		/**
		 * Gateway field visibility
		 */
		function toggleGatewayFields() {
			var selectedGateway = $( 'input[name="wmp_payment_gateway"]:checked' ).val();
			$( '.wmp-gateway-fields' ).hide();
			$( '#wmp-gateway-fields-' + selectedGateway ).show();
		}

		$( 'body' ).on( 'change', 'input[name="wmp_payment_gateway"]', toggleGatewayFields );
		toggleGatewayFields();


		/**
		 * Apply Coupon via AJAX
		 */
		$( '#wmp-apply-coupon' ).on( 'submit', function( e ) {
			e.preventDefault();

			var form = $( this );
			var btn = form.find( '#wmp-apply-coupon-btn' );
			var messageDiv = $( '#wmp-coupon-message' );
			var couponCode = form.find( '#wmp_coupon_code' ).val();
			var planId = $( 'input[name="wmp_plan_id"]' ).val();
			var nonce = form.find( '#wmp_apply_coupon_nonce' ).val();

			btn.prop( 'disabled', true );
			messageDiv.text( '' ).removeClass( 'error success' );

			$.ajax({
				type: 'POST',
				url: wmp_ajax.ajax_url,
				data: {
					action: 'wmp_apply_coupon',
					coupon_code: couponCode,
					plan_id: planId,
					nonce: nonce,
				},
				success: function( response ) {
					if ( response.success ) {
						messageDiv.text( response.data.message ).addClass( 'success' );
						$( '#wmp-price-display .wmp-price-original' ).css( 'text-decoration', 'line-through' );
						$( '#wmp-price-display .wmp-price-discounted' ).show().find( '.wmp-price-amount' ).text( response.data.discounted_price_formatted );
						$( '#wmp_applied_coupon' ).val( couponCode );
					} else {
						messageDiv.text( response.data.message ).addClass( 'error' );
					}
				},
				error: function() {
					messageDiv.text( 'An error occurred.' ).addClass( 'error' );
				},
				complete: function() {
					btn.prop( 'disabled', false );
				}
			});
		});

	});

})( jQuery );
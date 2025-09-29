(function( $ ) {
	'use strict';

	$(function() {
		var stripe = null;
		var cardElement = null;
		var form = $( '#wmp-checkout' );
		var couponForm = $( '#wmp-apply-coupon' );

		// Function to initialize Stripe and mount the card element
		function initializeStripe() {
			if ( ! window.wmp_stripe_vars || ! window.wmp_stripe_vars.publishable_key ) {
				console.error( 'Stripe public key not found.' );
				return;
			}

			stripe = Stripe( window.wmp_stripe_vars.publishable_key );
			var elements = stripe.elements();
			cardElement = elements.create( 'card' );
			cardElement.mount( '#wmp-stripe-card-element' );

			// Handle real-time validation errors from the card Element.
			cardElement.on('change', function(event) {
				var displayError = document.getElementById('wmp-stripe-card-errors');
				if (event.error) {
					displayError.textContent = event.error.message;
				} else {
					displayError.textContent = '';
				}
			});
		}

		// Function to handle main checkout form submission
		form.on( 'submit', function( e ) {
			var selectedGateway = $( 'input[name="wmp_payment_gateway"]:checked' ).val();

			if ( 'stripe' === selectedGateway ) {
				e.preventDefault();

				stripe.createToken( cardElement ).then( function( result ) {
					if ( result.error ) {
						// Inform the user if there was an error.
						var errorElement = document.getElementById( 'wmp-stripe-card-errors' );
						errorElement.textContent = result.error.message;
					} else {
						// Send the token to your server.
						$( '#stripe_token' ).val( result.token.id );
						form.get( 0 ).submit();
					}
				});
			}
		});

		// Function to handle AJAX coupon application
		couponForm.on( 'submit', function( e ) {
			e.preventDefault();
			var couponBtn = $( '#wmp-apply-coupon-btn' );
			couponBtn.prop( 'disabled', true ).text( 'Applying...' );

			var data = {
				action: 'wmp_apply_coupon',
				coupon_code: $( '#wmp_coupon_code' ).val(),
				plan_id: $( 'input[name="wmp_plan_id"]' ).val(),
				nonce: $( '#wmp_apply_coupon_nonce' ).val()
			};

			$.post( wmp_ajax.ajax_url, data, function( response ) {
				var messageContainer = $( '#wmp-coupon-message' );
				if ( response.success ) {
					messageContainer.html( '<div class="wmp-message success">' + response.data.message + '</div>' );
					$( '#wmp_applied_coupon' ).val( data.coupon_code );

					// Update price display
					var priceDisplay = $( '#wmp-price-display' );
					priceDisplay.find( '.wmp-price-original .wmp-price-amount' ).html( '<del>' + response.data.original_price_formatted + '</del>' );
					priceDisplay.find( '.wmp-price-discounted' ).show().find('.wmp-price-amount').text( response.data.discounted_price_formatted );

				} else {
					messageContainer.html( '<div class="wmp-message error">' + response.data.message + '</div>' );
					$( '#wmp_applied_coupon' ).val( '' );
				}
				couponBtn.prop( 'disabled', false ).text( 'Apply Coupon' );
			});
		});

		// Function to toggle payment gateway fields
		function toggleGatewayFields() {
			var selectedGateway = $( 'input[name="wmp_payment_gateway"]:checked' ).val();
			$( '.wmp-gateway-fields' ).hide();
			$( '#wmp-gateway-fields-' + selectedGateway ).show();

			// Initialize Stripe if it's the selected gateway and not already initialized
			if ( 'stripe' === selectedGateway && ! stripe ) {
				initializeStripe();
			}
		}

		// Initial check on page load
		toggleGatewayFields();

		// Handle change event on gateway radio buttons
		$( 'input[name="wmp_payment_gateway"]' ).on( 'change', toggleGatewayFields );
	});

})( jQuery );
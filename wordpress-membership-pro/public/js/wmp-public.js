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

        // --- Billing Portal: Update Payment Method ---
        $( '#wmp-update-payment-method-button' ).on( 'click', function( e ) {
            e.preventDefault();
            var button = $( this );
            button.text( 'Loading...' ).prop( 'disabled', true );

            $.ajax( {
                url: wmp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wmp_create_setup_intent',
                    // A nonce should be added here for security in a real plugin
                },
                success: function( response ) {
                    if ( response.success ) {
                        initializeStripeUpdateForm( response.data.client_secret, button );
                    } else {
                        button.after( '<div class="wmp-message error">' + response.data.message + '</div>' );
                        button.text( 'Update Payment Method' ).prop( 'disabled', false );
                    }
                },
                error: function() {
                    button.after( '<div class="wmp-message error">An unknown error occurred.</div>' );
                    button.text( 'Update Payment Method' ).prop( 'disabled', false );
                }
            } );
        } );

        function initializeStripeUpdateForm( clientSecret, originalButton ) {
            originalButton.hide();

            var formHtml = '<div id="wmp-update-payment-container">' +
                '<div id="wmp-stripe-payment-element-update"></div>' +
                '<button id="wmp-submit-update-payment" class="wmp-button">Save New Card</button>' +
                '<div id="wmp-stripe-payment-errors-update" role="alert" style="color: #a94442; margin-top: 10px;"></div>' +
                '</div>';

            originalButton.after( formHtml );

            if ( !stripe ) {
                 stripe = Stripe( window.wmp_stripe_vars.publishable_key );
            }

            var elements = stripe.elements( { clientSecret } );
            var paymentElement = elements.create( 'payment' );
            paymentElement.mount( '#wmp-stripe-payment-element-update' );

            var submitButton = $( '#wmp-submit-update-payment' );
            var errorDiv = $( '#wmp-stripe-payment-errors-update' );

            submitButton.on( 'click', async function( e ) {
                e.preventDefault();
                submitButton.text( 'Saving...' ).prop( 'disabled', true );
                errorDiv.text( '' );

                const { error } = await stripe.confirmSetup( {
                    elements,
                    confirmParams: {
                        return_url: window.location.href, // Required, but we handle the result client-side
                    },
                    redirect: 'if_required'
                } );

                if ( error ) {
                    if ( error.type === "card_error" || error.type === "validation_error" ) {
                        errorDiv.text( error.message );
                    } else {
                        errorDiv.text( "An unexpected error occurred." );
                    }
                    submitButton.text( 'Save New Card' ).prop( 'disabled', false );
                } else {
                    // Success. The payment method was updated.
                    $( '#wmp-update-payment-container' ).html( '<div class="wmp-message success">Your payment method has been updated successfully!</div>' );
                }
            } );
        }
	});

})( jQuery );
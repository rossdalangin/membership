<?php
/**
 * The file that defines the REST API routes for the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/api
 */

/**
 * The REST API registration class.
 *
 * This is used to register and handle all of the REST API routes for the plugin.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/api
 * @author     Jules
 */
class WMP_API {

    /**
     * The namespace for the API.
     *
     * @since 1.0.0
     * @var string
     */
    private $namespace = 'wmp/v1';

    /**
     * The subscription handler.
     *
     * @since 1.0.0
     * @access private
     * @var WMP_Subscriptions
     */
    private $subscriptions_handler;

    /**
     * Initialize the class.
     *
     * @since 1.0.0
     * @param WMP_Subscriptions $subscriptions_handler
     */
    public function __construct( WMP_Subscriptions $subscriptions_handler ) {
        $this->subscriptions_handler = $subscriptions_handler;
    }

    /**
     * Register the routes for the objects of the controller.
     *
     * @since 1.0.0
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/webhooks/paypal', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_paypal_webhook' ),
                'permission_callback' => '__return_true', // Webhooks don't have user authentication
            ),
        ) );

        register_rest_route( $this->namespace, '/webhooks/stripe', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_stripe_webhook' ),
                'permission_callback' => '__return_true',
            ),
        ) );
    }

    /**
     * Handle incoming webhooks from PayPal.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_REST_Response|WP_Error
     */
    public function handle_paypal_webhook( WP_REST_Request $request ) {
        $payload = $request->get_body();
        $data    = json_decode( $payload, true );

        // In a real application, you would verify the webhook signature here for security.
        if ( ! isset( $data['event_type'] ) || ! isset( $data['resource'] ) ) {
            return new WP_REST_Response( [ 'status' => 'error', 'message' => 'Invalid payload.' ], 400 );
        }

        $event_type = $data['event_type'];
        $resource   = $data['resource'];

        $paypal_subscription_id = '';
        if ( isset( $resource['id'] ) && strpos($event_type, 'BILLING.SUBSCRIPTION') === 0 ) {
            $paypal_subscription_id = $resource['id'];
        } elseif ( isset( $resource['billing_agreement_id'] ) ) {
            $paypal_subscription_id = $resource['billing_agreement_id'];
        }

        if ( empty( $paypal_subscription_id ) ) {
            return new WP_REST_Response( [ 'status' => 'success', 'message' => 'No subscription ID found.' ], 200 );
        }

        $subscription = $this->subscriptions_handler->get_subscription_by_gateway_id( 'paypal', $paypal_subscription_id );

        if ( ! $subscription ) {
            return new WP_REST_Response( [ 'status' => 'success', 'message' => 'Subscription not found.' ], 200 );
        }

        switch ( $event_type ) {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
            case 'PAYMENT.SALE.COMPLETED':
                if ( 'active' !== $subscription->status ) {
                    $this->subscriptions_handler->update_status( $subscription->id, 'active' );
                }
                break;

            case 'BILLING.SUBSCRIPTION.CANCELLED':
                $this->subscriptions_handler->update_status( $subscription->id, 'cancelled' );
                break;

            case 'BILLING.SUBSCRIPTION.EXPIRED':
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
                $this->subscriptions_handler->update_status( $subscription->id, 'expired' );
                break;
        }

        return new WP_REST_Response( [ 'status' => 'success' ], 200 );
    }

    /**
     * Handle incoming webhooks from Stripe.
     *
     * @since 1.0.9
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_REST_Response|WP_Error
     */
    public function handle_stripe_webhook( WP_REST_Request $request ) {
        $payload = $request->get_body();

        // In a real app, you'd verify the webhook signature here.
        // For simulation, we decode the JSON directly.
        $event = json_decode( $payload );

        if ( ! isset( $event->type ) || ! isset( $event->data->object ) ) {
            return new WP_REST_Response( [ 'status' => 'error', 'message' => 'Invalid event payload.' ], 400 );
        }

        switch ( $event->type ) {
            case 'invoice.payment_failed':
                $invoice = $event->data->object;
                if ( ! empty( $invoice->subscription ) ) {
                    $subscription = $this->subscriptions_handler->get_subscription_by_gateway_id( 'stripe', $invoice->subscription );
                    if ( $subscription ) {
                        $this->subscriptions_handler->update_status( $subscription->id, 'on-hold' );

                        // Schedule a retry attempt.
                        $retry_count = get_user_meta( $subscription->user_id, '_wmp_retry_count', true ) ?: 0;
                        if ( $retry_count < 3 ) { // Limit to 3 retries
                            wp_schedule_single_event( time() + DAY_IN_SECONDS, 'wmp_process_payment_retry', array( $subscription->id ) );
                            update_user_meta( $subscription->user_id, '_wmp_retry_count', $retry_count + 1 );
                        } else {
                            // Max retries reached, cancel the subscription.
                            $this->subscriptions_handler->update_status( $subscription->id, 'cancelled' );
                            delete_user_meta( $subscription->user_id, '_wmp_retry_count' );
                        }
                    }
                }
                break;

            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                if ( ! empty( $invoice->subscription ) ) {
                    $subscription = $this->subscriptions_handler->get_subscription_by_gateway_id( 'stripe', $invoice->subscription );
                    if ( $subscription && 'on-hold' === $subscription->status ) {
                        // Payment succeeded after a retry, reactivate the subscription.
                        $this->subscriptions_handler->update_status( $subscription->id, 'active' );
                        delete_user_meta( $subscription->user_id, '_wmp_retry_count' );
                    }
                }
                break;
        }

        return new WP_REST_Response( [ 'status' => 'success' ], 200 );
    }
}
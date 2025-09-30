<?php
/**
 * The file that defines the PayPal payment gateway.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/gateways
 */

/**
 * The PayPal payment gateway class.
 *
 * This is used to handle all PayPal-related functionality, including
 * settings, checkout processing, and API interactions.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/gateways
 * @author     Jules
 */
class WMP_Gateway_Paypal {

    /**
     * The ID of this gateway.
     *
     * @since    1.0.0
     * @access   public
     * @var      string
     */
    public $id = 'paypal';

    /**
     * The title of this gateway.
     *
     * @since    1.0.0
     * @access   public
     * @var      string
     */
    public $title;

    /**
     * The PayPal API Client ID.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $client_id;

    /**
     * The PayPal API Secret Key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $secret_key;

    /**
     * The PayPal API environment mode (sandbox or live).
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $mode;

    /**
     * The subscription handler.
     *
     * @since 1.0.0
     * @access private
     * @var WMP_Subscriptions
     */
    private $subscriptions_handler;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct( WMP_Subscriptions $subscriptions_handler ) {
        $this->title = __( 'PayPal', 'wordpress-membership-pro' );
        $this->subscriptions_handler = $subscriptions_handler;

        // Load settings - these will be implemented in the next step.
        $options = get_option( 'wmp_settings' );
        $this->client_id = isset( $options['paypal_client_id'] ) ? $options['paypal_client_id'] : '';
        $this->secret_key = isset( $options['paypal_secret_key'] ) ? $options['paypal_secret_key'] : '';
        $this->mode = isset( $options['paypal_mode'] ) ? $options['paypal_mode'] : 'sandbox';
    }

    /**
     * Get the API base URL.
     *
     * @since 1.0.0
     * @return string
     */
    private function get_api_base_url() {
        return 'sandbox' === $this->mode ? 'https://api.sandbox.paypal.com' : 'https://api.paypal.com';
    }

    /**
     * Get a PayPal API access token.
     *
     * @since 1.0.0
     * @return string|false The access token or false on failure.
     */
    private function get_access_token() {
        $url = $this->get_api_base_url() . '/v1/oauth2/token';
        $response = wp_remote_post( $url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->secret_key ),
            ),
            'body' => 'grant_type=client_credentials',
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return isset( $body['access_token'] ) ? $body['access_token'] : false;
    }

    /**
     * Process a payment by creating a PayPal order and redirecting the user.
     *
     * @since    1.0.0
     * @param    array    $data    The data for the payment, including plan_id.
     */
    public function process_payment( $data ) {
        $plan_id = $data['plan_id'];
        $payment_type = get_post_meta( $plan_id, '_wmp_payment_type', true );

        if ( 'subscription' === $payment_type ) {
            return $this->process_subscription_payment( $data );
        } else {
            return $this->process_one_time_payment( $data );
        }
    }

    /**
     * Process a one-time payment by creating a PayPal order.
     *
     * @since    1.0.0
     * @param    array    $data    The data for the payment.
     */
    private function process_one_time_payment( $data ) {
        $access_token = $this->get_access_token();
        if ( ! $access_token ) {
            wp_die( 'Could not connect to PayPal. Please check API credentials.' );
        }

        $plan_id = $data['plan_id'];
        $plan = get_post( $plan_id );
        $price = get_post_meta( $plan_id, '_wmp_price', true );

        $order_data = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'amount' => array(
                        'currency_code' => 'USD', // A real plugin would have a currency setting
                        'value' => $price,
                    ),
                    'description' => $plan->post_title,
                ),
            ),
            'application_context' => array(
                'return_url' => add_query_arg( 'wmp_action', 'paypal_return', home_url( '/thank-you' ) ),
                'cancel_url' => get_permalink( $data['checkout_page_id'] ), // Redirect back to checkout on cancel
            ),
        );

        $url = $this->get_api_base_url() . '/v2/checkout/orders';
        $response = wp_remote_post( $url, array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
            'body' => json_encode( $order_data ),
        ) );

        if ( is_wp_error( $response ) ) {
            wp_die( 'Could not create PayPal order.' );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['links'] ) ) {
            foreach ( $body['links'] as $link ) {
                if ( 'approve' === $link['rel'] ) {
                    // Store the order ID and plan ID in a transient to verify it on return.
                    $transient_data = [
                        'order_id' => $body['id'],
                        'plan_id'  => $plan_id,
                    ];
                    set_transient( 'wmp_paypal_order_' . $body['id'], $transient_data, HOUR_IN_SECONDS );
                    wp_redirect( $link['href'] );
                    exit;
                }
            }
        }

        wp_die( 'Could not get PayPal approval link. Please try again.' );
    }

    /**
     * Process a recurring subscription payment.
     *
     * @since    1.0.0
     * @param    array    $data    The data for the payment.
     */
    private function process_subscription_payment( $data ) {
        $access_token = $this->get_access_token();
        if ( ! $access_token ) {
            wp_die( 'Could not connect to PayPal. Please check API credentials.' );
        }

        $plan_id = $data['plan_id'];
        $plan = get_post( $plan_id );

        // Step 1: Create a Product on PayPal for the site (if it doesn't exist)
        $product_id = get_option( 'wmp_paypal_product_id' );
        if ( ! $product_id ) {
            $product_data = array(
                'name'        => get_bloginfo( 'name' ),
                'description' => 'Memberships for ' . get_bloginfo( 'name' ),
                'type'        => 'SERVICE',
                'category'    => 'SOFTWARE',
            );
            $product_response = $this->api_request( '/v1/catalogs/products', $product_data );
            if ( isset( $product_response['id'] ) ) {
                $product_id = $product_response['id'];
                update_option( 'wmp_paypal_product_id', $product_id );
            } else {
                wp_die( 'Could not create PayPal Product.' );
            }
        }

        // Step 2: Create a Plan on PayPal for this membership level (if it doesn't exist)
        $paypal_plan_id = get_post_meta( $plan_id, '_wmp_paypal_plan_id', true );
        if ( ! $paypal_plan_id ) {
            $billing_frequency = get_post_meta( $plan_id, '_wmp_billing_frequency', true );
            $billing_period = get_post_meta( $plan_id, '_wmp_billing_period', true );
            $price = get_post_meta( $plan_id, '_wmp_price', true );
            $trial_days = get_post_meta( $plan_id, '_wmp_trial_days', true );

            $billing_cycles = [];
            $sequence = 1;

            // Add trial period if it exists
            if ( ! empty( $trial_days ) && absint( $trial_days ) > 0 ) {
                $billing_cycles[] = [
                    'frequency'      => [
                        'interval_unit'  => 'DAY',
                        'interval_count' => absint( $trial_days ),
                    ],
                    'tenure_type'    => 'TRIAL',
                    'sequence'       => $sequence++,
                    'total_cycles'   => 1,
                    'pricing_scheme' => [
                        'fixed_price' => [
                            'value'         => '0.00',
                            'currency_code' => 'USD',
                        ],
                    ],
                ];
            }

            // Add regular billing cycle
            $billing_cycles[] = [
                'frequency'      => [
                    'interval_unit'  => strtoupper( $billing_period ),
                    'interval_count' => absint( $billing_frequency ),
                ],
                'tenure_type'    => 'REGULAR',
                'sequence'       => $sequence,
                'total_cycles'   => 0, // 0 for infinite
                'pricing_scheme' => [
                    'fixed_price' => [
                        'value'         => $price,
                        'currency_code' => 'USD',
                    ],
                ],
            ];

            $plan_data = array(
                'product_id'        => $product_id,
                'name'              => $plan->post_title,
                'description'       => wp_strip_all_tags( $plan->post_content ),
                'status'            => 'ACTIVE',
                'billing_cycles'    => $billing_cycles,
                'payment_preferences' => array(
                    'auto_bill_outstanding' => true,
                    'payment_failure_threshold' => 3,
                ),
            );
            $plan_response = $this->api_request( '/v1/billing/plans', $plan_data );
            if ( isset( $plan_response['id'] ) ) {
                $paypal_plan_id = $plan_response['id'];
                update_post_meta( $plan_id, '_wmp_paypal_plan_id', $paypal_plan_id );
            } else {
                wp_die( 'Could not create PayPal Plan.' );
            }
        }

        // Step 3: Create the Subscription
        $subscription_data = array(
            'plan_id'             => $paypal_plan_id,
            'application_context' => array(
                'return_url' => add_query_arg( [ 'wmp_action' => 'paypal_return_subscription' ], home_url( '/thank-you' ) ),
                'cancel_url' => get_permalink( $data['checkout_page_id'] ),
            ),
        );
        $subscription_response = $this->api_request( '/v1/billing/subscriptions', $subscription_data );

        if ( isset( $subscription_response['links'] ) ) {
            foreach ( $subscription_response['links'] as $link ) {
                if ( 'approve' === $link['rel'] ) {
                    // Temporarily store the plan_id to retrieve it on return.
                    // This is not ideal and will be replaced by a proper webhook handler.
                    set_transient( 'wmp_temp_plan_id_for_user_' . $data['user_id'], $plan_id, HOUR_IN_SECONDS );
                    wp_redirect( $link['href'] );
                    exit;
                }
            }
        }

        wp_die( 'Could not create PayPal Subscription. Please try again.' );
    }

    /**
     * Generic method to make a POST request to the PayPal API.
     *
     * @since 1.0.0
     * @param string $endpoint The API endpoint.
     * @param array $data The data to send.
     * @return array|false The API response or false on failure.
     */
    private function api_request( $endpoint, $data ) {
        $access_token = $this->get_access_token();
        if ( ! $access_token ) {
            return false;
        }

        $url = $this->get_api_base_url() . $endpoint;
        $response = wp_remote_post( $url, array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
            'body' => json_encode( $data ),
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }


    /**
     * Execute the payment after user approval.
     *
     * @since 1.0.0
     * @param string $order_id The PayPal order ID.
     * @return array|false The API response or false on failure.
     */
    public function execute_payment( $order_id ) {
        $access_token = $this->get_access_token();
        if ( ! $access_token ) {
            return false;
        }

        $url = $this->get_api_base_url() . '/v2/checkout/orders/' . $order_id . '/capture';
        $response = wp_remote_post( $url, array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }
}
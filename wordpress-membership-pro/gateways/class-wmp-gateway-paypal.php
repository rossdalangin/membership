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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->title = __( 'PayPal', 'wordpress-membership-pro' );

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
                'return_url' => add_query_arg( [ 'wmp_action' => 'paypal_return', 'plan_id' => $plan_id ], home_url( '/thank-you' ) ),
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
                    // Store the order ID in a transient to verify it on return
                    set_transient( 'wmp_paypal_order_id_' . $body['id'], $body['id'], HOUR_IN_SECONDS );
                    wp_redirect( $link['href'] );
                    exit;
                }
            }
        }

        wp_die( 'Could not get PayPal approval link. Please try again.' );
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
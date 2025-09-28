<?php
/**
 * The file that defines the GCash payment gateway.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/gateways
 */

/**
 * The GCash payment gateway class.
 *
 * This is used to handle all GCash-related functionality.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/gateways
 * @author     Jules
 */
class WMP_Gateway_Gcash {

    /**
     * The ID of this gateway.
     *
     * @since    1.0.0
     * @access   public
     * @var      string
     */
    public $id = 'gcash';

    /**
     * The title of this gateway.
     *
     * @since    1.0.0
     * @access   public
     * @var      string
     */
    public $title;

    /**
     * The GCash API Public Key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $public_key;

    /**
     * The GCash API Secret Key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $secret_key;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->title = __( 'GCash', 'wordpress-membership-pro' );

        $options = get_option( 'wmp_settings' );
        $this->public_key = isset( $options['gcash_public_key'] ) ? $options['gcash_public_key'] : '';
        $this->secret_key = isset( $options['gcash_secret_key'] ) ? $options['gcash_secret_key'] : '';
    }

    /**
     * Process a payment.
     *
     * This simulates creating a GCash payment source and redirecting the user.
     *
     * @since    1.0.0
     * @param    array    $data    The data for the payment.
     */
    public function process_payment( $data ) {
        $plan_id = $data['plan_id'];
        $price = get_post_meta( $plan_id, '_wmp_price', true );

        // In a real scenario, we would make an API call to GCash here to create a payment source.
        // The API would return a redirect URL. We will simulate this.

        $simulated_transaction_id = 'gcash_' . uniqid();

        $redirect_url = add_query_arg(
            array(
                'wmp_action'     => 'gcash_return',
                'plan_id'        => $plan_id,
                'transaction_id' => $simulated_transaction_id,
                'status'         => 'success', // Simulating a successful payment
            ),
            home_url( '/thank-you' )
        );

        // In a real scenario, we would store the transaction ID and associate it with a pending order.
        // For this simulation, we'll just redirect.
        wp_redirect( $redirect_url );
        exit;
    }
}
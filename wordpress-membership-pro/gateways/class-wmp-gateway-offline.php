<?php
/**
 * The file that defines the Offline payment gateway.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/gateways
 */

/**
 * The Offline payment gateway class.
 *
 * This is used to handle offline payment functionality.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/gateways
 * @author     Jules
 */
class WMP_Gateway_Offline {

    /**
     * The ID of this gateway.
     *
     * @since    1.0.0
     * @access   public
     * @var      string
     */
    public $id = 'offline';

    /**
     * The title of this gateway.
     *
     * @since    1.0.0
     * @access   public
     * @var      string
     */
    public $title;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->title = __( 'Offline Payment', 'wordpress-membership-pro' );
    }

    /**
     * Process a payment.
     *
     * For offline payments, this method doesn't need to do much, as the
     * subscription status is handled by the checkout processor.
     *
     * @since    1.0.0
     * @param    array    $data    The data for the payment.
     */
    public function process_payment( $data ) {
        // No external processing needed for offline payments.
        // The logic to set the subscription to "on-hold" will be in the checkout handler.
        return true;
    }
}
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
    /**
     * The subscription handler.
     *
     * @since 1.0.0
     * @access private
     * @var WMP_Subscriptions
     */
    private $subscriptions_handler;

    /**
     * The transaction handler instance.
     *
     * @since    1.0.2
     * @access   protected
     * @var      WMP_Transactions    $transactions_handler    Handles transaction logic.
     */
    protected $transactions_handler;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    WMP_Subscriptions    $subscriptions_handler    The subscription handler instance.
     * @param    WMP_Transactions     $transactions_handler     The transaction handler instance.
     */
    public function __construct( WMP_Subscriptions $subscriptions_handler, WMP_Transactions $transactions_handler ) {
        $this->title = __( 'Offline Payment', 'wordpress-membership-pro' );
        $this->subscriptions_handler = $subscriptions_handler;
        $this->transactions_handler = $transactions_handler;
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
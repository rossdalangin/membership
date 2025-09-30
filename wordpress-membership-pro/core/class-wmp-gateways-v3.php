<?php
/**
 * The file that defines the gateway manager.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 */

/**
 * The gateway manager class.
 *
 * This is used to load and manage all available payment gateways.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 * @author     Jules
 */
class WMP_Gateways {

    /**
     * The array of available gateways.
     *
     * @since    1.0.0
     * @access   private
     * @var      array
     */
    private $gateways = array();

    /**
     * The subscription handler.
     *
     * @since 1.0.0
     * @access private
     * @var WMP_Subscriptions
     */
    private $subscriptions_handler;

    /**
     * Initialize the class and load the gateways.
     *
     * @since    1.0.0
     */
    public function __construct( WMP_Subscriptions $subscriptions_handler ) {
        $this->subscriptions_handler = $subscriptions_handler;
        $this->load_gateways();
    }

    /**
     * Load all available gateway classes from the gateways directory.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_gateways() {
        $gateway_files = glob( WMP_PLUGIN_DIR . 'gateways/class-wmp-gateway-*.php' );

        foreach ( $gateway_files as $gateway_file ) {
            // Do not load the Stripe gateway, as it is incomplete.
            if ( strpos( $gateway_file, 'stripe' ) !== false ) {
                continue;
            }
            require_once $gateway_file;
            $class_name = basename( $gateway_file, '.php' );
            $class_name = str_replace( 'class-wmp-gateway-', '', $class_name );
            $class_name = 'WMP_Gateway_' . str_replace( '-', '_', ucwords( $class_name, '-' ) );

            if ( class_exists( $class_name ) ) {
                $gateway = new $class_name( $this->subscriptions_handler );
                $this->gateways[ $gateway->id ] = $gateway;
            }
        }
    }

    /**
     * Get all registered gateways.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_gateways() {
        return $this->gateways;
    }

    /**
     * Get a specific gateway by its ID.
     *
     * @since    1.0.0
     * @param    string    $gateway_id    The ID of the gateway to retrieve.
     * @return   object|null
     */
    public function get_gateway( $gateway_id ) {
        return isset( $this->gateways[ $gateway_id ] ) ? $this->gateways[ $gateway_id ] : null;
    }
}
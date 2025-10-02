<?php
/**
 * The file that defines the core transaction management functions.
 *
 * @link       https://example.com
 * @since      1.0.2
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 */

/**
 * The transaction management class.
 *
 * @since      1.0.2
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 * @author     Jules
 */
class WMP_Transactions {

    /**
     * The name of the transactions table.
     *
     * @since    1.0.2
     * @access   private
     * @var      string
     */
    private $table_name;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.2
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wmp_transactions';
    }

    /**
     * Create a new transaction.
     *
     * @since   1.0.2
     * @param   array $data     The data for the new transaction.
     * @return  int|false       The ID of the new transaction or false on failure.
     */
    public function create_transaction( $data ) {
        global $wpdb;

        $defaults = array(
            'created_at' => current_time( 'mysql' ),
        );
        $data = wp_parse_args( $data, $defaults );

        $result = $wpdb->insert( $this->table_name, $data );

        if ( ! $result ) {
            return false;
        }

        $transaction_id = $wpdb->insert_id;
        do_action( 'wmp_transaction_created', $transaction_id, $data );

        return $transaction_id;
    }

    /**
     * Get a transaction by its ID.
     *
     * @since 1.0.8
     * @param int $transaction_id The ID of the transaction.
     * @return object|null The transaction object or null if not found.
     */
    public function get_transaction( $transaction_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $transaction_id ) );
    }

    /**
     * Refund a transaction, including processing it with the payment gateway.
     *
     * @since 1.0.3
     * @param int $transaction_id The ID of the transaction to refund.
     * @return bool True on success, false on failure.
     */
    public function refund_transaction( $transaction_id ) {
        global $wpdb;

        $transaction = $this->get_transaction( $transaction_id );
        if ( ! $transaction ) {
            return false;
        }

        // We need the gateway manager to get the correct gateway object
        $subscriptions_handler = new WMP_Subscriptions(); // Dummy, not used for refunds
        $gateways_manager = new WMP_Gateways( $subscriptions_handler, $this );
        $gateway = $gateways_manager->get_gateway( $transaction->gateway );

        $refund_successful = false;
        if ( $gateway && method_exists( $gateway, 'process_refund' ) ) {
            $refund_successful = $gateway->process_refund( $transaction->transaction_id );
        } elseif ( 'offline' === $transaction->gateway ) {
            // Offline transactions can be refunded manually
            $refund_successful = true;
        }

        if ( ! $refund_successful ) {
            return false;
        }

        // If the gateway refund was successful, update our local record.
        $result = $wpdb->update(
            $this->table_name,
            array( 'status' => 'refunded' ),
            array( 'id' => $transaction_id ),
            array( '%s' ),
            array( '%d' )
        );

        if ( ! $result ) {
            // The gateway refund succeeded but our DB update failed. This requires manual intervention.
            // In a real plugin, you would log this error.
            return false;
        }

        do_action( 'wmp_transaction_refunded', $transaction_id );

        return true;
    }
}
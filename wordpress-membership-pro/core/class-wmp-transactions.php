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
     * Refund a transaction.
     *
     * @since 1.0.3
     * @param int $transaction_id The ID of the transaction to refund.
     * @return bool True on success, false on failure.
     */
    public function refund_transaction( $transaction_id ) {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name,
            array( 'status' => 'refunded' ),
            array( 'id' => $transaction_id ),
            array( '%s' ),
            array( '%d' )
        );

        if ( ! $result ) {
            return false;
        }

        do_action( 'wmp_transaction_refunded', $transaction_id );

        return true;
    }
}
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
}
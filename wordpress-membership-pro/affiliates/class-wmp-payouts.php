<?php
/**
 * The file that defines the core payout management functions.
 *
 * @link       https://example.com
 * @since      1.0.4
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/affiliates
 */

/**
 * The payout management class.
 *
 * @since      1.0.4
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/affiliates
 * @author     Jules
 */
class WMP_Payouts {

    /**
     * The name of the payouts table.
     *
     * @since    1.0.4
     * @access   private
     * @var      string
     */
    private $table_name;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.4
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wmp_payouts';
    }

    /**
     * Create a new payout request.
     *
     * @since   1.0.4
     * @param   array $data     The data for the new payout.
     * @return  int|false       The ID of the new payout or false on failure.
     */
    public function create_payout( $data ) {
        global $wpdb;

        $defaults = array(
            'status'     => 'pending',
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' ),
        );
        $data = wp_parse_args( $data, $defaults );

        $result = $wpdb->insert( $this->table_name, $data );

        if ( ! $result ) {
            return false;
        }

        $payout_id = $wpdb->insert_id;
        do_action( 'wmp_payout_created', $payout_id, $data );

        return $payout_id;
    }

    /**
     * Update a payout's status.
     *
     * @since   1.0.4
     * @param   int    $payout_id    The ID of the payout to update.
     * @param   string $new_status   The new status.
     * @return  bool                 True on success, false on failure.
     */
    public function update_payout_status( $payout_id, $new_status ) {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name,
            array(
                'status'     => $new_status,
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $payout_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( ! $result ) {
            return false;
        }

        do_action( 'wmp_payout_status_updated', $payout_id, $new_status );

        return true;
    }
}
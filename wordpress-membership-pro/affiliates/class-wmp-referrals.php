<?php
/**
 * The file that defines the core referral management functions.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/affiliates
 */

/**
 * The referral management class.
 *
 * This is used to manage referral data, including creating new referral records
 * and updating them upon conversion.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/affiliates
 * @author     Jules
 */
class WMP_Referrals {

    /**
     * The name of the referrals table.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $table_name;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wmp_referrals';
    }

    /**
     * Create a new referral record.
     *
     * @since   1.0.0
     * @param   array $data     The data for the new referral.
     * @return  int|false       The ID of the new referral or false on failure.
     */
    public function create_referral( $data ) {
        global $wpdb;

        $data['created_at'] = current_time( 'mysql' );
        $data['status'] = 'pending'; // All referrals start as pending

        $result = $wpdb->insert( $this->table_name, $data );

        if ( ! $result ) {
            return false;
        }

        $referral_id = $wpdb->insert_id;
        do_action( 'wmp_referral_created', $referral_id, $data );

        return $referral_id;
    }

    /**
     * Mark a referral as converted.
     *
     * @since   1.0.0
     * @param   int $referral_id       The ID of the referral to update.
     * @param   int $transaction_id    The ID of the associated transaction.
     * @return  bool                   True on success, false on failure.
     */
    public function mark_as_converted( $referral_id, $transaction_id ) {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name,
            array(
                'status'         => 'converted',
                'transaction_id' => $transaction_id,
            ),
            array( 'id' => $referral_id ),
            array( '%s', '%d' ),
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Get all referrals for a given affiliate.
     *
     * @since 1.0.2
     * @param int $affiliate_id The ID of the affiliate.
     * @return array|null
     */
    public function get_affiliate_referrals( $affiliate_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE affiliate_id = %d AND transaction_id IS NOT NULL", $affiliate_id ) );
    }

    /**
     * Get the total number of successful referrals for an affiliate.
     *
     * @since 1.0.2
     * @param int $affiliate_id The ID of the affiliate.
     * @return int
     */
    public function get_referral_count( $affiliate_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$this->table_name} WHERE affiliate_id = %d AND transaction_id IS NOT NULL", $affiliate_id ) );
    }

    /**
     * Mark all of an affiliate's unpaid referrals as paid.
     *
     * @since 1.0.4
     * @param int $affiliate_id The ID of the affiliate.
     * @return bool True on success, false on failure.
     */
    public function mark_referrals_as_paid( $affiliate_id ) {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name,
            array( 'status' => 'paid' ),
            array(
                'affiliate_id' => $affiliate_id,
                'status'       => 'unpaid',
            ),
            array( '%s' ),
            array( '%d', '%s' )
        );

        return $result !== false;
    }
}
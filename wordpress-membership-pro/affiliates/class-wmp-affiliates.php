<?php
/**
 * The file that defines the core affiliate management functions.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/affiliates
 */

/**
 * The affiliate management class.
 *
 * This is used to manage affiliate data, including registration, status updates,
 * and retrieving affiliate information from the custom database table.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/affiliates
 * @author     Jules
 */
class WMP_Affiliates {

    /**
     * The name of the affiliates table.
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
        $this->table_name = $wpdb->prefix . 'wmp_affiliates';
    }

    /**
     * Get an affiliate by their ID.
     *
     * @since   1.0.0
     * @param   int $affiliate_id    The ID of the affiliate.
     * @return  object|null          The affiliate object or null if not found.
     */
    public function get_affiliate( $affiliate_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $affiliate_id ) );
    }

    /**
     * Get an affiliate by their WordPress user ID.
     *
     * @since   1.0.0
     * @param   int $user_id    The WordPress user ID.
     * @return  object|null     The affiliate object or null if not found.
     */
    public function get_affiliate_by_user( $user_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE user_id = %d", $user_id ) );
    }

    /**
     * Create a new affiliate application.
     *
     * @since   1.0.0
     * @param   array $data     The data for the new affiliate.
     * @return  int|false       The ID of the new affiliate or false on failure.
     */
    public function create_affiliate( $data ) {
        global $wpdb;

        $data['created_at'] = current_time( 'mysql' );

        $result = $wpdb->insert( $this->table_name, $data );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update an affiliate's status.
     *
     * @since   1.0.0
     * @param   int    $affiliate_id   The ID of the affiliate to update.
     * @param   string $new_status     The new status (e.g., 'active', 'rejected').
     * @return  bool                   True on success, false on failure.
     */
    public function update_status( $affiliate_id, $new_status ) {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name,
            array( 'status' => $new_status ),
            array( 'id' => $affiliate_id ),
            array( '%s' ),
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Calculate the total earnings for an affiliate.
     *
     * @since 1.0.2
     * @param int $affiliate_id The ID of the affiliate.
     * @return float The total earnings.
     */
    public function get_affiliate_earnings( $affiliate_id ) {
        global $wpdb;
        $total_earnings = 0.00;

        $affiliate = $this->get_affiliate( $affiliate_id );
        if ( ! $affiliate ) {
            return $total_earnings;
        }

        $referrals_handler = new WMP_Referrals();
        $referrals = $referrals_handler->get_affiliate_referrals( $affiliate_id );

        if ( ! empty( $referrals ) ) {
            $transactions_table = $wpdb->prefix . 'wmp_transactions';
            foreach ( $referrals as $referral ) {
                if ( ! empty( $referral->transaction_id ) ) {
                    $transaction = $wpdb->get_row( $wpdb->prepare( "SELECT amount, status FROM {$transactions_table} WHERE id = %d", $referral->transaction_id ) );
                    if ( $transaction && 'completed' === $transaction->status ) {
                        $commission = (float) $transaction->amount * ( (float) $affiliate->commission_rate / 100 );
                        $total_earnings += $commission;
                    }
                }
            }
        }

        return (float) $total_earnings;
    }
}
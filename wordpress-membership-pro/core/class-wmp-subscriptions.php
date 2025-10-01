<?php
/**
 * The file that defines the core subscription management functions.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 */

/**
 * The subscription management class.
 *
 * This is used to manage user subscriptions, including creation, updates,
 * cancellation, and status checks, interacting with the custom subscriptions table.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 * @author     Jules
 */
class WMP_Subscriptions {

    /**
     * The name of the subscriptions table.
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
        $this->table_name = $wpdb->prefix . 'wmp_subscriptions';
    }

    /**
     * Get a subscription by its ID.
     *
     * @since   1.0.0
     * @param   int $subscription_id    The ID of the subscription.
     * @return  object|null             The subscription object or null if not found.
     */
    public function get_subscription( $subscription_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $subscription_id ) );
    }

    /**
     * Get a user's active subscription for a specific plan.
     *
     * @since   1.0.0
     * @param   int $user_id    The ID of the user.
     * @param   int $plan_id    The ID of the plan.
     * @return  object|null     The active subscription object or null if none.
     */
    public function get_user_active_subscription_for_plan( $user_id, $plan_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE user_id = %d AND plan_id = %d AND status = 'active'", $user_id, $plan_id ) );
    }

    /**
     * Get all of a user's subscriptions.
     *
     * @since   1.0.0
     * @param   int $user_id    The ID of the user.
     * @return  array|null      Array of subscription objects or null.
     */
    public function get_user_subscriptions( $user_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE user_id = %d", $user_id ) );
    }

    /**
     * Get a user's most recent subscription.
     *
     * @since   1.0.0
     * @param   int $user_id    The ID of the user.
     * @return  object|null     The latest subscription object or null if none.
     */
    public function get_user_latest_subscription( $user_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1", $user_id ) );
    }

    /**
     * Get a user's subscription for a specific plan (most recent).
     *
     * @since   1.0.1
     * @param   int $user_id    The ID of the user.
     * @param   int $plan_id    The ID of the plan.
     * @return  object|null     The subscription object or null if none.
     */
    public function get_user_subscription_for_plan( $user_id, $plan_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE user_id = %d AND plan_id = %d ORDER BY created_at DESC LIMIT 1", $user_id, $plan_id ) );
    }

    /**
     * Get a subscription by its gateway subscription ID.
     *
     * @since   1.0.0
     * @param   string $gateway_id                 The ID of the gateway.
     * @param   string $gateway_subscription_id    The subscription ID from the gateway.
     * @return  object|null                         The subscription object or null if not found.
     */
    public function get_subscription_by_gateway_id( $gateway_id, $gateway_subscription_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE gateway = %s AND gateway_subscription_id = %s", $gateway_id, $gateway_subscription_id ) );
    }

    /**
     * Create a new subscription.
     *
     * @since   1.0.0
     * @param   array $data     The data for the new subscription.
     * @return  int|false       The ID of the new subscription or false on failure.
     */
    public function create_subscription( $data ) {
        global $wpdb;

        $data['created_at'] = current_time( 'mysql' );
        $data['updated_at'] = current_time( 'mysql' );

        $result = $wpdb->insert( $this->table_name, $data );

        if ( ! $result ) {
            return false;
        }

        $subscription_id = $wpdb->insert_id;

        do_action('wmp_subscription_created', $subscription_id, $data['user_id'], $data['plan_id']);

        if ( isset( $data['status'] ) && 'active' === $data['status'] ) {
            do_action( 'wmp_subscription_activated', $subscription_id, $data['user_id'] );
        }

        return $subscription_id;
    }

    /**
     * Update a subscription's status.
     *
     * @since   1.0.0
     * @param   int    $subscription_id   The ID of the subscription to update.
     * @param   string $new_status        The new status.
     * @return  bool                      True on success, false on failure.
     */
    public function update_status( $subscription_id, $new_status ) {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name,
            array(
                'status' => $new_status,
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $subscription_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( ! $result ) {
            return false;
        }

        $subscription = $this->get_subscription( $subscription_id );

        if ($new_status === 'active') {
            do_action('wmp_subscription_activated', $subscription_id, $subscription->user_id);
        } elseif ($new_status === 'cancelled' || $new_status === 'expired') {
            do_action('wmp_subscription_cancelled', $subscription_id, $subscription->user_id);
        }

        return true;
    }

    /**
     * Delete a subscription.
     *
     * @since   1.0.0
     * @param   int $subscription_id   The ID of the subscription to delete.
     * @return  bool                   True on success, false on failure.
     */
    public function delete_subscription( $subscription_id ) {
        global $wpdb;

        $subscription = $this->get_subscription( $subscription_id );
        if ( ! $subscription ) {
            return false;
        }

        // First, fire a cancellation hook to remove capabilities if the subscription was active.
        if ( 'active' === $subscription->status ) {
            do_action('wmp_subscription_cancelled', $subscription_id, $subscription->user_id);
        }

        return $wpdb->delete( $this->table_name, array( 'id' => $subscription_id ), array( '%d' ) );
    }

    /**
     * Update a subscription's plan.
     *
     * @since   1.0.2
     * @param   int   $subscription_id   The ID of the subscription to update.
     * @param   int   $new_plan_id       The ID of the new plan.
     * @param   array $gateway_data      Optional data from the payment gateway (e.g., new gateway subscription ID).
     * @return  bool                     True on success, false on failure.
     */
    public function change_subscription_plan( $subscription_id, $new_plan_id, $gateway_data = array() ) {
        global $wpdb;

        $subscription = $this->get_subscription( $subscription_id );
        if ( ! $subscription ) {
            return false;
        }

        $old_plan_id = $subscription->plan_id;

        $update_data = array(
            'plan_id'    => $new_plan_id,
            'updated_at' => current_time( 'mysql' ),
        );

        $update_formats = array( '%d', '%s' );

        if ( isset( $gateway_data['gateway_subscription_id'] ) ) {
            $update_data['gateway_subscription_id'] = $gateway_data['gateway_subscription_id'];
            $update_formats[] = '%s';
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array( 'id' => $subscription_id ),
            $update_formats,
            array( '%d' )
        );

        if ( false === $result ) {
            return false;
        }

        // --- Placeholder for Future Proration Logic ---
        // In a real-world scenario, you would add logic here to handle
        // prorated payments for upgrades or downgrades. This might involve:
        // 1. Calculating the remaining value of the current plan.
        // 2. Calculating the cost of the new plan.
        // 3. Charging the user the difference for an upgrade or providing a credit for a downgrade.
        // 4. Hooking into the payment gateway to process the proration charge.
        // --- End of Placeholder ---

        do_action( 'wmp_subscription_plan_changed', $subscription_id, $subscription->user_id, $new_plan_id, $old_plan_id );

        return true;
    }

    /**
     * Changes a user's subscription from one plan to another, calculating proration.
     *
     * @since    1.0.7
     * @param    int    $subscription_id      The ID of the subscription to change.
     * @param    int    $new_plan_id          The ID of the new plan.
     * @return   float|false                  The proration charge (positive for upgrade, negative for downgrade credit), or false on failure.
     */
    public function change_subscription( $subscription_id, $new_plan_id ) {
        $subscription = $this->get_subscription( $subscription_id );
        if ( ! $subscription ) {
            return false;
        }

        $old_plan_id = $subscription->plan_id;
        $old_plan_price = (float) get_post_meta( $old_plan_id, '_wmp_price', true );
        $old_plan_period = get_post_meta( $old_plan_id, '_wmp_billing_period', true );
        $old_plan_frequency = (int) get_post_meta( $old_plan_id, '_wmp_billing_frequency', true );

        $new_plan_price = (float) get_post_meta( $new_plan_id, '_wmp_price', true );

        // For simplicity, this calculation assumes the new plan has the same billing cycle as the old one.
        // A more advanced implementation would handle changing cycles (e.g., monthly to yearly).

        $days_in_cycle = 0;
        switch ( $old_plan_period ) {
            case 'day':   $days_in_cycle = $old_plan_frequency; break;
            case 'week':  $days_in_cycle = $old_plan_frequency * 7; break;
            case 'month': $days_in_cycle = $old_plan_frequency * 30; break; // Simplified
            case 'year':  $days_in_cycle = $old_plan_frequency * 365; break; // Simplified
        }

        if ( $days_in_cycle <= 0 ) {
            return false; // Invalid billing cycle
        }

        // --- Time-based Proration Calculation ---
        $today = time();
        $start_date = strtotime( $subscription->start_date );

        // This is a simplified way to find the current cycle start date.
        // A robust solution would need a dedicated `current_period_start` field in the database.
        $seconds_in_cycle = $days_in_cycle * 86400;
        $cycles_passed = floor( ( $today - $start_date ) / $seconds_in_cycle );
        $current_cycle_start = $start_date + ( $cycles_passed * $seconds_in_cycle );
        $current_cycle_end = $current_cycle_start + $seconds_in_cycle;

        $seconds_remaining = $current_cycle_end - $today;
        if ( $seconds_remaining < 0 ) {
            $seconds_remaining = 0;
        }

        $days_remaining = floor( $seconds_remaining / 86400 );

        $old_plan_cost_per_day = $old_plan_price / $days_in_cycle;
        $new_plan_cost_per_day = $new_plan_price / $days_in_cycle;

        $remaining_cost_of_old_plan = $days_remaining * $old_plan_cost_per_day;
        $cost_of_new_plan_for_rest_of_cycle = $days_remaining * $new_plan_cost_per_day;

        $proration_charge = $cost_of_new_plan_for_rest_of_cycle - $remaining_cost_of_old_plan;

        // --- Update Local Subscription ---
        $result = $this->change_subscription_plan( $subscription_id, $new_plan_id, array() );

        if ( ! $result ) {
            return false;
        }

        // Return the calculated charge. The calling function will handle the transaction.
        return round( $proration_charge, 2 );
    }
}
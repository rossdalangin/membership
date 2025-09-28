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
}
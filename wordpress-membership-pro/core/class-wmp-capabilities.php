<?php
/**
 * The file that defines the core capability management functions.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 */

/**
 * The capability management class.
 *
 * This is used to dynamically add and remove capabilities and roles from users
 * based on their subscription status. It hooks into the actions fired by the
 * WMP_Subscriptions class.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 * @author     Jules
 */
class WMP_Capabilities {

    /**
     * The subscription handler.
     *
     * @since 1.0.0
     * @access private
     * @var WMP_Subscriptions
     */
    private $subscriptions_handler;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct( WMP_Subscriptions $subscriptions_handler ) {
        $this->subscriptions_handler = $subscriptions_handler;
    }

    /**
     * Register the hooks for this class.
     *
     * @since 1.0.0
     */
    public function register_hooks() {
        add_action( 'wmp_subscription_activated', array( $this, 'on_subscription_activated' ), 10, 2 );
        add_action( 'wmp_subscription_cancelled', array( $this, 'on_subscription_deactivated' ), 10, 2 );
        // We can add another hook for expiration if needed.
    }

    /**
     * Fired when a subscription is activated.
     *
     * @since 1.0.0
     * @param int $subscription_id The ID of the subscription.
     * @param int $user_id The ID of the user.
     */
    public function on_subscription_activated( $subscription_id, $user_id ) {
        $subscription = $this->subscriptions_handler->get_subscription( $subscription_id );
        if ( ! $subscription ) {
            return;
        }

        $this->add_membership_capabilities( $user_id, $subscription->plan_id );
    }

    /**
     * Fired when a subscription is cancelled or expires.
     *
     * @since 1.0.0
     * @param int $subscription_id The ID of the subscription.
     * @param int $user_id The ID of the user.
     */
    public function on_subscription_deactivated( $subscription_id, $user_id ) {
        $subscription = $this->subscriptions_handler->get_subscription( $subscription_id );
        if ( ! $subscription ) {
            return;
        }

        $this->remove_membership_capabilities( $user_id, $subscription->plan_id );
    }

    /**
     * Add capabilities and role to a user based on their membership plan.
     *
     * @since   1.0.0
     * @param   int $user_id The ID of the user.
     * @param   int $plan_id The ID of the membership plan.
     */
    public function add_membership_capabilities( $user_id, $plan_id ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return;
        }

        // Add the dynamic capability for the plan.
        $capability = 'access_wmp_content_for_plan_' . $plan_id;
        $user->add_cap( $capability, true );

        // Assign the role associated with the plan.
        $assigned_role = get_post_meta( $plan_id, '_wmp_assigned_role', true );
        if ( ! empty( $assigned_role ) && get_role( $assigned_role ) ) {
            $user->set_role( $assigned_role );
        }
    }

    /**
     * Remove capabilities and role from a user.
     *
     * @since   1.0.0
     * @param   int $user_id The ID of the user.
     * @param   int $plan_id The ID of the membership plan.
     */
    public function remove_membership_capabilities( $user_id, $plan_id ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return;
        }

        // Remove the dynamic capability.
        $capability = 'access_wmp_content_for_plan_' . $plan_id;
        $user->remove_cap( $capability );

        // Revert user to the default 'subscriber' role.
        // A more advanced implementation might check if they have other active subscriptions.
        $user->set_role( get_option( 'default_role', 'subscriber' ) );
    }
}
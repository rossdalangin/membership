<?php
/**
 * The file that defines the email hooks for the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 */

/**
 * The email hooks class.
 *
 * This is used to hook into actions and trigger emails.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 * @author     Jules
 */
class WMP_Email_Hooks {

    /**
     * The subscription handler.
     *
     * @since 1.0.0
     * @access private
     * @var WMP_Subscriptions
     */
    private $subscriptions_handler;

    /**
     * Initialize the class.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->subscriptions_handler = new WMP_Subscriptions();
    }

    /**
     * Handle the subscription activated email.
     *
     * @since 1.0.0
     * @param int $subscription_id The ID of the subscription.
     * @param int $user_id The ID of the user.
     */
    public function on_subscription_activated( $subscription_id, $user_id ) {
        $settings = get_option( 'wmp_settings' );
        if ( empty( $settings['email_subscription_activated_enabled'] ) ) {
            return;
        }

        $user = get_user_by( 'id', $user_id );
        $subscription = $this->subscriptions_handler->get_subscription( $subscription_id );
        $plan_name = get_the_title( $subscription->plan_id );

        $subject = sprintf( __( 'Your subscription to %s is now active!', 'wordpress-membership-pro' ), $plan_name );
        $args = array(
            'user'      => $user,
            'plan_name' => $plan_name,
        );

        WMP_Emails()->send( $user->user_email, $subject, 'subscription-activated', $args );
    }

    /**
     * Handle the subscription cancelled email.
     *
     * @since 1.0.0
     * @param int $subscription_id The ID of the subscription.
     * @param int $user_id The ID of the user.
     */
    public function on_subscription_cancelled( $subscription_id, $user_id ) {
        $settings = get_option( 'wmp_settings' );
        if ( empty( $settings['email_subscription_cancelled_enabled'] ) ) {
            return;
        }

        $user = get_user_by( 'id', $user_id );
        $subscription = $this->subscriptions_handler->get_subscription( $subscription_id );
        $plan_name = get_the_title( $subscription->plan_id );

        $subject = sprintf( __( 'Your subscription to %s has been cancelled', 'wordpress-membership-pro' ), $plan_name );
        $args = array(
            'user'      => $user,
            'plan_name' => $plan_name,
        );

        WMP_Emails()->send( $user->user_email, $subject, 'subscription-cancelled', $args );
    }

    /**
     * Handle the subscription created email (for on-hold orders).
     *
     * @since 1.0.0
     * @param int $subscription_id The ID of the subscription.
     * @param int $user_id The ID of the user.
     * @param int $plan_id The ID of the plan.
     */
    public function on_subscription_created( $subscription_id, $user_id, $plan_id ) {
        $settings = get_option( 'wmp_settings' );
        if ( empty( $settings['email_order_on_hold_enabled'] ) ) {
            return;
        }

        $subscription = $this->subscriptions_handler->get_subscription( $subscription_id );

        // Only send for on-hold orders (i.e., offline payments).
        if ( 'on-hold' !== $subscription->status ) {
            return;
        }

        $user = get_user_by( 'id', $user_id );
        $instructions = isset( $settings['offline_instructions'] ) ? $settings['offline_instructions'] : '';

        $subject = __( 'Your order has been received', 'wordpress-membership-pro' );
        $args = array(
            'user'         => $user,
            'instructions' => $instructions,
        );

        WMP_Emails()->send( $user->user_email, $subject, 'order-on-hold', $args );
    }
}
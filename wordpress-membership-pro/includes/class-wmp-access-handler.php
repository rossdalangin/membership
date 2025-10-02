<?php
/**
 * Handles checking user access permissions for various resources.
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/includes
 * @author     Jules
 */
class WMP_Access_Handler {

    /**
     * The subscriptions handler.
     *
     * @since 1.0.7
     * @access private
     * @var WMP_Subscriptions
     */
    private $subscriptions_handler;

    /**
     * Initialize the class.
     *
     * @since    1.0.7
     * @param    WMP_Subscriptions    $subscriptions_handler    The subscription handler instance.
     */
    public function __construct( $subscriptions_handler ) {
        $this->subscriptions_handler = $subscriptions_handler;
    }

    /**
     * Checks if a user has access to a specific secure file.
     *
     * @since    1.0.7
     * @param    int    $user_id    The ID of the user to check.
     * @param    int    $file_id    The ID of the secure file post.
     * @return   bool   True if the user has access, false otherwise.
     */
    public function has_access_to_file( $user_id, $file_id ) {
        // Admins have access to everything.
        if ( user_can( $user_id, 'manage_options' ) ) {
            return true;
        }

        // Check if the user was granted this file as a one-time bonus.
        $bonus_files = get_user_meta( $user_id, '_wmp_bonus_files', true );
        if ( is_array( $bonus_files ) && in_array( $file_id, $bonus_files ) ) {
            return true;
        }

        // --- Standard Plan-Based Access ---
        $restricted_to = get_post_meta( $file_id, '_wmp_restricted_to_plans', true );
        $restricted_to = is_array( $restricted_to ) ? $restricted_to : array();

        // If the file is not restricted to any plans, it's accessible to all logged-in users.
        if ( empty( $restricted_to ) ) {
            return true;
        }

        $user_subscriptions = $this->subscriptions_handler->get_user_subscriptions( $user_id );
        if ( empty( $user_subscriptions ) ) {
            return false;
        }

        $user_plan_ids = array();
        foreach($user_subscriptions as $sub) {
            if ( 'active' === $sub->status ) {
                $user_plan_ids[] = $sub->plan_id;
            }
        }

        // Check if the user's active plan IDs intersect with the file's restricted plan IDs.
        $common_plans = array_intersect( $user_plan_ids, $restricted_to );

        return ! empty( $common_plans );
    }

    /**
     * Checks if a user has access to a specific forum.
     *
     * @since    1.0.10
     * @param    int    $user_id    The ID of the user to check.
     * @param    int    $forum_id   The ID of the bbPress forum.
     * @return   bool   True if the user has access, false otherwise.
     */
    public function has_access_to_forum( $user_id, $forum_id ) {
        // Admins have access to everything.
        if ( user_can( $user_id, 'manage_options' ) ) {
            return true;
        }

        // Find all plans that restrict this forum.
        $args = array(
            'post_type'      => 'wmp_membership_plan',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        );
        $all_plan_ids = get_posts( $args );

        $restricting_plan_ids = array();
        foreach ( $all_plan_ids as $plan_id ) {
            $restricted_forums = get_post_meta( $plan_id, '_wmp_restricted_forums', true );
            if ( is_array( $restricted_forums ) && in_array( $forum_id, $restricted_forums ) ) {
                $restricting_plan_ids[] = $plan_id;
            }
        }

        // If no plans restrict this forum, it's public.
        if ( empty( $restricting_plan_ids ) ) {
            return true;
        }

        // If the user is not logged in, they cannot access a restricted forum.
        if ( ! $user_id ) {
            return false;
        }

        $user_subscriptions = $this->subscriptions_handler->get_user_subscriptions( $user_id );
        if ( empty( $user_subscriptions ) ) {
            return false;
        }

        $user_plan_ids = array();
        foreach ( $user_subscriptions as $subscription ) {
            if ( 'active' === $subscription->status ) {
                $user_plan_ids[] = $subscription->plan_id;
            }
        }

        if ( empty( $user_plan_ids ) ) {
            return false;
        }

        // Check if the user's active plan IDs intersect with the plans that grant access.
        $common_plans = array_intersect( $user_plan_ids, $restricting_plan_ids );

        return ! empty( $common_plans );
    }
}
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
     */
    public function __construct() {
        $this->subscriptions_handler = new WMP_Subscriptions();
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

        $restricted_to = get_post_meta( $file_id, '_wmp_restricted_to_plans', true );
        $restricted_to = is_array( $restricted_to ) ? $restricted_to : array();

        // If the file is not restricted to any plans, it's accessible to all logged-in users.
        if ( empty( $restricted_to ) ) {
            return true;
        }

        $user_subscriptions = $this->subscriptions_handler->get_user_subscriptions( $user_id, 'active' );

        if ( empty( $user_subscriptions ) ) {
            return false;
        }

        $user_plan_ids = wp_list_pluck( $user_subscriptions, 'plan_id' );

        // Check if the user's active plan IDs intersect with the file's restricted plan IDs.
        $common_plans = array_intersect( $user_plan_ids, $restricted_to );

        return ! empty( $common_plans );
    }
}
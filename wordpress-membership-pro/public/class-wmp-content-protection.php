<?php
/**
 * The file that defines the core content protection functions.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/public
 */

/**
 * The content protection class.
 *
 * This is used to protect content based on user capabilities derived from their membership.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/public
 * @author     Jules
 */
class WMP_Content_Protection {

    /**
     * Filters the content of a post to apply protection rules.
     *
     * @since    1.0.0
     * @param    string    $content    The content of the post.
     * @return   string    The potentially modified content.
     */
    public function filter_content( $content ) {
        $post_id = get_the_ID();

        // Using a different meta key for clarity, as specified in CPT section
        $required_plan_id = get_post_meta( $post_id, '_wmp_required_plan_id', true );

        if ( empty( $required_plan_id ) ) {
            return $content;
        }

        if ( $this->has_access( $required_plan_id ) ) {
            return $content;
        } else {
            return $this->get_restriction_message( $required_plan_id );
        }
    }

    /**
     * Handles the [wmp_restrict] shortcode.
     *
     * @since    1.0.0
     * @param    array     $atts       Shortcode attributes.
     * @param    string    $content    The content enclosed by the shortcode.
     * @return   string    The output of the shortcode.
     */
    public function shortcode_restrict( $atts, $content = null ) {
        $atts = shortcode_atts(
            array(
                'plan_id' => '',
            ),
            $atts,
            'wmp_restrict'
        );

        $plan_ids = ! empty( $atts['plan_id'] ) ? array_map( 'absint', explode( ',', $atts['plan_id'] ) ) : array();

        if ( empty( $plan_ids ) ) {
            return ''; // Don't show content if no plan is specified
        }

        if ( $this->has_access( $plan_ids ) ) {
            return do_shortcode( $content );
        } else {
            return $this->get_restriction_message( $plan_ids );
        }
    }

    /**
     * Checks if the current user has access to content for a given plan.
     *
     * @since    1.0.0
     * @param    array|int  $plan_ids    A single plan ID or an array of plan IDs.
     * @return   bool       True if the user has access, false otherwise.
     */
    private function has_access( $plan_ids ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        // Admins always have access
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        if ( ! is_array( $plan_ids ) ) {
            $plan_ids = array( $plan_ids );
        }

        foreach ( $plan_ids as $plan_id ) {
            if ( current_user_can( 'access_wmp_content_for_plan_' . $plan_id ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves the message to display for restricted content.
     *
     * @since    1.0.0
     * @param    array|int  $plan_ids    The required plan ID(s).
     * @return   string     The restriction message.
     */
    private function get_restriction_message( $plan_ids ) {
        $message = '<div class="wmp-restricted-content">';
        $message .= '<h4>' . __( 'Restricted Content', 'wordpress-membership-pro' ) . '</h4>';
        $message .= '<p>' . __( 'This content is for members only. Please login or upgrade your membership to view this content.', 'wordpress-membership-pro' ) . '</p>';
        // In a future step, this could link to the relevant plan pages.
        $message .= '</div>';

        // Allow developers to filter the message
        return apply_filters( 'wmp_get_restriction_message', $message, get_the_ID() );
    }
}
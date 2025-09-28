<?php
/**
 * The file that defines the public-facing shortcodes for the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/public
 */

/**
 * The shortcode definition class.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/public
 * @author     Jules
 */
class WMP_Shortcodes {

    /**
     * Renders the [wmp_plans] shortcode.
     *
     * Displays a grid of membership plans fetched from the CPT.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    The shortcode output.
     */
    public function render_plans_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'checkout_page_url' => '/checkout', // Default checkout page slug
            ),
            $atts,
            'wmp_plans'
        );

        $args = array(
            'post_type'      => 'wmp_membership_plan',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );

        $plans = new WP_Query( $args );
        $output = '';

        if ( $plans->have_posts() ) {
            $output .= '<div class="wmp-plans-grid">';
            while ( $plans->have_posts() ) {
                $plans->the_post();
                $price = get_post_meta( get_the_ID(), '_wmp_price', true );
                $checkout_url = add_query_arg( 'plan_id', get_the_ID(), $atts['checkout_page_url'] );

                $output .= '<div class="wmp-plan">';
                $output .= '<h2>' . get_the_title() . '</h2>';
                $output .= '<div class="wmp-plan-description">' . get_the_content() . '</div>';
                $output .= '<div class="wmp-plan-price">$' . esc_html( $price ) . '</div>';
                $output .= '<a href="' . esc_url( $checkout_url ) . '" class="wmp-button">' . __( 'Sign Up', 'wordpress-membership-pro' ) . '</a>';
                $output .= '</div>';
            }
            $output .= '</div>';
            wp_reset_postdata();
        } else {
            $output .= '<p>' . __( 'No membership plans found.', 'wordpress-membership-pro' ) . '</p>';
        }

        return $output;
    }

    /**
     * Renders the [wmp_checkout] shortcode.
     *
     * Displays a confirmation form for the selected plan.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    The shortcode output.
     */
    public function render_checkout_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return __( 'You must be logged in to purchase a plan. Please login or register.', 'wordpress-membership-pro' );
        }

        if ( ! isset( $_GET['plan_id'] ) ) {
            return __( 'No plan selected. Please go back and choose a plan.', 'wordpress-membership-pro' );
        }

        $plan_id = absint( $_GET['plan_id'] );
        $plan = get_post( $plan_id );

        if ( ! $plan || 'wmp_membership_plan' !== $plan->post_type ) {
            return __( 'Invalid plan selected.', 'wordpress-membership-pro' );
        }

        $price = get_post_meta( $plan_id, '_wmp_price', true );

        $output = '<div class="wmp-checkout-form">';
        $output .= '<h3>' . sprintf( __( 'Confirm Your Purchase: %s', 'wordpress-membership-pro' ), esc_html( $plan->post_title ) ) . '</h3>';
        $output .= '<p><strong>' . __( 'Price:', 'wordpress-membership-pro' ) . '</strong> $' . esc_html( $price ) . '</p>';
        $output .= '<form id="wmp-checkout" action="" method="post">';
        $output .= '<input type="hidden" name="wmp_plan_id" value="' . esc_attr( $plan_id ) . '" />';
        $output .= wp_nonce_field( 'wmp_checkout_nonce', '_wpnonce', true, false );
        $output .= '<input type="hidden" name="wmp_action" value="process_checkout" />';
        $output .= '<input type="submit" value="' . __( 'Confirm Purchase (Simulated)', 'wordpress-membership-pro' ) . '" />';
        $output .= '</form>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Renders the [wmp_account] shortcode.
     *
     * Displays the member's account management portal.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    The shortcode output.
     */
    public function render_account_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'You must be logged in to view your account.', 'wordpress-membership-pro' ) . '</p>' . wp_login_form( array( 'echo' => false ) );
        }

        $current_user = wp_get_current_user();
        $output = '<div class="wmp-account-dashboard">';
        $output .= '<h2>' . __( 'My Account', 'wordpress-membership-pro' ) . '</h2>';
        $output .= '<p>' . sprintf( __( 'Welcome back, %s!', 'wordpress-membership-pro' ), esc_html( $current_user->display_name ) ) . '</p>';

        // These sections are placeholders for now and will be built out in future steps.
        $output .= '<h3>' . __( 'My Subscription', 'wordpress-membership-pro' ) . '</h3>';
        $output .= '<p>' . __( 'Your subscription details will appear here.', 'wordpress-membership-pro' ) . '</p>';

        $output .= '<h3>' . __( 'Billing History', 'wordpress-membership-pro' ) . '</h3>';
        $output .= '<p>' . __( 'Your billing history will appear here.', 'wordpress-membership-pro' ) . '</p>';

        $output .= '</div>';

        return $output;
    }
}
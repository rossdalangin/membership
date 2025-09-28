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
     * The subscription handler.
     *
     * @since 1.0.0
     * @access private
     * @var WMP_Subscriptions
     */
    private $subscriptions_handler;

    /**
     * The gateways manager.
     *
     * @since 1.0.0
     * @access private
     * @var WMP_Gateways
     */
    private $gateways_manager;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    WMP_Subscriptions    $subscriptions_handler    The subscription handler instance.
     * @param    WMP_Gateways         $gateways_manager         The gateways manager instance.
     */
    public function __construct( WMP_Subscriptions $subscriptions_handler, WMP_Gateways $gateways_manager ) {
        $this->subscriptions_handler = $subscriptions_handler;
        $this->gateways_manager      = $gateways_manager;
    }

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
        $gateways = $this->gateways_manager->get_gateways();

        $output = '<div class="wmp-checkout-form">';
        $output .= '<h3>' . sprintf( __( 'Confirm Your Purchase: %s', 'wordpress-membership-pro' ), esc_html( $plan->post_title ) ) . '</h3>';
        $output .= '<p><strong>' . __( 'Price:', 'wordpress-membership-pro' ) . '</strong> $' . esc_html( $price ) . '</p>';

        $output .= '<form id="wmp-checkout" action="" method="post">';

        if ( ! empty( $gateways ) ) {
            $output .= '<h4>' . __( 'Select Payment Method', 'wordpress-membership-pro' ) . '</h4>';
            $output .= '<ul class="wmp-payment-gateways">';
            foreach ( $gateways as $gateway ) {
                $output .= '<li>';
                $output .= '<input type="radio" name="wmp_payment_gateway" id="wmp_gateway_' . esc_attr( $gateway->id ) . '" value="' . esc_attr( $gateway->id ) . '" checked="checked"/>';
                $output .= '<label for="wmp_gateway_' . esc_attr( $gateway->id ) . '">' . esc_html( $gateway->title ) . '</label>';
                $output .= '</li>';
            }
            $output .= '</ul>';
        }

        $output .= '<input type="hidden" name="wmp_plan_id" value="' . esc_attr( $plan_id ) . '" />';
        $output .= wp_nonce_field( 'wmp_checkout_nonce', '_wpnonce', true, false );
        $output .= '<input type="hidden" name="wmp_action" value="process_checkout" />';
        $output .= '<input type="submit" value="' . __( 'Confirm Purchase', 'wordpress-membership-pro' ) . '" />';
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
        $subscriptions = $this->subscriptions_handler->get_user_subscriptions( $current_user->ID );

        $output = '<div class="wmp-account-dashboard">';

        if ( isset( $_GET['wmp_message'] ) && 'purchase_success' === $_GET['wmp_message'] ) {
            $output .= '<div class="wmp-message success"><p>' . __( 'Thank you for your purchase! Your new plan is now active.', 'wordpress-membership-pro' ) . '</p></div>';
        }

        $output .= '<h2>' . __( 'My Account', 'wordpress-membership-pro' ) . '</h2>';
        $output .= '<p>' . sprintf( __( 'Welcome back, %s!', 'wordpress-membership-pro' ), esc_html( $current_user->display_name ) ) . '</p>';

        $output .= '<h3>' . __( 'My Subscriptions', 'wordpress-membership-pro' ) . '</h3>';

        if ( ! empty( $subscriptions ) ) {
            $output .= '<table class="wmp-subscriptions-table">';
            $output .= '<thead><tr>';
            $output .= '<th>' . __( 'Plan', 'wordpress-membership-pro' ) . '</th>';
            $output .= '<th>' . __( 'Status', 'wordpress-membership-pro' ) . '</th>';
            $output .= '<th>' . __( 'Start Date', 'wordpress-membership-pro' ) . '</th>';
            $output .= '</tr></thead>';
            $output .= '<tbody>';

            foreach ( $subscriptions as $subscription ) {
                $plan_name = get_the_title( $subscription->plan_id );
                $output .= '<tr>';
                $output .= '<td>' . esc_html( $plan_name ) . '</td>';
                $output .= '<td>' . esc_html( ucfirst( $subscription->status ) ) . '</td>';
                $output .= '<td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $subscription->start_date ) ) ) . '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
        } else {
            $output .= '<p>' . __( 'You do not have any subscriptions.', 'wordpress-membership-pro' ) . '</p>';
        }

        $output .= '<h3>' . __( 'Billing History', 'wordpress-membership-pro' ) . '</h3>';
        $output .= '<p>' . __( 'Your billing history will appear here.', 'wordpress-membership-pro' ) . '</p>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Renders the [wmp_thank_you] shortcode.
     *
     * Displays a confirmation message and payment instructions after checkout.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    The shortcode output.
     */
    public function render_thank_you_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return __( 'Invalid request.', 'wordpress-membership-pro' );
        }

        $output = '<div class="wmp-thank-you">';

        if ( ! isset( $_GET['wmp_message'] ) || 'order_received' !== $_GET['wmp_message'] ) {
            $output .= '<p>' . __( 'Thank you for your purchase!', 'wordpress-membership-pro' ) . '</p>';
            return $output . '</div>';
        }

        $subscription = $this->subscriptions_handler->get_user_latest_subscription( get_current_user_id() );

        if ( ! $subscription ) {
            return __( 'Could not find your order details.', 'wordpress-membership-pro' );
        }

        $output .= '<h2>' . __( 'Thank You. Your Order Has Been Received.', 'wordpress-membership-pro' ) . '</h2>';

        if ( 'offline' === $subscription->gateway ) {
            $settings = get_option( 'wmp_settings' );
            $instructions = isset( $settings['offline_instructions'] ) ? $settings['offline_instructions'] : '';

            if ( ! empty( $instructions ) ) {
                $output .= '<h3>' . __( 'Payment Instructions', 'wordpress-membership-pro' ) . '</h3>';
                $output .= '<div class="wmp-offline-instructions">' . wpautop( wp_kses_post( $instructions ) ) . '</div>';
            }
        }

        $output .= '</div>';
        return $output;
    }
}
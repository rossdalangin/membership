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
     * The affiliates handler.
     *
     * @since 1.0.0
     * @access private
     * @var WMP_Affiliates
     */
    private $affiliates_handler;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    WMP_Subscriptions    $subscriptions_handler    The subscription handler instance.
     * @param    WMP_Gateways         $gateways_manager         The gateways manager instance.
     * @param    WMP_Affiliates       $affiliates_handler       The affiliates handler instance.
     */
    public function __construct( WMP_Subscriptions $subscriptions_handler, WMP_Gateways $gateways_manager, WMP_Affiliates $affiliates_handler ) {
        $this->subscriptions_handler = $subscriptions_handler;
        $this->gateways_manager      = $gateways_manager;
        $this->affiliates_handler    = $affiliates_handler;
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

        $change_subscription_id = isset( $_GET['change_subscription_id'] ) ? absint( $_GET['change_subscription_id'] ) : 0;
        $current_subscription = null;
        $current_plan_price = null;
        $current_plan_id = null;

        if ( $change_subscription_id && is_user_logged_in() ) {
            $current_subscription = $this->subscriptions_handler->get_subscription( $change_subscription_id );
            // Ensure the user owns this subscription
            if ( $current_subscription && $current_subscription->user_id == get_current_user_id() ) {
                $current_plan_id = $current_subscription->plan_id;
                $current_plan_price = get_post_meta( $current_plan_id, '_wmp_price', true );
            } else {
                $change_subscription_id = 0; // Invalid subscription, reset.
            }
        }

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
                $plan_id = get_the_ID();

                // Skip the current plan if the user is changing their subscription
                if ( $change_subscription_id && $plan_id == $current_plan_id ) {
                    continue;
                }

                $price = get_post_meta( $plan_id, '_wmp_price', true );
                $button_text = __( 'Sign Up', 'wordpress-membership-pro' );
                $checkout_url = add_query_arg( 'plan_id', $plan_id, $atts['checkout_page_url'] );

                if ( $change_subscription_id ) {
                    $button_text = ( (float) $price > (float) $current_plan_price ) ? __( 'Upgrade', 'wordpress-membership-pro' ) : __( 'Downgrade', 'wordpress-membership-pro' );
                    $checkout_url = add_query_arg( 'change_subscription_id', $change_subscription_id, $checkout_url );
                }

                $output .= '<div class="wmp-plan">';
                $output .= '<h2>' . get_the_title() . '</h2>';
                $output .= '<div class="wmp-plan-description">' . get_the_content() . '</div>';
                $output .= '<div class="wmp-plan-price">$' . esc_html( $price ) . '</div>';
                $output .= '<a href="' . esc_url( $checkout_url ) . '" class="wmp-button">' . $button_text . '</a>';
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

        if ( ! isset( $_REQUEST['plan_id'] ) ) { // Use $_REQUEST to catch both GET and POST
            return __( 'No plan selected. Please go back and choose a plan.', 'wordpress-membership-pro' );
        }

        $plan_id = absint( $_REQUEST['plan_id'] );
        $plan = get_post( $plan_id );

        if ( ! $plan || 'wmp_membership_plan' !== $plan->post_type ) {
            return __( 'Invalid plan selected.', 'wordpress-membership-pro' );
        }

        $price = get_post_meta( $plan_id, '_wmp_price', true );
        $gateways = $this->gateways_manager->get_gateways();

        $coupon_message = '';
        $applied_coupon_code = '';
        $display_price = $price;

        $output = '<div class="wmp-checkout-form">';
        $output .= '<h3>' . sprintf( __( 'Confirm Your Purchase: %s', 'wordpress-membership-pro' ), esc_html( $plan->post_title ) ) . '</h3>';

        // Price display area
        $output .= '<div id="wmp-price-display">';
        $output .= '<p class="wmp-price-original"><strong>' . __( 'Price:', 'wordpress-membership-pro' ) . '</strong> <span class="wmp-price-amount">$' . esc_html( $price ) . '</span></p>';
        $output .= '<p class="wmp-price-discounted" style="display:none;"><strong>' . __( 'Discounted Price:', 'wordpress-membership-pro' ) . '</strong> <span class="wmp-price-amount"></span></p>';
        $output .= '</div>';


        // Coupon Form
        $output .= '<div class="wmp-coupon-area">';
        $output .= '<div id="wmp-coupon-message"></div>';
        $output .= '<form id="wmp-apply-coupon">';
        $output .= ' <label for="wmp_coupon_code">' . __('Have a coupon?', 'wordpress-membership-pro') . '</label><br/>';
        $output .= ' <input type="text" name="wmp_coupon_code" id="wmp_coupon_code" />';
        $output .= ' <button type="submit" id="wmp-apply-coupon-btn">' . __( 'Apply Coupon', 'wordpress-membership-pro' ) . '</button>';
        $coupon_nonce = wp_nonce_field( 'wmp_apply_coupon_nonce', 'wmp_apply_coupon_nonce', true, false );
        $output .= $coupon_nonce;
        $output .= '</form>';
        $output .= '</div><hr/>';


        // Main Checkout Form
        $output .= '<form id="wmp-checkout" action="" method="post">';

        if ( ! empty( $gateways ) ) {
            $output .= '<h4>' . __( 'Select Payment Method', 'wordpress-membership-pro' ) . '</h4>';
            $output .= '<ul class="wmp-payment-gateways">';
            foreach ( $gateways as $gateway ) {
                $output .= '<li>';
                $output .= '<input type="radio" name="wmp_payment_gateway" id="wmp_gateway_' . esc_attr( $gateway->id ) . '" value="' . esc_attr( $gateway->id ) . '"' . checked( $gateway->id, 'stripe', false ) . ' class="wmp-gateway-radio" data-gateway-id="' . esc_attr( $gateway->id ) . '"/>';
                $output .= '<label for="wmp_gateway_' . esc_attr( $gateway->id ) . '">' . esc_html( $gateway->title ) . '</label>';

                if ( method_exists( $gateway, 'get_payment_form_fields' ) ) {
                    $output .= '<div class="wmp-gateway-fields" id="wmp-gateway-fields-' . esc_attr( $gateway->id ) . '" style="display:none;">';
                    $output .= $gateway->get_payment_form_fields();
                    $output .= '</div>';
                }

                $output .= '</li>';
            }
            $output .= '</ul>';
        }

        $output .= '<input type="hidden" name="wmp_plan_id" value="' . esc_attr( $plan_id ) . '" />';
        $output .= '<input type="hidden" name="wmp_applied_coupon" id="wmp_applied_coupon" value="" />';

        // Pass the subscription ID if this is a change request
        if ( isset( $_REQUEST['change_subscription_id'] ) ) {
            $output .= '<input type="hidden" name="change_subscription_id" value="' . absint( $_REQUEST['change_subscription_id'] ) . '" />';
        }

        $checkout_nonce = wp_nonce_field( 'wmp_checkout_nonce', '_wpnonce', true, false );
        $output .= $checkout_nonce;
        $output .= '<input type="hidden" name="wmp_action" value="process_checkout" />';
        $output .= '<input type="submit" value="' . __( 'Confirm Purchase', 'wordpress-membership-pro' ) . '" />';
        $output .= '</form>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Retrieves a coupon post by its code (title).
     *
     * @since 1.0.1
     * @access private
     * @param string $code The coupon code.
     * @return WP_Post|false The coupon post object or false if not found.
     */

    /**
     * Calculates the discounted price.
     *
     * @since 1.0.1
     * @access private
     * @param float $original_price The original price.
     * @param WP_Post $coupon The coupon post object.
     * @return float The discounted price.
     */

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

        if ( isset( $_GET['wmp_message'] ) ) {
            $message = '';
            $message_type = 'success';
            switch ( $_GET['wmp_message'] ) {
                case 'purchase_success':
                    $message = __( 'Thank you for your purchase! Your new plan is now active.', 'wordpress-membership-pro' );
                    break;
                case 'plan_changed_success':
                    $message = __( 'Your plan has been changed successfully.', 'wordpress-membership-pro' );
                    break;
                case 'plan_changed_pending':
                    $message = __( 'Your plan change request has been received and is pending confirmation.', 'wordpress-membership-pro' );
                    $message_type = 'notice';
                    break;
            }
            if ( ! empty( $message ) ) {
                $output .= '<div class="wmp-message ' . esc_attr( $message_type ) . '"><p>' . $message . '</p></div>';
            }
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
            $output .= '<th>' . __( 'Actions', 'wordpress-membership-pro' ) . '</th>';
            $output .= '</tr></thead>';
            $output .= '<tbody>';

            foreach ( $subscriptions as $subscription ) {
                $plan_name = get_the_title( $subscription->plan_id );
                $status_text = esc_html( ucfirst( $subscription->status ) );
                $actions = '';

                // Check for and display trial information
                if ( 'active' === $subscription->status && ! empty( $subscription->trial_end ) && strtotime( $subscription->trial_end ) > time() ) {
                    $trial_end_date = date_i18n( get_option( 'date_format' ), strtotime( $subscription->trial_end ) );
                    $status_text = sprintf( __( 'On Trial (ends %s)', 'wordpress-membership-pro' ), $trial_end_date );
                }

                if ( 'active' === $subscription->status ) {
                    // Assuming the plans page is at '/plans/'
                    $change_plan_url = add_query_arg( 'change_subscription_id', $subscription->id, home_url( '/plans' ) );
                    $actions .= '<a href="' . esc_url( $change_plan_url ) . '" class="wmp-button">' . __( 'Change Plan', 'wordpress-membership-pro' ) . '</a>';
                }

                $output .= '<tr>';
                $output .= '<td>' . esc_html( $plan_name ) . '</td>';
                $output .= '<td>' . $status_text . '</td>';
                $output .= '<td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $subscription->start_date ) ) ) . '</td>';
                $output .= '<td>' . $actions . '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
        } else {
            $output .= '<p>' . __( 'You do not have any subscriptions.', 'wordpress-membership-pro' ) . '</p>';
        }

        $output .= '<h3>' . __( 'Billing History', 'wordpress-membership-pro' ) . '</h3>';

        global $wpdb;
        $transactions_table = $wpdb->prefix . 'wmp_transactions';
        $transactions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$transactions_table} WHERE user_id = %d ORDER BY created_at DESC", $current_user->ID ) );

        if ( ! empty( $transactions ) ) {
            $output .= '<table class="wmp-billing-history-table">';
            $output .= '<thead><tr>';
            $output .= '<th>' . __( 'Date', 'wordpress-membership-pro' ) . '</th>';
            $output .= '<th>' . __( 'Amount', 'wordpress-membership-pro' ) . '</th>';
            $output .= '<th>' . __( 'Status', 'wordpress-membership-pro' ) . '</th>';
            $output .= '<th>' . __( 'Actions', 'wordpress-membership-pro' ) . '</th>';
            $output .= '</tr></thead>';
            $output .= '<tbody>';

            foreach ( $transactions as $transaction ) {
                $download_invoice_url = add_query_arg( array(
                    'wmp_action' => 'download_invoice',
                    'transaction_id' => $transaction->id,
                    '_wpnonce' => wp_create_nonce( 'wmp_download_invoice_nonce' )
                ), home_url() );

                $output .= '<tr>';
                $output .= '<td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $transaction->created_at ) ) ) . '</td>';
                $output .= '<td>$' . esc_html( $transaction->amount ) . '</td>';
                $output .= '<td>' . esc_html( ucfirst( $transaction->status ) ) . '</td>';
                $output .= '<td><a href="' . esc_url( $download_invoice_url ) . '">' . __( 'Download Invoice', 'wordpress-membership-pro' ) . '</a></td>';
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
        } else {
            $output .= '<p>' . __( 'You have no transactions.', 'wordpress-membership-pro' ) . '</p>';
        }

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

    /**
     * Renders the [wmp_affiliate_registration] shortcode.
     *
     * Displays a form for users to apply to the affiliate program.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    The shortcode output.
     */
    public function render_affiliate_registration_shortcode( $atts ) {
        if ( isset( $_GET['wmp_message'] ) && 'affiliate_application_received' === $_GET['wmp_message'] ) {
            return '<div class="wmp-message success"><p>' . __( 'Thank you for your application! We will review it shortly.', 'wordpress-membership-pro' ) . '</p></div>';
        }

        if ( ! is_user_logged_in() ) {
            return __( 'You must be logged in to apply for the affiliate program.', 'wordpress-membership-pro' );
        }

        $user_id = get_current_user_id();
        $affiliate = $this->affiliates_handler->get_affiliate_by_user( $user_id );

        if ( $affiliate ) {
            if ( 'active' === $affiliate->status ) {
                return __( 'You are already an active affiliate.', 'wordpress-membership-pro' );
            } elseif ( 'pending' === $affiliate->status ) {
                return __( 'Your affiliate application is currently pending review.', 'wordpress-membership-pro' );
            } else {
                return __( 'Your affiliate application has been rejected.', 'wordpress-membership-pro' );
            }
        }

        $output = '<div class="wmp-affiliate-registration-form">';
        $output .= '<h3>' . __( 'Become an Affiliate', 'wordpress-membership-pro' ) . '</h3>';
        $output .= '<p>' . __( 'Apply to become an affiliate and earn commissions by promoting our memberships.', 'wordpress-membership-pro' ) . '</p>';
        $output .= '<form id="wmp-affiliate-registration" action="" method="post">';
        $output .= wp_nonce_field( 'wmp_affiliate_registration_nonce', '_wpnonce', true, false );
        $output .= '<input type="hidden" name="wmp_action" value="process_affiliate_registration" />';
        $output .= '<input type="submit" value="' . __( 'Apply Now', 'wordpress-membership-pro' ) . '" />';
        $output .= '</form>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Renders the [wmp_affiliate_dashboard] shortcode.
     *
     * Displays the affiliate's dashboard with their link and stats.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    The shortcode output.
     */
    public function render_affiliate_dashboard_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return __( 'You must be logged in to view the affiliate dashboard.', 'wordpress-membership-pro' );
        }

        $user_id = get_current_user_id();
        $affiliate = $this->affiliates_handler->get_affiliate_by_user( $user_id );

        if ( ! $affiliate || 'active' !== $affiliate->status ) {
            // In a real plugin, this URL would be a setting.
            $registration_url = home_url( '/affiliate-program/' );
            return __( 'You are not an active affiliate. You can apply to become one <a href="'. esc_url( $registration_url ) .'">here</a>.', 'wordpress-membership-pro' );
        }

        $referral_url = add_query_arg( 'ref', $affiliate->id, home_url( '/' ) );

        $output = '<div class="wmp-affiliate-dashboard">';

        if ( isset( $_GET['wmp_message'] ) && 'payout_requested' === $_GET['wmp_message'] ) {
            $output .= '<div class="wmp-message success"><p>' . __( 'Your payout request has been received and will be processed shortly.', 'wordpress-membership-pro' ) . '</p></div>';
        }

        $output .= '<h2>' . __( 'Affiliate Dashboard', 'wordpress-membership-pro' ) . '</h2>';

        $output .= '<h3>' . __( 'Your Referral Link', 'wordpress-membership-pro' ) . '</h3>';
        $output .= '<p>' . __( 'Share this link to earn commissions on new memberships:', 'wordpress-membership-pro' ) . '</p>';
        $output .= '<input type="text" value="' . esc_url( $referral_url ) . '" readonly="readonly" style="width: 100%;" />';

        // --- Performance Stats ---
        $referrals_handler = new WMP_Referrals();
        $conversions = $referrals_handler->get_referral_count( $affiliate->id );
        $total_earnings = $this->affiliates_handler->get_affiliate_earnings( $affiliate->id );

        $output .= '<h3>' . __( 'Your Performance', 'wordpress-membership-pro' ) . '</h3>';
        $unpaid_earnings = $this->affiliates_handler->get_unpaid_earnings( $affiliate->id );

        $output .= '<ul>';
        $output .= '<li><strong>' . __( 'Referral Visits:', 'wordpress-membership-pro' ) . '</strong> ' . __( 'N/A', 'wordpress-membership-pro' ) . '</li>'; // Placeholder for now
        $output .= '<li><strong>' . __( 'Successful Conversions:', 'wordpress-membership-pro' ) . '</strong> ' . absint( $conversions ) . '</li>';
        $output .= '<li><strong>' . __( 'Total Earnings:', 'wordpress-membership-pro' ) . '</strong> $' . esc_html( number_format( $total_earnings, 2 ) ) . '</li>';
        $output .= '<li><strong>' . __( 'Unpaid Earnings:', 'wordpress-membership-pro' ) . '</strong> $' . esc_html( number_format( $unpaid_earnings, 2 ) ) . '</li>';
        $output .= '</ul>';

        // --- Payout Request ---
        $minimum_payout = 100; // In a real plugin, this would be a setting.
        if ( $unpaid_earnings >= $minimum_payout ) {
            $output .= '<h3>' . __( 'Request Payout', 'wordpress-membership-pro' ) . '</h3>';
            $output .= '<form id="wmp-request-payout" action="" method="post">';
            $output .= '<p>' . __( 'You have reached the minimum payout amount. You can request a payout of your unpaid earnings.', 'wordpress-membership-pro' ) . '</p>';
            $output .= '<input type="hidden" name="wmp_action" value="request_payout" />';
            $output .= '<input type="hidden" name="affiliate_id" value="' . esc_attr( $affiliate->id ) . '" />';
            $output .= '<input type="hidden" name="amount" value="' . esc_attr( $unpaid_earnings ) . '" />';
            $output .= wp_nonce_field( 'wmp_request_payout_nonce', '_wpnonce', true, false );
            $output .= '<input type="submit" value="' . __( 'Request Payout', 'wordpress-membership-pro' ) . '" />';
            $output .= '</form>';
        }

        // --- Recent Referrals ---
        $output .= '<h3>' . __( 'Recent Referrals', 'wordpress-membership-pro' ) . '</h3>';
        $referrals = $referrals_handler->get_affiliate_referrals( $affiliate->id );

        if ( ! empty( $referrals ) ) {
            $output .= '<table class="wmp-referrals-table">';
            $output .= '<thead><tr>';
            $output .= '<th>' . __( 'Date', 'wordpress-membership-pro' ) . '</th>';
            $output .= '<th>' . __( 'Status', 'wordpress-membership-pro' ) . '</th>';
            $output .= '</tr></thead>';
            $output .= '<tbody>';

            foreach ( $referrals as $referral ) {
                $output .= '<tr>';
                $output .= '<td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $referral->created_at ) ) ) . '</td>';
                $output .= '<td>' . esc_html( ucfirst( $referral->status ) ) . '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
        } else {
            $output .= '<p>' . __( 'You have no successful referrals yet.', 'wordpress-membership-pro' ) . '</p>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Renders the [wmp_oto] shortcode.
     *
     * Displays a one-time offer page.
     *
     * @since    1.0.3
     * @param    array     $atts    Shortcode attributes.
     * @return   string    The shortcode output.
     */
    public function render_oto_shortcode( $atts ) {
        if ( ! isset( $_GET['plan_id'] ) || ! isset( $_GET['subscription_id'] ) ) {
            return __( 'Invalid offer.', 'wordpress-membership-pro' );
        }

        $plan_id = absint( $_GET['plan_id'] );
        $subscription_id = absint( $_GET['subscription_id'] );
        $plan = get_post( $plan_id );

        if ( ! $plan || 'wmp_membership_plan' !== $plan->post_type ) {
            return __( 'Invalid offer.', 'wordpress-membership-pro' );
        }

        $price = get_post_meta( $plan_id, '_wmp_price', true );
        $accept_url = add_query_arg( array(
            'plan_id' => $plan_id,
        ), home_url( '/checkout' ) );

        // --- Decline URL Logic ---
        $downsell_query = new WP_Query( array(
            'post_type'  => 'wmp_membership_plan',
            'meta_key'   => '_wmp_oto_downsell_for',
            'meta_value' => get_post_meta( $plan_id, '_wmp_oto_upsell_for', true ), // Find downsell for the original plan
            'posts_per_page' => 1,
        ) );

        if ( $downsell_query->have_posts() ) {
            $downsell_plan = $downsell_query->posts[0];
            $decline_url = add_query_arg( array(
                'wmp_action' => 'oto_downsell',
                'plan_id' => $downsell_plan->ID,
                'subscription_id' => $subscription_id,
            ), home_url( '/one-time-offer' ) );
        } else {
            $decline_url = add_query_arg( 'wmp_message', 'purchase_success', home_url( '/thank-you' ) );
        }

        $output = '<div class="wmp-oto-page">';
        $output .= '<h2>' . __( 'Wait! Here Is a Special One-Time Offer', 'wordpress-membership-pro' ) . '</h2>';
        $output .= '<h3>' . esc_html( $plan->post_title ) . '</h3>';
        $output .= '<div>' . apply_filters( 'the_content', $plan->post_content ) . '</div>';
        $output .= '<div class="wmp-oto-price">$' . esc_html( $price ) . '</div>';
        $output .= '<div class="wmp-oto-actions">';
        $output .= '<a href="' . esc_url( $accept_url ) . '" class="wmp-button wmp-oto-accept">' . __( 'Yes, Add This To My Order!', 'wordpress-membership-pro' ) . '</a>';
        $output .= '<a href="' . esc_url( $decline_url ) . '" class="wmp-oto-decline">' . __( 'No, Thank You', 'wordpress-membership-pro' ) . '</a>';
        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Renders the [wmp_download] shortcode.
     *
     * Displays a secure download link for a file.
     *
     * @since    1.0.4
     * @param    array     $atts    Shortcode attributes.
     * @return   string    The shortcode output.
     */
    public function render_download_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'id' => 0,
                'text' => __( 'Download Now', 'wordpress-membership-pro' ),
            ),
            $atts,
            'wmp_download'
        );

        $file_id = absint( $atts['id'] );
        if ( ! $file_id ) {
            return '';
        }

        $file = get_post( $file_id );
        if ( ! $file || 'wmp_secure_file' !== $file->post_type ) {
            return '';
        }

        $download_url = add_query_arg( array(
            'wmp_action' => 'download_secure_file',
            'file_id' => $file_id,
            '_wpnonce' => wp_create_nonce( 'wmp_download_secure_file_nonce' ),
        ), home_url() );

        return '<a href="' . esc_url( $download_url ) . '" class="wmp-download-link">' . esc_html( $atts['text'] ) . '</a>';
    }
}
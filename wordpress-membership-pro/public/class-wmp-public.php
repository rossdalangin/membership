<?php
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the public-facing side of the site.
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/public
 * @author     Jules
 */
class WMP_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The content protection handler.
     *
     * @since    1.0.0
     * @access   private
     * @var      WMP_Content_Protection    $content_protection
     */
    private $content_protection;

    /**
     * The shortcode handler.
     *
     * @since    1.0.0
     * @access   private
     * @var      WMP_Shortcodes    $shortcodes
     */
    private $shortcodes;

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
     * The referrals handler.
     *
     * @since 1.0.0
     * @access private
     * @var WMP_Referrals
     */
    private $referrals_handler;

    public function __construct( $plugin_name, $version, $subscriptions_handler, $gateways_manager, $affiliates_handler, $referrals_handler ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->subscriptions_handler = $subscriptions_handler;
        $this->gateways_manager = $gateways_manager;
        $this->affiliates_handler = $affiliates_handler;
        $this->referrals_handler = $referrals_handler;

        require_once plugin_dir_path( __FILE__ ) . 'class-wmp-content-protection.php';
        $this->content_protection = new WMP_Content_Protection();

        require_once plugin_dir_path( __FILE__ ) . 'class-wmp-shortcodes.php';
        $this->shortcodes = new WMP_Shortcodes( $this->subscriptions_handler, $this->gateways_manager, $this->affiliates_handler );
    }

    /**
     * Process the checkout form submission.
     *
     * @since    1.0.0
     */
    public function process_checkout() {
        if ( ! isset( $_POST['wmp_action'] ) || 'process_checkout' !== $_POST['wmp_action'] ) {
            return;
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wmp_checkout_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        if ( ! is_user_logged_in() ) {
            wp_die( 'You must be logged in to complete this action.' );
        }

        $plan_id = isset( $_POST['wmp_plan_id'] ) ? absint( $_POST['wmp_plan_id'] ) : 0;
        if ( empty( $plan_id ) ) {
            wp_die( 'Invalid plan ID.' );
        }

        $gateway_id = isset( $_POST['wmp_payment_gateway'] ) ? sanitize_text_field( $_POST['wmp_payment_gateway'] ) : '';
        if ( empty( $gateway_id ) ) {
            wp_die( __( 'Please select a payment method.', 'wordpress-membership-pro' ) );
        }

        $gateway = $this->gateways_manager->get_gateway( $gateway_id );
        if ( ! $gateway ) {
            wp_die( 'Invalid payment gateway.' );
        }

        // For offline payments, we create the subscription directly with an on-hold status.
        if ( 'offline' === $gateway_id ) {
            $subscription_data = array(
                'user_id'                 => get_current_user_id(),
                'plan_id'                 => $plan_id,
                'status'                  => 'on-hold',
                'start_date'              => current_time( 'mysql' ),
                'gateway'                 => 'offline',
                'gateway_subscription_id' => '',
            );
            $this->subscriptions_handler->create_subscription( $subscription_data );
            $redirect_url = add_query_arg( 'wmp_message', 'order_received', home_url( '/thank-you' ) );
            wp_redirect( $redirect_url );
            exit;
        }

        // For other gateways like PayPal, we call their processing method.
        $data = [
            'plan_id'          => $plan_id,
            'user_id'          => get_current_user_id(),
            'checkout_page_id' => get_the_ID(),
        ];
        $gateway->process_payment( $data );
    }

    /**
     * Records a referral if a referral cookie is present during subscription creation.
     *
     * @since    1.0.0
     * @param    int    $subscription_id    The ID of the new subscription.
     * @param    int    $user_id            The ID of the user.
     * @param    int    $plan_id            The ID of the plan.
     */
    public function record_referral_on_subscription( $subscription_id, $user_id, $plan_id ) {
        if ( ! isset( $_COOKIE['wmp_ref_id'] ) ) {
            return;
        }

        $affiliate_id = absint( $_COOKIE['wmp_ref_id'] );
        if ( ! $affiliate_id ) {
            return;
        }

        $affiliate = $this->affiliates_handler->get_affiliate( $affiliate_id );
        if ( ! $affiliate || 'active' !== $affiliate->status ) {
            return; // Only track for active affiliates.
        }

        $referral_data = array(
            'affiliate_id'  => $affiliate_id,
            'referring_url' => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : '',
            'ip_address'    => $this->get_ip_address(),
        );

        $this->referrals_handler->create_referral( $referral_data );

        // Unset the cookie after it has been used to prevent duplicate referrals.
        unset( $_COOKIE['wmp_ref_id'] );
        setcookie( 'wmp_ref_id', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
    }

    /**
     * Get the user's IP address.
     *
     * @since    1.0.0
     * @return   string
     */
    private function get_ip_address() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field( $ip );
    }

    /**
     * Tracks affiliate referrals by setting a cookie.
     *
     * @since    1.0.0
     */
    public function track_referral_visit() {
        if ( isset( $_GET['ref'] ) ) {
            $affiliate_id = absint( $_GET['ref'] );
            if ( $affiliate_id ) {
                // Set a cookie to track the referral for 30 days.
                setcookie( 'wmp_ref_id', $affiliate_id, time() + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
            }
        }
    }

    /**
     * Process the affiliate registration form submission.
     *
     * @since    1.0.0
     */
    public function process_affiliate_registration() {
        if ( ! isset( $_POST['wmp_action'] ) || 'process_affiliate_registration' !== $_POST['wmp_action'] ) {
            return;
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wmp_affiliate_registration_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        if ( ! is_user_logged_in() ) {
            wp_die( 'You must be logged in to complete this action.' );
        }

        $user_id = get_current_user_id();
        $affiliate = $this->affiliates_handler->get_affiliate_by_user( $user_id );

        if ( $affiliate ) {
            // User already has an affiliate status, do nothing.
            return;
        }

        $affiliate_data = array(
            'user_id'         => $user_id,
            'status'          => 'pending',
            'commission_rate' => 20.00, // Default commission rate, could be a setting
        );

        $this->affiliates_handler->create_affiliate( $affiliate_data );

        $redirect_url = add_query_arg( 'wmp_message', 'affiliate_application_received', get_permalink() );
        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Handle the user's return from PayPal after approval.
     *
     * @since    1.0.0
     */
    public function handle_paypal_return() {
        if ( ! isset( $_GET['wmp_action'] ) || 'paypal_return' !== $_GET['wmp_action'] ) {
            return;
        }

        if ( ! isset( $_GET['token'] ) || ! isset( $_GET['PayerID'] ) ) {
            return;
        }

        $order_id = sanitize_text_field( $_GET['token'] );

        // Verify the transient to make sure this is a legitimate return.
        $transient_key = 'wmp_paypal_order_id_' . $order_id;
        $paypal_order_id = get_transient( $transient_key );

        if ( false === $paypal_order_id || $paypal_order_id !== $order_id ) {
            wp_die( 'Invalid PayPal order. Please try again.' );
        }

        // The transient is valid, so we can delete it now.
        delete_transient( $transient_key );

        $gateway = $this->gateways_manager->get_gateway( 'paypal' );
        $response = $gateway->execute_payment( $order_id );

        // If payment is completed, create the subscription.
        if ( $response && isset( $response['status'] ) && 'COMPLETED' === $response['status'] ) {
            $plan_id = isset( $_GET['plan_id'] ) ? absint( $_GET['plan_id'] ) : 0;
            $user_id = get_current_user_id();

            if ( ! $user_id || ! $plan_id ) {
                wp_die( __( 'Error: Missing user or plan information during payment processing.', 'wordpress-membership-pro' ) );
            }

            // Get the transaction ID from the PayPal response.
            $transaction_id = '';
            if ( isset( $response['purchase_units'][0]['payments']['captures'][0]['id'] ) ) {
                $transaction_id = $response['purchase_units'][0]['payments']['captures'][0]['id'];
            }

            $subscription_data = array(
                'user_id'                 => $user_id,
                'plan_id'                 => $plan_id,
                'status'                  => 'active',
                'start_date'              => current_time( 'mysql' ),
                'gateway'                 => 'paypal',
                'gateway_subscription_id' => $transaction_id, // For one-time payments, the transaction ID is sufficient.
            );

            $this->subscriptions_handler->create_subscription( $subscription_data );

            // Redirect to a success page.
            wp_redirect( home_url( '/thank-you?wmp_message=purchase_success' ) );
            exit;
        } else {
            wp_die( __( 'There was an error processing your payment with PayPal. Please try again.', 'wordpress-membership-pro' ) );
        }
    }

    /**
     * Handle the user's return from GCash (simulated).
     *
     * @since    1.0.0
     */
    public function handle_gcash_return() {
        if ( ! isset( $_GET['wmp_action'] ) || 'gcash_return' !== $_GET['wmp_action'] ) {
            return;
        }

        // In a real scenario, we would verify a signature or make a server-to-server request.
        // For this simulation, we just check for a success status in the URL.
        if ( ! isset( $_GET['status'] ) || 'success' !== $_GET['status'] ) {
            wp_die( 'GCash payment was not successful. Please try again.' );
        }

        $plan_id = isset( $_GET['plan_id'] ) ? absint( $_GET['plan_id'] ) : 0;
        $transaction_id = isset( $_GET['transaction_id'] ) ? sanitize_text_field( $_GET['transaction_id'] ) : '';
        $user_id = get_current_user_id();

        if ( ! $user_id || ! $plan_id ) {
            wp_die( __( 'Error: Missing user or plan information during payment processing.', 'wordpress-membership-pro' ) );
        }

        // Create the active subscription.
        $subscription_data = array(
            'user_id'                 => $user_id,
            'plan_id'                 => $plan_id,
            'status'                  => 'active',
            'start_date'              => current_time( 'mysql' ),
            'gateway'                 => 'gcash',
            'gateway_subscription_id' => $transaction_id,
        );

        $this->subscriptions_handler->create_subscription( $subscription_data );

        // Redirect to a success page.
        wp_redirect( home_url( '/thank-you?wmp_message=purchase_success' ) );
        exit;
    }

    /**
     * Register the shortcodes for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode( 'wmp_restrict', array( $this->content_protection, 'shortcode_restrict' ) );
        add_shortcode( 'wmp_plans', array( $this->shortcodes, 'render_plans_shortcode' ) );
        add_shortcode( 'wmp_account', array( $this->shortcodes, 'render_account_shortcode' ) );
        add_shortcode( 'wmp_checkout', array( $this->shortcodes, 'render_checkout_shortcode' ) );
        add_shortcode( 'wmp_thank_you', array( $this->shortcodes, 'render_thank_you_shortcode' ) );
        add_shortcode( 'wmp_affiliate_registration', array( $this->shortcodes, 'render_affiliate_registration_shortcode' ) );
        add_shortcode( 'wmp_affiliate_dashboard', array( $this->shortcodes, 'render_affiliate_dashboard_shortcode' ) );
    }

    /**
     * Filters the content of a post to apply protection rules.
     * This method is designed to be hooked directly by the loader.
     *
     * @since    1.0.0
     * @param    string    $content    The content of the post.
     * @return   string    The potentially modified content.
     */
    public function filter_the_content( $content ) {
        return $this->content_protection->filter_content( $content );
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // wp_enqueue_style( $this->plugin_name, WMP_PLUGIN_URL . 'public/css/wmp-public.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // wp_enqueue_script( $this->plugin_name, WMP_PLUGIN_URL . 'public/js/wmp-public.js', array( 'jquery' ), $this->version, false );
    }
}
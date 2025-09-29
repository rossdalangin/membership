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

    /**
     * The transaction handler instance.
     *
     * @since    1.0.2
     * @access   private
     * @var      WMP_Transactions    $transactions_handler    Handles transaction logic.
     */
    private $transactions_handler;

    public function __construct( $plugin_name, $version, $subscriptions_handler, $gateways_manager, $affiliates_handler, $referrals_handler, $transactions_handler ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->subscriptions_handler = $subscriptions_handler;
        $this->gateways_manager = $gateways_manager;
        $this->affiliates_handler = $affiliates_handler;
        $this->referrals_handler = $referrals_handler;
        $this->transactions_handler = $transactions_handler;

        require_once plugin_dir_path( __FILE__ ) . 'class-wmp-content-protection.php';
        $this->content_protection = new WMP_Content_Protection( $this->subscriptions_handler );

        require_once plugin_dir_path( __FILE__ ) . 'class-wmp-shortcodes.php';
        $this->shortcodes = new WMP_Shortcodes( $this->subscriptions_handler, $this->gateways_manager, $this->affiliates_handler );
    }

    /**
     * Handle the invoice download request.
     *
     * @since 1.0.2
     */
    public function handle_invoice_download() {
        if ( ! isset( $_GET['wmp_action'] ) || 'download_invoice' !== $_GET['wmp_action'] ) {
            return;
        }

        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wmp_download_invoice_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        if ( ! isset( $_GET['transaction_id'] ) ) {
            wp_die( 'Invalid transaction ID.' );
        }

        $transaction_id = absint( $_GET['transaction_id'] );

        // In a real plugin, you would add more robust security checks here
        // to ensure the current user is allowed to view this invoice.

        require_once WMP_PLUGIN_DIR . 'includes/vendor/fpdf/fpdf.php';
        $invoices = new WMP_Invoices();
        $invoices->generate_invoice( $transaction_id );
    }

    /**
     * Handle the secure file download request.
     *
     * @since 1.0.4
     */
    public function handle_secure_file_download() {
        if ( ! isset( $_GET['wmp_action'] ) || 'download_secure_file' !== $_GET['wmp_action'] ) {
            return;
        }

        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wmp_download_secure_file_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        if ( ! is_user_logged_in() ) {
            wp_die( 'You must be logged in to download this file.' );
        }

        $file_id = isset( $_GET['file_id'] ) ? absint( $_GET['file_id'] ) : 0;
        if ( ! $file_id ) {
            wp_die( 'Invalid file ID.' );
        }

        $access_handler = new WMP_Access_Handler();
        if ( ! $access_handler->has_access_to_file( get_current_user_id(), $file_id ) ) {
            wp_die( 'You do not have permission to download this file.' );
        }

        $file_path = get_post_meta( $file_id, '_wmp_secure_file_path', true );
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            wp_die( 'File not found or has been moved.' );
        }

        // --- File Delivery ---
        // This is a basic implementation. A more robust solution would use
        // x-sendfile or x-accel-redirect for better performance.
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/octet-stream' );
        header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate' );
        header( 'Pragma: public' );
        header( 'Content-Length: ' . filesize( $file_path ) );
        readfile( $file_path );
        exit;
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

        $change_subscription_id = isset( $_POST['change_subscription_id'] ) ? absint( $_POST['change_subscription_id'] ) : 0;

        // Security check if changing subscription
        if ( $change_subscription_id ) {
            $subscription = $this->subscriptions_handler->get_subscription( $change_subscription_id );
            if ( ! $subscription || $subscription->user_id != get_current_user_id() ) {
                wp_die( __( 'Invalid subscription change request.', 'wordpress-membership-pro' ) );
            }
        }

        // For offline payments, we create or update the subscription directly.
        if ( 'offline' === $gateway_id ) {
            $price = get_post_meta( $plan_id, '_wmp_price', true );
            $subscription_id = null;

            if ( $change_subscription_id ) {
                $this->subscriptions_handler->change_subscription_plan( $change_subscription_id, $plan_id );
                $subscription_id = $change_subscription_id;
                $redirect_url = add_query_arg( 'wmp_message', 'plan_changed_pending', home_url( '/account' ) );
            } else {
                $subscription_data = array(
                    'user_id'                 => get_current_user_id(),
                    'plan_id'                 => $plan_id,
                    'status'                  => 'on-hold',
                    'start_date'              => current_time( 'mysql' ),
                    'gateway'                 => 'offline',
                    'gateway_subscription_id' => '',
                );
                $subscription_id = $this->subscriptions_handler->create_subscription( $subscription_data );
                $redirect_url = add_query_arg( 'wmp_message', 'order_received', home_url( '/thank-you' ) );
            }

            // Log the transaction for offline payments
            if ( $subscription_id ) {
                $this->transactions_handler->create_transaction( array(
                    'subscription_id' => $subscription_id,
                    'user_id'         => get_current_user_id(),
                    'amount'          => $price,
                    'gateway'         => 'offline',
                    'transaction_id'  => 'offline_' . $subscription_id,
                    'status'          => 'on-hold',
                ) );
            }

            wp_redirect( $redirect_url );
            exit;
        }

        // For other gateways, we pass all data to their processing method.
        $data = [
            'plan_id'                => $plan_id,
            'user_id'                => get_current_user_id(),
            'checkout_page_id'       => get_the_ID(),
            'change_subscription_id' => $change_subscription_id,
        ];
        $gateway->process_payment( $data );
    }

    /**
     * Records a referral if a referral cookie is present when a transaction is created.
     *
     * @since    1.0.2
     * @param    int    $transaction_id      The ID of the new transaction.
     * @param    array  $transaction_data    The data for the transaction.
     */
    public function record_referral_on_transaction( $transaction_id, $transaction_data ) {
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
            'affiliate_id'   => $affiliate_id,
            'referring_url'  => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : '',
            'ip_address'     => $this->get_ip_address(),
            'transaction_id' => $transaction_id,
            'status'         => 'unpaid', // Referrals are unpaid until an admin processes them.
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
                $options = get_option( 'wmp_settings' );
                $cookie_expiration = isset( $options['affiliate_cookie_expiration'] ) ? absint( $options['affiliate_cookie_expiration'] ) : 30;
                // Set a cookie to track the referral.
                setcookie( 'wmp_ref_id', $affiliate_id, time() + ( 86400 * $cookie_expiration ), COOKIEPATH, COOKIE_DOMAIN );
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

        $options = get_option( 'wmp_settings' );
        $commission_rate = isset( $options['affiliate_commission_rate'] ) ? floatval( $options['affiliate_commission_rate'] ) : 20.00;

        $affiliate_data = array(
            'user_id'         => $user_id,
            'status'          => 'pending',
            'commission_rate' => $commission_rate,
        );

        $this->affiliates_handler->create_affiliate( $affiliate_data );

        $redirect_url = add_query_arg( 'wmp_message', 'affiliate_application_received', get_permalink() );
        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Process the affiliate payout request form submission.
     *
     * @since    1.0.4
     */
    public function process_payout_request() {
        if ( ! isset( $_POST['wmp_action'] ) || 'request_payout' !== $_POST['wmp_action'] ) {
            return;
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wmp_request_payout_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        $affiliate_id = isset( $_POST['affiliate_id'] ) ? absint( $_POST['affiliate_id'] ) : 0;
        $amount = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0.00;

        if ( ! $affiliate_id || $amount <= 0 ) {
            wp_die( 'Invalid payout request.' );
        }

        // Security check: ensure the current user is the one making the request
        $affiliate = $this->affiliates_handler->get_affiliate( $affiliate_id );
        if ( ! $affiliate || $affiliate->user_id != get_current_user_id() ) {
            wp_die( 'You do not have permission to make this request.' );
        }

        $payouts_handler = new WMP_Payouts();
        $payout_id = $payouts_handler->create_payout( array(
            'affiliate_id' => $affiliate_id,
            'amount'       => $amount,
        ) );

        if ( $payout_id ) {
            $referrals_handler = new WMP_Referrals();
            $referrals_handler->mark_referrals_as_paid( $affiliate_id );
        }

        $redirect_url = add_query_arg( 'wmp_message', 'payout_requested', get_permalink() );
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

        if ( ! isset( $_GET['token'] ) ) {
            return;
        }

        $order_id = sanitize_text_field( $_GET['token'] );

        // Verify the transient to make sure this is a legitimate return.
        $transient_key = 'wmp_paypal_order_' . $order_id;
        $transient_data = get_transient( $transient_key );

        if ( false === $transient_data || ! is_array( $transient_data ) || $transient_data['order_id'] !== $order_id ) {
            wp_die( 'Invalid PayPal order. Please try again.' );
        }

        // The transient is valid, so we can delete it now.
        delete_transient( $transient_key );

        $plan_id = $transient_data['plan_id'];
        $gateway = $this->gateways_manager->get_gateway( 'paypal' );
        $response = $gateway->execute_payment( $order_id );

        // If payment is completed, create the subscription.
        if ( $response && isset( $response['status'] ) && 'COMPLETED' === $response['status'] ) {
            $user_id = get_current_user_id();

            if ( ! $user_id ) {
                wp_die( __( 'Error: Missing user information during payment processing.', 'wordpress-membership-pro' ) );
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
                'gateway_subscription_id' => $transaction_id,
            );

            $subscription_id = $this->subscriptions_handler->create_subscription( $subscription_data );

            if ( $subscription_id ) {
                $this->transactions_handler->create_transaction( array(
                    'subscription_id' => $subscription_id,
                    'user_id'         => $user_id,
                    'amount'          => $response['purchase_units'][0]['amount']['value'],
                    'gateway'         => 'paypal',
                    'transaction_id'  => $transaction_id,
                    'status'          => 'completed',
                ) );
            }

            // Redirect to a success page.
            wp_redirect( home_url( '/thank-you?wmp_message=purchase_success' ) );
            exit;
        } else {
            wp_die( __( 'There was an error processing your payment with PayPal. Please try again.', 'wordpress-membership-pro' ) );
        }
    }

    /**
     * Handle the user's return from PayPal after approving a subscription.
     *
     * @since    1.0.0
     */
    public function handle_paypal_subscription_return() {
        if ( ! isset( $_GET['wmp_action'] ) || 'paypal_return_subscription' !== $_GET['wmp_action'] ) {
            return;
        }

        if ( ! isset( $_GET['subscription_id'] ) ) {
            return;
        }

        $paypal_subscription_id = sanitize_text_field( $_GET['subscription_id'] );

        // We will get the plan_id from a transient we stored during the initial call.
        $plan_id = get_transient( 'wmp_temp_plan_id_for_user_' . get_current_user_id() );
        if ( ! $plan_id ) {
            wp_die( 'Your session has expired. Please try the checkout process again.' );
        }
        delete_transient( 'wmp_temp_plan_id_for_user_' . get_current_user_id() );

        // Create a 'pending' subscription. The webhook will activate it.
        $user_id = get_current_user_id();
        $subscription_data = array(
            'user_id'                 => $user_id,
            'plan_id'                 => $plan_id,
            'status'                  => 'pending',
            'start_date'              => current_time( 'mysql' ),
            'gateway'                 => 'paypal',
            'gateway_subscription_id' => $paypal_subscription_id,
        );

        // Check for and calculate trial end date
        $trial_days = get_post_meta( $plan_id, '_wmp_trial_days', true );
        if ( ! empty( $trial_days ) && absint( $trial_days ) > 0 ) {
            $trial_end_date = date( 'Y-m-d H:i:s', strtotime( '+' . absint( $trial_days ) . ' days' ) );
            $subscription_data['trial_end'] = $trial_end_date;
        }

        $this->subscriptions_handler->create_subscription( $subscription_data );

        // Redirect to a success page, informing the user that the subscription is being finalized.
        wp_redirect( home_url( '/thank-you?wmp_message=purchase_pending' ) );
        exit;
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

        $subscription_id = $this->subscriptions_handler->create_subscription( $subscription_data );

        if ( $subscription_id ) {
            $price = get_post_meta( $plan_id, '_wmp_price', true );
            $this->transactions_handler->create_transaction( array(
                'subscription_id' => $subscription_id,
                'user_id'         => $user_id,
                'amount'          => $price,
                'gateway'         => 'gcash',
                'transaction_id'  => $transaction_id,
                'status'          => 'completed',
            ) );
        }

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
        add_shortcode( 'wmp_oto', array( $this->shortcodes, 'render_oto_shortcode' ) );
        add_shortcode( 'wmp_download', array( $this->shortcodes, 'render_download_shortcode' ) );
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
        wp_enqueue_script( $this->plugin_name . '-stripe', 'https://js.stripe.com/v3/', array(), null, true );
        wp_enqueue_script( $this->plugin_name, WMP_PLUGIN_URL . 'public/js/wmp-public.js', array( 'jquery', $this->plugin_name . '-stripe' ), $this->version, true );

        $options = get_option( 'wmp_settings' );
        $stripe_vars = array(
            'publishable_key' => isset( $options['stripe_publishable_key'] ) ? $options['stripe_publishable_key'] : ''
        );
        wp_localize_script( $this->plugin_name, 'wmp_stripe_vars', $stripe_vars );

        // Localize AJAX URL for the public script
        wp_localize_script( $this->plugin_name, 'wmp_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    /**
     * Handle the AJAX request for applying a coupon.
     *
     * @since 1.0.1
     */
    public function apply_coupon_ajax_handler() {
        check_ajax_referer( 'wmp_apply_coupon_nonce', 'nonce' );

        $coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( $_POST['coupon_code'] ) : '';
        $plan_id = isset( $_POST['plan_id'] ) ? absint( $_POST['plan_id'] ) : 0;

        if ( empty( $coupon_code ) || empty( $plan_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'wordpress-membership-pro' ) ) );
        }

        $plan_price = get_post_meta( $plan_id, '_wmp_price', true );
        $coupon = WMP_Coupons::get_coupon_by_code( $coupon_code );

        if ( ! $coupon ) {
            wp_send_json_error( array( 'message' => __( 'Invalid coupon code.', 'wordpress-membership-pro' ) ) );
        }

        $usage_limit = get_post_meta( $coupon->ID, '_wmp_usage_limit', true );
        $usage_count = get_post_meta( $coupon->ID, '_wmp_usage_count', true );

        if ( ! empty( $usage_limit ) && absint( $usage_count ) >= absint( $usage_limit ) ) {
            wp_send_json_error( array( 'message' => __( 'This coupon has reached its usage limit.', 'wordpress-membership-pro' ) ) );
        }

        $new_price = WMP_Coupons::calculate_discounted_price( $plan_price, $coupon );

        wp_send_json_success( array(
            'message' => __( 'Coupon applied successfully!', 'wordpress-membership-pro' ),
            'original_price' => (float) $plan_price,
            'discounted_price' => (float) $new_price,
            'original_price_formatted' => '$' . number_format( (float)$plan_price, 2 ),
            'discounted_price_formatted' => '$' . number_format( (float)$new_price, 2 ),
        ) );
    }
}
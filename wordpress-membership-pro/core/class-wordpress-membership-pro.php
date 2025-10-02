<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 * @author     Jules
 */
class WordPress_Membership_Pro {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WMP_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The subscription handler instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WMP_Subscriptions    $subscriptions_handler    Handles subscription logic.
     */
    protected $subscriptions_handler;

    /**
     * The gateway manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WMP_Gateways    $gateways_manager    Handles payment gateways.
     */
    protected $gateways_manager;

    /**
     * The affiliate handler instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WMP_Affiliates    $affiliates_handler    Handles affiliate logic.
     */
    protected $affiliates_handler;

    /**
     * The referrals handler instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WMP_Referrals    $referrals_handler    Handles referral logic.
     */
    protected $referrals_handler;

    /**
     * The transaction handler instance.
     *
     * @since    1.0.2
     * @access   protected
     * @var      WMP_Transactions    $transactions_handler    Handles transaction logic.
     */
    protected $transactions_handler;

    /**
     * The gamification handler instance.
     *
     * @since    1.0.8
     * @access   protected
     * @var      WMP_Gamification    $gamification_handler    Handles gamification logic.
     */
    protected $gamification_handler;

    /**
     * The access handler instance.
     *
     * @since    1.0.10
     * @access   protected
     * @var      WMP_Access_Handler    $access_handler    Handles access control logic.
     */
    protected $access_handler;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = WMP_VERSION;
        $this->plugin_name = 'wordpress-membership-pro';

        $this->load_dependencies();
        $this->subscriptions_handler = new WMP_Subscriptions();
        $this->transactions_handler  = new WMP_Transactions();
        $this->access_handler        = new WMP_Access_Handler( $this->subscriptions_handler );
        $this->gateways_manager      = new WMP_Gateways( $this->subscriptions_handler, $this->transactions_handler );
        $this->affiliates_handler    = new WMP_Affiliates();
        $this->referrals_handler     = new WMP_Referrals();
        $this->gamification_handler  = new WMP_Gamification( $this->referrals_handler, $this->affiliates_handler, $this->subscriptions_handler );
        $this->set_locale();
        $this->define_core_hooks();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once WMP_PLUGIN_DIR . 'includes/class-wmp-loader.php';
        require_once WMP_PLUGIN_DIR . 'includes/class-wmp-access-handler.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-cpts.php';
        require_once WMP_PLUGIN_DIR . 'admin/class-wmp-admin.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-coupons.php';
        require_once WMP_PLUGIN_DIR . 'includes/class-wmp-invoices.php';
        require_once WMP_PLUGIN_DIR . 'admin/class-wmp-subscriptions-list-table.php';
        require_once WMP_PLUGIN_DIR . 'admin/class-wmp-transactions-list-table.php';
        require_once WMP_PLUGIN_DIR . 'admin/class-wmp-payouts-list-table.php';
        require_once WMP_PLUGIN_DIR . 'public/class-wmp-public.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-subscriptions.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-transactions.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-reports.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-capabilities.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-gateways.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-emails.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-email-hooks.php';
        require_once WMP_PLUGIN_DIR . 'affiliates/class-wmp-affiliates.php';
        require_once WMP_PLUGIN_DIR . 'affiliates/class-wmp-referrals.php';
        require_once WMP_PLUGIN_DIR . 'affiliates/class-wmp-payouts.php';
        require_once WMP_PLUGIN_DIR . 'admin/class-wmp-affiliates-list-table.php';
        require_once WMP_PLUGIN_DIR . 'admin/class-wmp-payouts-list-table.php';
        require_once WMP_PLUGIN_DIR . 'api/class-wmp-api.php';
        require_once WMP_PLUGIN_DIR . 'integrations/class-wmp-integration.php';
        require_once WMP_PLUGIN_DIR . 'integrations/class-wmp-mailchimp-integration.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-gamification.php';
        require_once WMP_PLUGIN_DIR . 'includes/class-wmp-access-handler.php';

        $this->loader = new WMP_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        // This is a placeholder for future i18n functionality.
    }

    /**
     * Register all of the core hooks (context-agnostic) for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_core_hooks() {
        // Capability hooks
        $plugin_capabilities = new WMP_Capabilities( $this->subscriptions_handler );
        $this->loader->add_action( 'wmp_subscription_activated', $plugin_capabilities, 'on_subscription_activated', 10, 2 );
        $this->loader->add_action( 'wmp_subscription_cancelled', $plugin_capabilities, 'on_subscription_deactivated', 10, 2 );

        // Email hooks
        $plugin_email_hooks = new WMP_Email_Hooks();
        $this->loader->add_action( 'wmp_subscription_created', $plugin_email_hooks, 'on_subscription_created', 10, 3 );
        $this->loader->add_action( 'wmp_subscription_activated', $plugin_email_hooks, 'on_subscription_activated', 10, 2 );
        $this->loader->add_action( 'wmp_subscription_cancelled', $plugin_email_hooks, 'on_subscription_cancelled', 10, 2 );

        // REST API hooks
        $plugin_api = new WMP_API( $this->subscriptions_handler );
        $this->loader->add_action( 'rest_api_init', $plugin_api, 'register_routes' );

        // Block registration
        $this->loader->add_action( 'init', $this, 'register_blocks' );

        // Integration hooks
        $this->loader->add_action( 'wmp_subscription_activated', $this, 'subscribe_to_mailchimp_on_activation', 10, 2 );
        $this->loader->add_action( 'wmp_subscription_cancelled', $this, 'unsubscribe_from_mailchimp_on_cancellation', 10, 2 );

        // Gamification hooks
        $this->loader->add_action( 'wmp_subscription_activated', $this->gamification_handler, 'check_for_badges_on_activation', 10, 2 );
        $this->loader->add_action( 'wmp_referral_created', $this->gamification_handler, 'check_for_first_referral_badge', 10, 2 );

        // Cron hooks
        $this->loader->add_action( 'wmp_daily_cron', $this, 'run_daily_cron_jobs' );
        $this->loader->add_action( 'wmp_process_payment_retry', $this, 'process_payment_retry', 10, 1 );

        // Bonus content hooks
        $this->loader->add_action( 'wmp_subscription_activated', $this, 'grant_bonus_on_activation', 10, 2 );
    }

    /**
     * Grant access to a bonus file when a subscription is activated.
     *
     * @since 1.0.10
     * @param int $subscription_id The ID of the subscription.
     * @param int $user_id         The ID of the user.
     */
    public function grant_bonus_on_activation( $subscription_id, $user_id ) {
        $subscription = $this->subscriptions_handler->get_subscription( $subscription_id );
        if ( ! $subscription ) {
            return;
        }

        $bonus_file_id = get_post_meta( $subscription->plan_id, '_wmp_bonus_file_id', true );
        if ( empty( $bonus_file_id ) ) {
            return;
        }

        $bonus_files = get_user_meta( $user_id, '_wmp_bonus_files', true );
        if ( ! is_array( $bonus_files ) ) {
            $bonus_files = array();
        }

        if ( ! in_array( $bonus_file_id, $bonus_files ) ) {
            $bonus_files[] = $bonus_file_id;
            update_user_meta( $user_id, '_wmp_bonus_files', $bonus_files );
        }
    }

    /**
     * Run all daily cron jobs for the plugin.
     *
     * @since 1.0.9
     */
    public function run_daily_cron_jobs() {
        $this->gamification_handler->check_for_anniversary_badges();
    }

    /**
     * Process a scheduled payment retry.
     *
     * @since 1.0.9
     * @param int $subscription_id The ID of the subscription to retry payment for.
     */
    public function process_payment_retry( $subscription_id ) {
        $subscription = $this->subscriptions_handler->get_subscription( $subscription_id );

        if ( ! $subscription || 'on-hold' !== $subscription->status ) {
            return;
        }

        $gateway = $this->gateways_manager->get_gateway( $subscription->gateway );

        if ( $gateway && method_exists( $gateway, 'attempt_payment_retry' ) ) {
            // The gateway's method will handle the success/failure logic,
            // including updating subscription status via webhooks.
            $gateway->attempt_payment_retry( $subscription );
        }
    }

    /**
     * Subscribes a user to Mailchimp when their subscription is activated.
     *
     * @since    1.0.7
     * @param    int    $subscription_id      The ID of the subscription.
     * @param    int    $user_id              The ID of the user.
     */
    public function subscribe_to_mailchimp_on_activation( $subscription_id, $user_id ) {
        $options = get_option( 'wmp_settings' );
        $api_key = isset( $options['mailchimp_api_key'] ) ? $options['mailchimp_api_key'] : '';
        $list_id = isset( $options['mailchimp_list_id'] ) ? $options['mailchimp_list_id'] : '';

        if ( empty( $api_key ) || empty( $list_id ) ) {
            return;
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return;
        }

        $mailchimp = new WMP_Mailchimp_Integration();
        $mailchimp->add_subscriber( $user->user_email, $list_id );
    }

    /**
     * Unsubscribes a user from Mailchimp when their subscription is cancelled.
     *
     * @since    1.0.7
     * @param    int    $subscription_id      The ID of the subscription.
     * @param    int    $user_id              The ID of the user.
     */
    public function unsubscribe_from_mailchimp_on_cancellation( $subscription_id, $user_id ) {
        $options = get_option( 'wmp_settings' );
        $api_key = isset( $options['mailchimp_api_key'] ) ? $options['mailchimp_api_key'] : '';
        $list_id = isset( $options['mailchimp_list_id'] ) ? $options['mailchimp_list_id'] : '';

        if ( empty( $api_key ) || empty( $list_id ) ) {
            return;
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return;
        }

        $mailchimp = new WMP_Mailchimp_Integration();
        $mailchimp->remove_subscriber( $user->user_email, $list_id );
    }

    /**
     * Register all custom Gutenberg blocks.
     *
     * @since    1.0.5
     */
    public function register_blocks() {
        register_block_type( WMP_PLUGIN_DIR . 'blocks/plans' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_cpts = new WMP_CPTs();
        $this->loader->add_action( 'init', $plugin_cpts, 'register' );


        $plugin_admin = new WMP_Admin( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_boxes' );
        $this->loader->add_action( 'save_post', $plugin_admin, 'save_meta_boxes' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menus' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'process_subscription_actions' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'process_affiliate_actions' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'process_transaction_actions' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'process_export_actions' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'process_payout_actions' );
        $this->loader->add_action( 'admin_notices', $plugin_admin, 'check_stripe_library' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_admin_scripts' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new WMP_Public(
            $this->get_plugin_name(),
            $this->get_version(),
            $this->subscriptions_handler,
            $this->gateways_manager,
            $this->affiliates_handler,
            $this->referrals_handler,
            $this->transactions_handler,
            $this->gamification_handler,
            $this->access_handler
        );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        $plugin_coupons = new WMP_Coupons();
        $this->loader->add_action( 'init', $plugin_coupons, 'register_cpt' );
        $this->loader->add_action( 'add_meta_boxes', $plugin_coupons, 'add_meta_boxes' );
        $this->loader->add_action( 'save_post', $plugin_coupons, 'save_meta_box' );

        // Register shortcodes, content protection, and checkout processing
        $this->loader->add_action( 'init', $plugin_public, 'track_referral_visit' );
        $this->loader->add_action( 'wmp_transaction_created', $plugin_public, 'record_referral_on_transaction', 10, 2 );
        $this->loader->add_action( 'wp_ajax_wmp_apply_coupon', $plugin_public, 'apply_coupon_ajax_handler' );
        $this->loader->add_action( 'wp_ajax_nopriv_wmp_apply_coupon', $plugin_public, 'apply_coupon_ajax_handler' );
        $this->loader->add_action( 'wp_ajax_wmp_create_setup_intent', $plugin_public, 'create_setup_intent_ajax_handler' );
        $this->loader->add_action( 'wp_ajax_wmp_update_payment_method', $plugin_public, 'update_payment_method_ajax_handler' );
        $this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
        $this->loader->add_action( 'init', $plugin_public, 'process_checkout' );
        $this->loader->add_action( 'init', $plugin_public, 'handle_invoice_download' );
        $this->loader->add_action( 'init', $plugin_public, 'handle_secure_file_download' );
        $this->loader->add_action( 'init', $plugin_public, 'handle_paypal_return' );
        $this->loader->add_action( 'init', $plugin_public, 'handle_gcash_return' );
        $this->loader->add_action( 'init', $plugin_public, 'handle_paypal_subscription_return' );
        $this->loader->add_action( 'init', $plugin_public, 'process_affiliate_registration' );
        $this->loader->add_action( 'init', $plugin_public, 'process_payout_request' );
        $this->loader->add_action( 'init', $plugin_public, 'process_lead_capture' );
        $this->loader->add_filter( 'the_content', $plugin_public, 'filter_the_content' );

        // bbPress Integration
        $this->loader->add_filter( 'bbp_user_can_view_forum', $plugin_public, 'filter_bbpress_forum_access', 10, 3 );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    WMP_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
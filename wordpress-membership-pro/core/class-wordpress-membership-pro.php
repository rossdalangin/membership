<?php
/**
 * The core plugin class.
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 * @author     Jules
 */
class WordPress_Membership_Pro {

    protected $loader;
    protected $plugin_name;
    protected $version;
    protected $subscriptions_handler;
    protected $gateways_manager;
    protected $affiliates_handler;
    protected $referrals_handler;
    protected $transactions_handler;

    public function __construct() {
        $this->version = WMP_VERSION;
        $this->plugin_name = 'wordpress-membership-pro';

        $this->load_dependencies();

        // Instantiate handlers
        $this->subscriptions_handler = new WMP_Subscriptions();
        $this->transactions_handler  = new WMP_Transactions();
        $this->affiliates_handler    = new WMP_Affiliates();
        $this->referrals_handler     = new WMP_Referrals();
        $this->gateways_manager      = new WMP_Gateways( $this->subscriptions_handler, $this->transactions_handler );

        $this->set_locale();
        $this->define_core_hooks();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // Core
        require_once WMP_PLUGIN_DIR . 'includes/class-wmp-loader.php';
        require_once WMP_PLUGIN_DIR . 'includes/class-wmp-access-handler.php';
        require_once WMP_PLUGIN_DIR . 'includes/class-wmp-invoices.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-cpts.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-subscriptions.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-transactions.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-coupons.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-reports.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-capabilities.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-emails.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-email-hooks.php';

        // Gateways
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-gateways.php';
        require_once WMP_PLUGIN_DIR . 'gateways/class-wmp-gateway-offline.php';
        require_once WMP_PLUGIN_DIR . 'gateways/class-wmp-gateway-paypal.php';
        require_once WMP_PLUGIN_DIR . 'gateways/class-wmp-gateway-gcash.php';

        // Affiliates
        require_once WMP_PLUGIN_DIR . 'affiliates/class-wmp-affiliates.php';
        require_once WMP_PLUGIN_DIR . 'affiliates/class-wmp-referrals.php';
        require_once WMP_PLUGIN_DIR . 'affiliates/class-wmp-payouts.php';

        // Admin
        if ( is_admin() ) {
            require_once WMP_PLUGIN_DIR . 'admin/class-wmp-admin.php';
            require_once WMP_PLUGIN_DIR . 'admin/class-wmp-subscriptions-list-table.php';
            require_once WMP_PLUGIN_DIR . 'admin/class-wmp-transactions-list-table.php';
            require_once WMP_PLUGIN_DIR . 'admin/class-wmp-affiliates-list-table.php';
            require_once WMP_PLUGIN_DIR . 'admin/class-wmp-payouts-list-table.php';
        }

        // Public
        require_once WMP_PLUGIN_DIR . 'public/class-wmp-public.php';

        // API
        require_once WMP_PLUGIN_DIR . 'api/class-wmp-api.php';

        $this->loader = new WMP_Loader();
    }

    private function set_locale() {
        // Placeholder for i18n
    }

    private function define_core_hooks() {
        $plugin_capabilities = new WMP_Capabilities( $this->subscriptions_handler );
        $this->loader->add_action( 'wmp_subscription_activated', $plugin_capabilities, 'on_subscription_activated', 10, 2 );
        $this->loader->add_action( 'wmp_subscription_cancelled', $plugin_capabilities, 'on_subscription_deactivated', 10, 2 );

        $plugin_email_hooks = new WMP_Email_Hooks();
        $this->loader->add_action( 'wmp_subscription_created', $plugin_email_hooks, 'on_subscription_created', 10, 3 );
        $this->loader->add_action( 'wmp_subscription_activated', $plugin_email_hooks, 'on_subscription_activated', 10, 2 );
        $this->loader->add_action( 'wmp_subscription_cancelled', $plugin_email_hooks, 'on_subscription_cancelled', 10, 2 );

        $plugin_api = new WMP_API( $this->subscriptions_handler, $this->transactions_handler );
        $this->loader->add_action( 'rest_api_init', $plugin_api, 'register_routes' );

        $this->loader->add_action( 'init', $this, 'register_blocks' );
    }

    public function register_blocks() {
        register_block_type( WMP_PLUGIN_DIR . 'blocks/plans' );
    }

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
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_admin_scripts' );
    }

    private function define_public_hooks() {
        $plugin_public = new WMP_Public( $this->get_plugin_name(), $this->get_version(), $this->subscriptions_handler, $this->gateways_manager, $this->affiliates_handler, $this->referrals_handler, $this->transactions_handler );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        $plugin_coupons = new WMP_Coupons();
        $this->loader->add_action( 'init', $plugin_coupons, 'register_cpt' );
        $this->loader->add_action( 'add_meta_boxes', $plugin_coupons, 'add_meta_boxes' );
        $this->loader->add_action( 'save_post', $plugin_coupons, 'save_meta_box' );

        $this->loader->add_action( 'init', $plugin_public, 'track_referral_visit' );
        $this->loader->add_action( 'wmp_transaction_created', $plugin_public, 'record_referral_on_transaction', 10, 2 );
        $this->loader->add_action( 'wp_ajax_wmp_apply_coupon', $plugin_public, 'apply_coupon_ajax_handler' );
        $this->loader->add_action( 'wp_ajax_nopriv_wmp_apply_coupon', $plugin_public, 'apply_coupon_ajax_handler' );
        $this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
        $this->loader->add_action( 'init', $plugin_public, 'process_checkout' );
        $this->loader->add_action( 'init', $plugin_public, 'handle_invoice_download' );
        $this->loader->add_action( 'init', $plugin_public, 'handle_secure_file_download' );
        $this->loader->add_action( 'init', $plugin_public, 'handle_paypal_return' );
        $this->loader->add_action( 'init', $plugin_public, 'handle_gcash_return' );
        $this->loader->add_action( 'init', $plugin_public, 'handle_paypal_subscription_return' );
        $this->loader->add_action( 'init', $plugin_public, 'process_affiliate_registration' );
        $this->loader->add_action( 'init', $plugin_public, 'process_payout_request' );
        $this->loader->add_filter( 'the_content', $plugin_public, 'filter_the_content' );
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}
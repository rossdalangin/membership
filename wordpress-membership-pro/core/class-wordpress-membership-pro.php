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
        $this->gateways_manager = new WMP_Gateways();
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
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-cpts.php';
        require_once WMP_PLUGIN_DIR . 'admin/class-wmp-admin.php';
        require_once WMP_PLUGIN_DIR . 'admin/class-wmp-subscriptions-list-table.php';
        require_once WMP_PLUGIN_DIR . 'public/class-wmp-public.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-subscriptions.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-capabilities.php';
        require_once WMP_PLUGIN_DIR . 'core/class-wmp-gateways.php';

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
        $plugin_capabilities = new WMP_Capabilities( $this->subscriptions_handler );
        $this->loader->add_action( 'wmp_subscription_activated', $plugin_capabilities, 'on_subscription_activated', 10, 2 );
        $this->loader->add_action( 'wmp_subscription_cancelled', $plugin_capabilities, 'on_subscription_deactivated', 10, 2 );
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
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new WMP_Public( $this->get_plugin_name(), $this->get_version(), $this->subscriptions_handler, $this->gateways_manager );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        // Register shortcodes, content protection, and checkout processing
        $this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
        $this->loader->add_action( 'init', $plugin_public, 'process_checkout' );
        $this->loader->add_action( 'init', $plugin_public, 'handle_paypal_return' );
        $this->loader->add_action( 'init', $plugin_public, 'handle_gcash_return' );
        $this->loader->add_filter( 'the_content', $plugin_public, 'filter_the_content' );
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
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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version, $subscriptions_handler ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->subscriptions_handler = $subscriptions_handler;

        require_once plugin_dir_path( __FILE__ ) . 'class-wmp-content-protection.php';
        $this->content_protection = new WMP_Content_Protection();

        require_once plugin_dir_path( __FILE__ ) . 'class-wmp-shortcodes.php';
        $this->shortcodes = new WMP_Shortcodes();
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

        $user_id = get_current_user_id();

        // This is where a real payment gateway would be called.
        // For our simulation, we'll directly create and activate the subscription.
        $subscription_data = array(
            'user_id'                 => $user_id,
            'plan_id'                 => $plan_id,
            'status'                  => 'active',
            'start_date'              => current_time( 'mysql' ),
            'gateway'                 => 'simulated',
            'gateway_subscription_id' => 'sim-' . uniqid(),
        );

        $this->subscriptions_handler->create_subscription( $subscription_data );

        // Redirect to the account page with a success message.
        // A real plugin would have a setting for this page.
        wp_redirect( home_url( '/account?wmp_message=purchase_success' ) );
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
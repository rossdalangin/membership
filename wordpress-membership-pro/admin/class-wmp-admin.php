<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/admin
 * @author     Jules
 */
class WMP_Admin {

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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Add all meta boxes.
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        // Plan Details Meta Box
        add_meta_box(
            'wmp_plan_details',
            __( 'Plan Details', 'wordpress-membership-pro' ),
            array( $this, 'render_plan_details_meta_box' ),
            'wmp_membership_plan',
            'normal',
            'high'
        );

        // Forum Access Meta Box
        add_meta_box(
            'wmp_forum_access',
            __( 'Forum Access', 'wordpress-membership-pro' ),
            array( $this, 'render_forum_access_meta_box' ),
            'wmp_membership_plan',
            'normal',
            'default'
        );

        // Bonus Content Meta Box
        add_meta_box(
            'wmp_bonus_content',
            __( 'Bonus Content', 'wordpress-membership-pro' ),
            array( $this, 'render_bonus_content_meta_box' ),
            'wmp_membership_plan',
            'normal',
            'default'
        );

        // Content Protection Meta Box
        $post_types = get_post_types_by_support( 'editor' );
        foreach ( $post_types as $post_type ) {
            if ( $post_type === 'wmp_membership_plan' || $post_type === 'wmp_payment' ) {
                continue;
            }
            add_meta_box(
                'wmp_content_protection',
                __( 'Membership Access', 'wordpress-membership-pro' ),
                array( $this, 'render_content_protection_meta_box' ),
                $post_type,
                'side',
                'default'
            );
        }

        // Secure File Meta Box
        add_meta_box(
            'wmp_secure_file_details',
            __( 'File Details & Restrictions', 'wordpress-membership-pro' ),
            array( $this, 'render_secure_file_meta_box' ),
            'wmp_secure_file',
            'normal',
            'high'
        );

        // Contest Details Meta Box
        add_meta_box(
            'wmp_contest_details',
            __( 'Contest Details', 'wordpress-membership-pro' ),
            array( $this, 'render_contest_details_meta_box' ),
            'wmp_contest',
            'normal',
            'high'
        );

        // Badge Trigger Meta Box
        add_meta_box(
            'wmp_badge_trigger',
            __( 'Badge Trigger', 'wordpress-membership-pro' ),
            array( $this, 'render_badge_trigger_meta_box' ),
            'wmp_badge',
            'normal',
            'high'
        );
    }

    /**
     * Add the settings and subscriptions pages to the admin menu.
     *
     * @since    1.0.0
     */
    public function add_admin_menus() {
        // Subscriptions Page
        add_menu_page(
            __( 'Subscriptions', 'wordpress-membership-pro' ),
            __( 'Subscriptions', 'wordpress-membership-pro' ),
            'manage_options',
            'wmp-subscriptions',
            array( $this, 'render_subscriptions_page' ),
            'dashicons-money-alt',
            25
        );

        // Settings Submenu Page
        add_submenu_page(
            'edit.php?post_type=wmp_membership_plan',
            __( 'Membership Settings', 'wordpress-membership-pro' ),
            __( 'Settings', 'wordpress-membership-pro' ),
            'manage_options',
            'wmp-settings',
            array( $this, 'render_settings_page' )
        );

        // Affiliates Submenu Page
        add_submenu_page(
            'wmp-subscriptions', // Parent slug
            __( 'Affiliates', 'wordpress-membership-pro' ),
            __( 'Affiliates', 'wordpress-membership-pro' ),
            'manage_options',
            'wmp-affiliates',
            array( $this, 'render_affiliates_page' )
        );

        // Transactions Submenu Page
        add_submenu_page(
            'wmp-subscriptions', // Parent slug
            __( 'Transactions', 'wordpress-membership-pro' ),
            __( 'Transactions', 'wordpress-membership-pro' ),
            'manage_options',
            'wmp-transactions',
            array( $this, 'render_transactions_page' )
        );

        // Payouts Submenu Page
        add_submenu_page(
            'wmp-affiliates', // Parent slug
            __( 'Payouts', 'wordpress-membership-pro' ),
            __( 'Payouts', 'wordpress-membership-pro' ),
            'manage_options',
            'wmp-payouts',
            array( $this, 'render_payouts_page' )
        );

        // Reports Page
        add_menu_page(
            __( 'Reports', 'wordpress-membership-pro' ),
            __( 'Reports', 'wordpress-membership-pro' ),
            'manage_options',
            'wmp-reports',
            array( $this, 'render_reports_page' ),
            'dashicons-chart-area',
            26
        );
    }

    /**
     * Render the reports page.
     *
     * @since    1.0.5
     */
    public function render_reports_page() {
        $reports_handler = new WMP_Reports();
        $mrr = $reports_handler->get_mrr();
        $churn_rate = $reports_handler->get_churn_rate();
        $ltv = $reports_handler->get_ltv();
        ?>
        <div class="wrap">
            <h1><?php _e( 'Reports', 'wordpress-membership-pro' ); ?></h1>

            <div style="display: flex; gap: 20px;">
                <div class="wmp-reports-widget">
                    <h2><?php _e( 'Monthly Recurring Revenue (MRR)', 'wordpress-membership-pro' ); ?></h2>
                    <p class="wmp-reports-stat"><?php echo '$' . number_format( $mrr, 2 ); ?></p>
                </div>

                <div class="wmp-reports-widget">
                    <h2><?php _e( 'Churn Rate (Last 30 Days)', 'wordpress-membership-pro' ); ?></h2>
                    <p class="wmp-reports-stat"><?php echo number_format( $churn_rate, 2 ); ?>%</p>
                </div>

                <div class="wmp-reports-widget">
                    <h2><?php _e( 'Customer Lifetime Value (LTV)', 'wordpress-membership-pro' ); ?></h2>
                    <p class="wmp-reports-stat"><?php echo '$' . number_format( $ltv, 2 ); ?></p>
                </div>
            </div>

            <hr/>

            <h2><?php _e( 'Export Data', 'wordpress-membership-pro' ); ?></h2>
            <form method="post" action="">
                <input type="hidden" name="wmp_action" value="export_transactions" />
                <?php wp_nonce_field( 'wmp_export_nonce', '_wpnonce_export' ); ?>
                <button type="submit" class="button"><?php _e( 'Export Transactions to CSV', 'wordpress-membership-pro' ); ?></button>
            </form>

        </div>
        <?php
    }

    /**
     * Render the payouts list table page.
     *
     * @since    1.0.4
     */
    public function render_payouts_page() {
        $payouts_list_table = new WMP_Payouts_List_Table();
        $payouts_list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Payouts', 'wordpress-membership-pro' ); ?></h1>
            <form method="post">
                <?php
                $payouts_list_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the transactions list table page.
     *
     * @since    1.0.3
     */
    public function render_transactions_page() {
        $transactions_list_table = new WMP_Transactions_List_Table();
        $transactions_list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Transactions', 'wordpress-membership-pro' ); ?></h1>
            <form method="post">
                <?php
                $transactions_list_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the affiliates list table page.
     *
     * @since    1.0.0
     */
    public function render_affiliates_page() {
        $affiliates_list_table = new WMP_Affiliates_List_Table();
        $affiliates_list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Affiliates', 'wordpress-membership-pro' ); ?></h1>
            <form method="post">
                <?php
                $affiliates_list_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the subscriptions list table page.
     *
     * @since    1.0.0
     */
    public function render_subscriptions_page() {
        $subscriptions_list_table = new WMP_Subscriptions_List_Table();
        $subscriptions_list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <?php $this->display_admin_notices(); ?>
            <form method="post">
                <?php
                $subscriptions_list_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Process the subscription list table actions.
     *
     * @since    1.0.0
     */
    public function process_subscription_actions() {
        if ( ! isset( $_GET['page'] ) || 'wmp-subscriptions' !== $_GET['page'] ) {
            return;
        }

        if ( ! isset( $_GET['action'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wmp_subscription_action_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        $action = sanitize_key( $_GET['action'] );
        $subscription_id = absint( $_GET['subscription'] );
        $subscriptions_handler = new WMP_Subscriptions();
        $redirect_url = admin_url( 'admin.php?page=wmp-subscriptions' );

        switch ( $action ) {
            case 'activate':
                $subscriptions_handler->update_status( $subscription_id, 'active' );
                $redirect_url = add_query_arg( 'wmp_message', 'activated', $redirect_url );
                break;
            case 'cancel':
                $subscriptions_handler->update_status( $subscription_id, 'cancelled' );
                $redirect_url = add_query_arg( 'wmp_message', 'cancelled', $redirect_url );
                break;
            case 'delete':
                $subscriptions_handler->delete_subscription( $subscription_id );
                $redirect_url = add_query_arg( 'wmp_message', 'deleted', $redirect_url );
                break;
        }

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Process the payout list table actions.
     *
     * @since    1.0.4
     */
    public function process_payout_actions() {
        if ( ! isset( $_GET['page'] ) || 'wmp-payouts' !== $_GET['page'] ) {
            return;
        }

        $action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : false;
        $payout_id = isset( $_GET['payout_id'] ) ? absint( $_GET['payout_id'] ) : 0;

        if ( ! $payout_id || ! $action ) {
            return;
        }

        $payouts_handler = new WMP_Payouts();
        $redirect_url = admin_url( 'admin.php?page=wmp-payouts' );

        if ( 'wmp_approve_payout' === $action ) {
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wmp_approve_payout_nonce' ) ) {
                wp_die( 'Security check failed.' );
            }
            $payouts_handler->update_payout_status( $payout_id, 'completed' );
            $redirect_url = add_query_arg( 'wmp_message', 'payout_approved', $redirect_url );
        }

        if ( 'wmp_reject_payout' === $action ) {
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wmp_reject_payout_nonce' ) ) {
                wp_die( 'Security check failed.' );
            }
            $payouts_handler->update_payout_status( $payout_id, 'rejected' );
            $redirect_url = add_query_arg( 'wmp_message', 'payout_rejected', $redirect_url );
        }

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Process the transaction list table actions.
     *
     * @since    1.0.3
     */
    public function process_transaction_actions() {
        if ( ! isset( $_GET['page'] ) || 'wmp-transactions' !== $_GET['page'] ) {
            return;
        }

        if ( ! isset( $_GET['action'] ) || 'wmp_refund_transaction' !== $_GET['action'] ) {
            return;
        }

        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wmp_refund_transaction_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        $transaction_id = isset( $_GET['transaction_id'] ) ? absint( $_GET['transaction_id'] ) : 0;
        if ( ! $transaction_id ) {
            wp_die( 'Invalid transaction ID.' );
        }

        $transactions_handler = new WMP_Transactions();
        $transactions_handler->refund_transaction( $transaction_id );

        // --- IMPORTANT: GATEWAY INTEGRATION REQUIRED ---
        // The above code only marks the transaction as "refunded" in the local database.
        // For a complete refund solution, you must also integrate with the relevant payment gateway's API
        // to process the actual financial transaction. This would involve fetching the transaction details,
        // identifying the gateway used (e.g., 'stripe', 'paypal'), and calling a method like `$gateway->process_refund( $transaction_id )`.
        // This is a placeholder for that future development.
        // --- END OF IMPORTANT NOTE ---

        $redirect_url = add_query_arg( array(
            'page' => 'wmp-transactions',
            'wmp_message' => 'transaction_refunded'
        ), admin_url( 'admin.php' ) );

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Process the export actions from the reports page.
     *
     * @since    1.0.5
     */
    public function process_export_actions() {
        if ( ! isset( $_POST['wmp_action'] ) || 'export_transactions' !== $_POST['wmp_action'] ) {
            return;
        }

        if ( ! isset( $_POST['_wpnonce_export'] ) || ! wp_verify_nonce( $_POST['_wpnonce_export'], 'wmp_export_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        $reports_handler = new WMP_Reports();
        $reports_handler->export_transactions_to_csv();
    }

    /**
     * Process the affiliate list table actions.
     *
     * @since    1.0.0
     */
    public function process_affiliate_actions() {
        if ( ! isset( $_GET['page'] ) || 'wmp-affiliates' !== $_GET['page'] ) {
            return;
        }

        if ( ! isset( $_GET['action'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wmp_affiliate_action_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        $action = sanitize_key( $_GET['action'] );
        $affiliate_id = absint( $_GET['affiliate'] );
        $affiliates_handler = new WMP_Affiliates();
        $redirect_url = admin_url( 'admin.php?page=wmp-affiliates' );

        switch ( $action ) {
            case 'approve_affiliate':
                $affiliates_handler->update_status( $affiliate_id, 'active' );
                $redirect_url = add_query_arg( 'wmp_message', 'affiliate_approved', $redirect_url );
                break;
            case 'reject_affiliate':
                $affiliates_handler->update_status( $affiliate_id, 'rejected' );
                $redirect_url = add_query_arg( 'wmp_message', 'affiliate_rejected', $redirect_url );
                break;
        }

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Render the meta box for secure file details.
     *
     * @since    1.0.4
     * @param    WP_Post    $post    The post object.
     */
    public function render_secure_file_meta_box( $post ) {
        wp_nonce_field( 'wmp_save_secure_file_details', 'wmp_secure_file_details_nonce' );

        $file_path = get_post_meta( $post->ID, '_wmp_secure_file_path', true );
        $restricted_to = get_post_meta( $post->ID, '_wmp_restricted_to_plans', true );
        $restricted_to = is_array( $restricted_to ) ? $restricted_to : array();

        // --- File Uploader ---
        echo '<h4>' . __( 'File Upload', 'wordpress-membership-pro' ) . '</h4>';
        echo '<p>';
        echo '<input type="hidden" name="wmp_secure_file_attachment_id" id="wmp_secure_file_attachment_id" value="" />';
        echo '<input type="text" id="wmp_file_display_name" value="' . esc_attr( basename( $file_path ) ) . '" style="width: 70%;" readonly />';
        echo ' <button type="button" id="wmp_upload_file_button" class="button">' . __( 'Select or Upload File', 'wordpress-membership-pro' ) . '</button>';
        echo '</p>';
        echo '<p class="description">' . __( 'Select a file from the media library. It will be moved to a secure location upon saving.', 'wordpress-membership-pro' ) . '</p>';

        // --- Plan Restriction ---
        echo '<hr><h4>' . __( 'Access Restriction', 'wordpress-membership-pro' ) . '</h4>';
        $plans_query = new WP_Query( array(
            'post_type'      => 'wmp_membership_plan',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        if ( $plans_query->have_posts() ) {
            echo '<div style="max-height: 200px; overflow-y: scroll; border: 1px solid #ddd; padding: 10px;">';
            while ( $plans_query->have_posts() ) {
                $plans_query->the_post();
                $plan_id = get_the_ID();
                echo '<label><input type="checkbox" name="wmp_restricted_to_plans[]" value="' . esc_attr( $plan_id ) . '" ' . checked( in_array( $plan_id, $restricted_to ), true, false ) . '> ' . esc_html( get_the_title() ) . '</label><br/>';
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>' . __( 'No membership plans found.', 'wordpress-membership-pro' ) . '</p>';
        }
    }

    /**
     * Enqueue scripts and styles for the admin area.
     *
     * @since 1.0.4
     * @param string $hook The current admin page.
     */
    public function enqueue_admin_scripts( $hook ) {
        global $post;
        if ( ( 'post-new.php' === $hook || 'post.php' === $hook ) && isset( $post->post_type ) && 'wmp_secure_file' === $post->post_type ) {
            wp_enqueue_media();
            wp_enqueue_script( $this->plugin_name . '-admin', WMP_PLUGIN_URL . 'admin/js/wmp-admin.js', array( 'jquery' ), $this->version, false );
        }

        // Enqueue block editor script
        if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
            wp_enqueue_script(
                'wmp-plans-block-editor',
                WMP_PLUGIN_URL . 'blocks/plans/index.js',
                array( 'wp-blocks', 'wp-i18n', 'wp-element' ),
                WMP_VERSION,
                true
            );
        }
    }

    /**
     * Display admin notices.
     *
     * @since    1.0.0
     */
    private function display_admin_notices() {
        if ( ! isset( $_GET['wmp_message'] ) ) {
            return;
        }

        $message = '';
        switch ( sanitize_key( $_GET['wmp_message'] ) ) {
            case 'activated':
                $message = __( 'Subscription activated successfully.', 'wordpress-membership-pro' );
                break;
            case 'cancelled':
                $message = __( 'Subscription cancelled successfully.', 'wordpress-membership-pro' );
                break;
            case 'deleted':
                $message = __( 'Subscription deleted successfully.', 'wordpress-membership-pro' );
                break;
            case 'affiliate_approved':
                $message = __( 'Affiliate approved successfully.', 'wordpress-membership-pro' );
                break;
            case 'affiliate_rejected':
                $message = __( 'Affiliate rejected successfully.', 'wordpress-membership-pro' );
                break;
            case 'transaction_refunded':
                $message = __( 'Transaction refunded successfully.', 'wordpress-membership-pro' );
                break;
            case 'payout_approved':
                $message = __( 'Payout approved successfully.', 'wordpress-membership-pro' );
                break;
            case 'payout_rejected':
                $message = __( 'Payout rejected successfully.', 'wordpress-membership-pro' );
                break;
        }

        if ( ! empty( $message ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
        }
    }

    /**
     * Check if the Stripe PHP library is loaded and display a notice if it is not.
     *
     * @since 1.0.1
     */
    public function check_stripe_library() {
        $options = get_option( 'wmp_settings' );
        $stripe_enabled = ! empty( $options['stripe_publishable_key'] ) && ! empty( $options['stripe_secret_key'] );

        if ( $stripe_enabled && ! class_exists( '\Stripe\Stripe' ) ) {
            $message = sprintf(
                // translators: %s is a link to the Stripe PHP library on GitHub.
                __( '<strong>WordPress Membership Pro:</strong> The Stripe gateway is enabled, but the Stripe PHP library is not installed. Please install it via Composer or include it in your project. You can find the library <a href="%s" target="_blank">here</a>.', 'wordpress-membership-pro' ),
                'https://github.com/stripe/stripe-php'
            );
            echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
        }
    }

    /**
     * Render the settings page.
     *
     * @since    1.0.0
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'wmp_settings_group' );
                do_settings_sections( 'wmp-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register the settings and their fields.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting( 'wmp_settings_group', 'wmp_settings' );

        // Gateways Section
        add_settings_section(
            'wmp_settings_gateways',
            __( 'Payment Gateways', 'wordpress-membership-pro' ),
            '__return_false', // No callback needed for the section description
            'wmp-settings'
        );

        // Stripe Settings
        add_settings_field(
            'wmp_stripe_publishable_key',
            __( 'Stripe Publishable Key', 'wordpress-membership-pro' ),
            array( $this, 'render_text_input' ),
            'wmp-settings',
            'wmp_settings_gateways',
            array(
                'label_for' => 'wmp_stripe_publishable_key',
                'option_name' => 'wmp_settings',
                'key' => 'stripe_publishable_key',
            )
        );
        add_settings_field(
            'wmp_stripe_secret_key',
            __( 'Stripe Secret Key', 'wordpress-membership-pro' ),
            array( $this, 'render_text_input' ),
            'wmp-settings',
            'wmp_settings_gateways',
            array(
                'label_for' => 'wmp_stripe_secret_key',
                'option_name' => 'wmp_settings',
                'key' => 'stripe_secret_key',
            )
        );

        // Offline Payment Settings
        add_settings_field(
            'wmp_offline_instructions',
            __( 'Offline Payment Instructions', 'wordpress-membership-pro' ),
            array( $this, 'render_textarea_input' ),
            'wmp-settings',
            'wmp_settings_gateways',
            array(
                'label_for' => 'wmp_offline_instructions',
                'option_name' => 'wmp_settings',
                'key' => 'offline_instructions',
                'description' => __( 'These instructions will be shown to users after they choose the offline payment method.', 'wordpress-membership-pro' ),
            )
        );

        // PayPal Settings
        add_settings_field(
            'wmp_paypal_mode',
            __( 'PayPal Mode', 'wordpress-membership-pro' ),
            array( $this, 'render_select_input' ),
            'wmp-settings',
            'wmp_settings_gateways',
            array(
                'label_for' => 'wmp_paypal_mode',
                'option_name' => 'wmp_settings',
                'key' => 'paypal_mode',
                'options' => array(
                    'sandbox' => __( 'Sandbox', 'wordpress-membership-pro' ),
                    'live' => __( 'Live', 'wordpress-membership-pro' ),
                ),
            )
        );
        add_settings_field(
            'wmp_paypal_client_id',
            __( 'PayPal Client ID', 'wordpress-membership-pro' ),
            array( $this, 'render_text_input' ),
            'wmp-settings',
            'wmp_settings_gateways',
            array(
                'label_for' => 'wmp_paypal_client_id',
                'option_name' => 'wmp_settings',
                'key' => 'paypal_client_id',
            )
        );
        add_settings_field(
            'wmp_paypal_secret_key',
            __( 'PayPal Secret Key', 'wordpress-membership-pro' ),
            array( $this, 'render_text_input' ),
            'wmp-settings',
            'wmp_settings_gateways',
            array(
                'label_for' => 'wmp_paypal_secret_key',
                'option_name' => 'wmp_settings',
                'key' => 'paypal_secret_key',
            )
        );
        add_settings_field(
            'wmp_paypal_webhook_url',
            __( 'PayPal Webhook URL', 'wordpress-membership-pro' ),
            array( $this, 'render_static_text' ),
            'wmp-settings',
            'wmp_settings_gateways',
            array(
                'text' => get_rest_url( null, 'wmp/v1/webhooks/paypal' ),
                'description' => __( 'Add this URL to your PayPal account to enable automated subscription updates.', 'wordpress-membership-pro' ),
            )
        );

        // GCash Settings
        add_settings_field(
            'wmp_gcash_public_key',
            __( 'GCash Public Key', 'wordpress-membership-pro' ),
            array( $this, 'render_text_input' ),
            'wmp-settings',
            'wmp_settings_gateways',
            array(
                'label_for' => 'wmp_gcash_public_key',
                'option_name' => 'wmp_settings',
                'key' => 'gcash_public_key',
            )
        );
        add_settings_field(
            'wmp_gcash_secret_key',
            __( 'GCash Secret Key', 'wordpress-membership-pro' ),
            array( $this, 'render_text_input' ),
            'wmp-settings',
            'wmp_settings_gateways',
            array(
                'label_for' => 'wmp_gcash_secret_key',
                'option_name' => 'wmp_settings',
                'key' => 'gcash_secret_key',
            )
        );

        // Emails Section
        add_settings_section(
            'wmp_settings_emails',
            __( 'Email Notifications', 'wordpress-membership-pro' ),
            '__return_false',
            'wmp-settings'
        );

        add_settings_field(
            'wmp_email_subscription_activated',
            __( 'Subscription Activated Email', 'wordpress-membership-pro' ),
            array( $this, 'render_checkbox_input' ),
            'wmp-settings',
            'wmp_settings_emails',
            array(
                'label_for' => 'wmp_email_subscription_activated',
                'option_name' => 'wmp_settings',
                'key' => 'email_subscription_activated_enabled',
                'description' => __( 'Send a notification to the user when their subscription is activated.', 'wordpress-membership-pro' ),
            )
        );
        add_settings_field(
            'wmp_email_subscription_cancelled',
            __( 'Subscription Cancelled Email', 'wordpress-membership-pro' ),
            array( $this, 'render_checkbox_input' ),
            'wmp-settings',
            'wmp_settings_emails',
            array(
                'label_for' => 'wmp_email_subscription_cancelled',
                'option_name' => 'wmp_settings',
                'key' => 'email_subscription_cancelled_enabled',
                'description' => __( 'Send a notification to the user when their subscription is cancelled.', 'wordpress-membership-pro' ),
            )
        );
        add_settings_field(
            'wmp_email_order_on_hold',
            __( 'Order On-Hold Email', 'wordpress-membership-pro' ),
            array( $this, 'render_checkbox_input' ),
            'wmp-settings',
            'wmp_settings_emails',
            array(
                'label_for' => 'wmp_email_order_on_hold',
                'option_name' => 'wmp_settings',
                'key' => 'email_order_on_hold_enabled',
                'description' => __( 'Send a notification for offline orders that are awaiting payment.', 'wordpress-membership-pro' ),
            )
        );

        // Pages Section
        add_settings_section(
            'wmp_settings_pages',
            __( 'Page Settings', 'wordpress-membership-pro' ),
            '__return_false',
            'wmp-settings'
        );

        add_settings_field(
            'wmp_checkout_page_id',
            __( 'Checkout Page', 'wordpress-membership-pro' ),
            array( $this, 'render_page_select' ),
            'wmp-settings',
            'wmp_settings_pages',
            array(
                'label_for' => 'wmp_checkout_page_id',
                'option_name' => 'wmp_settings',
                'key' => 'checkout_page_id',
                'description' => __( 'Select the page where the [wmp_checkout] shortcode is located.', 'wordpress-membership-pro' ),
            )
        );

        add_settings_field(
            'wmp_affiliate_registration_page_id',
            __( 'Affiliate Registration Page', 'wordpress-membership-pro' ),
            array( $this, 'render_page_select' ),
            'wmp-settings',
            'wmp_settings_pages',
            array(
                'label_for' => 'wmp_affiliate_registration_page_id',
                'option_name' => 'wmp_settings',
                'key' => 'affiliate_registration_page_id',
                'description' => __( 'Select the page where the [wmp_affiliate_registration] shortcode is located.', 'wordpress-membership-pro' ),
            )
        );

        add_settings_field(
            'wmp_plans_page_id',
            __( 'Plans Page', 'wordpress-membership-pro' ),
            array( $this, 'render_page_select' ),
            'wmp-settings',
            'wmp_settings_pages',
            array(
                'label_for' => 'wmp_plans_page_id',
                'option_name' => 'wmp_settings',
                'key' => 'plans_page_id',
                'description' => __( 'Select the page where the [wmp_plans] shortcode is located. This is used for the "Change Plan" link.', 'wordpress-membership-pro' ),
            )
        );

        add_settings_field(
            'wmp_oto_page_id',
            __( 'One-Time Offer Page', 'wordpress-membership-pro' ),
            array( $this, 'render_page_select' ),
            'wmp-settings',
            'wmp_settings_pages',
            array(
                'label_for' => 'wmp_oto_page_id',
                'option_name' => 'wmp_settings',
                'key' => 'oto_page_id',
                'description' => __( 'Select the page where the [wmp_oto] shortcode is located.', 'wordpress-membership-pro' ),
            )
        );

        add_settings_field(
            'wmp_thank_you_page_id',
            __( 'Thank You Page', 'wordpress-membership-pro' ),
            array( $this, 'render_page_select' ),
            'wmp-settings',
            'wmp_settings_pages',
            array(
                'label_for' => 'wmp_thank_you_page_id',
                'option_name' => 'wmp_settings',
                'key' => 'thank_you_page_id',
                'description' => __( 'Select the page where the [wmp_thank_you] shortcode is located.', 'wordpress-membership-pro' ),
            )
        );

        add_settings_field(
            'wmp_account_page_id',
            __( 'Account Page', 'wordpress-membership-pro' ),
            array( $this, 'render_page_select' ),
            'wmp-settings',
            'wmp_settings_pages',
            array(
                'label_for' => 'wmp_account_page_id',
                'option_name' => 'wmp_settings',
                'key' => 'account_page_id',
                'description' => __( 'Select the page where the [wmp_account] shortcode is located.', 'wordpress-membership-pro' ),
            )
        );

        // Integrations Section
        add_settings_section(
            'wmp_settings_integrations',
            __( 'Integrations', 'wordpress-membership-pro' ),
            '__return_false',
            'wmp-settings'
        );

        // Mailchimp API Key
        add_settings_field(
            'wmp_mailchimp_api_key',
            __( 'Mailchimp API Key', 'wordpress-membership-pro' ),
            array( $this, 'render_text_input' ),
            'wmp-settings',
            'wmp_settings_integrations',
            array(
                'label_for' => 'wmp_mailchimp_api_key',
                'option_name' => 'wmp_settings',
                'key' => 'mailchimp_api_key',
                'description' => __( 'Enter your Mailchimp API key to enable integration.', 'wordpress-membership-pro' ),
            )
        );

        // Mailchimp List
        add_settings_field(
            'wmp_mailchimp_list_id',
            __( 'Mailchimp List', 'wordpress-membership-pro' ),
            array( $this, 'render_mailchimp_list_select' ),
            'wmp-settings',
            'wmp_settings_integrations',
            array(
                'label_for' => 'wmp_mailchimp_list_id',
                'option_name' => 'wmp_settings',
                'key' => 'mailchimp_list_id',
                'description' => __( 'Select the list to which new members should be subscribed.', 'wordpress-membership-pro' ),
            )
        );

        // Affiliate Settings Section
        add_settings_section(
            'wmp_settings_affiliates',
            __( 'Affiliate Settings', 'wordpress-membership-pro' ),
            '__return_false',
            'wmp-settings'
        );

        add_settings_field(
            'wmp_affiliate_commission_rate',
            __( 'Commission Rate (%)', 'wordpress-membership-pro' ),
            array( $this, 'render_text_input' ),
            'wmp-settings',
            'wmp_settings_affiliates',
            array(
                'label_for' => 'wmp_affiliate_commission_rate',
                'option_name' => 'wmp_settings',
                'key' => 'affiliate_commission_rate',
                'description' => __( 'The commission rate affiliates earn for successful referrals.', 'wordpress-membership-pro' ),
            )
        );

        add_settings_field(
            'wmp_affiliate_cookie_expiration',
            __( 'Cookie Expiration (Days)', 'wordpress-membership-pro' ),
            array( $this, 'render_text_input' ),
            'wmp-settings',
            'wmp_settings_affiliates',
            array(
                'label_for' => 'wmp_affiliate_cookie_expiration',
                'option_name' => 'wmp_settings',
                'key' => 'affiliate_cookie_expiration',
                'description' => __( 'The number of days the referral tracking cookie is valid.', 'wordpress-membership-pro' ),
            )
        );

        add_settings_field(
            'wmp_affiliate_minimum_payout',
            __( 'Minimum Payout ($)', 'wordpress-membership-pro' ),
            array( $this, 'render_text_input' ),
            'wmp-settings',
            'wmp_settings_affiliates',
            array(
                'label_for' => 'wmp_affiliate_minimum_payout',
                'option_name' => 'wmp_settings',
                'key' => 'affiliate_minimum_payout',
                'description' => __( 'The minimum earnings an affiliate must have to request a payout.', 'wordpress-membership-pro' ),
            )
        );
    }

    /**
     * Render a generic text input field for a settings page.
     *
     * @since    1.0.0
     * @param    array    $args    The arguments for the field.
     */
    public function render_text_input( $args ) {
        $options = get_option( $args['option_name'] );
        $value = isset( $options[ $args['key'] ] ) ? $options[ $args['key'] ] : '';
        echo '<input type="text" id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option_name'] . '[' . $args['key'] . ']' ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
    }

    /**
     * Render a generic textarea input field for a settings page.
     *
     * @since    1.0.0
     * @param    array    $args    The arguments for the field.
     */
    public function render_textarea_input( $args ) {
        $options = get_option( $args['option_name'] );
        $value = isset( $options[ $args['key'] ] ) ? $options[ $args['key'] ] : '';
        echo '<textarea id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option_name'] . '[' . $args['key'] . ']' ) . '" rows="5" class="large-text">' . esc_textarea( $value ) . '</textarea>';
        if ( ! empty( $args['description'] ) ) {
            echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
        }
    }

    /**
     * Render a page select dropdown for a settings page.
     *
     * @since    1.0.6
     * @param    array    $args    The arguments for the field.
     */
    public function render_page_select( $args ) {
        $options = get_option( $args['option_name'] );
        $value = isset( $options[ $args['key'] ] ) ? $options[ $args['key'] ] : '';

        $pages = get_pages();
        echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option_name'] . '[' . $args['key'] . ']' ) . '">';
        echo '<option value="">' . __( '— Select a Page —', 'wordpress-membership-pro' ) . '</option>';
        foreach ( $pages as $page ) {
            echo '<option value="' . esc_attr( $page->ID ) . '" ' . selected( $value, $page->ID, false ) . '>' . esc_html( $page->post_title ) . '</option>';
        }
        echo '</select>';

        if ( ! empty( $args['description'] ) ) {
            echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
        }
    }

    /**
     * Render a static text field for a settings page.
     *
     * @since    1.0.0
     * @param    array    $args    The arguments for the field.
     */
    public function render_static_text( $args ) {
        echo '<input type="text" value="' . esc_attr( $args['text'] ) . '" readonly="readonly" class="regular-text" />';
        if ( ! empty( $args['description'] ) ) {
            echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
        }
    }

    /**
     * Render a generic checkbox input field for a settings page.
     *
     * @since    1.0.0
     * @param    array    $args    The arguments for the field.
     */
    public function render_checkbox_input( $args ) {
        $options = get_option( $args['option_name'] );
        $checked = isset( $options[ $args['key'] ] ) ? $options[ $args['key'] ] : 0;
        echo '<label><input type="checkbox" id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option_name'] . '[' . $args['key'] . ']' ) . '" value="1" ' . checked( 1, $checked, false ) . ' /> ';
        if ( ! empty( $args['description'] ) ) {
            echo esc_html( $args['description'] ) . '</label>';
        }
    }

    /**
     * Render a generic select input field for a settings page.
     *
     * @since    1.0.0
     * @param    array    $args    The arguments for the field.
     */
    public function render_select_input( $args ) {
        $options = get_option( $args['option_name'] );
        $value = isset( $options[ $args['key'] ] ) ? $options[ $args['key'] ] : '';

        echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option_name'] . '[' . $args['key'] . ']' ) . '">';
        foreach ( $args['options'] as $option_key => $option_value ) {
            echo '<option value="' . esc_attr( $option_key ) . '" ' . selected( $value, $option_key, false ) . '>' . esc_html( $option_value ) . '</option>';
        }
        echo '</select>';

        if ( ! empty( $args['description'] ) ) {
            echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
        }
    }

    /**
     * Render the meta box for plan details.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_plan_details_meta_box( $post ) {
        // Add a nonce field for security.
        wp_nonce_field( 'wmp_save_plan_details', 'wmp_plan_details_nonce' );

        // Get existing values.
        $price = get_post_meta( $post->ID, '_wmp_price', true );
        $billing_period = get_post_meta( $post->ID, '_wmp_billing_period', true );
        $billing_frequency = get_post_meta( $post->ID, '_wmp_billing_frequency', true );
        $trial_days = get_post_meta( $post->ID, '_wmp_trial_days', true );
        $assigned_role = get_post_meta( $post->ID, '_wmp_assigned_role', true );
        $payment_type = get_post_meta( $post->ID, '_wmp_payment_type', true );

        // --- Payment Type ---
        echo '<p>';
        echo '<label for="wmp_payment_type"><strong>' . __( 'Payment Type', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<select id="wmp_payment_type" name="wmp_payment_type">';
        $types = array(
            'one-time' => __( 'One-Time Payment', 'wordpress-membership-pro' ),
            'subscription' => __( 'Subscription', 'wordpress-membership-pro' ),
        );
        foreach ($types as $key => $value) {
            echo '<option value="' . esc_attr( $key ) . '" ' . selected( $payment_type, $key, false ) . '>' . esc_html( $value ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        // --- Price ---
        echo '<p>';
        echo '<label for="wmp_price"><strong>' . __( 'Price ($)', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<input type="text" id="wmp_price" name="wmp_price" value="' . esc_attr( $price ) . '" size="25" />';
        echo '</p>';

        // --- Billing Cycle ---
        echo '<p>';
        echo '<label for="wmp_billing_frequency"><strong>' . __( 'Billing Cycle', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo 'Every <input type="number" id="wmp_billing_frequency" name="wmp_billing_frequency" value="' . esc_attr( $billing_frequency ) . '" style="width: 60px;"/>';
        echo '<select id="wmp_billing_period" name="wmp_billing_period">';
        $periods = array('day' => 'Day(s)', 'week' => 'Week(s)', 'month' => 'Month(s)', 'year' => 'Year(s)');
        foreach ($periods as $key => $value) {
            echo '<option value="' . esc_attr( $key ) . '" ' . selected( $billing_period, $key, false ) . '>' . esc_html( $value ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        // --- Trial Period ---
        echo '<p>';
        echo '<label for="wmp_trial_days"><strong>' . __( 'Trial Period (days)', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<input type="number" id="wmp_trial_days" name="wmp_trial_days" value="' . esc_attr( $trial_days ) . '" style="width: 60px;"/>';
        echo '</p>';

        // --- Assigned Role ---
        echo '<p>';
        echo '<label for="wmp_assigned_role"><strong>' . __( 'Assigned Role', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<select id="wmp_assigned_role" name="wmp_assigned_role">';
        echo '<option value="">' . __( '— No Role Change —', 'wordpress-membership-pro' ) . '</option>';
        $roles = get_editable_roles();
        foreach ($roles as $role_key => $role_data) {
            echo '<option value="' . esc_attr( $role_key ) . '" ' . selected( $assigned_role, $role_key, false ) . '>' . esc_html( $role_data['name'] ) . '</option>';
        }
        echo '</select>';
        echo '<br/><em>' . __('This role will be assigned to the user upon activation of this plan.', 'wordpress-membership-pro') . '</em>';
        echo '</p>';

        echo '<hr/><h4>' . __( 'One-Time Offer (OTO) Settings', 'wordpress-membership-pro' ) . '</h4>';

        $is_oto = get_post_meta( $post->ID, '_wmp_is_oto', true );
        echo '<p>';
        echo '<label for="wmp_is_oto"><input type="checkbox" id="wmp_is_oto" name="wmp_is_oto" value="1" ' . checked( 1, $is_oto, false ) . ' /> ';
        echo '<strong>' . __( 'Is this a One-Time Offer?', 'wordpress-membership-pro' ) . '</strong></label>';
        echo '<p class="description">' . __( 'If checked, this plan will not be shown on the main plans page.', 'wordpress-membership-pro' ) . '</p>';
        echo '</p>';

        $plans_query = new WP_Query( array(
            'post_type'      => 'wmp_membership_plan',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'post__not_in'   => array( $post->ID ),
        ) );

        $upsell_for = get_post_meta( $post->ID, '_wmp_oto_upsell_for', true );
        echo '<p>';
        echo '<label for="wmp_oto_upsell_for"><strong>' . __( 'Upsell for Plan:', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<select id="wmp_oto_upsell_for" name="wmp_oto_upsell_for" style="width: 100%;">';
        echo '<option value="">' . __( '— None —', 'wordpress-membership-pro' ) . '</option>';
        if ( $plans_query->have_posts() ) {
            while ( $plans_query->have_posts() ) {
                $plans_query->the_post();
                echo '<option value="' . esc_attr( get_the_ID() ) . '" ' . selected( $upsell_for, get_the_ID(), false ) . '>' . esc_html( get_the_title() ) . '</option>';
            }
            wp_reset_postdata();
        }
        echo '</select>';
        echo '<p class="description">' . __( 'Show this plan as an upsell after a user purchases the selected plan.', 'wordpress-membership-pro' ) . '</p>';
        echo '</p>';

        $downsell_for = get_post_meta( $post->ID, '_wmp_oto_downsell_for', true );
        echo '<p>';
        echo '<label for="wmp_oto_downsell_for"><strong>' . __( 'Downsell for Plan:', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<select id="wmp_oto_downsell_for" name="wmp_oto_downsell_for" style="width: 100%;">';
        echo '<option value="">' . __( '— None —', 'wordpress-membership-pro' ) . '</option>';
        if ( $plans_query->have_posts() ) {
            while ( $plans_query->have_posts() ) {
                $plans_query->the_post();
                echo '<option value="' . esc_attr( get_the_ID() ) . '" ' . selected( $downsell_for, get_the_ID(), false ) . '>' . esc_html( get_the_title() ) . '</option>';
            }
            wp_reset_postdata();
        }
        echo '</select>';
        echo '<p class="description">' . __( 'Show this plan as a downsell if a user declines an upsell offer.', 'wordpress-membership-pro' ) . '</p>';
        echo '</p>';
    }

    /**
     * Render the meta box for content protection.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_content_protection_meta_box( $post ) {
        wp_nonce_field( 'wmp_save_content_protection', 'wmp_content_protection_nonce' );

        $required_plan_id = get_post_meta( $post->ID, '_wmp_required_plan_id', true );
        $drip_delay = get_post_meta( $post->ID, '_wmp_drip_delay', true );

        $plans_query = new WP_Query( array(
            'post_type'      => 'wmp_membership_plan',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        echo '<p>';
        echo '<label for="wmp_required_plan_id">' . __( 'Restrict access to members of:', 'wordpress-membership-pro' ) . '</label>';
        echo '<select id="wmp_required_plan_id" name="wmp_required_plan_id" style="width: 100%;">';
        echo '<option value="">' . __( '— Public (No restriction) —', 'wordpress-membership-pro' ) . '</option>';

        if ( $plans_query->have_posts() ) {
            while ( $plans_query->have_posts() ) {
                $plans_query->the_post();
                echo '<option value="' . esc_attr( get_the_ID() ) . '" ' . selected( $required_plan_id, get_the_ID(), false ) . '>' . esc_html( get_the_title() ) . '</option>';
            }
            wp_reset_postdata();
        }

        echo '</select>';
        echo '</p>';

        echo '<hr/>';

        echo '<h4>' . __( 'Drip Content', 'wordpress-membership-pro' ) . '</h4>';
        echo '<p>';
        echo '<label for="wmp_drip_delay">' . __( 'Release Delay (in days)', 'wordpress-membership-pro' ) . '</label><br/>';
        echo '<input type="number" id="wmp_drip_delay" name="wmp_drip_delay" value="' . esc_attr( $drip_delay ) . '" min="0" step="1" style="width: 100%;" />';
        echo '<p class="description">' . __( 'Release this content X days after the user registers. Leave blank or 0 for immediate access.', 'wordpress-membership-pro' ) . '</p>';
        echo '</p>';
    }

    /**
     * Save the data from all meta boxes.
     *
     * @since    1.0.0
     * @param    int    $post_id    The ID of the post being saved.
     */
    public function save_meta_boxes( $post_id ) {
        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        // Check the user's permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save Plan Details meta box
        if ( isset( $_POST['wmp_plan_details_nonce'] ) && wp_verify_nonce( $_POST['wmp_plan_details_nonce'], 'wmp_save_plan_details' ) ) {
            $plan_fields = [
                'wmp_price',
                'wmp_billing_period',
                'wmp_billing_frequency',
                'wmp_trial_days',
                'wmp_assigned_role',
                'wmp_payment_type',
                'wmp_oto_upsell_for',
                'wmp_oto_downsell_for',
            ];
            foreach ($plan_fields as $field) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
                }
            }
            // Handle checkbox
            $is_oto = isset( $_POST['wmp_is_oto'] ) ? 1 : 0;
            update_post_meta( $post_id, '_wmp_is_oto', $is_oto );
        }

        // Save Content Protection meta box
        if ( isset( $_POST['wmp_content_protection_nonce'] ) && wp_verify_nonce( $_POST['wmp_content_protection_nonce'], 'wmp_save_content_protection' ) ) {
            if ( isset( $_POST['wmp_required_plan_id'] ) ) {
                update_post_meta( $post_id, '_wmp_required_plan_id', sanitize_text_field( $_POST['wmp_required_plan_id'] ) );
            }
            if ( isset( $_POST['wmp_drip_delay'] ) ) {
                update_post_meta( $post_id, '_wmp_drip_delay', absint( $_POST['wmp_drip_delay'] ) );
            }
        }

        // Save Secure File Details meta box
        if ( isset( $_POST['wmp_secure_file_details_nonce'] ) && wp_verify_nonce( $_POST['wmp_secure_file_details_nonce'], 'wmp_save_secure_file_details' ) ) {

            // Handle the file moving
            if ( ! empty( $_POST['wmp_secure_file_attachment_id'] ) ) {
                $attachment_id = absint( $_POST['wmp_secure_file_attachment_id'] );
                $file_path = get_attached_file( $attachment_id );

                $upload_dir = WMP_PLUGIN_DIR . 'secure_uploads/';
                $new_file_path = $upload_dir . basename( $file_path );

                if ( copy( $file_path, $new_file_path ) ) {
                    update_post_meta( $post_id, '_wmp_secure_file_path', $new_file_path );
                }
            }

            $restricted_plans = isset( $_POST['wmp_restricted_to_plans'] ) ? array_map( 'absint', $_POST['wmp_restricted_to_plans'] ) : array();
            update_post_meta( $post_id, '_wmp_restricted_to_plans', $restricted_plans );
        }

        // Save Contest Details meta box
        if ( isset( $_POST['wmp_contest_details_nonce'] ) && wp_verify_nonce( $_POST['wmp_contest_details_nonce'], 'wmp_save_contest_details' ) ) {
            if ( isset( $_POST['wmp_start_date'] ) ) {
                update_post_meta( $post_id, '_wmp_start_date', sanitize_text_field( $_POST['wmp_start_date'] ) );
            }
            if ( isset( $_POST['wmp_end_date'] ) ) {
                update_post_meta( $post_id, '_wmp_end_date', sanitize_text_field( $_POST['wmp_end_date'] ) );
            }
        }

        // Save Badge Trigger meta box
        if ( isset( $_POST['wmp_badge_trigger_nonce'] ) && wp_verify_nonce( $_POST['wmp_badge_trigger_nonce'], 'wmp_save_badge_trigger' ) ) {
            if ( isset( $_POST['wmp_badge_trigger'] ) ) {
                update_post_meta( $post_id, '_wmp_badge_trigger', sanitize_text_field( $_POST['wmp_badge_trigger'] ) );
            }
            if ( isset( $_POST['wmp_badge_trigger_value'] ) ) {
                update_post_meta( $post_id, '_wmp_badge_trigger_value', sanitize_text_field( $_POST['wmp_badge_trigger_value'] ) );
            }
        }

        // Save Forum Access meta box
        if ( isset( $_POST['wmp_forum_access_nonce'] ) && wp_verify_nonce( $_POST['wmp_forum_access_nonce'], 'wmp_save_forum_access' ) ) {
            $restricted_forums = isset( $_POST['wmp_restricted_forums'] ) ? array_map( 'absint', $_POST['wmp_restricted_forums'] ) : array();
            update_post_meta( $post_id, '_wmp_restricted_forums', $restricted_forums );
        }

        // Save Bonus Content meta box
        if ( isset( $_POST['wmp_bonus_content_nonce'] ) && wp_verify_nonce( $_POST['wmp_bonus_content_nonce'], 'wmp_save_bonus_content' ) ) {
            $bonus_file_id = isset( $_POST['wmp_bonus_file_id'] ) ? absint( $_POST['wmp_bonus_file_id'] ) : 0;
            update_post_meta( $post_id, '_wmp_bonus_file_id', $bonus_file_id );
        }
    }

    /**
     * Render the Mailchimp list select dropdown.
     *
     * @since    1.0.7
     * @param    array    $args    The arguments for the field.
     */
    public function render_mailchimp_list_select( $args ) {
        $options = get_option( $args['option_name'] );
        $api_key = isset( $options['mailchimp_api_key'] ) ? $options['mailchimp_api_key'] : '';
        $selected_list = isset( $options[ $args['key'] ] ) ? $options[ $args['key'] ] : '';

        if ( empty( $api_key ) ) {
            echo '<p>' . __( 'Please enter your Mailchimp API key above and save settings to see a list of available audiences.', 'wordpress-membership-pro' ) . '</p>';
            return;
        }

        $mailchimp = new WMP_Mailchimp_Integration();
        $lists = $mailchimp->get_lists();

        if ( ! $lists || empty( $lists ) ) {
            echo '<p>' . __( 'Could not retrieve lists from Mailchimp. Please check your API key.', 'wordpress-membership-pro' ) . '</p>';
            return;
        }

        echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option_name'] . '[' . $args['key'] . ']' ) . '">';
        echo '<option value="">' . __( '— Select a List —', 'wordpress-membership-pro' ) . '</option>';
        foreach ( $lists as $list ) {
            echo '<option value="' . esc_attr( $list['id'] ) . '" ' . selected( $selected_list, $list['id'], false ) . '>' . esc_html( $list['name'] ) . '</option>';
        }
        echo '</select>';

        if ( ! empty( $args['description'] ) ) {
            echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
        }
    }

    /**
     * Render the meta box for contest details.
     *
     * @since    1.0.8
     * @param    WP_Post    $post    The post object.
     */
    public function render_contest_details_meta_box( $post ) {
        wp_nonce_field( 'wmp_save_contest_details', 'wmp_contest_details_nonce' );

        $start_date = get_post_meta( $post->ID, '_wmp_start_date', true );
        $end_date = get_post_meta( $post->ID, '_wmp_end_date', true );

        echo '<p>';
        echo '<label for="wmp_start_date"><strong>' . __( 'Start Date', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<input type="date" id="wmp_start_date" name="wmp_start_date" value="' . esc_attr( $start_date ) . '" />';
        echo '</p>';

        echo '<p>';
        echo '<label for="wmp_end_date"><strong>' . __( 'End Date', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<input type="date" id="wmp_end_date" name="wmp_end_date" value="' . esc_attr( $end_date ) . '" />';
        echo '</p>';
    }

    /**
     * Render the meta box for badge triggers.
     *
     * @since    1.0.8
     * @param    WP_Post    $post    The post object.
     */
    public function render_badge_trigger_meta_box( $post ) {
        wp_nonce_field( 'wmp_save_badge_trigger', 'wmp_badge_trigger_nonce' );

        $trigger = get_post_meta( $post->ID, '_wmp_badge_trigger', true );
        $trigger_value = get_post_meta( $post->ID, '_wmp_badge_trigger_value', true );

        $triggers = array(
            '' => __( '— Select a Trigger —', 'wordpress-membership-pro' ),
            'subscription_activated' => __( 'Subscription Activated', 'wordpress-membership-pro' ),
            'first_referral' => __( 'First Successful Referral', 'wordpress-membership-pro' ),
            'anniversary' => __( 'Membership Anniversary (in years)', 'wordpress-membership-pro' ),
        );

        echo '<p>';
        echo '<label for="wmp_badge_trigger"><strong>' . __( 'Trigger', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<select id="wmp_badge_trigger" name="wmp_badge_trigger">';
        foreach ( $triggers as $key => $label ) {
            echo '<option value="' . esc_attr( $key ) . '" ' . selected( $trigger, $key, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        echo '<p>';
        echo '<label for="wmp_badge_trigger_value"><strong>' . __( 'Trigger Value', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<input type="text" id="wmp_badge_trigger_value" name="wmp_badge_trigger_value" value="' . esc_attr( $trigger_value ) . '" />';
        echo '<p class="description">' . __( 'For anniversaries, enter the number of years (e.g., 1). For other triggers, this can be left blank.', 'wordpress-membership-pro' ) . '</p>';
        echo '</p>';
    }

    /**
     * Render the meta box for forum access settings.
     *
     * @since    1.0.10
     * @param    WP_Post    $post    The post object.
     */
    public function render_forum_access_meta_box( $post ) {
        wp_nonce_field( 'wmp_save_forum_access', 'wmp_forum_access_nonce' );

        if ( ! class_exists( 'bbPress' ) ) {
            echo '<p>' . __( 'The bbPress plugin is not active. Please activate bbPress to use this feature.', 'wordpress-membership-pro' ) . '</p>';
            return;
        }

        $restricted_forums = get_post_meta( $post->ID, '_wmp_restricted_forums', true );
        $restricted_forums = is_array( $restricted_forums ) ? $restricted_forums : array();

        $forums_query = new WP_Query( array(
            'post_type'      => 'forum',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        if ( $forums_query->have_posts() ) {
            echo '<div style="max-height: 200px; overflow-y: scroll; border: 1px solid #ddd; padding: 10px;">';
            while ( $forums_query->have_posts() ) {
                $forums_query->the_post();
                $forum_id = get_the_ID();
                echo '<label><input type="checkbox" name="wmp_restricted_forums[]" value="' . esc_attr( $forum_id ) . '" ' . checked( in_array( $forum_id, $restricted_forums ), true, false ) . '> ' . esc_html( get_the_title() ) . '</label><br/>';
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>' . __( 'No forums found. Please create a forum in bbPress first.', 'wordpress-membership-pro' ) . '</p>';
        }
    }

    /**
     * Render the meta box for bonus content settings.
     *
     * @since    1.0.10
     * @param    WP_Post    $post    The post object.
     */
    public function render_bonus_content_meta_box( $post ) {
        wp_nonce_field( 'wmp_save_bonus_content', 'wmp_bonus_content_nonce' );

        $bonus_file_id = get_post_meta( $post->ID, '_wmp_bonus_file_id', true );

        $secure_files_query = new WP_Query( array(
            'post_type'      => 'wmp_secure_file',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        echo '<p>';
        echo '<label for="wmp_bonus_file_id"><strong>' . __( 'Select Bonus File', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<select id="wmp_bonus_file_id" name="wmp_bonus_file_id" style="width: 100%;">';
        echo '<option value="">' . __( '— No Bonus —', 'wordpress-membership-pro' ) . '</option>';

        if ( $secure_files_query->have_posts() ) {
            while ( $secure_files_query->have_posts() ) {
                $secure_files_query->the_post();
                echo '<option value="' . esc_attr( get_the_ID() ) . '" ' . selected( $bonus_file_id, get_the_ID(), false ) . '>' . esc_html( get_the_title() ) . '</option>';
            }
            wp_reset_postdata();
        }

        echo '</select>';
        echo '<p class="description">' . __( 'This secure file will be granted to the user upon activation of this plan.', 'wordpress-membership-pro' ) . '</p>';
        echo '</p>';
    }
}
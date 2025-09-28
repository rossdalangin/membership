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
        }

        if ( ! empty( $message ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
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
            $plan_fields = ['wmp_price', 'wmp_billing_period', 'wmp_billing_frequency', 'wmp_trial_days', 'wmp_assigned_role'];
            foreach ($plan_fields as $field) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
                }
            }
        }

        // Save Content Protection meta box
        if ( isset( $_POST['wmp_content_protection_nonce'] ) && wp_verify_nonce( $_POST['wmp_content_protection_nonce'], 'wmp_save_content_protection' ) ) {
            if ( isset( $_POST['wmp_required_plan_id'] ) ) {
                update_post_meta($post_id, '_wmp_required_plan_id', sanitize_text_field($_POST['wmp_required_plan_id']));
            }
        }
    }
}
<?php
/**
 * The file that defines the coupon CPT and admin functionality.
 *
 * @link       https://example.com
 * @since      1.0.1
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/admin
 */

/**
 * The coupon CPT and admin functionality class.
 *
 * @since      1.0.1
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/admin
 * @author     Jules
 */
class WMP_Coupons {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.1
     */
    public function __construct() {
        // Left empty for now
    }

    /**
     * Add the meta boxes for the coupon CPT.
     *
     * @since 1.0.1
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wmp_coupon_details',
            __( 'Coupon Details', 'wordpress-membership-pro' ),
            array( $this, 'render_coupon_details_meta_box' ),
            'wmp_coupon',
            'normal',
            'high'
        );
    }

    /**
     * Render the meta box for coupon details.
     *
     * @since 1.0.1
     * @param WP_Post $post The post object.
     */
    public function render_coupon_details_meta_box( $post ) {
        wp_nonce_field( 'wmp_save_coupon_details', 'wmp_coupon_details_nonce' );

        $discount_type = get_post_meta( $post->ID, '_wmp_discount_type', true );
        $amount = get_post_meta( $post->ID, '_wmp_amount', true );
        $usage_limit = get_post_meta( $post->ID, '_wmp_usage_limit', true );
        $usage_count = get_post_meta( $post->ID, '_wmp_usage_count', true );
        $usage_count = ! empty( $usage_count ) ? absint( $usage_count ) : 0;

        // --- Discount Type ---
        echo '<p>';
        echo '<label for="wmp_discount_type"><strong>' . __( 'Discount Type', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<select id="wmp_discount_type" name="wmp_discount_type">';
        $types = array(
            'percentage' => __( 'Percentage Discount', 'wordpress-membership-pro' ),
            'fixed' => __( 'Fixed Amount Discount', 'wordpress-membership-pro' ),
        );
        foreach ( $types as $key => $value ) {
            echo '<option value="' . esc_attr( $key ) . '" ' . selected( $discount_type, $key, false ) . '>' . esc_html( $value ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        // --- Amount ---
        echo '<p>';
        echo '<label for="wmp_amount"><strong>' . __( 'Amount', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<input type="text" id="wmp_amount" name="wmp_amount" value="' . esc_attr( $amount ) . '" size="25" />';
        echo '<p class="description">' . __( 'Enter a percentage (e.g., 10) or a fixed amount (e.g., 19.99).', 'wordpress-membership-pro' ) . '</p>';
        echo '</p>';

        // --- Usage Limit ---
        echo '<p>';
        echo '<label for="wmp_usage_limit"><strong>' . __( 'Usage Limit', 'wordpress-membership-pro' ) . '</strong></label><br/>';
        echo '<input type="number" id="wmp_usage_limit" name="wmp_usage_limit" value="' . esc_attr( $usage_limit ) . '" />';
        echo '<p class="description">' . __( 'Leave blank for unlimited usage.', 'wordpress-membership-pro' ) . '</p>';
        echo '</p>';

        // --- Usage Count (display only) ---
        echo '<p>';
        echo '<strong>' . __( 'Times Used:', 'wordpress-membership-pro' ) . '</strong> ' . $usage_count;
        echo '</p>';
    }

    /**
     * Save the data from the meta box.
     *
     * @since 1.0.1
     * @param int $post_id The ID of the post being saved.
     */
    public function save_meta_box( $post_id ) {
        if ( ! isset( $_POST['wmp_coupon_details_nonce'] ) || ! wp_verify_nonce( $_POST['wmp_coupon_details_nonce'], 'wmp_save_coupon_details' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( !isset($_POST['post_type']) || 'wmp_coupon' !== $_POST['post_type'] ) {
            return;
        }

        $fields = [
            'wmp_discount_type',
            'wmp_amount',
            'wmp_usage_limit',
        ];

        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }

        // Initialize usage count if it doesn't exist
        $usage_count = get_post_meta( $post_id, '_wmp_usage_count', true );
        if ( '' === $usage_count ) {
            update_post_meta( $post_id, '_wmp_usage_count', 0 );
        }
    }

    /**
     * Retrieves a coupon post by its code (title).
     *
     * @since 1.0.1
     * @param string $code The coupon code.
     * @return WP_Post|false The coupon post object or false if not found.
     */
    public static function get_coupon_by_code( $code ) {
        $args = array(
            'post_type' => 'wmp_coupon',
            'post_title' => $code,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'fields' => 'ids',
        );
        $query = new WP_Query( $args );
        if ( ! empty( $query->posts ) ) {
            return get_post( $query->posts[0] );
        }
        return false;
    }

    /**
     * Increment the usage count for a coupon.
     *
     * @since 1.0.1
     * @param int $coupon_id The ID of the coupon.
     */
    public static function increment_usage_count( $coupon_id ) {
        $count = get_post_meta( $coupon_id, '_wmp_usage_count', true );
        $count = ! empty( $count ) ? absint( $count ) : 0;
        $count++;
        update_post_meta( $coupon_id, '_wmp_usage_count', $count );
    }

    /**
     * Calculates the discounted price.
     *
     * @since 1.0.1
     * @param float $original_price The original price.
     * @param WP_Post $coupon The coupon post object.
     * @return float The discounted price.
     */
    public static function calculate_discounted_price( $original_price, $coupon ) {
        $discount_type = get_post_meta( $coupon->ID, '_wmp_discount_type', true );
        $amount = get_post_meta( $coupon->ID, '_wmp_amount', true );

        if ( 'percentage' === $discount_type ) {
            $discount = $original_price * ( (float)$amount / 100 );
            $new_price = $original_price - $discount;
        } elseif ( 'fixed' === $discount_type ) {
            $new_price = $original_price - (float)$amount;
        } else {
            $new_price = $original_price;
        }

        return max( $new_price, 0 ); // Price can't be negative
    }

    /**
     * Register the "Coupons" Custom Post Type.
     *
     * @since    1.0.1
     */
    public function register_cpt() {
        $labels = array(
            'name'               => _x( 'Coupons', 'post type general name', 'wordpress-membership-pro' ),
            'singular_name'      => _x( 'Coupon', 'post type singular name', 'wordpress-membership-pro' ),
            'menu_name'          => _x( 'Coupons', 'admin menu', 'wordpress-membership-pro' ),
            'name_admin_bar'     => _x( 'Coupon', 'add new on admin bar', 'wordpress-membership-pro' ),
            'add_new'            => _x( 'Add New', 'coupon', 'wordpress-membership-pro' ),
            'add_new_item'       => __( 'Add New Coupon', 'wordpress-membership-pro' ),
            'new_item'           => __( 'New Coupon', 'wordpress-membership-pro' ),
            'edit_item'          => __( 'Edit Coupon', 'wordpress-membership-pro' ),
            'view_item'          => __( 'View Coupon', 'wordpress-membership-pro' ),
            'all_items'          => __( 'All Coupons', 'wordpress-membership-pro' ),
            'search_items'       => __( 'Search Coupons', 'wordpress-membership-pro' ),
            'parent_item_colon'  => __( 'Parent Coupons:', 'wordpress-membership-pro' ),
            'not_found'          => __( 'No coupons found.', 'wordpress-membership-pro' ),
            'not_found_in_trash' => __( 'No coupons found in Trash.', 'wordpress-membership-pro' )
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'wmp-subscriptions',
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title' )
        );

        register_post_type( 'wmp_coupon', $args );
    }
}
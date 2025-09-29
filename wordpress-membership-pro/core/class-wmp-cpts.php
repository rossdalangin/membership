<?php
/**
 * The file that defines the custom post types and taxonomies for the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 */

/**
 * The custom post type and taxonomy registration class.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 * @author     Jules
 */
class WMP_CPTs {

    /**
     * Register all CPTs and taxonomies.
     *
     * @since    1.0.0
     */
    public function register() {
        $this->register_membership_plan_cpt();
        $this->register_payment_cpt();
        $this->register_secure_files_cpt();
        $this->register_plan_category_taxonomy();
    }

    /**
     * Register the Membership Plan Custom Post Type.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_membership_plan_cpt() {
        $labels = array(
            'name'                  => _x( 'Membership Plans', 'Post Type General Name', 'wordpress-membership-pro' ),
            'singular_name'         => _x( 'Membership Plan', 'Post Type Singular Name', 'wordpress-membership-pro' ),
            'menu_name'             => __( 'Membership Plans', 'wordpress-membership-pro' ),
            'all_items'             => __( 'All Plans', 'wordpress-membership-pro' ),
            'add_new_item'          => __( 'Add New Plan', 'wordpress-membership-pro' ),
            'add_new'               => __( 'Add New', 'wordpress-membership-pro' ),
            'new_item'              => __( 'New Plan', 'wordpress-membership-pro' ),
            'edit_item'             => __( 'Edit Plan', 'wordpress-membership-pro' ),
            'update_item'           => __( 'Update Plan', 'wordpress-membership-pro' ),
            'view_item'             => __( 'View Plan', 'wordpress-membership-pro' ),
            'search_items'          => __( 'Search Plan', 'wordpress-membership-pro' ),
        );
        $args = array(
            'label'                 => __( 'Membership Plan', 'wordpress-membership-pro' ),
            'description'           => __( 'For creating and managing membership levels.', 'wordpress-membership-pro' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-groups',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        register_post_type( 'wmp_membership_plan', $args );
    }

    /**
     * Register the Payment Custom Post Type.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_payment_cpt() {
        $labels = array(
            'name'                  => _x( 'Payments', 'Post Type General Name', 'wordpress-membership-pro' ),
            'singular_name'         => _x( 'Payment', 'Post Type Singular Name', 'wordpress-membership-pro' ),
            'menu_name'             => __( 'Payments', 'wordpress-membership-pro' ),
            'all_items'             => __( 'All Payments', 'wordpress-membership-pro' ),
            'view_item'             => __( 'View Payment', 'wordpress-membership-pro' ),
            'search_items'          => __( 'Search Payments', 'wordpress-membership-pro' ),
            'not_found'             => __( 'No payments found', 'wordpress-membership-pro' ),
        );
        $args = array(
            'label'                 => __( 'Payment', 'wordpress-membership-pro' ),
            'description'           => __( 'To log payment transactions.', 'wordpress-membership-pro' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'custom-fields' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=wmp_membership_plan',
            'capability_type'       => 'post',
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'show_in_rest'          => true,
        );
        register_post_type( 'wmp_payment', $args );
    }

    /**
     * Register the Plan Category Taxonomy.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_plan_category_taxonomy() {
        $labels = array(
            'name'              => _x( 'Plan Categories', 'taxonomy general name', 'wordpress-membership-pro' ),
            'singular_name'     => _x( 'Plan Category', 'taxonomy singular name', 'wordpress-membership-pro' ),
            'search_items'      => __( 'Search Plan Categories', 'wordpress-membership-pro' ),
            'all_items'         => __( 'All Plan Categories', 'wordpress-membership-pro' ),
            'parent_item'       => __( 'Parent Plan Category', 'wordpress-membership-pro' ),
            'parent_item_colon' => __( 'Parent Plan Category:', 'wordpress-membership-pro' ),
            'edit_item'         => __( 'Edit Plan Category', 'wordpress-membership-pro' ),
            'update_item'       => __( 'Update Plan Category', 'wordpress-membership-pro' ),
            'add_new_item'      => __( 'Add New Plan Category', 'wordpress-membership-pro' ),
            'new_item_name'     => __( 'New Plan Category Name', 'wordpress-membership-pro' ),
            'menu_name'         => __( 'Categories', 'wordpress-membership-pro' ),
        );
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
        );
        register_taxonomy( 'wmp_plan_category', array( 'wmp_membership_plan' ), $args );
    }

    /**
     * Register the Secure Files Custom Post Type.
     *
     * @since    1.0.4
     * @access   private
     */
    private function register_secure_files_cpt() {
        $labels = array(
            'name'                  => _x( 'Secure Files', 'Post Type General Name', 'wordpress-membership-pro' ),
            'singular_name'         => _x( 'Secure File', 'Post Type Singular Name', 'wordpress-membership-pro' ),
            'menu_name'             => __( 'Secure Files', 'wordpress-membership-pro' ),
            'all_items'             => __( 'All Files', 'wordpress-membership-pro' ),
            'add_new_item'          => __( 'Add New File', 'wordpress-membership-pro' ),
            'add_new'               => __( 'Add New', 'wordpress-membership-pro' ),
            'new_item'              => __( 'New File', 'wordpress-membership-pro' ),
            'edit_item'             => __( 'Edit File', 'wordpress-membership-pro' ),
            'update_item'           => __( 'Update File', 'wordpress-membership-pro' ),
            'view_item'             => __( 'View File', 'wordpress-membership-pro' ),
            'search_items'          => __( 'Search Files', 'wordpress-membership-pro' ),
        );
        $args = array(
            'label'                 => __( 'Secure File', 'wordpress-membership-pro' ),
            'description'           => __( 'For managing protected file downloads.', 'wordpress-membership-pro' ),
            'labels'                => $labels,
            'supports'              => array( 'title' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 21,
            'menu_icon'             => 'dashicons-media-default',
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'show_in_rest'          => false,
        );
        register_post_type( 'wmp_secure_file', $args );
    }
}
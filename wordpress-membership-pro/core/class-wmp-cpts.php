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
        $this->register_secure_files_cpt();
        $this->register_promo_tools_cpt();
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

    /**
     * Register the Promo Tools Custom Post Type.
     *
     * @since    1.0.6
     * @access   private
     */
    private function register_promo_tools_cpt() {
        $labels = array(
            'name'                  => _x( 'Promo Tools', 'Post Type General Name', 'wordpress-membership-pro' ),
            'singular_name'         => _x( 'Promo Tool', 'Post Type Singular Name', 'wordpress-membership-pro' ),
            'menu_name'             => __( 'Promo Tools', 'wordpress-membership-pro' ),
            'all_items'             => __( 'All Promo Tools', 'wordpress-membership-pro' ),
            'add_new_item'          => __( 'Add New Promo Tool', 'wordpress-membership-pro' ),
            'add_new'               => __( 'Add New', 'wordpress-membership-pro' ),
            'new_item'              => __( 'New Promo Tool', 'wordpress-membership-pro' ),
            'edit_item'             => __( 'Edit Promo Tool', 'wordpress-membership-pro' ),
            'update_item'           => __( 'Update Promo Tool', 'wordpress-membership-pro' ),
            'view_item'             => __( 'View Promo Tool', 'wordpress-membership-pro' ),
            'search_items'          => __( 'Search Promo Tools', 'wordpress-membership-pro' ),
        );
        $args = array(
            'label'                 => __( 'Promo Tool', 'wordpress-membership-pro' ),
            'description'           => __( 'For managing affiliate promotional materials like banners and swipe copy.', 'wordpress-membership-pro' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'wmp-affiliates',
            'capability_type'       => 'post',
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'show_in_rest'          => false,
        );
        register_post_type( 'wmp_promo_tool', $args );

        // Register Contest CPT
        $labels = array(
            'name'                  => _x( 'Contests', 'Post Type General Name', 'wordpress-membership-pro' ),
            'singular_name'         => _x( 'Contest', 'Post Type Singular Name', 'wordpress-membership-pro' ),
            'menu_name'             => __( 'Affiliate Contests', 'wordpress-membership-pro' ),
            'name_admin_bar'        => __( 'Contest', 'wordpress-membership-pro' ),
            'archives'              => __( 'Contest Archives', 'wordpress-membership-pro' ),
            'attributes'            => __( 'Contest Attributes', 'wordpress-membership-pro' ),
            'parent_item_colon'     => __( 'Parent Contest:', 'wordpress-membership-pro' ),
            'all_items'             => __( 'All Contests', 'wordpress-membership-pro' ),
            'add_new_item'          => __( 'Add New Contest', 'wordpress-membership-pro' ),
            'add_new'               => __( 'Add New', 'wordpress-membership-pro' ),
            'new_item'              => __( 'New Contest', 'wordpress-membership-pro' ),
            'edit_item'             => __( 'Edit Contest', 'wordpress-membership-pro' ),
            'update_item'           => __( 'Update Contest', 'wordpress-membership-pro' ),
            'view_item'             => __( 'View Contest', 'wordpress-membership-pro' ),
            'view_items'            => __( 'View Contests', 'wordpress-membership-pro' ),
            'search_items'          => __( 'Search Contest', 'wordpress-membership-pro' ),
            'not_found'             => __( 'Not found', 'wordpress-membership-pro' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'wordpress-membership-pro' ),
            'featured_image'        => __( 'Featured Image', 'wordpress-membership-pro' ),
            'set_featured_image'    => __( 'Set featured image', 'wordpress-membership-pro' ),
            'remove_featured_image' => __( 'Remove featured image', 'wordpress-membership-pro' ),
            'use_featured_image'    => __( 'Use as featured image', 'wordpress-membership-pro' ),
            'insert_into_item'      => __( 'Insert into contest', 'wordpress-membership-pro' ),
            'uploaded_to_this_item' => __( 'Uploaded to this contest', 'wordpress-membership-pro' ),
            'items_list'            => __( 'Contests list', 'wordpress-membership-pro' ),
            'items_list_navigation' => __( 'Contests list navigation', 'wordpress-membership-pro' ),
            'filter_items_list'     => __( 'Filter contests list', 'wordpress-membership-pro' ),
        );
        $args = array(
            'label'                 => __( 'Contest', 'wordpress-membership-pro' ),
            'description'           => __( 'For creating affiliate contests and leaderboards.', 'wordpress-membership-pro' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'wmp-affiliates',
            'menu_position'         => 10,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'rewrite'               => false,
        );
        register_post_type( 'wmp_contest', $args );

        // Register Badge CPT
        $labels = array(
            'name'                  => _x( 'Badges', 'Post Type General Name', 'wordpress-membership-pro' ),
            'singular_name'         => _x( 'Badge', 'Post Type Singular Name', 'wordpress-membership-pro' ),
            'menu_name'             => __( 'Badges', 'wordpress-membership-pro' ),
            'name_admin_bar'        => __( 'Badge', 'wordpress-membership-pro' ),
            'archives'              => __( 'Badge Archives', 'wordpress-membership-pro' ),
            'attributes'            => __( 'Badge Attributes', 'wordpress-membership-pro' ),
            'parent_item_colon'     => __( 'Parent Badge:', 'wordpress-membership-pro' ),
            'all_items'             => __( 'All Badges', 'wordpress-membership-pro' ),
            'add_new_item'          => __( 'Add New Badge', 'wordpress-membership-pro' ),
            'add_new'               => __( 'Add New', 'wordpress-membership-pro' ),
            'new_item'              => __( 'New Badge', 'wordpress-membership-pro' ),
            'edit_item'             => __( 'Edit Badge', 'wordpress-membership-pro' ),
            'update_item'           => __( 'Update Badge', 'wordpress-membership-pro' ),
            'view_item'             => __( 'View Badge', 'wordpress-membership-pro' ),
            'view_items'            => __( 'View Badges', 'wordpress-membership-pro' ),
            'search_items'          => __( 'Search Badge', 'wordpress-membership-pro' ),
            'not_found'             => __( 'Not found', 'wordpress-membership-pro' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'wordpress-membership-pro' ),
            'featured_image'        => __( 'Badge Image', 'wordpress-membership-pro' ),
            'set_featured_image'    => __( 'Set badge image', 'wordpress-membership-pro' ),
            'remove_featured_image' => __( 'Remove badge image', 'wordpress-membership-pro' ),
            'use_featured_image'    => __( 'Use as badge image', 'wordpress-membership-pro' ),
            'insert_into_item'      => __( 'Insert into badge', 'wordpress-membership-pro' ),
            'uploaded_to_this_item' => __( 'Uploaded to this badge', 'wordpress-membership-pro' ),
            'items_list'            => __( 'Badges list', 'wordpress-membership-pro' ),
            'items_list_navigation' => __( 'Badges list navigation', 'wordpress-membership-pro' ),
            'filter_items_list'     => __( 'Filter badges list', 'wordpress-membership-pro' ),
        );
        $args = array(
            'label'                 => __( 'Badge', 'wordpress-membership-pro' ),
            'description'           => __( 'Gamification badges for members.', 'wordpress-membership-pro' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'wmp-subscriptions',
            'menu_position'         => 15,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'rewrite'               => false,
        );
        register_post_type( 'wmp_badge', $args );
    }
}
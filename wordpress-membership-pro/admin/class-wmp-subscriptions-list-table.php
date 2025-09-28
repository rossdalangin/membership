<?php
/**
 * The file that defines the Subscriptions List Table class.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/admin
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * The Subscriptions List Table class.
 *
 * This is used to display the list of subscriptions in the admin area.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/admin
 * @author     Jules
 */
class WMP_Subscriptions_List_Table extends WP_List_Table {

    /**
     * Constructor.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Subscription', 'wordpress-membership-pro' ),
            'plural'   => __( 'Subscriptions', 'wordpress-membership-pro' ),
            'ajax'     => false,
        ) );
    }

    /**
     * Prepare the items for the table to process.
     *
     * @since    1.0.0
     */
    public function prepare_items() {
        $this->_column_headers = $this->get_column_info();

        $per_page     = $this->get_items_per_page( 'subscriptions_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items  = self::get_subscription_count();

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );

        $this->items = self::get_subscriptions( $per_page, $current_page );
    }

    /**
     * Get a list of columns.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_columns() {
        return array(
            'cb'         => '<input type="checkbox" />',
            'id'         => __( 'ID', 'wordpress-membership-pro' ),
            'user_id'    => __( 'Member', 'wordpress-membership-pro' ),
            'plan_id'    => __( 'Plan', 'wordpress-membership-pro' ),
            'status'     => __( 'Status', 'wordpress-membership-pro' ),
            'gateway'    => __( 'Gateway', 'wordpress-membership-pro' ),
            'start_date' => __( 'Start Date', 'wordpress-membership-pro' ),
        );
    }

    /**
     *  Associates the data with the columns.
     *
     * @since 1.0.0
     * @param array $item The item data.
     * @param string $column_name The column name.
     * @return mixed
     */
    protected function column_default( $item, $column_name ) {
        return $item[ $column_name ];
    }

    /**
     * Render the checkbox column.
     */
    function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id'] );
    }

    /**
     * Render the Member column.
     */
    public function column_user_id( $item ) {
        $user = get_user_by( 'id', $item['user_id'] );
        if ( ! $user ) {
            return __( 'Unknown User', 'wordpress-membership-pro' );
        }
        return sprintf( '<a href="%s">%s</a>', esc_url( get_edit_user_link( $user->ID ) ), esc_html( $user->display_name ) );
    }

    /**
     * Render the Plan column.
     */
    public function column_plan_id( $item ) {
        $plan_title = get_the_title( $item['plan_id'] );
        if ( ! $plan_title ) {
            return __( 'Unknown Plan', 'wordpress-membership-pro' );
        }
        return sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $item['plan_id'] ) ), esc_html( $plan_title ) );
    }

    /**
     * Render the Status column.
     */
    public function column_status( $item ) {
        return sprintf( '<span class="wmp-status-%s">%s</span>', esc_attr( $item['status'] ), esc_html( ucfirst( $item['status'] ) ) );
    }

    /**
     * Render the ID column with row actions.
     */
    public function column_id( $item ) {
        $nonce = wp_create_nonce( 'wmp_subscription_action_nonce' );
        $page = request_parameter( 'page' );

        $actions = array();

        if ( 'on-hold' === $item['status'] ) {
            $actions['activate'] = sprintf(
                '<a href="?page=%s&action=activate&subscription=%s&_wpnonce=%s">' . __( 'Activate', 'wordpress-membership-pro' ) . '</a>',
                esc_attr( $page ),
                absint( $item['id'] ),
                $nonce
            );
        }

        if ( 'active' === $item['status'] ) {
            $actions['cancel'] = sprintf(
                '<a href="?page=%s&action=cancel&subscription=%s&_wpnonce=%s">' . __( 'Cancel', 'wordpress-membership-pro' ) . '</a>',
                esc_attr( $page ),
                absint( $item['id'] ),
                $nonce
            );
        }

        $actions['delete'] = sprintf(
            '<a href="?page=%s&action=delete&subscription=%s&_wpnonce=%s" style="color:#a00;">' . __( 'Delete Permanently', 'wordpress-membership-pro' ) . '</a>',
            esc_attr( $page ),
            absint( $item['id'] ),
            $nonce
        );

        return sprintf( '%1$s %2$s', $item['id'], $this->row_actions( $actions ) );
    }

    /**
     * Retrieve subscriptions data from the database.
     *
     * @param int $per_page
     * @param int $page_number
     * @return array
     */
    public static function get_subscriptions( $per_page = 20, $page_number = 1 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wmp_subscriptions';
        $sql = "SELECT * FROM {$table_name}";
        $sql .= ' ORDER BY created_at DESC';
        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
        return $wpdb->get_results( $sql, 'ARRAY_A' );
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function get_subscription_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wmp_subscriptions';
        $sql = "SELECT COUNT(*) FROM {$table_name}";
        return $wpdb->get_var( $sql );
    }
}
<?php
/**
 * The file that defines the Affiliates List Table class.
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
 * The Affiliates List Table class.
 *
 * This is used to display the list of affiliates in the admin area.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/admin
 * @author     Jules
 */
class WMP_Affiliates_List_Table extends WP_List_Table {

    /**
     * Constructor.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Affiliate', 'wordpress-membership-pro' ),
            'plural'   => __( 'Affiliates', 'wordpress-membership-pro' ),
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
        $status = isset( $_REQUEST['status'] ) ? sanitize_key( $_REQUEST['status'] ) : 'all';

        $per_page     = $this->get_items_per_page( 'affiliates_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items  = self::get_affiliate_count( $status );

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );

        $this->items = self::get_affiliates( $per_page, $current_page, $status );
    }

    /**
     * Get a list of columns.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_columns() {
        return array(
            'cb'              => '<input type="checkbox" />',
            'user_id'         => __( 'User', 'wordpress-membership-pro' ),
            'status'          => __( 'Status', 'wordpress-membership-pro' ),
            'commission_rate' => __( 'Commission Rate (%)', 'wordpress-membership-pro' ),
            'created_at'      => __( 'Date Registered', 'wordpress-membership-pro' ),
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
    protected function get_views() {
        $status_counts = self::get_status_counts();
        $current_status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'all';
        $base_url = admin_url( 'admin.php?page=wmp-affiliates' );

        $views = array();
        $views['all'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            esc_url( remove_query_arg( 'status', $base_url ) ),
            'all' === $current_status ? 'current' : '',
            __( 'All', 'wordpress-membership-pro' ),
            $status_counts['all']
        );

        foreach ( $status_counts as $status => $count ) {
            if ( 'all' === $status || $count === 0 ) {
                continue;
            }
            $url = add_query_arg( 'status', $status, $base_url );
            $views[ $status ] = sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url( $url ),
                $status === $current_status ? 'current' : '',
                esc_html( ucfirst( $status ) ),
                $count
            );
        }

        return $views;
    }

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
     * Render the User column with row actions.
     */
    public function column_user_id( $item ) {
        $user = get_user_by( 'id', $item['user_id'] );
        if ( ! $user ) {
            return __( 'Unknown User', 'wordpress-membership-pro' );
        }

        $actions = array();
        $nonce = wp_create_nonce( 'wmp_affiliate_action_nonce' );
       // $page = request_parameter( 'page' );
		$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '';

        if ( 'pending' === $item['status'] ) {
            $actions['approve'] = sprintf(
                '<a href="?page=%s&action=approve_affiliate&affiliate=%s&_wpnonce=%s">' . __( 'Approve', 'wordpress-membership-pro' ) . '</a>',
                esc_attr( $page ),
                absint( $item['id'] ),
                $nonce
            );
            $actions['reject'] = sprintf(
                '<a href="?page=%s&action=reject_affiliate&affiliate=%s&_wpnonce=%s" style="color:#a00;">' . __( 'Reject', 'wordpress-membership-pro' ) . '</a>',
                esc_attr( $page ),
                absint( $item['id'] ),
                $nonce
            );
        }

        return sprintf( '<a href="%s">%s</a> %s', esc_url( get_edit_user_link( $user->ID ) ), esc_html( $user->display_name ), $this->row_actions( $actions ) );
    }

    /**
     * Render the Status column.
     */
    public function column_status( $item ) {
        return esc_html( ucfirst( $item['status'] ) );
    }

    /**
     * Retrieve affiliates data from the database.
     */
    public static function get_affiliates( $per_page = 20, $page_number = 1, $status = 'all' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wmp_affiliates';
        $sql = "SELECT * FROM {$table_name}";

        if ( 'all' !== $status ) {
            $sql .= $wpdb->prepare( " WHERE status = %s", $status );
        }

        $sql .= ' ORDER BY created_at DESC';
        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

        return $wpdb->get_results( $sql, 'ARRAY_A' );
    }

    /**
     * Returns the count of records in the database.
     */
    public static function get_affiliate_count( $status = 'all' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wmp_affiliates';
        $sql = "SELECT COUNT(*) FROM {$table_name}";

        if ( 'all' !== $status ) {
            $sql .= $wpdb->prepare( " WHERE status = %s", $status );
        }

        return $wpdb->get_var( $sql );
    }

    /**
     * Get the counts of affiliates for each status.
     *
     * @since 1.0.11
     * @return array
     */
    public static function get_status_counts() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wmp_affiliates';

        $counts = array(
            'all'      => 0,
            'pending'  => 0,
            'active'   => 0,
            'rejected' => 0,
        );

        $results = $wpdb->get_results( "SELECT status, COUNT(*) as count FROM {$table_name} GROUP BY status", ARRAY_A );

        $total = 0;
        foreach ( $results as $row ) {
            if ( isset( $counts[ $row['status'] ] ) ) {
                $counts[ $row['status'] ] = (int) $row['count'];
            }
            $total += (int) $row['count'];
        }
        $counts['all'] = $total;

        return $counts;
    }
}
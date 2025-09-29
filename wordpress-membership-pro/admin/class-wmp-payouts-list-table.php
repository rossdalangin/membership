<?php
/**
 * The file that defines the payouts list table class.
 *
 * @link       https://example.com
 * @since      1.0.4
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/admin
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * The payouts list table class.
 *
 * @since      1.0.4
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/admin
 * @author     Jules
 */
class WMP_Payouts_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Payout', 'wordpress-membership-pro' ),
            'plural'   => __( 'Payouts', 'wordpress-membership-pro' ),
            'ajax'     => false
        ] );
    }

    /**
     * Get a list of columns.
     *
     * @return array
     */
    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'affiliate_id' => __( 'Affiliate', 'wordpress-membership-pro' ),
            'amount'       => __( 'Amount', 'wordpress-membership-pro' ),
            'status'       => __( 'Status', 'wordpress-membership-pro' ),
            'created_at'   => __( 'Date Requested', 'wordpress-membership-pro' ),
        ];
    }

    /**
     * Prepare the items for the table to process.
     */
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wmp_payouts';

        $per_page = 20;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [ $columns, $hidden, $sortable ];

        $current_page = $this->get_pagenum();
        $total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );

        $orderby = ( isset( $_GET['orderby'] ) ) ? esc_sql( $_GET['orderby'] ) : 'created_at';
        $order = ( isset( $_GET['order'] ) ) ? esc_sql( $_GET['order'] ) : 'DESC';

        $offset = ( $current_page - 1 ) * $per_page;

        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ), ARRAY_A
        );
    }

    /**
     * Default column rendering.
     *
     * @param  array  $item
     * @param  string $column_name
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'affiliate_id':
                $affiliate_handler = new WMP_Affiliates();
                $affiliate = $affiliate_handler->get_affiliate( $item['affiliate_id'] );
                $user = get_user_by( 'id', $affiliate->user_id );
                return $user ? $user->display_name : __( 'N/A', 'wordpress-membership-pro' );
            case 'amount':
                return '$' . number_format_i18n( $item[ $column_name ], 2 );
            case 'created_at':
                return date_i18n( get_option( 'date_format' ), strtotime( $item[ $column_name ] ) );
            case 'status':
                return esc_html( ucfirst( $item[ $column_name ] ) );
            default:
                return print_r( $item, true );
        }
    }

    /**
     * Render the checkbox column.
     *
     * @param  array $item
     * @return string
     */
    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="payout[]" value="%s" />', $item['id']
        );
    }

    /**
     * Get the sortable columns.
     *
     * @return array
     */
    public function get_sortable_columns() {
        return [
            'amount' => [ 'amount', false ],
            'status' => [ 'status', false ],
            'created_at' => [ 'created_at', true ],
        ];
    }
}
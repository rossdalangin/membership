<?php
/**
 * The file that defines the core reporting functions.
 *
 * @link       https://example.com
 * @since      1.0.5
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 */

/**
 * The reporting class.
 *
 * @since      1.0.5
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 * @author     Jules
 */
class WMP_Reports {

    /**
     * Calculate the Monthly Recurring Revenue (MRR).
     *
     * @since 1.0.5
     * @return float The calculated MRR.
     */
    public function get_mrr() {
        global $wpdb;
        $subscriptions_table = $wpdb->prefix . 'wmp_subscriptions';
        $mrr = 0.00;

        $active_subscriptions = $wpdb->get_results( "SELECT plan_id FROM {$subscriptions_table} WHERE status = 'active'" );

        if ( ! empty( $active_subscriptions ) ) {
            foreach ( $active_subscriptions as $subscription ) {
                $price = get_post_meta( $subscription->plan_id, '_wmp_price', true );
                $billing_period = get_post_meta( $subscription->plan_id, '_wmp_billing_period', true );
                $billing_frequency = get_post_meta( $subscription->plan_id, '_wmp_billing_frequency', true );

                if ( ! empty( $price ) && ! empty( $billing_period ) && ! empty( $billing_frequency ) ) {
                    $price = (float) $price;
                    $billing_frequency = (int) $billing_frequency;
                    $monthly_value = 0;

                    switch ( $billing_period ) {
                        case 'day':
                            $monthly_value = ( $price / $billing_frequency ) * 30;
                            break;
                        case 'week':
                            $monthly_value = ( $price / $billing_frequency ) * 4;
                            break;
                        case 'month':
                            $monthly_value = $price / $billing_frequency;
                            break;
                        case 'year':
                            $monthly_value = ( $price / $billing_frequency ) / 12;
                            break;
                    }
                    $mrr += $monthly_value;
                }
            }
        }

        return $mrr;
    }

    /**
     * Export all transactions to a CSV file.
     *
     * @since 1.0.5
     */
    public function export_transactions_to_csv() {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'wmp_transactions';
        $transactions = $wpdb->get_results( "SELECT * FROM {$transactions_table}", ARRAY_A );

        if ( empty( $transactions ) ) {
            return;
        }

        $filename = 'wmp-transactions-' . date( 'Y-m-d' ) . '.csv';

        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

        $output = fopen( 'php://output', 'w' );

        // Add headers
        fputcsv( $output, array_keys( $transactions[0] ) );

        // Add data
        foreach ( $transactions as $transaction ) {
            fputcsv( $output, $transaction );
        }

        fclose( $output );
        exit;
    }

    /**
     * Calculate the Churn Rate for the last 30 days.
     *
     * @since 1.0.6
     * @return float The churn rate percentage.
     */
    public function get_churn_rate() {
        global $wpdb;
        $subscriptions_table = $wpdb->prefix . 'wmp_subscriptions';

        $start_date = date( 'Y-m-d H:i:s', strtotime( '-30 days' ) );

        // Customers at the start of the period
        $initial_customers = $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM {$subscriptions_table} WHERE start_date < %s", $start_date )
        );

        if ( $initial_customers == 0 ) {
            return 0; // Cannot divide by zero
        }

        // Customers who churned during the period
        $churned_customers = $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM {$subscriptions_table} WHERE status = 'cancelled' AND updated_at >= %s", $start_date )
        );

        $churn_rate = ( $churned_customers / $initial_customers ) * 100;

        return round( $churn_rate, 2 );
    }

    /**
     * Calculate the Customer Lifetime Value (LTV).
     *
     * @since 1.0.6
     * @return float The calculated LTV.
     */
    public function get_ltv() {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'wmp_transactions';
        $subscriptions_table = $wpdb->prefix . 'wmp_subscriptions';

        // 1. Calculate Average Revenue Per Account (ARPA)
        $total_revenue = $wpdb->get_var( "SELECT SUM(amount) FROM {$transactions_table} WHERE status = 'completed'" );
        $total_customers = $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$subscriptions_table}" );

        if ($total_customers == 0) {
            return 0;
        }

        $arpa = $total_revenue / $total_customers;

        // 2. Calculate Customer Lifetime (1 / Churn Rate)
        $churn_rate = $this->get_churn_rate();
        if ( $churn_rate == 0 ) {
            return 0; // Avoid division by zero, indicates no churn or infinite lifetime
        }
        $customer_lifetime = 1 / ( $churn_rate / 100 );

        // 3. LTV = ARPA * Customer Lifetime
        $ltv = $arpa * $customer_lifetime;

        return round( $ltv, 2 );
    }
}
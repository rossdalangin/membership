<?php
/**
 * The file that defines the invoice generation class.
 *
 * @link       https://example.com
 * @since      1.0.2
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/includes
 */

/**
 * The invoice generation class.
 *
 * @since      1.0.2
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/includes
 * @author     Jules
 */
class WMP_Invoices {

    /**
     * Generate and output a PDF invoice for a given transaction.
     *
     * @since 1.0.2
     * @param int $transaction_id The ID of the transaction.
     */
    public function generate_invoice( $transaction_id ) {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'wmp_transactions';
        $transaction = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$transactions_table} WHERE id = %d", $transaction_id ) );

        if ( ! $transaction ) {
            wp_die( __( 'Invalid transaction.', 'wordpress-membership-pro' ) );
        }

        $user_info = get_userdata( $transaction->user_id );
        $plan = get_post( $transaction->subscription_id ); // Assuming subscription_id stores plan_id for now

        // --- PDF Generation using FPDF (Placeholder) ---
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont( 'Arial', 'B', 16 );

        // Header
        $pdf->Cell( 0, 10, 'Invoice', 0, 1, 'C' );
        $pdf->Ln(10);

        // Invoice Details
        $pdf->SetFont( 'Arial', '', 12 );
        $pdf->Cell( 40, 10, 'Transaction ID:', 0 );
        $pdf->Cell( 0, 10, $transaction->id, 0, 1 );
        $pdf->Cell( 40, 10, 'Date:', 0 );
        $pdf->Cell( 0, 10, date_i18n( get_option( 'date_format' ), strtotime( $transaction->created_at ) ), 0, 1 );
        $pdf->Cell( 40, 10, 'Status:', 0 );
        $pdf->Cell( 0, 10, ucfirst( $transaction->status ), 0, 1 );
        $pdf->Ln(10);

        // Customer Details
        $pdf->SetFont( 'Arial', 'B', 12 );
        $pdf->Cell( 0, 10, 'Bill To:', 0, 1 );
        $pdf->SetFont( 'Arial', '', 12 );
        $pdf->Cell( 0, 10, $user_info->display_name, 0, 1 );
        $pdf->Cell( 0, 10, $user_info->user_email, 0, 1 );
        $pdf->Ln(10);

        // Line Items
        $pdf->SetFont( 'Arial', 'B', 12 );
        $pdf->Cell( 130, 10, 'Description', 1 );
        $pdf->Cell( 60, 10, 'Amount', 1, 1, 'C' );
        $pdf->SetFont( 'Arial', '', 12 );
        $pdf->Cell( 130, 10, 'Membership Plan: ' . get_the_title($transaction->subscription_id), 1 );
        $pdf->Cell( 60, 10, '$' . number_format( $transaction->amount, 2 ), 1, 1, 'C' );
        $pdf->Ln(10);

        // Total
        $pdf->SetFont( 'Arial', 'B', 14 );
        $pdf->Cell( 130, 10, 'Total', 0, 0, 'R' );
        $pdf->Cell( 60, 10, '$' . number_format( $transaction->amount, 2 ), 0, 1, 'C' );

        // Output the PDF
        $pdf->Output( 'I', 'Invoice-' . $transaction->id . '.pdf' );
        exit;
    }
}
<?php
/**
 * Admin - New Affiliate Application Email Template
 *
 * @since 1.0.11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<p><?php printf( esc_html__( 'A new affiliate application has been submitted by the user: %s.', 'wordpress-membership-pro' ), '<strong>' . esc_html( $user_login ) . '</strong>' ); ?></p>
<p><?php esc_html_e( 'You can review, approve, or reject this application from the affiliate management page.', 'wordpress-membership-pro' ); ?></p>
<p><a href="<?php echo esc_url( $manage_url ); ?>"><?php esc_html_e( 'Manage Affiliates', 'wordpress-membership-pro' ); ?></a></p>
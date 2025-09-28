<?php
/**
 * Email template for a cancelled subscription.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<h2><?php esc_html_e( 'Your Subscription Has Been Cancelled', 'wordpress-membership-pro' ); ?></h2>

<p><?php printf( esc_html__( 'Hi %s,', 'wordpress-membership-pro' ), esc_html( $user->display_name ) ); ?></p>

<p><?php printf( esc_html__( 'This is a confirmation that your subscription to the %s plan has been cancelled. You will no longer have access to the exclusive content for this plan.', 'wordpress-membership-pro' ), '<strong>' . esc_html( $plan_name ) . '</strong>' ); ?></p>

<p><?php esc_html_e( 'If you believe this was a mistake, please contact us.', 'wordpress-membership-pro' ); ?></p>

<p><?php esc_html_e( 'Thank you.', 'wordpress-membership-pro' ); ?></p>
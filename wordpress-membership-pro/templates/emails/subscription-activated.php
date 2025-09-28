<?php
/**
 * Email template for an activated subscription.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<h2><?php esc_html_e( 'Your Subscription is Active!', 'wordpress-membership-pro' ); ?></h2>

<p><?php printf( esc_html__( 'Hi %s,', 'wordpress-membership-pro' ), esc_html( $user->display_name ) ); ?></p>

<p><?php printf( esc_html__( 'Your subscription to the %s plan has been successfully activated. You can now access all the exclusive content available to members of this plan.', 'wordpress-membership-pro' ), '<strong>' . esc_html( $plan_name ) . '</strong>' ); ?></p>

<p><?php printf( esc_html__( 'You can manage your subscription from your %s.', 'wordpress-membership-pro' ), '<a href="' . esc_url( home_url( '/account' ) ) . '">' . esc_html__( 'account page', 'wordpress-membership-pro' ) . '</a>' ); ?></p>

<p><?php esc_html_e( 'Thank you for being a member!', 'wordpress-membership-pro' ); ?></p>
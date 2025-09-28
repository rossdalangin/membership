<?php
/**
 * Email template for an on-hold order.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<h2><?php esc_html_e( 'Your Order is On-Hold', 'wordpress-membership-pro' ); ?></h2>

<p><?php printf( esc_html__( 'Hi %s,', 'wordpress-membership-pro' ), esc_html( $user->display_name ) ); ?></p>

<p><?php esc_html_e( 'Thank you for your order. We have received it and it is currently on-hold pending payment confirmation. Your subscription will be activated once payment has been received.', 'wordpress-membership-pro' ); ?></p>

<h3><?php esc_html_e( 'Payment Instructions', 'wordpress-membership-pro' ); ?></h3>
<div class="wmp-offline-instructions">
    <?php echo wpautop( wp_kses_post( $instructions ) ); ?>
</div>

<p><?php esc_html_e( 'Thank you.', 'wordpress-membership-pro' ); ?></p>
<?php
/**
 * The file that defines the Stripe gateway class.
 *
 * @link       https://example.com
 * @since      1.0.1
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/gateways
 */

/**
 * The Stripe gateway class.
 *
 * @since      1.0.1
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/gateways
 * @author     Jules
 */
class WMP_Gateway_Stripe {

    /**
     * The ID of the gateway.
     *
     * @since 1.0.1
     * @access public
     * @var string
     */
    public $id = 'stripe';

    /**
     * The title of the gateway.
     *
     * @since 1.0.1
     * @access public
     * @var string
     */
    public $title = 'Stripe';

    /**
     * The description of the gateway.
     *
     * @since 1.0.1
     * @access public
     * @var string
     */
    public $description = 'Pay with your credit card via Stripe.';

    /**
     * The publishable key for the gateway.
     *
     * @since 1.0.1
     * @access private
     * @var string
     */
    private $publishable_key;

    /**
     * The secret key for the gateway.
     *
     * @since 1.0.1
     * @access private
     * @var string
     */
    private $secret_key;

    /**
     * The subscription handler instance.
     *
     * @since    1.0.1
     * @access   protected
     * @var      WMP_Subscriptions    $subscriptions_handler    Handles subscription logic.
     */
    protected $subscriptions_handler;

    /**
     * The transaction handler instance.
     *
     * @since    1.0.2
     * @access   protected
     * @var      WMP_Transactions    $transactions_handler    Handles transaction logic.
     */
    protected $transactions_handler;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.1
     * @param WMP_Subscriptions $subscriptions_handler The subscription handler instance.
     * @param WMP_Transactions  $transactions_handler  The transaction handler instance.
     */
    public function __construct( WMP_Subscriptions $subscriptions_handler, WMP_Transactions $transactions_handler ) {
        $this->subscriptions_handler = $subscriptions_handler;
        $this->transactions_handler = $transactions_handler;

        $options = get_option( 'wmp_settings' );
        $this->publishable_key = isset( $options['stripe_publishable_key'] ) ? $options['stripe_publishable_key'] : '';
        $this->secret_key = isset( $options['stripe_secret_key'] ) ? $options['stripe_secret_key'] : '';
    }

    /**
     * Process the payment and create a subscription.
     *
     * @since 1.0.1
     * @param array $data The data from the checkout form.
     */
    public function process_payment( $data ) {
        // In a real scenario, we would use the Stripe PHP library.
        // For this simulation, we'll assume the payment is successful if a token is present.

        if ( ! isset( $_POST['stripe_token'] ) || empty( $_POST['stripe_token'] ) ) {
            wp_die( __( 'Stripe token not found. Please try again.', 'wordpress-membership-pro' ) );
        }

        $token = sanitize_text_field( $_POST['stripe_token'] );
        $plan_id = absint( $data['plan_id'] );
        $user_id = absint( $data['user_id'] );
        $price = get_post_meta( $plan_id, '_wmp_price', true );

        $final_price = $price;
        $coupon_code = isset( $_POST['wmp_applied_coupon'] ) ? sanitize_text_field( $_POST['wmp_applied_coupon'] ) : '';
        $coupon = null;

        if ( ! empty( $coupon_code ) ) {
            $coupon = WMP_Coupons::get_coupon_by_code( $coupon_code );
            if ( $coupon ) {
                $final_price = WMP_Coupons::calculate_discounted_price( $price, $coupon );
            }
        }

        // --- IMPORTANT: DEVELOPMENT-ONLY SIMULATION ---
        // The following code simulates a Stripe payment but does NOT process a real transaction.
        // For a production environment, you must have the Stripe PHP SDK installed and replace
        // this simulation logic with a proper API call to \Stripe\Charge::create() or a similar method.
        // An admin notice will appear if the Stripe SDK is not detected.
        //
        // Example of a real implementation:
        // if ( ! class_exists( '\Stripe\Stripe' ) ) {
        //     wp_die( 'Stripe PHP library not found.' );
        // }
        // try {
        //     \Stripe\Stripe::setApiKey( $this->secret_key );
        //     $charge = \Stripe\Charge::create([
        //         'amount' => $final_price * 100, // Amount in cents
        //         'currency' => 'usd',
        //         'source' => $token,
        //         'description' => 'Membership Plan: ' . get_the_title( $plan_id ),
        //     ]);
        //     $charge_id = $charge->id;
        // } catch ( \Stripe\Exception\CardException $e ) {
        //     wp_die( 'Card Error: ' . $e->get_error()->message );
        // } catch ( \Exception $e ) {
        //     wp_die( 'An unexpected error occurred. Please try again.' );
        // }
        // --- END OF DEVELOPMENT-ONLY SIMULATION ---

        // Simulate a successful charge for demonstration purposes.
        $charge_id = 'sim_ch_' . uniqid();
        $change_subscription_id = isset( $data['change_subscription_id'] ) ? absint( $data['change_subscription_id'] ) : 0;
        $subscription_id = null;

        if ( $change_subscription_id ) {
            // This is a plan change. First, calculate the proration.
            $proration_charge = $this->subscriptions_handler->change_subscription_plan( $change_subscription_id, $plan_id );

            // If the proration charge is positive, it's an upgrade that requires payment.
            if ( $proration_charge > 0 ) {
                // Here you would create a real Stripe charge for the proration amount.
                // For this simulation, we'll just log it as a completed transaction.
                $this->transactions_handler->create_transaction( array(
                    'subscription_id' => $change_subscription_id,
                    'user_id'         => $user_id,
                    'amount'          => $proration_charge,
                    'gateway'         => $this->id,
                    'transaction_id'  => 'sim_proration_' . uniqid(),
                    'status'          => 'completed',
                ) );
            }

            // For downgrades (negative proration_charge), a credit system would be implemented here.

            $subscription_id = $change_subscription_id;
            $redirect_url = add_query_arg( 'wmp_message', 'plan_changed_success', home_url( '/account' ) );
        } else {
            // This is a new subscription.
            $subscription_data = array(
                'user_id'                 => $user_id,
                'plan_id'                 => $plan_id,
                'status'                  => 'active',
                'start_date'              => current_time( 'mysql' ),
                'gateway'                 => $this->id,
                'gateway_subscription_id' => $charge_id,
            );
            $subscription_id = $this->subscriptions_handler->create_subscription( $subscription_data );
            $redirect_url = add_query_arg( 'wmp_message', 'purchase_success', home_url( '/thank-you' ) );
        }

        // Log the transaction
        if ( $subscription_id ) {
            $this->transactions_handler->create_transaction( array(
                'subscription_id' => $subscription_id,
                'user_id'         => $user_id,
                'amount'          => $final_price,
                'gateway'         => $this->id,
                'transaction_id'  => $charge_id,
                'status'          => 'completed',
            ) );
        }

        // Increment coupon usage if one was applied (only for new subscriptions for now)
        if ( $coupon && ! $change_subscription_id ) {
            WMP_Coupons::increment_usage_count( $coupon->ID );
        }

        // Check for an upsell offer
        $upsell_query = new WP_Query( array(
            'post_type'  => 'wmp_membership_plan',
            'meta_key'   => '_wmp_oto_upsell_for',
            'meta_value' => $plan_id,
            'posts_per_page' => 1,
        ) );

        if ( $upsell_query->have_posts() ) {
            $upsell_plan = $upsell_query->posts[0];
            $redirect_url = add_query_arg( array(
                'wmp_action' => 'oto_upsell',
                'plan_id' => $upsell_plan->ID,
                'subscription_id' => $subscription_id,
            ), home_url( '/one-time-offer' ) );
        }

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Get the HTML for the payment form fields.
     *
     * @since 1.0.1
     * @return string
     */
    public function get_payment_form_fields() {
        ob_start();
        ?>
        <div id="wmp-stripe-card-element" class="wmp-stripe-element" style="margin-bottom: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <!-- A Stripe Element will be inserted here. -->
        </div>
        <!-- Used to display form errors. -->
        <div id="wmp-stripe-card-errors" role="alert" style="color: #a94442;"></div>
        <input type="hidden" name="stripe_token" id="stripe_token" />
        <?php
        return ob_get_clean();
    }

    /**
     * Retrieves a coupon post by its code (title).
     *
     * @since 1.0.1
     * @access private
     * @param string $code The coupon code.
     * @return WP_Post|false The coupon post object or false if not found.
     */

    /**
     * Calculates the discounted price.
     *
     * @since 1.0.1
     * @access private
     * @param float $original_price The original price.
     * @param WP_Post $coupon The coupon post object.
     * @return float The discounted price.
     */

    /**
     * Process a refund for a transaction.
     *
     * @since 1.0.8
     * @param string $charge_id The Stripe charge ID to refund.
     * @return bool True on success, false on failure.
     */
    public function process_refund( $charge_id ) {
        // --- IMPORTANT: DEVELOPMENT-ONLY SIMULATION ---
        // For a production environment, you must have the Stripe PHP SDK installed and
        // replace this simulation logic with a proper API call to \Stripe\Refund::create().
        //
        // Example of a real implementation:
        // if ( ! class_exists( '\Stripe\Stripe' ) ) {
        //     return false;
        // }
        // try {
        //     \Stripe\Stripe::setApiKey( $this->secret_key );
        //     $refund = \Stripe\Refund::create([
        //         'charge' => $charge_id,
        //     ]);
        //     return 'succeeded' === $refund->status;
        // } catch ( \Exception $e ) {
        //     // Log the error message: $e->getMessage()
        //     return false;
        // }
        // --- END OF DEVELOPMENT-ONLY SIMULATION ---

        // Simulate a successful refund for demonstration purposes.
        // In a real scenario, this would return true only if the API call was successful.
        return true;
    }

    /**
     * Get or create a Stripe Customer ID for a user.
     *
     * @since 1.0.9
     * @param WP_User $user The user object.
     * @return string|false The Stripe Customer ID or false on failure.
     */
    public function get_or_create_stripe_customer( $user ) {
        $customer_id = get_user_meta( $user->ID, '_wmp_stripe_customer_id', true );

        if ( ! empty( $customer_id ) ) {
            return $customer_id;
        }

        // --- IMPORTANT: DEVELOPMENT-ONLY SIMULATION ---
        // In a production environment, you would call the Stripe API to create a customer.
        // Example:
        // try {
        //     \Stripe\Stripe::setApiKey( $this->secret_key );
        //     $customer = \Stripe\Customer::create([
        //         'email' => $user->user_email,
        //         'name'  => $user->display_name,
        //     ]);
        //     $customer_id = $customer->id;
        // } catch ( \Exception $e ) {
        //     // Log error
        //     return false;
        // }
        // --- END OF DEVELOPMENT-ONLY SIMULATION ---

        // Simulate creating a customer
        $customer_id = 'cus_' . uniqid();

        update_user_meta( $user->ID, '_wmp_stripe_customer_id', $customer_id );

        return $customer_id;
    }

    /**
     * Attempt to process a recurring payment for a subscription.
     *
     * @since 1.0.9
     * @param object $subscription The subscription object.
     * @return bool True on success, false on failure.
     */
    public function attempt_payment_retry( $subscription ) {
        // --- IMPORTANT: DEVELOPMENT-ONLY SIMULATION ---
        // In a production environment, you would use the Stripe API to create a new invoice
        // or charge the customer directly. This is a placeholder for that logic.
        // Example:
        // $customer_id = get_user_meta( $subscription->user_id, '_wmp_stripe_customer_id', true );
        // $price = get_post_meta( $subscription->plan_id, '_wmp_price', true );
        // try {
        //     \Stripe\Stripe::setApiKey( $this->secret_key );
        //     \Stripe\Charge::create([
        //         'amount'   => $price * 100, // Amount in cents
        //         'currency' => 'usd',
        //         'customer' => $customer_id,
        //         'description' => 'Retry for subscription #' . $subscription->id,
        //     ]);
        //     // The 'invoice.payment_succeeded' webhook will handle reactivating the subscription.
        // } catch ( \Exception $e ) {
        //     // The 'invoice.payment_failed' webhook will handle the next retry or cancellation.
        //     return false;
        // }
        // --- END OF DEVELOPMENT-ONLY SIMULATION ---

        // For simulation purposes, we'll assume the retry is successful.
        // The webhook handler will then update the subscription status.
        return true;
    }

    /**
     * Update the payment method for a Stripe subscription.
     *
     * @since 1.0.10
     * @param string $subscription_id The Stripe Subscription ID.
     * @param string $payment_method_id The new Stripe Payment Method ID.
     * @return bool True on success, false on failure.
     */
    public function update_subscription_payment_method( $subscription_id, $payment_method_id ) {
        // --- IMPORTANT: DEVELOPMENT-ONLY SIMULATION ---
        // In a production environment, you would use the Stripe API to:
        // 1. Attach the new payment method to the customer.
        // 2. Update the subscription to use the new payment method as the default.
        // Example:
        // try {
        //     \Stripe\Stripe::setApiKey( $this->secret_key );
        //     // First, attach the payment method to the customer
        //     \Stripe\PaymentMethod::attach( $payment_method_id, [
        //         'customer' => $customer_id, // You'd need to fetch or have the customer ID
        //     ]);
        //     // Then, update the subscription to use the new payment method
        //     \Stripe\Subscription::update( $subscription_id, [
        //         'default_payment_method' => $payment_method_id,
        //     ]);
        //     return true;
        // } catch ( \Exception $e ) {
        //     // Log error
        //     return false;
        // }
        // --- END OF DEVELOPMENT-ONLY SIMULATION ---

        // Simulate a successful update.
        return true;
    }
}
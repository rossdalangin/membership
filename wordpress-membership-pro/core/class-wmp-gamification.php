<?php
/**
 * The file that defines the core gamification functions.
 *
 * @link       https://example.com
 * @since      1.0.8
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 */

/**
 * The gamification class.
 *
 * @since      1.0.8
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 * @author     Jules
 */
class WMP_Gamification {

    private $referrals_handler;
    private $affiliates_handler;
    private $subscriptions_handler;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.8
     */
    public function __construct( $referrals_handler, $affiliates_handler, $subscriptions_handler ) {
        $this->referrals_handler = $referrals_handler;
        $this->affiliates_handler = $affiliates_handler;
        $this->subscriptions_handler = $subscriptions_handler;
    }

    /**
     * Award a badge to a user.
     *
     * @since    1.0.8
     * @param    int    $user_id     The ID of the user to award the badge to.
     * @param    int    $badge_id    The ID of the badge to award.
     */
    public function award_badge( $user_id, $badge_id ) {
        // Check if the user has already earned this badge to avoid duplicates.
        $existing_badges = get_user_meta( $user_id, '_wmp_earned_badge', false );
        if ( ! in_array( $badge_id, $existing_badges ) ) {
            add_user_meta( $user_id, '_wmp_earned_badge', $badge_id, false );
            do_action( 'wmp_badge_awarded', $user_id, $badge_id );
        }
    }

    /**
     * Check for and award badges when a subscription is activated.
     *
     * @since    1.0.8
     * @param    int    $subscription_id      The ID of the subscription.
     * @param    int    $user_id              The ID of the user.
     */
    public function check_for_badges_on_activation( $subscription_id, $user_id ) {
        $badges_query = new WP_Query( array(
            'post_type'  => 'wmp_badge',
            'meta_key'   => '_wmp_badge_trigger',
            'meta_value' => 'subscription_activated',
        ) );

        if ( $badges_query->have_posts() ) {
            while ( $badges_query->have_posts() ) {
                $badges_query->the_post();
                $this->award_badge( $user_id, get_the_ID() );
            }
        }
        wp_reset_postdata();
    }

    /**
     * Check for and award the "first referral" badge.
     *
     * @since    1.0.9
     * @param    int    $referral_id    The ID of the newly created referral.
     * @param    array  $referral_data  The data for the new referral.
     */
    public function check_for_first_referral_badge( $referral_id, $referral_data ) {
        if ( ! isset( $referral_data['affiliate_id'] ) || ! isset( $referral_data['transaction_id'] ) ) {
            return;
        }

        $affiliate_id = $referral_data['affiliate_id'];

        $referral_count = $this->referrals_handler->get_referral_count( $affiliate_id );

        // We only award the badge on the very first successful referral.
        if ( 1 !== $referral_count ) {
            return;
        }

        $badges_query = new WP_Query( array(
            'post_type'  => 'wmp_badge',
            'meta_key'   => '_wmp_badge_trigger',
            'meta_value' => 'first_referral',
        ) );

        if ( $badges_query->have_posts() ) {
            $affiliates_handler = new WMP_Affiliates();
            $affiliate = $affiliates_handler->get_affiliate( $affiliate_id );
            if ( $affiliate && $affiliate->user_id ) {
                while ( $badges_query->have_posts() ) {
                    $badges_query->the_post();
                    $this->award_badge( $affiliate->user_id, get_the_ID() );
                }
            }
        }
        wp_reset_postdata();
    }

    /**
     * Check for and award anniversary badges.
     * This method is intended to be run by a daily cron job.
     *
     * @since 1.0.9
     */
    public function check_for_anniversary_badges() {
        $badges_query = new WP_Query( array(
            'post_type'      => 'wmp_badge',
            'posts_per_page' => -1,
            'meta_key'       => '_wmp_badge_trigger',
            'meta_value'     => 'anniversary',
        ) );

        if ( ! $badges_query->have_posts() ) {
            return;
        }

        $anniversary_badges = array();
        while ( $badges_query->have_posts() ) {
            $badges_query->the_post();
            $years = (int) get_post_meta( get_the_ID(), '_wmp_badge_trigger_value', true );
            if ( $years > 0 ) {
                $anniversary_badges[ get_the_ID() ] = $years;
            }
        }
        wp_reset_postdata();

        if ( empty( $anniversary_badges ) ) {
            return;
        }

        $users = get_users();
        $today = new DateTime();

        foreach ( $users as $user ) {
            $subscriptions = $this->subscriptions_handler->get_user_subscriptions( $user->ID );

            if ( empty( $subscriptions ) ) {
                continue;
            }

            foreach ( $subscriptions as $subscription ) {
                if ( 'active' !== $subscription->status ) {
                    continue;
                }

                $start_date = new DateTime( $subscription->start_date );
                $interval = $today->diff( $start_date );
                $years_active = $interval->y;

                foreach ( $anniversary_badges as $badge_id => $required_years ) {
                    if ( $years_active >= $required_years ) {
                        $meta_key = '_wmp_anniversary_badge_awarded_' . $required_years;
                        $already_awarded = get_user_meta( $user->ID, $meta_key, true );
                        if ( ! $already_awarded ) {
                            $this->award_badge( $user->ID, $badge_id );
                            update_user_meta( $user->ID, $meta_key, true );
                        }
                    }
                }
            }
        }
    }
}
<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/includes
 * @author     Jules
 */
class WMP_Activator {

	/**
	 * The main activation method.
	 *
	 * This method is called when the plugin is activated. It's responsible for
	 * tasks like creating database tables and setting default options.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Subscriptions Table
		$table_name = $wpdb->prefix . 'wmp_subscriptions';
		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			plan_id bigint(20) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			start_date datetime NOT NULL,
			end_date datetime DEFAULT NULL,
			trial_end datetime DEFAULT NULL,
			gateway varchar(50) NOT NULL,
			gateway_subscription_id varchar(255) DEFAULT '' NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY plan_id (plan_id),
			KEY gateway_subscription_id (gateway_subscription_id)
		) $charset_collate;";
		dbDelta( $sql );

		// Transactions Table
		$table_name = $wpdb->prefix . 'wmp_transactions';
		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			subscription_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			amount decimal(10,2) NOT NULL,
			gateway varchar(50) NOT NULL,
			transaction_id varchar(255) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY subscription_id (subscription_id),
			KEY user_id (user_id),
			KEY transaction_id (transaction_id)
		) $charset_collate;";
		dbDelta( $sql );

		// Affiliates Table
		$table_name = $wpdb->prefix . 'wmp_affiliates';
		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			commission_rate decimal(5,2) NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_id (user_id)
		) $charset_collate;";
		dbDelta( $sql );

		// Referrals Table
		$table_name = $wpdb->prefix . 'wmp_referrals';
		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			affiliate_id bigint(20) NOT NULL,
			referring_url text,
			ip_address varchar(100) NOT NULL,
			transaction_id bigint(20) DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY affiliate_id (affiliate_id)
		) $charset_collate;";
		dbDelta( $sql );

		// --- CPT Registration and Rewrite Rule Flushing ---
		// It's important to register CPTs before flushing rewrite rules on activation.

		// Load dependencies for CPTs
		require_once WMP_PLUGIN_DIR . 'core/class-wmp-cpts.php';
		require_once WMP_PLUGIN_DIR . 'core/class-wmp-coupons.php';

		// Register CPTs
		$cpts = new WMP_CPTs();
		$cpts->register();
		$coupons = new WMP_Coupons();
		$coupons->register_cpt();

		// Flush rewrite rules to ensure the new CPTs are recognized.
		flush_rewrite_rules();
	}

}
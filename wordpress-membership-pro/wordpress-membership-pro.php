<?php
/**
 * Plugin Name:       WordPress Membership Pro
 * Plugin URI:        https://example.com/
 * Description:       A premium, feature-rich membership plugin for WordPress designed for scalability and extensibility.
 * Version:           1.0.0
 * Author:            Jules
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wordpress-membership-pro
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define constants
 */
define( 'WMP_VERSION', '1.0.0' );
define( 'WMP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WMP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_wmp() {
	require_once WMP_PLUGIN_DIR . 'includes/class-wmp-activator.php';
	WMP_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wmp() {
	require_once WMP_PLUGIN_DIR . 'includes/class-wmp-deactivator.php';
	WMP_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wmp' );
register_deactivation_hook( __FILE__, 'deactivate_wmp' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require WMP_PLUGIN_DIR . 'core/class-wordpress-membership-pro.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wmp() {
	$plugin = new WordPress_Membership_Pro();
	$plugin->run();
}

run_wmp();
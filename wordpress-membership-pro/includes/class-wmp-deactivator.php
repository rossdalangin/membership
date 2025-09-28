<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/includes
 * @author     Jules
 */
class WMP_Deactivator {

	/**
	 * The main deactivation method.
	 *
	 * This method is called when the plugin is deactivated. It's responsible for
	 * any cleanup tasks, but typically does not remove data.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Deactivation logic, such as flushing rewrite rules, can go here.
	}

}
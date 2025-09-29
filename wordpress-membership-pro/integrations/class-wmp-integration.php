<?php
/**
 * The file that defines the base integration class.
 *
 * @link       https://example.com
 * @since      1.0.6
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/integrations
 */

/**
 * The base integration class.
 *
 * This class serves as a template for all email marketing integrations.
 *
 * @since      1.0.6
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/integrations
 * @author     Jules
 */
abstract class WMP_Integration {

    /**
     * The ID of the integration.
     *
     * @since 1.0.6
     * @access public
     * @var string
     */
    public $id;

    /**
     * The title of the integration.
     *
     * @since 1.0.6
     * @access public
     * @var string
     */
    public $title;

    /**
     * The API key for the integration.
     *
     * @since 1.0.6
     * @access protected
     * @var string
     */
    protected $api_key;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.6
     */
    public function __construct() {
        $this->load_settings();
    }

    /**
     * Load the settings for the integration.
     *
     * @since 1.0.6
     * @access private
     */
    abstract protected function load_settings();

    /**
     * Add a subscriber to a list.
     *
     * @since 1.0.6
     * @param string $email The email address to add.
     * @param string $list_id The ID of the list to add the subscriber to.
     * @return bool True on success, false on failure.
     */
    abstract public function add_subscriber( $email, $list_id );

    /**
     * Remove a subscriber from a list.
     *
     * @since 1.0.6
     * @param string $email The email address to remove.
     * @param string $list_id The ID of the list to remove the subscriber from.
     * @return bool True on success, false on failure.
     */
    abstract public function remove_subscriber( $email, $list_id );

}
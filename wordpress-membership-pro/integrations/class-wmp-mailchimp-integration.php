<?php
/**
 * The file that defines the Mailchimp integration class.
 *
 * @link       https://example.com
 * @since      1.0.7
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/integrations
 */

/**
 * The Mailchimp integration class.
 *
 * @since      1.0.7
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/integrations
 * @author     Jules
 */
class WMP_Mailchimp_Integration extends WMP_Integration {

    /**
     * The server prefix for the Mailchimp API.
     *
     * @since 1.0.7
     * @access private
     * @var string
     */
    private $server_prefix;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.7
     */
    public function __construct() {
        $this->id = 'mailchimp';
        $this->title = 'Mailchimp';
        parent::__construct();
    }

    /**
     * Load the settings for the integration.
     *
     * @since 1.0.7
     * @access protected
     */
    protected function load_settings() {
        $options = get_option( 'wmp_settings' );
        $this->api_key = isset( $options['mailchimp_api_key'] ) ? $options['mailchimp_api_key'] : '';
        if ( ! empty( $this->api_key ) ) {
            $parts = explode( '-', $this->api_key );
            $this->server_prefix = isset( $parts[1] ) ? $parts[1] : '';
        }
    }

    /**
     * Add a subscriber to a list.
     *
     * @since 1.0.7
     * @param string $email The email address to add.
     * @param string $list_id The ID of the list to add the subscriber to.
     * @return bool True on success, false on failure.
     */
    public function add_subscriber( $email, $list_id ) {
        if ( empty( $this->api_key ) || empty( $this->server_prefix ) ) {
            return false;
        }

        $url = "https://{$this->server_prefix}.api.mailchimp.com/3.0/lists/{$list_id}/members/";

        $body = json_encode( array(
            'email_address' => $email,
            'status'        => 'subscribed',
        ) );

        $response = wp_remote_post( $url, array(
            'headers' => array(
                'Authorization' => 'apikey ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => $body,
        ) );

        $response_code = wp_remote_retrieve_response_code( $response );

        // 200 is success, 400 with 'member_exists' is also considered success for our purpose.
        if ( 200 === $response_code ) {
            return true;
        } elseif ( 400 === $response_code ) {
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $response_body['title'] ) && 'Member Exists' === $response_body['title'] ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Remove a subscriber from a list by archiving them.
     *
     * @since 1.0.7
     * @param string $email The email address to remove.
     * @param string $list_id The ID of the list to remove the subscriber from.
     * @return bool True on success, false on failure.
     */
    public function remove_subscriber( $email, $list_id ) {
        if ( empty( $this->api_key ) || empty( $this->server_prefix ) ) {
            return false;
        }

        $subscriber_hash = md5( strtolower( $email ) );
        $url = "https://{$this->server_prefix}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$subscriber_hash}";

        $response = wp_remote_request( $url, array(
            'method'  => 'DELETE',
            'headers' => array(
                'Authorization' => 'apikey ' . $this->api_key,
            ),
        ) );

        $response_code = wp_remote_retrieve_response_code( $response );

        // A 204 No Content response means the deletion was successful.
        return 204 === $response_code;
    }

    /**
     * Get all Mailchimp lists.
     *
     * @since 1.0.7
     * @return array|false The lists or false on failure.
     */
    public function get_lists() {
        if ( empty( $this->api_key ) || empty( $this->server_prefix ) ) {
            return false;
        }

        $url = "https://{$this->server_prefix}.api.mailchimp.com/3.0/lists/";

        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Authorization' => 'apikey ' . $this->api_key,
            ),
        ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return isset( $body['lists'] ) ? $body['lists'] : false;
    }
}
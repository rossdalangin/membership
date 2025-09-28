<?php
/**
 * The file that defines the core email handling functions.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 */

/**
 * The core email handling class.
 *
 * This is used to manage all email sending functionality, including
 * loading templates and ensuring consistent branding.
 *
 * @since      1.0.0
 * @package    WordPress_Membership_Pro
 * @subpackage WordPress_Membership_Pro/core
 * @author     Jules
 */
class WMP_Emails {

    /**
     * The single instance of the class.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WMP_Emails
     */
    protected static $instance = null;

    /**
     * Ensures only one instance of the class is loaded.
     *
     * @since    1.0.0
     * @static
     * @return   WMP_Emails - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Send an email.
     *
     * @since    1.0.0
     * @param    string    $to             The email address of the recipient.
     * @param    string    $subject        The subject of the email.
     * @param    string    $template_name  The name of the template file to use.
     * @param    array     $args           Arguments to pass to the template.
     * @return   bool                      True if the email was sent successfully, false otherwise.
     */
    public function send( $to, $subject, $template_name, $args = array() ) {
        $message = $this->get_email_content( $template_name, $args );

        if ( ! $message ) {
            return false;
        }

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        return wp_mail( $to, $subject, $message, $headers );
    }

    /**
     * Get the content of an email template.
     *
     * @since    1.0.0
     * @param    string    $template_name  The name of the template file.
     * @param    array     $args           Arguments to pass to the template.
     * @return   string|false              The email content or false if the template is not found.
     */
    private function get_email_content( $template_name, $args = array() ) {
        $template_path = 'emails/' . $template_name . '.php';

        // Look for a custom template in the theme first.
        $template = locate_template( array(
            'wordpress-membership-pro/' . $template_path,
        ) );

        // If no custom template is found, use the default one from the plugin.
        if ( ! $template ) {
            $template = WMP_PLUGIN_DIR . 'templates/' . $template_path;
        }

        if ( ! file_exists( $template ) ) {
            return false;
        }

        // Pass arguments to the template.
        extract( $args );

        ob_start();
        include( $template );
        return ob_get_clean();
    }
}

/**
 * The main function for returning the WMP_Emails instance.
 *
 * @since 1.0.0
 * @return WMP_Emails
 */
function WMP_Emails() {
    return WMP_Emails::instance();
}
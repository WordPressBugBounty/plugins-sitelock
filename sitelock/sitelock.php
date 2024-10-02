<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.sitelock.com
 * @since             1.9.0
 * @package           Sitelock
 *
 * @wordpress-plugin
 * Plugin Name:       SiteLock
 * Plugin URI:        https://www.sitelock.com/wordpress
 * Description:       Offers deep scan and site compliance
 * Version:           4.2.3
 * Author:            SiteLock
 * Author URI:        https://www.sitelock.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sitelock
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// name of HTTP header with an initial IP
define( 'SITELOCK_IP_HEADER', "HTTP_INCAP_CLIENT_IP" );

try {
    //stop process if there is no header
    if ( empty( sanitize_text_field( $_SERVER[ 'SITELOCK_IP_HEADER' ] ) ) ) {
        throw new Exception( 'No header defined', 1 );
    }
    
    //validate header value
    if ( function_exists( 'filter_var' ) ) {
        $ip = filter_var( sanitize_text_field( $_SERVER[ 'SITELOCK_IP_HEADER' ] ), FILTER_VALIDATE_IP );
        if ( false === $ip ) {
            throw new Exception( 'The value is not a valid IP address', 2 );
        }
    } else {
        $ip = sanitize_text_field( $_SERVER[ 'SITELOCK_IP_HEADER' ] );

        if ( false === preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $ip ) ) {
            throw new Exception( 'The value is not a valid IP address', 2 );
        }
    }
    
    //At this point the initial IP value is exist and validated
    $_SERVER[ 'REMOTE_ADDR' ] = $ip;
} catch (Exception $e) {}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sitelock-activator.php
 */
function activate_sitelock() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-sitelock-activator.php';
	Sitelock_Activator::activate();

    /* Create transient data */
    set_transient( 'slwp-plugin-activation-notice', true, 5 );
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sitelock-deactivator.php
 */
function deactivate_sitelock() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-sitelock-deactivator.php';
	Sitelock_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_sitelock' );
register_deactivation_hook( __FILE__, 'deactivate_sitelock' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-sitelock.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.9.0
 */
function run_sitelock() {

	$plugin = new Sitelock();
	$plugin->run();

    return $plugin->get_version();

}

$plugin_version = run_sitelock();


/**
 * Handles the auth connection 
 */
$sitelockapi = new Sitelock_API( $plugin_version );
add_action( 'admin_post_handle_auth_key', array( $sitelockapi, 'handle_auth' ) );


/**
 * WP Head stuff
 */
add_action( 'wp_head', 'sitelock_add_meta_tag' );


/**
 * Manage Columns Addition
 */
add_filter( 'manage_pages_columns', 'sitelock_page_sitelock_scan' );
add_action( 'manage_pages_custom_column',  'sitelock_page_sitelock_scan_results' );


/**
 * Manage Columns Addition
 */
add_action( 'wp_footer', 'sitelock_add_this_script_footer' );


/**
 * Add admin notice 
 */
add_action( 'admin_notices', 'slwp_plugin_activation_notice' );


/**
 * Admin Notice on Activation.
 * 
 * @since  3.5.0
 */
function slwp_plugin_activation_notice()
{ 
    /* Check transient, if available display notice */
    if( get_transient( 'slwp-plugin-activation-notice' ) )
    {
        ?>
        <div class="updated notice is-dismissible">
            <p>Thank you for installing the SiteLock plugin.  <a href="<?php echo esc_url_raw( admin_url( 'tools.php?page=sitelock' ) ); ?>">Click here</a> to get started.</p>
        </div>
        <?php

        /** 
         * Delete transient, only display this notice once. 
         */
        delete_transient( 'slwp-plugin-activation-notice' );
    }
}






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
 * Plugin Name:       SiteLock Security – WP Hardening, Login Security & Malware Scans
 * Plugin URI:        https://www.sitelock.com/wordpress
 * Description:       Free, lightweight WordPress security. WP Hardening, login protection and Site Health & on‑demand checks without slowing your site. Setup in minutes.
 * Version:           5.0.2
 * Author:            SiteLockSecurity
 * Author URI:        https://www.sitelock.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sitelock-wordpress-plugin
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// name of HTTP header with an initial IP
define('SITELOCK_IP_HEADER', "HTTP_INCAP_CLIENT_IP");

try {
    //stop process if there is no header
    if (empty(sanitize_text_field(wp_unslash($_SERVER['SITELOCK_IP_HEADER'] ?? "")))) {
        throw new Exception('No header defined', 1);
    }

    //validate header value
    if (function_exists('filter_var')) {
        $ip = filter_var(sanitize_text_field(wp_unslash($_SERVER['SITELOCK_IP_HEADER'])), FILTER_VALIDATE_IP);
        if (false === $ip) {
            throw new Exception('The value is not a valid IP address', 2);
        }
    } else {
        $ip = sanitize_text_field(wp_unslash($_SERVER['SITELOCK_IP_HEADER']));

        if (false === preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $ip)) {
            throw new Exception('The value is not a valid IP address', 2);
        }
    }

    //At this point the initial IP value is exist and validated
    $_SERVER['REMOTE_ADDR'] = $ip;
} catch (Exception $e) {
}

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! defined( 'SITELOCK_PLUGIN_DIR' ) ) {
    define( 'SITELOCK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
// Include helper globally
require_once plugin_dir_path( __FILE__ ) . 'includes/sitelock-filesystem-helpers.php';

require_once plugin_dir_path(__FILE__) . 'includes/class-sitelock-crypto.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sitelock-activator.php
 */
function activate_sitelock()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-sitelock-activator.php';
    Sitelock_Activator::activate();

    /* Create transient data */
    set_transient('slwp-plugin-activation-notice', true, 5);
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sitelock-deactivator.php
 */
function deactivate_sitelock()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-sitelock-deactivator.php';
    Sitelock_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_sitelock');
register_deactivation_hook(__FILE__, 'deactivate_sitelock');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-sitelock.php';


register_activation_hook(__FILE__, ['SiteLock_Admin_Monitor', 'on_activation']);
register_deactivation_hook(__FILE__, ['SiteLock_Admin_Monitor', 'on_deactivation']);

register_activation_hook(__FILE__, ['Sitelock_Login_Logger', 'on_activation']);
register_deactivation_hook(__FILE__, ['Sitelock_Login_Logger', 'on_deactivation']);


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.9.0
 */
function run_sitelock()
{

    $plugin = new Sitelock();
    $plugin->run();

    return $plugin->get_version();

}

$plugin_version = run_sitelock();


/**
 * Handles the auth connection
 */
$sitelockapi = new Sitelock_API($plugin_version);

add_action('admin_post_handle_auth_key', array($sitelockapi->auth, 'handle_auth'));

// Pass the nonce to the function call
add_action('admin_post_activate_email_key', function() use ($sitelockapi) {
    $nonce = wp_create_nonce('activate_email_key_action'); // Generate nonce within the function
    $sitelockapi->auth->activate_email_key($nonce);
});

/**
 * WP Head stuff
 */
add_action('wp_head', 'sitelock_add_meta_tag');



/**
 * Manage Columns Addition
 */
add_action('wp_footer', 'sitelock_add_this_script_footer');


/**
 * Add admin notice
 */
add_action('admin_notices', 'slwp_plugin_activation_notice');


/**
 * Admin Notice on Activation.
 *
 * @since  3.5.0
 */
function slwp_plugin_activation_notice()
{
    /* Check transient, if available display notice */
    if (get_transient('slwp-plugin-activation-notice')) {
        ?>
        <div class="updated notice is-dismissible">
            <p>Thank you for installing the SiteLock Security plugin. <a
                        href="<?php echo esc_url_raw(admin_url('admin.php?page=sitelock')); ?>">Click here</a> to get started.
            </p>
        </div>
        <?php

        /**
         * Delete transient, only display this notice once.
         */
        delete_transient('slwp-plugin-activation-notice');
    }
}
function sitelock_delete_plugin_options() {
    global $wpdb;

    // Delete all options starting with 'sitelock_'
    $options = wp_load_alloptions();
    foreach ($options as $option_name => $option_value) {
        if (strpos($option_name, 'sitelock_') === 0) {
            delete_option($option_name);
        }
    }

    // Delete all user meta keys starting with 'sitelock_'
    $users = get_users();

    // Loop through each user and delete metadata with the prefix 'sitelock_'
    foreach ($users as $user) {
        $user_id = $user->ID;

        // Get all user meta for the current user
        $user_meta = get_user_meta($user_id);

        // Loop through user meta keys and delete those starting with 'sitelock_'
        foreach ($user_meta as $meta_key => $meta_value) {
            if (strpos($meta_key, 'sitelock_') === 0) {
                delete_metadata('user', $user_id, $meta_key, '', true);
            }
        }
    }
}
function sitelock_remove_htaccess_rules() {
    $htaccess_file = ABSPATH . '.htaccess';

    if (file_exists($htaccess_file) && filesystem_is_writable($htaccess_file)) {
        $htaccess_content = file_get_contents($htaccess_file);
        $htaccess_content = preg_replace('/# SitelockRulesStart.*?# SitelockRulesEnd/s', '', $htaccess_content);

        // Save the cleaned file
        file_put_contents($htaccess_file, trim($htaccess_content) . PHP_EOL);
    }
}

function sitelock_plugin_deactivate() {
    sitelock_delete_plugin_options(); // Clean database
    sitelock_remove_htaccess_rules(); // Remove .htaccess modifications
}

register_uninstall_hook(__FILE__, 'sitelock_plugin_deactivate');
register_deactivation_hook(__FILE__, 'sitelock_plugin_deactivate');

function sitelock_sanitize_function( $input ) {
    return sanitize_text_field( $input ); // Or use another appropriate sanitizer
}

function get_language_tokens() {
    $json_path = plugin_dir_path(__FILE__) . 'languages/en.json';

    if (!file_exists($json_path)) {
        return []; // Or handle error
    }

    $json = file_get_contents($json_path);
    $tokens = json_decode($json, true); // decode as associative array

    return $tokens;
}

add_action('admin_enqueue_scripts', 'sitelock_admin_notice_css');

/**
 * Checks if the current screen is part of the SiteLock plugin's admin pages.
 *
 * @return bool True if the current screen is a SiteLock plugin page, false otherwise.
 */
function sitelock_is_plugin_page() {
    $screen = get_current_screen();
    if (!$screen) return false;

    // Replace with your plugin's screen ID or part of it
    return strpos($screen->id, 'sitelock') !== false;
}
function sitelock_admin_notice_css() {
    // Only apply on your plugin admin page(s)
    if (sitelock_is_plugin_page()) {
        echo '<style>
            /* Target all warning/error notices */
            .notice.notice-warning,
            .notice.notice-error {
                max-width: 1115px; /* Set your desired max width */
            }
        </style>';
    }
}

function sitelock_remove_admin_footer_text($text) {
    // Check if we are on SiteLock plugin's admin page
    if (sitelock_is_plugin_page()) {
        return ''; // Remove left-side footer text
    }
    return $text; // Return original text for other pages
}

function sitelock_remove_update_footer_text($text) {
    // Check if we are on SiteLock plugin's admin page
    if (sitelock_is_plugin_page()) {
        return ''; // Remove right-side version text
    }
    return $text; // Return original text for other pages
}

add_filter('admin_footer_text', 'sitelock_remove_admin_footer_text');
add_filter('update_footer', 'sitelock_remove_update_footer_text', 11);

/**
 * Logger class for SiteLock plugin.
 *
 * Handles logging messages to a file with rotation and security measures.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/logging/class-sitelock-logger.php';

/**
 * Global helper function for Logging.
 *
 * Usage: sitelock_log( 'error', 'Title', 'Detailed description', [ 'foo' => 'bar' ], __CLASS__ );
 *
 * @return bool
 */
function sitelock_log( $level, $title, $message = '', $context = array(), $class = '' ) {
    return SiteLock_Logger::instance()->log( $level, $title, $message, $context, $class );
}
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
 * Version:           5.1.0
 * Author:            SiteLockSecurity
 * Author URI:        https://www.sitelock.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
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
        $sitelock_ip = filter_var(sanitize_text_field(wp_unslash($_SERVER['SITELOCK_IP_HEADER'])), FILTER_VALIDATE_IP);
        if (false === $sitelock_ip) {
            throw new Exception('The value is not a valid IP address', 2);
        }
    } else {
        $sitelock_ip = sanitize_text_field(wp_unslash($_SERVER['SITELOCK_IP_HEADER']));

        if (false === preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $sitelock_ip)) {
            throw new Exception('The value is not a valid IP address', 2);
        }
    }

    //At this point the initial IP value is exist and validated
    $_SERVER['REMOTE_ADDR'] = $sitelock_ip;
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

require_once plugin_dir_path(__FILE__) . 'includes/class-sitelock-secure-cookie.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sitelock-activator.php
 */
function sitelock_activate()
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
function sitelock_deactivate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-sitelock-deactivator.php';
    Sitelock_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'sitelock_activate');
register_deactivation_hook(__FILE__, 'sitelock_deactivate');

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
 * Enforce 2FA after WP has validated username + password.
 *
 * This runs via wp_authenticate_user (receives a WP_User object or WP_Error).
 *
 * @param WP_User|WP_Error $user     WP_User on success, WP_Error on earlier failure.
 * @param string           $password Raw password input.
 * @return WP_User|WP_Error
 */
function sitelock_check_2fa_after_login($user, $password) {
    // If WP already returned an error (bad creds etc.), just pass it through.
    if (is_wp_error($user)) {
        return $user;
    }

    // Safety: ensure we have a WP_User object.
    if (!($user instanceof WP_User)) {
        return $user;
    }

    // Only enforce 2FA for users who can edit posts
    if (!user_can($user, 'edit_posts')) {
        return $user;
    }

    // IMPORTANT: The wp_authenticate_user filter fires BEFORE WordPress checks the password.
    // We must manually validate the password here to avoid 2FA bypass with valid username + valid TOTP.
    $raw_password = (string) $password;
    if (!wp_check_password($raw_password, $user->user_pass, $user->ID)) {
        // Return an auth error so WP treats credentials as invalid.
        return new WP_Error('incorrect_password', __('Invalid username or password.', 'sitelock-wordpress-plugin'));
    }

    // Custom helper that returns whether 2FA is enabled/configured for user
    $user_2fa_status = sitelock_get_user_2fa_status($user);
    $user_id = $user->ID;

    // If 2FA is required and configured, prevent completing the login and show 2FA form.
    if (!empty($user_2fa_status['2fa_enabled']) && !empty($user_2fa_status['has_2fa'])) {

        // Save pending user id in session (used by 2FA page)
        $cookie_set = sitelock_set_pending_user_cookie($user_id);

        if (!$cookie_set) {
            return new WP_Error(
                'sitelock_2fa_error',
                esc_html('Could not initiate Two-Factor Authentication due to a secure session error. Please try again or contact the administrator.')
            );
        }

        // Create a nonce for the POST form
        $nonce = wp_create_nonce('sitelock_2fa_verify');

        $action_url = sitelock_build_url_with_query_params(site_url('/wp-login.php?action=sitelock-2fa'));

        // Render the 2FA form HTML (your existing renderer)
        sitelock_render_2fa_form($action_url, $nonce);

        // Halt execution so WP doesn't proceed to complete the authentication.
        exit;
    }

    // No 2FA required — allow login to continue.
    return $user;
}
add_filter('wp_authenticate_user', 'sitelock_check_2fa_after_login', 10, 2);

/**
 * Build a URL with additional query parameters from the request.
 *
 * @param  string $base_url The base URL to which query parameters will be added.
 * @param  array  $exclude_keys Array of keys to exclude from the query parameters.
 * @return string The URL with appended query parameters.
 */
function sitelock_build_url_with_query_params($base_url, $exclude_keys = [])
{
    // Ensure the base URL is valid
    $redirect_to = esc_url_raw($base_url);

    // Default to admin URL if the base URL is empty
    if (empty($redirect_to)) {
        $redirect_to = admin_url();
    }

    // Keys to skip from the request
    $skip_keys = array_merge([
        'log', 'pwd', 'rememberme', 'wp-submit', 'testcookie', 'action',
        'sitelock_2fa_nonce', 'totp_code', 'recovery_code', '_wp_http_referer',
    ], $exclude_keys);

    $extra = [];
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified elsewhere in the code.
    foreach ($_REQUEST as $key => $value) {
        if (in_array($key, $skip_keys, true)) {
            continue;
        }
        if (is_scalar($value)) {
            if ($key === 'redirect_to') {
                $extra['redirect_to'] = esc_url_raw(wp_unslash($value));
            } else {
                $extra[sanitize_key($key)] = sanitize_text_field(wp_unslash($value));
            }
        }
    }

    // Append additional query parameters to the URL
    if ($extra) {
        $redirect_to = add_query_arg($extra, $redirect_to);
    }

    // Safeguard: Prevent redirecting to admin.php without parameters (blank page)
    // We check if the path ends in admin.php and has no query string
    $parsed = parse_url($redirect_to);
    $path   = isset($parsed['path']) ? $parsed['path'] : '';
    $query  = isset($parsed['query']) ? $parsed['query'] : '';

    if (basename($path) === 'admin.php' && empty($query)) {
        return admin_url();
    }

    return $redirect_to;
}


function sitelock_render_2fa_form($action_url, $nonce) {
    include plugin_dir_path(__FILE__) . 'pages/2fa-form-template.php';
}

register_activation_hook(__FILE__, ['Sitelock_Login_Logger', 'on_activation']);
register_deactivation_hook(__FILE__, ['Sitelock_Login_Logger', 'on_deactivation']);

// Show Notice on the Admin Dashboard After Login
function sitelock_admin_dashboard_notice() {
    $current_screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ( $current_screen && strpos( $current_screen->id, 'sitelock-your-2fa' ) !== false ) {
        return;
    }
    $current_user = wp_get_current_user();
    if ($message = get_transient('sitelock_admin_notice_'.$current_user->ID)) {
        $class = ''; // Initialize the style variable
        if (sitelock_is_plugin_page()) {
            $class = 'sitelock-admin-notice-custom';
        }
        echo '<div class="notice notice-warning is-dismissible ' . esc_attr($class) . '">
            <p><strong>' . wp_kses_post($message) . '</strong></p>
            <p><a href="' . esc_url(site_url('/wp-admin/admin.php?page=sitelock-your-2fa')) . '" class="button button-primary" style="width: auto; display: inline-block; text-align: center;">' . esc_html__('Setup 2FA', 'sitelock-wordpress-plugin') . '</a></p>
              </div>';


        $has_2fa = get_user_meta($current_user->ID, 'sitelock_2fa_enabled', true);
        if($has_2fa) {
            delete_transient('sitelock_admin_notice_'.$current_user->ID);
        }
    }
}
add_action('admin_notices', 'sitelock_admin_dashboard_notice');

function sitelock_get_user_2fa_status($user) {
    $sitelock_two_fa_settings = get_option('sitelock_2fa_settings', [
        'enable_2fa' => false,
        'mandatory_roles' => [],
        'grace_period' => 7
    ]);
    $sitelock_two_fa_settings['enable_2fa'] = isset($sitelock_two_fa_settings['enable_2fa']) ? $sitelock_two_fa_settings['enable_2fa'] : false;
    $sitelock_two_fa_settings['mandatory_roles'] = isset($sitelock_two_fa_settings['mandatory_roles']) ? $sitelock_two_fa_settings['mandatory_roles'] : [];
    $sitelock_two_fa_settings['grace_period'] = isset($sitelock_two_fa_settings['grace_period']) ? $sitelock_two_fa_settings['grace_period'] : 7;

    // Check if 2FA is mandatory for the user's role
    $user_roles = $user->roles;
    $role_requires_2fa = array_intersect($user_roles, $sitelock_two_fa_settings['mandatory_roles']);
    $grace_period_expiration = get_option('sitelock_2fa_grace_period', 0);
    $grace_period_expired = $grace_period_expiration && $grace_period_expiration < time();

    // Check if user has set up 2FA
    $has_2fa = get_user_meta($user->ID, 'sitelock_2fa_enabled', true);

    // Calculate remaining days for display if valid
    $remaining_days = 0;
    if (!$grace_period_expired && $grace_period_expiration) {
        $remaining = $grace_period_expiration - time();
        $remaining_days = max(1, ceil($remaining / DAY_IN_SECONDS));
    }

    return [
        '2fa_enabled'          => $sitelock_two_fa_settings['enable_2fa'],
        'role_requires_2fa'    => $role_requires_2fa,
        'grace_period_expired' => $grace_period_expired,
        'remaining_days'       => $remaining_days,
        'has_2fa'              => $has_2fa
    ];
}

// Secure 2FA enforcement logic
function sitelock_force_2fa_setup() {
    if (!is_user_logged_in()) {
        return;
    }

    $user = wp_get_current_user();
    $user_2fa_status = sitelock_get_user_2fa_status($user);

    if (!sitelock_should_enforce_2fa($user_2fa_status)) {
        delete_transient('sitelock_2fa_setup_notice_'.$user->ID);
        return;
    }

    if (sitelock_is_allowed_request()) {
        return;
    }

    sitelock_enforce_2fa_redirect($user, $user_2fa_status);
}

/**
 * Check if 2FA enforcement applies to the current user.
 *
 * @param array $user_2fa_status User 2FA status array.
 * @return bool
 */
function sitelock_should_enforce_2fa($user_2fa_status) {
    // Basic requirement check
    if (empty($user_2fa_status['2fa_enabled']) ||
        empty($user_2fa_status['role_requires_2fa']) ||
        !empty($user_2fa_status['has_2fa'])) {
        return false;
    }

    // PRIORITY 1: If settings were just saved in this session, allow access regardless of grace period
    if (Sitelock_Secure_Cookie::get('sitelock_2fa_settings_saved')) {
        return false;
    }

    // PRIORITY 2: If user skipped during a valid grace period, allow access
    $skipped = Sitelock_Secure_Cookie::get('sitelock_2fa_skipped');
    if (!$user_2fa_status['grace_period_expired'] && $skipped) {
        return false;
    }

    return true;
}

/**
 * Check if the current request is allowed during forced 2FA setup.
 * Handles AJAX checks internally (exits if blocked).
 *
 * @return bool
 */
function sitelock_is_allowed_request() {
    // 1. AJAX Handling (Whitelist 2FA actions & essentials)
    if (defined('DOING_AJAX') && DOING_AJAX) {
        $allowed_actions = [
            'sitelock_verify_2fa',
            'sitelock_disable_2fa',
            'sitelock_regenerate_backup_codes',
            'heartbeat',
            'query-attachments'
        ];
        $action = isset($_REQUEST['action']) ? sanitize_text_field(wp_unslash($_REQUEST['action'])) : '';

        if (in_array($action, $allowed_actions, true)) {
            return true;
        }

        // Block all other AJAX
        wp_send_json_error(['message' => '2FA Setup Required'], 403);
        exit;
    }

    // 2. Admin Post Handling (Allow saving 2FA settings)
    global $pagenow;
    if ($pagenow === 'admin-post.php') {
        $action = isset($_REQUEST['action']) ? sanitize_text_field(wp_unslash($_REQUEST['action'])) : '';
        if ($action === 'sitelock_security_form_data') {
            return true;
        }
    }

    // 3. Page Check (Strict Page Whitelist)
    // Allow the 2FA Setup page itself
    $is_2fa_page = ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'sitelock-your-2fa');

    return $is_2fa_page;
}

/**
 * Enforce 2FA by setting a notice and redirecting the user.
 *
 * @param WP_User $user            The user object.
 * @param array   $user_2fa_status User 2FA status array.
 */
function sitelock_enforce_2fa_redirect($user, $user_2fa_status) {
    $target_url  = site_url('/wp-admin/admin.php?page=sitelock-your-2fa&force_setup=1');

    if ($user_2fa_status['grace_period_expired']) {
            set_transient('sitelock_2fa_setup_notice_'.$user->ID,
        'Your grace period for setting up 2FA has expired. You must enable 2FA to continue accessing your account.');
    } else {
            set_transient('sitelock_2fa_setup_notice_'.$user->ID,
        'Two-Factor Authentication is required for your account. Please set it up now.');
    }

    wp_safe_redirect($target_url);
    exit;
}

add_action('admin_init', 'sitelock_force_2fa_setup');

/**
 * Restrict REST API for users who need 2FA.
 */
function sitelock_restrict_rest_api($result) {
    // If a previous authentication check already failed, return that result.
    if (is_wp_error($result)) {
        return $result;
    }

    if (!is_user_logged_in()) {
        return $result;
    }

    $user = wp_get_current_user();
    $user_2fa_status = sitelock_get_user_2fa_status($user);
    $skipped = Sitelock_Secure_Cookie::get('sitelock_2fa_skipped');

    if ($user_2fa_status['2fa_enabled'] && $user_2fa_status['role_requires_2fa'] && !$user_2fa_status['has_2fa']) {

        // Priority Checks
        if (Sitelock_Secure_Cookie::get('sitelock_2fa_settings_saved')) {
             return $result;
        }
        if (!$user_2fa_status['grace_period_expired'] && $skipped) {
             return $result;
        }

        return new WP_Error('rest_forbidden', __('Two-Factor Authentication Setup Required', 'sitelock-wordpress-plugin'), ['status' => 403]);
    }

    return $result;
}
add_filter('rest_authentication_errors', 'sitelock_restrict_rest_api');

// Check 2FA Status on Login and Store Notice
function sitelock_validate_2fa_status() {
    $user = wp_get_current_user();
    $user_2fa_status = sitelock_get_user_2fa_status($user);
    // Show warning if 2FA is mandatory but user hasn't set it up
    if ($user_2fa_status['2fa_enabled'] && $user_2fa_status['role_requires_2fa'] && !$user_2fa_status['has_2fa']) {
        if ($user_2fa_status['grace_period_expired']) {
            $admin_notice = 'Your 2FA grace period has expired. Please set up 2FA.';
        } else {
            $admin_notice = '2FA is mandatory for your role. Please set it up before the grace period ends.';
        }
        set_transient('sitelock_admin_notice_'.$user->ID, wp_kses_post($admin_notice));
    } else {
        delete_transient('sitelock_admin_notice_'.$user->ID);
    }
}
add_filter('admin_init', 'sitelock_validate_2fa_status');

/**
 * Cleanup specific SiteLock cookies on logout.
 */
function sitelock_clear_cookies_on_logout() {
    if (class_exists('Sitelock_Secure_Cookie')) {
        Sitelock_Secure_Cookie::delete('sitelock_2fa_skipped');
        Sitelock_Secure_Cookie::delete('sitelock_2fa_settings_saved');
    }
}
add_action('wp_logout', 'sitelock_clear_cookies_on_logout');

/**
 * Disable Application Passwords for users with 2FA enabled.
 *
 * Application Passwords bypass standard login forms and thus bypass 2FA.
 * To maintain security, we disable them for any user who has 2FA active.
 *
 * @param bool    $available Whether Application Passwords are available.
 * @param WP_User $user      The user being checked.
 * @return bool
 */
function sitelock_disable_app_passwords_for_2fa_users($available, $user) {
    if (!$available) {
        return false;
    }

    // If we don't have a user object, we can't check 2FA status.
    if (!($user instanceof WP_User)) {
        return $available;
    }

    $user_2fa_status = sitelock_get_user_2fa_status($user);

    // If 2FA is enabled and configured for this user, disable Application Passwords.
    // We also disable it if 2FA is mandatory for their role, even if not yet configured,
    // to prevent using App Passwords to bypass the setup requirement.
    if ((!empty($user_2fa_status['2fa_enabled']) && !empty($user_2fa_status['has_2fa'])) ||
        (!empty($user_2fa_status['role_requires_2fa']) && !empty($user_2fa_status['grace_period_expired']))) {
        return false;
    }

    return $available;
}
add_filter('wp_is_application_passwords_available_for_user', 'sitelock_disable_app_passwords_for_2fa_users', 10, 2);

/**
 * Render a notice explaining why Application Passwords are disabled.
 *
 * This hooks into the user profile to show a message where the Application Passwords
 * section would normally be.
 *
 * @param WP_User $user The user being edited.
 */
function sitelock_render_app_password_notice($user) {
    // Check if App Passwords are effectively disabled for this user by our filter
    $available = apply_filters('wp_is_application_passwords_available_for_user', true, $user);

    // If they are available, we don't need to show a notice (WP shows the form).
    if ($available) {
        return;
    }

    // Double check it was US who disabled it (by checking 2FA status again)
    // This prevents us from showing a confusing message if it was disabled by something else.
    $user_2fa_status = sitelock_get_user_2fa_status($user);
    $blocked_by_us = (!empty($user_2fa_status['2fa_enabled']) && !empty($user_2fa_status['has_2fa'])) ||
                     (!empty($user_2fa_status['role_requires_2fa']) && !empty($user_2fa_status['grace_period_expired']));

    if (!$blocked_by_us) {
        return;
    }

    include plugin_dir_path(__FILE__) . 'pages/2fa-app-password-notice.php';
}
add_action('show_user_profile', 'sitelock_render_app_password_notice');
add_action('edit_user_profile', 'sitelock_render_app_password_notice');

/**
 * Block XML-RPC for users with 2FA enabled.
 *
 * XML-RPC does not support 2FA, so allowing it would enable a bypass
 * using just the username and password.
 *
 * @param WP_User|WP_Error $user     WP_User on success, WP_Error on failure.
 * @param string           $username Username.
 * @param string           $password Password.
 * @return WP_User|WP_Error
 */
function sitelock_block_xmlrpc_for_2fa($user, $username, $password) {
    // If authentication already failed, don't interfere.
    if (is_wp_error($user)) {
        return $user;
    }

    // Only check during XML-RPC requests.
    if (!defined('XMLRPC_REQUEST') || !XMLRPC_REQUEST) {
        return $user;
    }

    // Check 2FA status
    $user_2fa_status = sitelock_get_user_2fa_status($user);
    $blocked_by_us = (!empty($user_2fa_status['2fa_enabled']) && !empty($user_2fa_status['has_2fa'])) ||
                     (!empty($user_2fa_status['role_requires_2fa']) && !empty($user_2fa_status['grace_period_expired']));

    if ($blocked_by_us) {
        return new WP_Error('xmlrpc_2fa_blocked', __('XML-RPC is disabled for accounts with Two-Factor Authentication enabled.', 'sitelock-wordpress-plugin'));
    }

    return $user;
}
add_filter('authenticate', 'sitelock_block_xmlrpc_for_2fa', 30, 3);





/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.9.0
 */
function sitelock_run()
{

    $plugin = new Sitelock();
    $plugin->run();

    return $plugin->get_version();

}

$sitelock_plugin_version = sitelock_run();


/**
 * Handles the auth connection
 */
$sitelockapi = new Sitelock_API($sitelock_plugin_version);

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
add_action('admin_notices', 'sitelock_plugin_activation_notice');


/**
 * Admin Notice on Activation.
 *
 * @since  3.5.0
 */
function sitelock_plugin_activation_notice()
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
    // Ensure insert_with_markers is available
    if (!function_exists('insert_with_markers')) {
        require_once ABSPATH . 'wp-admin/includes/misc.php';
    }

    $files_to_clean = [
        ABSPATH . '.htaccess',
        ABSPATH . 'wp-content/uploads/.htaccess'
    ];

    foreach ($files_to_clean as $htaccess_file) {
        if (file_exists($htaccess_file) && sitelock_filesystem_is_writable($htaccess_file)) {
            // 1. Remove markers
            insert_with_markers($htaccess_file, 'SitelockRules', []);

            // 1.5. Force remove standard markers if insert_with_markers left them
            $content = file_get_contents($htaccess_file);
            if (strpos($content, '# BEGIN SitelockRules') !== false) {
                $content = preg_replace('/[\r\n]*# BEGIN SitelockRules.*?# END SitelockRules[\r\n]*/s', "\n", $content);
                file_put_contents($htaccess_file, $content);
            }

            // 2. Remove legacy regex (if any)
            $content = file_get_contents($htaccess_file);
            if (strpos($content, '# SitelockRulesStart') !== false || strpos($content, '#SitelockRulesStart') !== false) {
                $content = preg_replace('/#\s?SitelockRulesStart.*?#\s?SitelockRulesEnd\s*/s', '', $content);
                file_put_contents($htaccess_file, $content);
            }

            // 3. Remove "File created by" comment and cleanup
            $content = file_get_contents($htaccess_file);
            $content = str_replace("# File created by Sitelock Security Plugin\n", "", $content);
            $content = str_replace("# File created by Sitelock Security Plugin", "", $content);
            $content = trim($content);

            if (empty($content)) {
                // If empty, delete the file
                @unlink($htaccess_file);
            } else {
                // Otherwise save trimmed content
                file_put_contents($htaccess_file, $content);
            }
        }
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

function sitelock_get_language_tokens() {
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
                margin: 10px 15px 0 10px; /* Set your desired margin */
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

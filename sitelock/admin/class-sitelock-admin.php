<?php
defined( 'ABSPATH' ) || exit;
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Sitelock
 * @subpackage Sitelock/admin
 * @author     Todd Low <tlow@sitelock.com>
 */

class Sitelock_Admin
{
    /**
     * The ID of this plugin.
     *
     * @since    1.9.0
     * @access   private
     * @var string $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.9.0
     * @access   private
     * @var string $version    The current version of this plugin.
     */
    private $version;

    public $admin_settings;

    /**
     * The api class

     * @since    1.9.0
     * @access   public
     */
    public $api;

    // Might need or might not need these below. Adding to resolve errors.
    public $table;
    public $table2;
    public $settings_2fa;
    public $wpslp_data;
    public $wpslp_partner_data;
    public $site_id;
    public $banner;
    public $boxes;
    public $waf;
    public $display_built;
    public $error;
    public $status;
    public $sitelock_sso;

    //Badge settings variables
    public $site_url;
    public $current_badge_location;
    public $current_badge_color;
    public $current_badge_size;
    public $current_badge_type;
    private $sitelock_language_tokens;
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.9.0
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        /**
         * Sets the plugin_name and version
         *
         * @since   1.9.0
         */
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
        if (function_exists('sitelock_get_language_tokens')) {
            $this->sitelock_language_tokens = sitelock_get_language_tokens();
        } else {
            $this->sitelock_language_tokens = [];
        }

        defined( 'ABSPATH' ) || exit;

        if (!empty($_GET['logout'])) {
            /**
             * If requesting to logout, remove token from wp_options
             * and redirect user to connect
             *
             * @since   1.9.0
             */
            if (function_exists('wp_verify_nonce') && isset($_GET['_wpnonce']) && !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sitelock_logout_action')) {
                wp_die(esc_html($this->sitelock_language_tokens['common_errors']['nonceVerificationFailed']));
            }

            // delete all cache data
            delete_option('wpslp_options');
            delete_option('sitelock_account_sites');
            delete_option('sitelock_account_scaninfo');
            delete_option('sitelock_malware_get_scan');
            delete_option('sitelock_word_quick');
            update_option('sitelock_license_key', '');
        }

        /**
         * Load dependencies and instantiate API class
         *
         * @since   1.9.0
         */
        $this->load_dependencies();

        /**
         * Save post action
         *
         * @since   2.0.0
         */
        add_action('save_post', [$this, 'save_page_protect'], 10, 3);

        // Add Sitelock Menu - Sidebar
        add_action('admin_menu', [$this, 'sitelock_plugin_menu']);

        // Add Feedback Submenu
        add_action('admin_menu', [$this, 'add_feedback_submenu']);

        // Remove the automatically created first submenu
        add_action('admin_menu', [$this, 'remove_default_submenu']);

        // Styles for the Sitelock Menu
        add_action('admin_head', [$this, 'custom_plugin_admin_styles']);

        add_action('admin_init', [$this, 'sitelock_register_login_logging_settings']);

        add_action('admin_init', [$this, 'sitelock_register_pst_settings']);

        // Force Logout Registration
        add_action('admin_init', [$this, 'sitelock_force_logout_register_settings']);

        // License Key Registration
        add_action('admin_init', [$this, 'sitelock_license_key_settings']);

        // Settings Form Data Save Registration
        add_action('admin_post_sitelock_security_form_data', [$this, 'sitelock_settings_form_save']);

        // License Key activation via email
        add_action('admin_init', [$this, 'redirect_admin_post_if_not_logged_in']);

        // Upgrade page
        add_action('admin_menu', array($this, 'sitelock_upgrade_page'));

        add_action('admin_enqueue_scripts', [$this, 'sitelock_scan_enqueue_scripts']);

        add_action('wp_ajax_sitelock_scan', [$this,'sitelock_scan_callback']);
    }

    /**
     * Loads dependencies
     *
     * @since    1.9.0
     */
    private function load_dependencies()
    {
        /**
         * Tables class
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sitelock-tables.php';

        /**
         * Loads in functions we need
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/functions-sitelock-admin.php';

        /**
         * The class responsible for all methods related to using our external API
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-sitelock-api.php';

        /**
         * The class responsible for 2FA settings
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sitelock-2fa-settings.php';

        $this->api          = new Sitelock_API($this->version);
        $this->table        = new Sitelock_Table();
        $this->table2       = new Sitelock_Table();
        $this->settings_2fa = new Sitelock_2FA_Settings(false);

        /**
         * Add thickbox
         */
        // wp_enqueue_script( 'thickbox' );
        // wp_enqueue_style( 'thickbox' );
    }

    /**
     * Gets site_parent_data
     *
     * @since    1.9.0
     */
    private function site_parent_data()
    {
        $this->wpslp_data = $this->get_features_new();
        $this->site_id    = $this->get_site_id();

        // $this->print_features_new($this->wpslp_data);
    }

    /**
     * Save post metadata when a post is saved.
     *
     * @since   2.0.0
     *
     * @param int  $post_id The post ID.
     * @param post $post    The post object.
     * @param bool $update  Whether this is an existing post being updated or not.
     */
    public function save_page_protect($post_id, $post, $update)
    {
        // Verify nonce to ensure the request is valid
        if (!isset($_POST['_sitelock_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_sitelock_nonce'])), 'sitelock_save_page_protect')) {
            return;
        }

        // If this isn't a 'page' post, don't update it.
        if ($post->post_type != 'page') {
            return;
        }

        $sitelock_page_protect         = isset($_REQUEST['sitelock_page_protect']) ? sanitize_key(wp_unslash($_REQUEST['sitelock_page_protect'])) : null;
        $sitelock_page_protect_current = isset($_REQUEST['sitelock_page_protect_current']) ? sanitize_key(wp_unslash($_REQUEST['sitelock_page_protect_current'])) : null;

        // Update page protect
        if (isset($sitelock_page_protect) && $sitelock_page_protect != $sitelock_page_protect_current) {
            // get action
            $action = $sitelock_page_protect == 'on' ? 'on' : 'off';

            // get current site
            if (empty($this->wpslp_data)) {
                $this->site_parent_data();
            }

            // get url for this page
            $page_url = get_permalink($post_id);

            // update API
            $this->api->update_page_protect($this->site_id, $page_url);

            // update DB to save result
            update_post_meta($post_id, 'page_protect', $action);
        }
        $sitelock_scan_page_type = isset($_REQUEST['sitelock_scan_page_type']) ? sanitize_key(wp_unslash($_REQUEST['sitelock_scan_page_type'])) : null;
        // Submit scan if needed
        if (isset($sitelock_scan_page_type)) {
            $this->api->sites->post_queue_scan($this->site_id, $sitelock_scan_page_type);
        }
    }

    public function sitelock_sanitize_function($input)
    {
        return sanitize_text_field($input); // Or use another appropriate sanitizer
    }

    public function sitelock_register_login_logging_settings()
    {
        register_setting('sitelock_login_security_settings', 'sitelock_login_logger_roles', [
            'type'              => 'array',
            'sanitize_callback' => function ($input) {
                return array_map('sanitize_text_field', (array) $input);
            },
            'default' => [],
        ]);
        register_setting('sitelock_login_security_settings', 'sitelock_login_logger_retention', [
            'sanitize_callback' => 'sitelock_sanitize_function',
        ]);
    }

    // Register settings for Password Strength Validation (PST)
    public function sitelock_register_pst_settings()
    {
        register_setting('sitelock_login_security_settings', 'sitelock_password_strength_enabled', [
            'sanitize_callback' => 'sitelock_sanitize_function',
        ]);
        register_setting('sitelock_login_security_settings', 'sitelock_password_strength_user_roles', [
            'type'              => 'array',
            'sanitize_callback' => function ($input) {
                return array_map('sanitize_text_field', (array) $input);
            },
            'default' => [],
        ]);
    }

    /**
     * check logout time
     *
     * @since    5.0.0
     */
    public function sitelock_force_logout_register_settings()
    {
        register_setting('sitelock_login_security_settings', 'sitelock_force_logout_enabled', [
            'sanitize_callback' => 'sitelock_sanitize_function',
        ]);
        register_setting('sitelock_login_security_settings', 'sitelock_force_logout_duration', [
            'sanitize_callback' => 'sitelock_sanitize_function',
        ]);
        register_setting('sitelock_login_security_settings', 'sitelock_force_logout_excluded_roles', [
            'type'              => 'array',
            'sanitize_callback' => function ($input) {
                return array_map('sanitize_text_field', (array) $input);
            },
            'default' => [],
        ]);
    }

    public function sitelock_plugin_menu()
    {
        $icon_url = plugin_dir_url(__FILE__) . 'images/sitelock_logo.png';

        add_menu_page(
            '', // Page title
            'SiteLock', // Menu title
            'manage_options', // Capability required
            'sitelock-plugin',  // Menu slug
            '', // Function to display the page content
            $icon_url, // Icon for the menu
            100// Position in the menu
        );

        //  Add submenus under the custom plugin menu
        add_submenu_page(
            'sitelock-plugin',  // Parent menu slug
            'Dashboard', // Page title for submenu
            'Dashboard', // Submenu title
            'manage_options',   // Capability required
            'sitelock', // Submenu slug
            [$this, 'main_options_page'] // Function to display the submenu content
        );

        add_submenu_page(
            'sitelock-plugin',  // Parent menu slug
            'Settings', // Page title for submenu
            'Settings', // Submenu title
            'manage_options',    // Capability required
            'sitelock-settings', // Submenu slug
            [$this, 'sitelock_plugin_settings_page'] // Function to display the submenu content
        );

        add_submenu_page(
            'sitelock-plugin',
            'Activity Logs',
            'Activity Logs',
            'manage_options',
            'sitelock-activity-logs',
            [$this, 'sitelock_activity_logs_page']
        );

            $sitelock_two_fa_settings               = get_option('sitelock_2fa_settings', []);
        $sitelock_two_fa_settings['enable_2fa'] = isset($sitelock_two_fa_settings['enable_2fa']) ? $sitelock_two_fa_settings['enable_2fa'] : false;
        if ($sitelock_two_fa_settings['enable_2fa']) {
            $hook = add_submenu_page(
                'sitelock-plugin',
                'Your 2FA',
                'Your 2FA',
                'edit_posts', // Default roles with this capability: Administrator, Editor, Author, Contributor
                'sitelock-your-2fa',
                [$this, 'sitelock_your_2fa_page']
            );
            add_action('load-' . $hook, array($this, 'on_load_your_2fa_page'));
        }

    }


    public function add_feedback_submenu() {

        add_submenu_page(
            'sitelock-plugin',
            'Give Feedback',
            'Give Feedback <img src="' . esc_url(plugin_dir_url(__FILE__) . 'images/vector.png') . '" style="width:15px; height:15px; vertical-align:middle; margin-left:6px;">',
            'manage_options',
            'sitelock-give-feedback',
            '__return_null'
        );

        global $submenu;

        if (isset($submenu['sitelock-plugin'])) {
            foreach ($submenu['sitelock-plugin'] as &$item) {
                if ($item[2] === 'sitelock-give-feedback') {
                    $item[2] = 'https://wordpress.org/support/plugin/sitelock/reviews/#new-post';
                    $item[4] = 'sitelock-give-feedback-link';
                }
            }
        }
    }

    public function remove_default_submenu()
    {
        remove_submenu_page('sitelock-plugin', 'sitelock-plugin');
    }

    public function custom_plugin_admin_styles()
    {
        echo '<style>
        #toplevel_page_sitelock-plugin .wp-menu-image img {
            margin: 0 12px;
            padding: 7px 0 0 0;
        }
    </style>';
    }

    /**
     * Displays tools page
     *
     * @since    1.9.0
     */
    public function main_options_page()
    {
        if ($this->api->auth->get_auth_key()) { # && $this->api->is_auth_key_valid() ) {
            $this->wpslp_data         = $this->api->sites->get_features();
            $this->wpslp_partner_data = $this->api->sites->get_partner_preference('');
            if ((isset($this->wpslp_data['status']) && $this->wpslp_data['status'] === 'error') || (isset($this->wpslp_partner_data['status']) && $this->wpslp_partner_data['status'] === 'error')) {
                $error_message = 'Something went wrong while refreshing the token. Please try again later.';
                set_transient('sitelock_auth_error', $error_message, 60); // Store error for 60 seconds
            }
            include 'pages/sitelock.php';
        } else {
            include 'pages/sitelock.php';
        }
    }

    public function sitelock_plugin_settings_page()
    {
        include plugin_dir_path(__FILE__) . 'pages/setting.php';
    }

    public function sitelock_activity_logs_page()
    {
        // Verify nonce to ensure the request is valid
        if (isset($_GET['report_type']) && (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sitelock_activity_logs_nonce'))) {
            wp_die(esc_html($this->sitelock_language_tokens['common_errors']['nonceVerificationFailed']));
        }

        // Determine report type
        if (isset($_GET['report_type'])) {
            $report_type = sanitize_text_field(wp_unslash($_GET['report_type']));
        } else {
            $report_type = 'login-activity';
        }

        if ($report_type == 'admin-audit') {
            $data                       = $this->get_admin_audit_data();
            $data['report_file']        = plugin_dir_path(__FILE__) . 'partials/activity-logs/sitelock_admin_audit.php';
            $data['status_options']     = ['All Statuses', 'Trusted', 'Suspicious'];
            $data['status_values']      = ['', 'trusted', 'suspicious'];
            $data['report_title']       = 'Admin Audit Logs';
            $data['report_description'] = 'Monitor administrative changes including user role modifications and account management.';
        } else {
            $data                       = $this->get_login_logs_data();
            $data['report_file']        = plugin_dir_path(__FILE__) . 'partials/activity-logs/sitelock_login_logs.php';
            $data['status_options']     = ['All Statuses', 'Success', 'Failure'];
            $data['status_values']      = ['', 'success', 'failure'];
            $data['report_title']       = 'Login Activity Logs';
            $data['report_description'] = 'Track user authentication attempts, successful logins, and session activity';
        }
        $data['report_type']            = $report_type;
        $data['admin_audit_total_rows'] = $this->get_log_table_count('sitelock_admin_logs');
        $data['login_logs_total_rows']  = $this->get_log_table_count('sitelock_login_logs');

        extract($data);
        include plugin_dir_path(__FILE__) . 'pages/activity-logs.php';
        wp_enqueue_script('sitelock-activity-logs-js', plugin_dir_url(__FILE__) . 'js/sitelock-activity-logs.js', ['jquery'], '1.0.0', true);
    }

    /**
     * Handle page load for 2FA page.
     * Used to intercept onboarding requests and render full-page UI.
     */
    public function on_load_your_2fa_page() {
        // Ensure 2FA assets are enqueued
        require_once plugin_dir_path(__FILE__) . 'class-sitelock-2fa.php';
        $sitelock_2fa = new Sitelock_2FA();
        $sitelock_2fa->enqueue_assets();

        $user = wp_get_current_user();
        $user_2fa_status = sitelock_get_user_2fa_status($user);
        $has_2fa = $user_2fa_status['has_2fa'];
        $grace_period_expired = $user_2fa_status['grace_period_expired'];
        // Handle "Skip for Now" Action
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Processing form data without nonce verification.
        if (isset($_GET['action']) && sanitize_text_field(wp_unslash($_GET['action'])) === 'sitelock_2fa_skip' && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sitelock_2fa_skip_action')) {
            // ONLY allowed if grace period is NOT expired
            if (!$grace_period_expired) {
                // Set a cookie (or transient) to skip for (e.g.) 1 day
                Sitelock_Secure_Cookie::set('sitelock_2fa_skipped', 'true', DAY_IN_SECONDS);

                // Redirect to dashboard
                wp_safe_redirect(admin_url());
                exit;
            }
        }

        // Setup Wizard View (Full Page)
        if (isset($_GET['setup_wizard']) && $_GET['setup_wizard'] == '1') {
             // We need data for the settings view
             $data = $this->settings_2fa->display_2fa_settings($user);
             $data['grace_period_expired'] = $grace_period_expired;
             extract($data);

             include plugin_dir_path(__FILE__) . 'pages/2fa-wizard-template.php';
             exit;
        }

        // Onboarding Prompt (Full Page)
        // If user has NO 2FA setup and is FORCED via parameter (or wizard mode),
        // render the onboarding interstitial.
        $force_setup = isset($_GET['force_setup']) && $_GET['force_setup'] == '1';

        if (!$has_2fa && $force_setup) {
           include plugin_dir_path(__FILE__) . 'pages/2fa-onboarding-template.php';
           exit; // Stop WordPress from loading the rest of the admin UI
        }
    }

    public function sitelock_feedback()
    {
        include plugin_dir_path(__FILE__) . 'partials/sitelock-admin-feedback.php';
    }

    public function sitelock_your_2fa_page()
    {
        $data = $this->settings_2fa->display_2fa_settings(wp_get_current_user());
        extract($data);
        include plugin_dir_path(__FILE__) . 'pages/your-2fa.php';
    }

    /**
     * Save and retrieve badge settings
     *
     * @since    1.9.0
     */
    public function badge_settings()
    {
        $this->site_url = $this->sitelock_site_url();
        $this->status   = false;

        // Verify nonce to ensure the request is valid
        if (!isset($_POST['_sitelock_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_sitelock_nonce'])), 'sitelock_badge_settings')) {
            return;
        }

        $badge_location = isset($_POST['sitelock_badge_location']) ? sanitize_key($_POST['sitelock_badge_location']) : '';
        $badge_color    = isset($_POST['sitelock_badge_color']) ? sanitize_key($_POST['sitelock_badge_color']) : '';
        $badge_size     = isset($_POST['sitelock_badge_size']) ? sanitize_key($_POST['sitelock_badge_size']) : '';
        $badge_type     = isset($_POST['sitelock_badge_type']) ? sanitize_key($_POST['sitelock_badge_type']) : '';

        if (!empty($badge_location)) {
            update_option('sitelock_badge_location', $badge_location); // badge location
            update_option('sitelock_badge_color', $badge_color); // color
            update_option('sitelock_badge_size', $badge_size); // size
            update_option('sitelock_badge_type', $badge_type); // type

            if (
                $badge_size && in_array($badge_size, ['small', 'medium', 'big'])
                && $badge_color && in_array($badge_color, ['white', 'red'])
                && $badge_type && in_array($badge_type, ['malware-free', 'secure'])
                && get_option('sitelock_site_id')
            ) {
                $type       = $badge_type == 'malware-free' ? 'mal_04' : 'secure_04';
                $badge_type = join(
                    '_',
                    [
                        strtolower($badge_size),
                        strtolower($badge_color),
                        'en',
                        $type,
                    ]
                );

                $response = $this->api->update_badge_settings(get_option('sitelock_site_id'), $badge_type);

                if (is_array($response) && $response['link'] && $response['img']) {
                    update_option('sitelock_badge_link', $response['link']);
                    update_option('sitelock_badge_img', $response['img']);
                }
            }

            $this->status = 'Settings Saved. It may take up to fifteen minutes for the badge settings to update on your website.';
        }

        $this->current_badge_location = get_option('sitelock_badge_location');
        $this->current_badge_color    = get_option('sitelock_badge_color');
        $this->current_badge_size     = get_option('sitelock_badge_size');
        $this->current_badge_type     = get_option('sitelock_badge_type');
    }

    /**
     * Build features array from LAPI
     *
     * @since 5.0.0
     */
    private function get_features_new()
    {
        return $this->api->sites->get_features();
    }

    /**
     * SL Site Url
     *
     * @since 2.0.0
     */
    public function sitelock_site_url()
    {
        return site_url();
    }

    /**
     * Clear Site ID from wp_options
     *
     * @since    1.9.0
     */
    public function clear_site_id()
    {
        update_option('sitelock_site_id', '');
    }

    /**
     * Get Site ID
     *
     * @since    1.9.0
     * @param array $data
     */
    public function get_site_id($data = [])
    {
        if (!empty($this->wpslp_data['site']['id'])) {
            $site_id = $this->wpslp_data['site']['id'];

            // save the site id
            update_option('sitelock_site_id', $site_id);

            return $site_id;
        }
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.9.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name . '-tailwind-css', plugin_dir_url(__FILE__) . 'css/tailwind.css', [], $this->version, 'all');

        // page styles
        wp_enqueue_style($this->plugin_name . '-style', plugin_dir_url(__FILE__) . 'css/style.css', [$this->plugin_name . '-tailwind-css'], $this->version, 'all');

        // font styles
        wp_enqueue_style($this->plugin_name . '-sans', plugin_dir_url(__FILE__) . 'css/sans.css', [], $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.9.0
     */
    public function enqueue_scripts()
    {
        // Ensure jQuery is loaded for inline scripts in setting.php
        wp_enqueue_script('jquery');

         // Validate JS
         wp_enqueue_script(
            'jquery-validate',
            plugin_dir_url(__FILE__) . 'js/jquery.validate.min.js',
            [],
            '1.0.0',
            true
        );

        // Admin JS
        wp_enqueue_script(
            'sitelock-give-feedback',
            plugin_dir_url(__FILE__) . 'js/sitelock-give-feedback.js',
            [],
            '1.0.0',
            true
        );
    }

    /**
     * Sanitize title with different separator
     *
     * @since 2.0.0
     * @param string $string    The string to be sanitized
     * @param string $separator The item to be used to separate words
     */
    public function sanitize_title($string, $separator = '-')
    {
        return str_replace('-', $separator, sanitize_title($string));
    }

    public function sitelock_license_key_settings()
    {
        register_setting('sitelock_license_key_settings', 'sitelock_license_key', [
            'sanitize_callback' => 'sitelock_sanitize_function',
        ]);
    }

    public function sitelock_process_license_key($license_key)
    {
        // Process the license key here
        // This function can be used to validate and save the license key
        $license_key = sanitize_text_field($license_key);
        update_option('sitelock_license_key', $license_key);
        // You can add additional logic to validate the license key with SiteLock API
    }

    public function sitelock_settings_form_save()
    {
        // Optional: capability check first
        if (!current_user_can('manage_options')) {
            wp_die(esc_html($this->sitelock_language_tokens['var']['unAuthorizedUser']));
        }

        // Identify which tab triggered the form
        $tab = isset($_POST['tab']) ? sanitize_text_field(wp_unslash($_POST['tab'])) : '';

        // Tab-specific nonce verification (prevents duplicate DOM IDs)
        if ($tab === 'sitelock_website_security') {
            $nonce = isset($_POST['sitelock_website_security_nonce']) ? sanitize_text_field(wp_unslash($_POST['sitelock_website_security_nonce'])) : '';
            if (!$nonce || !wp_verify_nonce($nonce, 'sitelock_website_security_action')) {
                wp_die(esc_html($this->sitelock_language_tokens['var']['websiteSecurityFailed']));
            }
        } elseif ($tab === 'sitelock_login_security') {
            $nonce = isset($_POST['sitelock_login_security_nonce']) ? sanitize_text_field(wp_unslash($_POST['sitelock_login_security_nonce'])) : '';
            if (!$nonce || !wp_verify_nonce($nonce, 'sitelock_login_security_action')) {
                wp_die(esc_html($this->sitelock_language_tokens['var']['loginSecurityFailed']));
            }
        } elseif ($tab === 'connection') {
            $nonce = isset($_POST['sitelock_connection_settings_nonce']) ? sanitize_text_field(wp_unslash($_POST['sitelock_connection_settings_nonce'])) : '';
            if (!$nonce || !wp_verify_nonce($nonce, 'sitelock_connection_settings_action')) {
                wp_die(esc_html($this->sitelock_language_tokens['var']['invalidTabSpecified']));
            }
        } else {
            // Fallback for invalid or missing tab
            wp_die(esc_html($this->sitelock_language_tokens['var']['invalidTabSpecified']));
        }



        // website security settings
        if (isset($_POST['tab']) && $_POST['tab'] === 'sitelock_website_security') {
            update_option('sitelock_security_settings', isset($_POST['sitelock_security_settings']) ? array_map('sanitize_text_field', wp_unslash($_POST['sitelock_security_settings'])) : []);
        }

        //login security settings
        if (isset($_POST['tab']) && $_POST['tab'] === 'sitelock_login_security') {
            // save 2fa settings
            if (isset($_POST['sitelock_2fa_settings']) && is_array($_POST['sitelock_2fa_settings'])) {

               // Sanitize submitted roles
                if (isset($_POST['sitelock_2fa_settings']['mandatory_roles']) && is_array($_POST['sitelock_2fa_settings']['mandatory_roles'])) {
                    $submitted_roles = array_map('sanitize_text_field', wp_unslash($_POST['sitelock_2fa_settings']['mandatory_roles']));
                } else {
                    $submitted_roles = [];
                }
                // Remove roles not in the allowed roles list
                $allowed_roles = ['administrator', 'editor', 'author', 'contributor', 'shop_manager'];

                foreach ($submitted_roles as $key => $role) {
                    if (!in_array($role, $allowed_roles, true)) {
                        unset($submitted_roles[$key]);
                    }
                }

                // Sanitize other fields
                $grace_period = isset($_POST['sitelock_2fa_settings']['grace_period']) && $_POST['sitelock_2fa_settings']['grace_period'] !== ''
                    ? intval($_POST['sitelock_2fa_settings']['grace_period'])
                    : 7;

                $enable_2fa = isset($_POST['sitelock_2fa_settings']['enable_2fa'])
                    ? boolval($_POST['sitelock_2fa_settings']['enable_2fa'])
                    : 0;

                // Prepare sanitized settings array
                $sanitized_settings = [
                    'mandatory_roles' => $submitted_roles,
                    'grace_period'    => $grace_period,
                    'enable_2fa'      => $enable_2fa,
                ];

                // Save sanitized settings
                update_option('sitelock_2fa_settings', $sanitized_settings);

                // If 2FA is enabled and the current user's role is mandatory, set a "settings saved" cookie
                // This prevents immediate redirect to setup page upon saving settings
                if ($enable_2fa) {
                    $current_user = wp_get_current_user();
                    $user_roles = $current_user->roles;
                    if (array_intersect($user_roles, $submitted_roles)) {
                        Sitelock_Secure_Cookie::set('sitelock_2fa_settings_saved', 'true', DAY_IN_SECONDS);
                    }
                }
            }


            // save login lockout settings
            update_option('sitelock_login_lockout_enabled', isset($_POST['sitelock_login_lockout_enabled']) ? sanitize_text_field(wp_unslash($_POST['sitelock_login_lockout_enabled'])) : 0);

            if (isset($_POST['sitelock_login_lockout_max_attempts']) && $_POST['sitelock_login_lockout_max_attempts'] !== '') {
                update_option('sitelock_login_lockout_max_attempts', sanitize_text_field(wp_unslash($_POST['sitelock_login_lockout_max_attempts'])));
            }

            if (isset($_POST['sitelock_login_lockout_duration']) && $_POST['sitelock_login_lockout_duration'] !== '') {
                update_option('sitelock_login_lockout_duration', sanitize_text_field(wp_unslash($_POST['sitelock_login_lockout_duration'])));
            }

            if (isset($_POST['sitelock_login_lockout_reset_time']) && $_POST['sitelock_login_lockout_reset_time'] !== '') {
                update_option('sitelock_login_lockout_reset_time', sanitize_text_field(wp_unslash($_POST['sitelock_login_lockout_reset_time'])));
            }

            // save force logout settings
            update_option('sitelock_force_logout_enabled', isset($_POST['sitelock_force_logout_enabled']) ? sanitize_text_field(wp_unslash($_POST['sitelock_force_logout_enabled'])) : 0);

            if (isset($_POST['sitelock_force_logout_duration']) && $_POST['sitelock_force_logout_duration'] !== '') {
                update_option('sitelock_force_logout_duration', sanitize_text_field(wp_unslash($_POST['sitelock_force_logout_duration'])));
            }
            update_option('sitelock_force_logout_excluded_roles', isset($_POST['sitelock_force_logout_excluded_roles']) ? array_map('sanitize_text_field', wp_unslash($_POST['sitelock_force_logout_excluded_roles'])) : []);

            // save password strength settings
            update_option('sitelock_password_strength_enabled', isset($_POST['sitelock_password_strength_enabled']) ? sanitize_text_field(wp_unslash($_POST['sitelock_password_strength_enabled'])) : 0);
            update_option('sitelock_password_strength_user_roles', isset($_POST['sitelock_password_strength_user_roles']) ? array_map('sanitize_text_field', wp_unslash($_POST['sitelock_password_strength_user_roles'])) : []);

            // save login logger settings
            update_option('sitelock_login_logger_roles', isset($_POST['sitelock_login_logger_roles']) ? array_map('sanitize_text_field', wp_unslash($_POST['sitelock_login_logger_roles'])) : []);

            if (isset($_POST['sitelock_login_logger_retention']) && $_POST['sitelock_login_logger_retention'] !== '') {
                update_option('sitelock_login_logger_retention', sanitize_text_field(wp_unslash($_POST['sitelock_login_logger_retention'])));
            }
        }

        // ✅ Redirect back to same tab
        $redirect_url = add_query_arg(
            [
                'settings-updated' => 'true',
            ],
            wp_get_referer()
        );
        wp_safe_redirect($redirect_url);
        exit;
    }

    public function get_login_logs_data()
    {
        // Verify nonce to ensure the request is valid
        if (isset($_GET['report_type']) && (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sitelock_activity_logs_nonce'))) {
            wp_die(esc_html($this->sitelock_language_tokens['common_errors']['nonceVerificationFailed']));
        }
        global $wpdb;

        $table = $wpdb->prefix . 'sitelock_login_logs';

        // Setup pagination
        $items_per_page = 10;
        $paged          = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset         = ($paged - 1) * $items_per_page;

        // Validate and sanitize the table name
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [
                'logs'                       => [],
                'total_items'                => 0,
                'items_per_page'             => $items_per_page,
                'paged'                      => $paged,
                'sitelock_connection_status' => $this->api->auth->get_auth_key(),
                'sitelock_language_tokens'   => sitelock_get_language_tokens(),
                'status_filter'              => '',
                'date_filter'                => '',
                'start_date'                 => '',
                'end_date'                   => '',
            ];
        }

        $table = esc_sql($table);

        // Filters from request
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field(wp_unslash($_GET['status_filter'])) : '';
        $date_filter   = isset($_GET['date_filter']) ? sanitize_text_field(wp_unslash($_GET['date_filter'])) : '';
        $start_date    = isset($_GET['start_date']) ? sanitize_text_field(wp_unslash($_GET['start_date'])) : '';
        $end_date      = isset($_GET['end_date']) ? sanitize_text_field(wp_unslash($_GET['end_date'])) : '';

        // Build WHERE conditions and prepare args
        $conditions   = [];
        $prepare_args = [];

        // Status filter
        if ($status_filter === 'success') {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $conditions[]   = 'status = %s';
            $prepare_args[] = 'success';
        } elseif ($status_filter === 'failure') {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $conditions[]   = 'status = %s';
            $prepare_args[] = 'failure';
        }

        // Date filter handling
        if ($date_filter === 'last_7') {
            $start = gmdate('Y-m-d', strtotime('-6 days')); // last 7 days including today
            $end   = gmdate('Y-m-d');

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $conditions[]   = 'DATE(logged_at) BETWEEN %s AND %s';
            $prepare_args[] = $start;
            $prepare_args[] = $end;

            $start_date = $start;
            $end_date   = $end;
        } elseif ($date_filter === 'custom') {
            $date_regex = '/^\d{4}-\d{2}-\d{2}$/';
            if (preg_match($date_regex, $start_date) && preg_match($date_regex, $end_date)) {
                if (strtotime($start_date) <= strtotime($end_date)) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $conditions[]   = 'DATE(logged_at) BETWEEN %s AND %s';
                    $prepare_args[] = $start_date;
                    $prepare_args[] = $end_date;
                } else {
                    // Invalid chronology: ignore the custom date filter
                    $date_filter = '';
                    $start_date  = '';
                    $end_date    = '';
                }
            } else {
                // Invalid format: ignore custom date filter
                $date_filter = '';
                $start_date  = '';
                $end_date    = '';
            }
        } else {
            // 'All dates' or unspecified -> no date condition
            $date_filter = $date_filter === '' ? '' : $date_filter;
        }

        // Build WHERE clause
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $where_clause = '';
        if (! empty($conditions)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $where_clause = 'WHERE ' . implode(' AND ', $conditions);
        }

        // Count total items (unfiltered) - cached
        $cache_key_total = 'sitelock_total_items_' . md5($table);
        $total_items     = wp_cache_get($cache_key_total, 'sitelock_login_logs_count_cache');

        if ($total_items === false) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $table_escaped = esc_sql($table);
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_escaped");
                $total_items = $total_items !== null ? (int) $total_items : 0;
                wp_cache_set($cache_key_total, $total_items, 'sitelock_login_logs_count_cache', 3600);
            } else {
                $total_items = 0;
            }
        }

        // Count total items with filter - cached
        $filtered_count_cache_key = 'sitelock_filtered_total_items_' . md5($table . '|' . $where_clause . '|' . $status_filter . '|' . $date_filter . '|' . $start_date . '|' . $end_date);
        $filtered_total_items     = wp_cache_get($filtered_count_cache_key, 'sitelock_login_logs_status_count_cache');

        if ($filtered_total_items === false) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $table_escaped = esc_sql($table);
                if (! empty($prepare_args)) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $query = "SELECT COUNT(*) FROM $table_escaped $where_clause";
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $filtered_total_items = $wpdb->get_var($wpdb->prepare(esc_sql($query), ...$prepare_args));
                } else {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $filtered_total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_escaped");
                }
                $filtered_total_items = $filtered_total_items !== null ? (int) $filtered_total_items : 0;
                wp_cache_set($filtered_count_cache_key, $filtered_total_items, 'sitelock_login_logs_status_count_cache', 3600);
            } else {
                $filtered_total_items = 0;
            }
        }

        // Fetch logs with filter and pagination - cached
        $filtered_logs_cache_key = 'sitelock_filtered_logs_' . md5($table . '|' . $where_clause . '|' . $status_filter . '|' . $date_filter . '|' . $start_date . '|' . $end_date . '|' . $items_per_page . '|' . $offset);
        $logs                    = wp_cache_get($filtered_logs_cache_key, 'sitelock_login_logs_filter_log_cache');

        if ($logs === false) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $table_escaped = esc_sql($table);
                $final_prepare_args   = $prepare_args;
                $final_prepare_args[] = $items_per_page;
                $final_prepare_args[] = $offset;

                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $query = "SELECT * FROM $table_escaped $where_clause ORDER BY logged_at DESC LIMIT %d OFFSET %d";

                // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.Security.EscapeOutput.UnsafeQuery
                $logs = $wpdb->get_results($wpdb->prepare($query, ...$final_prepare_args));

                $logs = $logs !== null ? $logs : [];
                wp_cache_set($filtered_logs_cache_key, $logs, 'sitelock_login_logs_filter_log_cache', 3600);
            } else {
                $logs = [];
            }
        }

        return [
            'logs'                       => $logs,
            'total_items'                => $filtered_total_items,
            'items_per_page'             => $items_per_page,
            'paged'                      => $paged,
            'sitelock_connection_status' => $this->api->auth->get_auth_key(),
            'sitelock_language_tokens'   => sitelock_get_language_tokens(),
            'status_filter'              => $status_filter,
            'date_filter'                => $date_filter,
            'start_date'                 => $start_date,
            'end_date'                   => $end_date,
        ];
    }

    public function get_admin_audit_data()
    {
        // Verify nonce to ensure the request is valid
        if (isset($_GET['report_type']) && (! isset($_GET['_wpnonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sitelock_activity_logs_nonce'))) {
            wp_die(esc_html($this->sitelock_language_tokens['common_errors']['nonceVerificationFailed']));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sitelock_admin_logs';

        // Setup pagination
        $items_per_page = 10;
        $paged          = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset         = ($paged - 1) * $items_per_page;

        // Count total items (unfiltered) - use esc_sql validated table name
        $cache_key   = 'sitelock_total_items';
        $total_items = wp_cache_get($cache_key);

        if ($total_items === false) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $table_name_escaped = esc_sql($table_name);

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name_escaped");

                if ($total_items === false) {
                    $total_items = 0;
                }

                wp_cache_set($cache_key, $total_items, '', 3600);
            } else {
                $total_items = 0;
            }
        }

        // Filters from request
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field(wp_unslash($_GET['status_filter'])) : '';
        $date_filter   = isset($_GET['date_filter']) ? sanitize_text_field(wp_unslash($_GET['date_filter'])) : '';
        $start_date    = isset($_GET['start_date']) ? sanitize_text_field(wp_unslash($_GET['start_date'])) : '';
        $end_date      = isset($_GET['end_date']) ? sanitize_text_field(wp_unslash($_GET['end_date'])) : '';

        // Build WHERE conditions safely (conditions contain placeholders, not raw user values)
        $conditions   = [];
        $prepare_args = [];

        if ($status_filter === 'suspicious') {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $conditions[]   = 'is_suspicious = %d';
            $prepare_args[] = 1;
        } elseif ($status_filter === 'trusted') {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $conditions[]   = 'is_suspicious = %d';
            $prepare_args[] = 0;
        }

        if ($date_filter === 'last_7') {
            $start = gmdate('Y-m-d', strtotime('-6 days'));
            $end   = gmdate('Y-m-d');

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $conditions[]   = 'DATE(logged_at) BETWEEN %s AND %s';
            $prepare_args[] = $start;
            $prepare_args[] = $end;

            $start_date = $start;
            $end_date   = $end;
        } elseif ($date_filter === 'custom') {
            $date_regex = '/^\d{4}-\d{2}-\d{2}$/';
            if (preg_match($date_regex, $start_date) && preg_match($date_regex, $end_date)) {
                if (strtotime($start_date) <= strtotime($end_date)) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $conditions[]   = 'DATE(logged_at) BETWEEN %s AND %s';
                    $prepare_args[] = $start_date;
                    $prepare_args[] = $end_date;
                } else {
                    // Invalid chronology - ignore date filter
                    $date_filter = '';
                    $start_date  = '';
                    $end_date    = '';
                }
            } else {
                // Invalid date format - ignore
                $date_filter = '';
                $start_date  = '';
                $end_date    = '';
            }
        }

        // Compose WHERE clause (conditions already use placeholders)
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $where_sql = '';
        if (! empty($conditions)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $where_sql = ' WHERE ' . implode(' AND ', $conditions);
        }

        // Build cache key for logs (include filters so different queries cache separately)
        $cache_key_logs = 'sitelock_logs_' . $paged . '_' . $items_per_page . '_' . md5($status_filter . '|' . $date_filter . '|' . $start_date . '|' . $end_date);
        $logs           = wp_cache_get($cache_key_logs);

        if ($logs === false) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $table_name_escaped = esc_sql($table_name);

                // Final prepare args: first the condition values, then limit and offset
                $final_prepare_args   = $prepare_args;
                $final_prepare_args[] = $items_per_page;
                $final_prepare_args[] = $offset;

                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $query = "SELECT * FROM $table_name_escaped$where_sql ORDER BY logged_at DESC LIMIT %d OFFSET %d";

               // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.Security.EscapeOutput.UnsafeQuery
                $logs = $wpdb->get_results($wpdb->prepare($query, ...$final_prepare_args));

                if ($logs !== false) {
                    wp_cache_set($cache_key_logs, $logs, '', 3600);
                } else {
                    wp_cache_set($cache_key_logs, [], '', 3600);
                }
            } else {
                $logs = [];
            }
        }

        return [
            'logs'                       => $logs,
            'total_items'                => $total_items,
            'items_per_page'             => $items_per_page,
            'paged'                      => $paged,
            'sitelock_connection_status' => $this->api->auth->get_auth_key(),
            'sitelock_language_tokens'   => sitelock_get_language_tokens(),
            'status_filter'              => $status_filter,
            'date_filter'                => $date_filter,
            'start_date'                 => $start_date,
            'end_date'                   => $end_date,
        ];
    }

    /**
     * Get the count of rows for the specified log table.
     *
     * @param  string $table_name The name of the log table (e.g., 'sitelock_admin_logs' or 'sitelock_login_logs').
     * @return int    The count of rows in the table, or 0 if the table name is invalid.
     */
    public function get_log_table_count($table_name)
    {
        global $wpdb;

        // Define allowed table names
        $allowed_tables = ['sitelock_admin_logs', 'sitelock_login_logs'];

        // Validate the table name
        if (!in_array($table_name, $allowed_tables, true)) {
            return 0; // Invalid table name
        }

        // Add the database prefix to the table name
        $full_table_name = $wpdb->prefix . $table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $full_table_name = esc_sql($full_table_name);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.Security.EscapeOutput.UnsafeQuery
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($full_table_name));

        // Return the row count or 0 if the query fails
        return $row_count !== null ? intval($row_count) : 0;
    }

    public function redirect_admin_post_if_not_logged_in()
    {
        // Check if the current request is for admin-post.php
        if (isset($_SERVER['REQUEST_URI']) && strpos(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])), 'admin-post.php') !== false) {
            // Check if the user is NOT logged in
            if (! is_user_logged_in()) {
                // Sanitize and validate HTTP_HOST
                $http_host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
                if (! filter_var('http://' . $http_host, FILTER_VALIDATE_URL) && ! filter_var('https://' . $http_host, FILTER_VALIDATE_URL)) {
                    $http_host = ''; // Fallback to an empty string if invalid
                }

                // Sanitize REQUEST_URI
                $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));

                // Construct the full redirect URL
                $redirect_to = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://{$http_host}{$request_uri}";

                // Redirect to the login page and set UTR to return to after login
                wp_safe_redirect(wp_login_url($redirect_to));
                exit; // Important to exit after redirection
            }
        }
    }

    public function sitelock_upgrade_page()
    {
        // Use a hidden parent slug to prevent showing in menu.
        add_submenu_page(
            '', // Hidden page: empty slug is safe across WP/PHP versions.
            'Upgrade', // Page title
            '', // Hidden from menu
            'manage_options', // Capability
            'sitelock-upgrade', // Slug
            [$this, 'sitelock_upgrade_page_callback'] // Callback function
        );
    }

    public function sitelock_upgrade_page_callback()
    {
        $this->wpslp_partner_data = $this->api->sites->get_partner_preference('customer_support_options');
        include plugin_dir_path(__FILE__) . 'pages/upgrade.php';
    }

    public function sitelock_scan_enqueue_scripts()
    {
        // Prevents nonce leaking to unauthorized users by only enqueuing the script for users with manage_options capability
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_enqueue_script('sitelock-scan', plugin_dir_url(__FILE__) . 'js/sitelock-scan.js', ['jquery'], '1.0', true);

        wp_localize_script('sitelock-scan', 'sitelockPlugin', [
            'ajax_url'  => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('sitelock_scan_nonce'),
            'pluginUrl' => plugin_dir_url(__FILE__),
        ]);
    }
    public function sitelock_scan_callback()
    {
        check_ajax_referer('sitelock_scan_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

        $scanType = isset($_POST['scan_type']) ? sanitize_text_field(wp_unslash($_POST['scan_type'])) : ''; // Default to 'patchman' if not provided
        $response = $this->api->sites->post_scan_now($scanType);

    // Check if the response is valid and print it
    if (is_array($response) && isset($response['status']) && $response['status'] === 'scan_queued') {
        wp_send_json_success($response);
    } else {
        if (empty($this->wpslp_partner_data)) {
            $this->wpslp_partner_data = $this->api->sites->get_partner_preference('dashboard_visibility');
        }
        $response['partner_data'] = $this->wpslp_partner_data;
        wp_send_json_error($response);
    }
}


}

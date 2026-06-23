<?php
defined('ABSPATH') || exit;
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.9.0
 * @package    Sitelock
 * @subpackage Sitelock/includes
 * @author     Todd Low <tlow@sitelock.com>
 */
class Sitelock
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.9.0
     * @access   protected
     * @var Sitelock_Loader $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.9.0
     * @access   protected
     * @var string $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.9.0
     * @access   protected
     * @var string $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.9.0
     */
    public function __construct()
    {
        $this->plugin_name = 'sitelock';
        $this->version     = '5.1.2';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->initialize_custom_class();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Sitelock_Loader. Orchestrates the hooks of the plugin.
     * - Sitelock_i18n. Defines internationalization functionality.
     * - Sitelock_Admin. Defines all hooks for the admin area.
     * - Sitelock_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.9.0
     * @access   private
     */
    private function load_dependencies()
    {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sitelock-loader.php';

        /**
         * Composer Autoloader
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sitelock-i18n.php';

        /**
         * The class responsible for IP utility functionality
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sitelock-ip-utility.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sitelock-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-sitelock-public.php';

        /**
         * The class responsible for all methods related to using our external API
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-sitelock-api.php';

        /**
         * Global functions (misnamed as admin functions)
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/functions-sitelock-admin.php';
        // Include the 2fa class files
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sitelock-2fa-settings.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sitelock-2fa.php';

        // Include the hardening class file
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sitelock-hardening.php';

        // Include the admin monitor class file
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sitelock-admin-monitor.php';

        // Include the login logger class file
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sitelock-login-logger.php';

        // Include the block admin class file
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sitelock-block-admin-username.php';

        /**
         * Password Strength class
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sitelock-password-strength.php';

        /**
         * Force Logout class
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sitelock-force-logout.php';

        /**
         * Login Lockout class
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sitelock-login-lockout.php';

        $this->loader = new Sitelock_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Sitelock_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.9.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new Sitelock_i18n();
        $plugin_i18n->set_domain($this->get_plugin_name());

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.9.0
     * @access   private
     */
    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.9.0
     * @access   private
     */
    protected function define_admin_hooks()
    {
        if ($this->should_load_admin_class()) {
            $plugin_admin = new Sitelock_Admin($this->get_plugin_name(), $this->get_version());

            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        }
    }

    /**
     * Check if we should load the main admin class.
     * This ensures backend logic (save_post, ajax, etc.) works while skipping frontend views.
     *
     * @return bool
     */
    protected function should_load_admin_class()
    {
        if (is_admin()) {
            return true;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return true;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return true;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return true;
        }

        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }

        return false;
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.9.0
     * @access   private
     */
    private function define_public_hooks()
    {
        $plugin_public = new Sitelock_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.9.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.9.0
     * @return string The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.9.0
     * @return Sitelock_Loader Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.9.0
     * @return string The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.9.0
     * @access   private
     */
    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.9.0
     * @access   private
     */
    protected function initialize_custom_class()
    {
        // 1. SiteLock_Admin_Monitor: Admin-only or Cron (admin changes can happen in cron) or REST (creating users)
        // We'll restrict to is_admin(), Cron, or REST.
        if (is_admin() || (defined('DOING_CRON') && DOING_CRON) || (defined('REST_REQUEST') && REST_REQUEST)) {
            add_action('plugins_loaded', function () {
                new SiteLock_Admin_Monitor();
            });
        }

        // 2. SiteLock_Hardening: Admin-only + Cron
        // Hardening settings are managed in admin. .htaccess rules might need update via cron?
        if (is_admin() || (defined('DOING_CRON') && DOING_CRON)) {
            new SiteLock_Hardening();
        }

        // 3. Login Security Classes (Logger, Block Username, Password Strength, Force Logout, Login Lockout)
        // These need to run on:
        // - Admin (settings, profile updates)
        // - Login pages (wp-login.php)
        // - Registration/Signup pages
        // - REST/XMLRPC (for auth checks)
        // - Logged in users (Force Logout needs to check session)

        if ($this->should_load_login_security()) {
            add_action('plugins_loaded', function () {
                new Sitelock_Login_Logger();
            });

            add_action('plugins_loaded', function () {
                new SiteLock_Block_Admin_Username();
            });

            new Sitelock_Password_Strength();

            new Sitelock_Force_Logout();

            new Sitelock_Login_Lockout();

            new Sitelock_2FA_Settings();

            new Sitelock_2FA();
        }
    }

    /**
     * Check if we should load login security related classes.
     *
     * @return bool
     */
    protected function should_load_login_security()
    {
        // Admin context
        if (is_admin()) {
            return true;
        }

        // Login related pages
        if ($this->is_login_related_page()) {
            return true;
        }

        // Authenticated API requests
        if ((defined('REST_REQUEST') && REST_REQUEST) || (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)) {
            return true;
        }

        // WP-CLI
        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }

        // Logged in users (for session timeouts, admin bar notices handled by these classes if any)
        if (defined('LOGGED_IN_COOKIE') && isset($_COOKIE[LOGGED_IN_COOKIE])) {
            return true;
        }

        return false;
    }

    /**
     * Check if current page is login/registration related.
     *
     * @return bool
     */
    protected function is_login_related_page()
    {
        $pagenow = $GLOBALS['pagenow'] ?? '';

        // Standard WP login/register
        if (in_array($pagenow, ['wp-login.php', 'wp-register.php'])) {
            return true;
        }

        // 2FA Setup page (frontend view)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Processing form data without nonce verification.
        if (!empty($_GET['sitelock-2fa-setup'])) {
            return true;
        }

        return false;
    }
}

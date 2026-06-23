<?php
defined('ABSPATH') || exit;
class Sitelock_Login_Lockout
{
    private $sitelock_language_tokens;

    public function __construct()
    {
        if (function_exists('sitelock_get_language_tokens')) {
            $this->sitelock_language_tokens = sitelock_get_language_tokens();
        } else {
            $this->sitelock_language_tokens = [];
        }

        add_action('wp_login_failed', [$this, 'sitelock_login_lockout_login_failed']);

        add_action('login_init', [$this,'sitelock_login_lockout_check_lockout']);

        add_action('wp_login', [$this, 'sitelock_login_lockout_clear_login_attempts'], 10, 2);

        add_action('admin_init', [$this, 'sitelock_login_lockout_register_settings']);

        $this->sitelock_login_lockout_default_settings();
        // Enqueue assets (CSS + JS) only on the login page when lockout is active
        add_action('login_enqueue_scripts', function () {
            if (get_option('sitelock_login_lockout_enabled', '1') === '1') {
                $this->enqueue_assets();
            }
        });
    }


    public function enqueue_assets()
    {
        $admin_url = plugin_dir_url(__FILE__);
        $root_url  = plugin_dir_url(dirname(__DIR__) . '/sitelock.php');
        $script_default_version = '1.0.0';
        $style_default_version = '1.0.0';
        wp_enqueue_style('sitelock-tailwind-css', $admin_url . 'css/tailwind.css', [], $style_default_version);
        wp_enqueue_style('sitelock-style-css', $admin_url . 'css/style.css', [], $style_default_version);
        wp_enqueue_style('sitelock-sans-css', $admin_url . 'css/sans.css', [], $style_default_version);
        wp_enqueue_style('sitelock-login-lockout-style', $root_url . 'assets/css/sitelock-login-lockout.css', [], $style_default_version);

    }

    public function sitelock_login_lockout_default_settings()
    {
        add_option('sitelock_login_lockout_enabled', '0'); // default: enabled
        add_option('sitelock_login_lockout_max_attempts', 5);
        add_option('sitelock_login_lockout_duration', 30); // in minutes
        add_option('sitelock_login_lockout_reset_time', 15); // in minutes
    }

    // Handle failed login attempts

    public function sitelock_login_lockout_login_failed($username)
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (get_option('sitelock_login_lockout_enabled', '1') !== '1') {
            return;
        }
        $ip           = Sitelock_IP_Utility::get_client_ip();
        $attempts     = get_transient("sitelock_login_lockout_login_attempts_{$ip}");
        $max_attempts = get_option('sitelock_login_lockout_max_attempts', 5);
        $lockout_duration = get_option('sitelock_login_lockout_duration', 30) * MINUTE_IN_SECONDS;
        $reset_time   = get_option('sitelock_login_lockout_reset_time', 15) * MINUTE_IN_SECONDS;

        $attempts = $attempts ? $attempts + 1 : 1;
        set_transient("sitelock_login_lockout_login_attempts_{$ip}", $attempts, $reset_time ? $reset_time : $lockout_duration);

        if ($attempts >= $max_attempts) {
            set_transient("sitelock_login_lockout_{$ip}", true, $lockout_duration);
            $this->do_redirect_and_exit(wp_login_url());
        }
    }

    // Clear login attempts on successful login

    public function sitelock_login_lockout_clear_login_attempts($user_login, $user)
    {
        if (get_option('sitelock_login_lockout_enabled', '1') !== '1') {
            return;
        }

        $ip = Sitelock_IP_Utility::get_client_ip();
        delete_transient("sitelock_login_lockout_login_attempts_{$ip}");
        delete_transient("sitelock_login_lockout_{$ip}");
        delete_option("_transient_timeout_sitelock_login_lockout_{$ip}");
        delete_option("_transient_timeout_sitelock_login_lockout_login_attempts_{$ip}");
    }

    // Register settings and fields

    public function sitelock_login_lockout_register_settings()
    {
        register_setting('sitelock_login_security_settings', 'sitelock_login_lockout_enabled', [
            'sanitize_callback' => 'sitelock_sanitize_function',
        ]);
        register_setting('sitelock_login_security_settings', 'sitelock_login_lockout_max_attempts', [
            'sanitize_callback' => 'sitelock_sanitize_function',
        ]);
        register_setting('sitelock_login_security_settings', 'sitelock_login_lockout_duration', [
            'sanitize_callback' => 'sitelock_sanitize_function',
        ]);
        register_setting('sitelock_login_security_settings', 'sitelock_login_lockout_reset_time', [
            'sanitize_callback' => 'sitelock_sanitize_function',
        ]);
    }

    public function sitelock_login_lockout_check_lockout() {

        if (get_option('sitelock_login_lockout_enabled', '1') !== '1') {
            return;
        }

        $ip = Sitelock_IP_Utility::get_client_ip();
        $timeout_option = get_option("_transient_timeout_sitelock_login_lockout_{$ip}");
        $time_remaining = $timeout_option ? max(0, $timeout_option - time()) : 0;
        if (get_transient("sitelock_login_lockout_{$ip}")) {
            // Set HTTP status to 403
            $this->do_status_header(403);

            // Load default WP login header
            $this->do_login_header();

            include_once __DIR__ . '/../pages/login-lockout-locked-template.php';
            exit; // Stop further execution (don't show login form)
        }
    }

    /**
     * Wrapper around wp_safe_redirect() + exit — overridable in tests.
     */
    protected function do_redirect_and_exit($url)
    {
        wp_safe_redirect($url);
        exit;
    }

    /**
     * Wrapper around status_header() — overridable in tests.
     */
    protected function do_status_header($code)
    {
        status_header($code);
    }

    /**
     * Wrapper around login_header() — overridable in tests.
     */
    protected function do_login_header()
    {
        login_header();
    }

}

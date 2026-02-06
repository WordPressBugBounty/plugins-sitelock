<?php

class Sitelock_Login_Lockout
{
    public function __construct()
    {
        add_action('wp_login_failed', [$this, 'sitelock_login_lockout_login_failed']);

        add_filter('authenticate', [$this, 'sitelock_login_lockout_check_lockout'], 30, 3);

        add_action('wp_login', [$this, 'sitelock_login_lockout_clear_login_attempts'], 10, 2);

        add_action('admin_init', [$this, 'sitelock_login_lockout_register_settings']);

        $this->sitelock_login_lockout_default_settings();
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
        $ip           = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $attempts     = get_transient("sitelock_login_lockout_login_attempts_{$ip}");
        $max_attempts = get_option('sitelock_login_lockout_max_attempts', 5);
        $reset_time   = get_option('sitelock_login_lockout_reset_time', 15) * MINUTE_IN_SECONDS;

        $attempts = $attempts ? $attempts + 1 : 1;
        set_transient("sitelock_login_lockout_login_attempts_{$ip}", $attempts, $reset_time);

        if ($attempts >= $max_attempts) {
            $lockout_duration = get_option('sitelock_login_lockout_duration', 30) * MINUTE_IN_SECONDS;
            set_transient("sitelock_login_lockout_{$ip}", true, $lockout_duration);
        }
    }

    // Block login if IP is locked out

    public function sitelock_login_lockout_check_lockout($user, $username, $password)
    {
        if (get_option('sitelock_login_lockout_enabled', '1') !== '1') {
            return $user;
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        if (get_transient("sitelock_login_lockout_{$ip}")) {
            return new WP_Error('sitelock_login_lockout_lockout', __('Too many failed login attempts. Please try again later.', 'sitelock-wordpress-plugin'));
        }

        return $user;
    }

    // Clear login attempts on successful login

    public function sitelock_login_lockout_clear_login_attempts($user_login, $user)
    {
        if (get_option('sitelock_login_lockout_enabled', '1') !== '1') {
            return;
        }

        $ip = (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        delete_transient("sitelock_login_lockout_login_attempts_{$ip}");
        delete_transient("sitelock_login_lockout_{$ip}");
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
}

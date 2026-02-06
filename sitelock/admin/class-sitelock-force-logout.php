<?php

class Sitelock_Force_Logout
{
    public function __construct()
    {
        // Force logout by time period variables
        define('SITELOCK_FORCE_LOGOUT_DEFAULT_DURATION', 12); // Default: 12 hour
        define('SITELOCK_FORCE_LOGOUT_DEFAULT_ENABLED', 0);     // Default: OFF
        define('SITELOCK_ONE_HOUR_IN_SECONDS', 3600);

        // Disable Remember Me - CSS
        add_action('login_form', [$this, 'sitelock_force_logout_conditionally_hide_remember_me_checkbox']);

        // Disable Remember Me
        add_filter('login_cookie_lifetime', [$this, 'sitelock_force_logout_disable_remember_me_cookie']);

        // Force Logout Set Time
        add_action('wp_login', [$this, 'sitelock_force_logout_set_login_time'], 10, 2);

        // LOGOUT: Check If Session Expired
        add_action('init', [$this, 'sitelock_force_logout_check_logout_time']);
    }

    /**
    * remember me hide when force logout time enabled css
    *
    * @since    5.0.0
    */
    public function sitelock_force_logout_conditionally_hide_remember_me_checkbox()
    {
        if (get_option('sitelock_force_logout_enabled') == 1) {
            echo '<style>#rememberme, label[for="rememberme"] { display: none !important; }</style>';
        }
    }

    /**
     * remember me hide when force logout time enabled
     *
     * @since    5.0.0
     * @param int $expire
     */
    public function sitelock_force_logout_disable_remember_me_cookie($expire)
    {
        if (get_option('sitelock_force_logout_enabled') == 1) {
            return 0; // Session expires when the browser closes
        }

        return $expire; // Default behavior otherwise
    }

    /**
      * force logout set login time
      *
      * @since    5.0.0
      */
    public function sitelock_force_logout_set_login_time($user_login, $user)
    {
        $utc_time = gmdate('U'); // UTC timestamp
        update_user_meta($user->ID, 'sitelock_force_logout_time', $utc_time);
    }

    /**
     * check logout time
     *
     * @since    5.0.0
     */
    public function sitelock_force_logout_check_logout_time()
    {
        $enabled = get_option('sitelock_force_logout_enabled', SITELOCK_FORCE_LOGOUT_DEFAULT_ENABLED);
        if (intval($enabled) !== 1) {
            return;
        }

        $duration = intval(get_option('sitelock_force_logout_duration', SITELOCK_FORCE_LOGOUT_DEFAULT_DURATION) * SITELOCK_ONE_HOUR_IN_SECONDS);

        if (is_user_logged_in()) {
            $current_user   = wp_get_current_user();
            $user_roles     = $current_user->roles;
            $excluded_roles = get_option('sitelock_force_logout_excluded_roles', []);

            // Check if user has any excluded role
            foreach ($user_roles as $role) {
                if ($excluded_roles && in_array($role, $excluded_roles)) {
                    return; // Skip logout
                }
            }
            $user_id    = get_current_user_id();
            $login_time = get_user_meta($user_id, 'sitelock_force_logout_time', true);

            $current_time = gmdate('U'); // UTC timestamp

            if ($login_time && ($current_time - $login_time) > $duration) {
                wp_logout();

                // Redirect with header fallback
                $redirect_url = home_url('/?session_expired=1');
                if (!headers_sent()) {
                    wp_redirect($redirect_url);
                    exit;
                } else {
                    echo '<script>window.location.href="' . esc_url($redirect_url) . '";</script>';
                    exit;
                }
            }
        }
    }
}

<?php
defined('ABSPATH') || exit;
class SiteLock_Block_Admin_Username
{

    private $sitelock_language_tokens;

    public function __construct()
    {
        if (function_exists('sitelock_get_language_tokens')) {
            $this->sitelock_language_tokens = sitelock_get_language_tokens();
        } else {
            $this->sitelock_language_tokens = [];
        }
        // Block "admin" username during user creation (wp_create_user)
        add_action( 'user_profile_update_errors',[$this, 'sitelock_block_admin_username_create_form'], 10, 3 );

        // Block "admin" username during user creation (REST API, etc.)
        add_filter('pre_user_login', [$this, 'sitelock_block_admin_username_creation']);

        // Graceful error for frontend registration form
        add_filter('registration_errors', [$this, 'sitelock_block_admin_username_registration'], 10, 3);

        // Add actions to save the username field on profile
        // add_action('user_profile_update_errors', [$this, 'sitelock_save_username_field'], 10, 3);

        // Add actions to show warning notice if "admin" user exists
        add_action('admin_notices', [$this, 'sitelock_admin_username_warning_notice']);

        /**
         * Registers an AJAX action to handle the dismissal of admin notices.
         *
         * This action is triggered via the 'wp_ajax_sitelock_dismiss_notice' hook
         * and calls the 'sitelock_admin_dismiss_admin_notice' method.
         */
        add_action('wp_ajax_sitelock_dismiss_notice', [$this, 'sitelock_admin_dismiss_admin_notice']);

        /**
         * Enqueues the admin notice script for the SiteLock plugin.
         *
         * This function is hooked to the 'admin_enqueue_scripts' action
         * to load necessary scripts for displaying admin notices.
         *
         * @see https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
         */
        add_action('admin_enqueue_scripts', [$this, 'sitelock_admin_notice_script']);
    }

    // Block "admin" username during user creation (REST API)
    public function sitelock_block_admin_username_creation($username)
    {
        // Check if it's a user creation action and nonce is verified
        if (defined('REST_REQUEST') && REST_REQUEST) {
            if (strtolower($username) === 'admin') {
                wp_die(
                    esc_html( $this->sitelock_language_tokens['admin_username']['usernameBlocked']),
                    esc_html($this->sitelock_language_tokens['admin_username']['invalidUsername']),
                    ['back_link' => true]);
            }
        }
    
        return $username;
    }

    /**
     * Block creation of username "admin" in any case variation (wp_create_user).
     */
    public function sitelock_block_admin_username_create_form($errors,$update, $user)
    {

        if ($update) { return; }
        if (
            isset($_POST['action'], $_POST['_wpnonce_create-user']) && sanitize_text_field(wp_unslash($_POST['action'])) === 'createuser' && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_create-user'])), 'create-user')
        ) {
                $username = strtolower($user->user_login);

                if ($username === 'admin') {
                    $errors->add(
                        'username_not_allowed',
                        esc_html($this->sitelock_language_tokens['admin_username']['usernameBlocked'])
                    );
                }
        }
    }

    // Block "admin" during frontend registration
    public function sitelock_block_admin_username_registration($errors, $sanitized_user_login, $user_email)
    {
        if (strtolower($sanitized_user_login) === 'admin') {
            $errors->add(
                'username_admin_blocked',
                esc_html($this->sitelock_language_tokens['admin_username']['usernameBlocked'])
            );
        }

        return $errors;
    }

    // Save username on profile
    // public function sitelock_save_username_field($errors, $update, $user)
    // {
    //     // Get the new username from the form submission
    //     $username = isset($_POST['user_login']) ? sanitize_user($_POST['user_login']) : '';

    //     // Restrict the username "admin"
    //     if (strtolower($username) === 'admin') {
    //         $errors->add(
    //             'username_error',
    //             esc_html($this->sitelock_language_tokens['admin_username']['usernameBlocked'])
    //         );
    //         return;
    //     }
    // }

    /**
     * Displays a security warning notice in the WordPress admin area if a user with the username "admin" exists.
     *
     * This function checks if the current user is logged in and has admin privileges. If a user with the
     * username "admin" exists, it displays a warning message advising to rename the user for improved security.
     * The notice is dismissible and will not be shown again unless the user visits the plugin page.
     *
     * @return void
     */
    public function sitelock_admin_username_warning_notice()
    {
        if (!is_user_logged_in() || !is_admin() || !username_exists('admin')) {
            return;
        }

        $user = wp_get_current_user();

        // Display notice only to administrators
        if (is_user_logged_in() && !current_user_can('administrator')) {
            return;
        }

        // Check if dismissed
        $dismissed = get_user_meta($user->ID, 'sitelock_dismissed_admin_warning', true);

        // Show only if not dismissed, or if it's plugin page
        if ($dismissed && !$this->sitelock_is_plugin_page()) {
            return;
        }

        $class = '';
        if ($this->sitelock_is_plugin_page()) {
            $class = 'sitelock-admin-notice-custom';
        }

        echo wp_kses_post('<div class="notice notice-warning is-dismissible sitelock-admin-warning' . ($class ? ' ' . esc_attr($class) : '') . '">
        <p><strong>' . esc_html($this->sitelock_language_tokens['admin_username']['securityWarning']) . ':</strong> ' . esc_html($this->sitelock_language_tokens['admin_username']['userWithUsername']) . ' <code>' . esc_html($this->sitelock_language_tokens['admin_username']['admin']) . '</code> ' . esc_html($this->sitelock_language_tokens['admin_username']['userExists']) . '</p>
    </div>');
    }

    /**
     * Checks if the current screen is part of the SiteLock plugin's admin pages.
     *
     * @return bool True if the current screen is a SiteLock plugin page, false otherwise.
     */
    public function sitelock_is_plugin_page()
    {
        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }

        // Replace with your plugin's screen ID or part of it
        return strpos($screen->id, 'sitelock') !== false;
    }

    /**
     * Handles the dismissal of the SiteLock admin notice via AJAX.
     *
     * Verifies the AJAX request using a nonce, updates the user meta
     * to mark the notice as dismissed for the current user, and sends
     * a JSON response indicating success or failure.
     *
     * @return void
     */
    public function sitelock_admin_dismiss_admin_notice()
    {
        check_ajax_referer('sitelock_dismiss_notice', '_ajax_nonce');

        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, 'sitelock_dismissed_admin_warning', 1);
            wp_send_json_success();
        }

        wp_send_json_error();
    }

    /**
     * Enqueues the JavaScript file for dismissing admin notices and localizes the script with AJAX data.
     *
     * @param string $hook The current admin page hook suffix.
     */
    public function sitelock_admin_notice_script($hook)
    {
        wp_enqueue_script(
            'sitelock_dismiss_notice',
            plugin_dir_url(__FILE__) . 'js/sitelock-admin-dismiss-notice.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('sitelock_dismiss_notice', 'sitelock_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sitelock_dismiss_notice'),
        ]);
    }
}

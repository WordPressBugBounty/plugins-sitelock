<?php
defined( 'ABSPATH' ) || exit;

use ZxcvbnPhp\Zxcvbn;

class Sitelock_Password_Strength
{
    private $sitelock_language_tokens;

    /**
     * The API instance.
     *
     * @var Sitelock_API
     */

    public function __construct()
    {
        if (function_exists('sitelock_get_language_tokens')) {
            $this->sitelock_language_tokens = sitelock_get_language_tokens();
        } else {
            $this->sitelock_language_tokens = [];
        }

        // Validate password strength during user creation or update
        add_action('user_profile_update_errors', [$this,'sitelock_validate_password_strength_on_user_creation'], 10, 4);

        // Enqueue password strength meter script on admin pages where password fields exist
        add_action('admin_enqueue_scripts', [$this, 'sitelock_enqueue_password_strength_script']);

        // Validate password strength during password reset
        add_action('validate_password_reset', function ($errors, $user) {
            $this->sitelock_enqueue_password_on_reset($errors, $user);
        }, 10, 2);

        // Corrected to use array($this, ...) for class method
        add_action('login_head', [$this, 'sitelock_hide_weak_password_checkbox_css']);

        // Combined admin_head actions for simplicity
        add_action('admin_head', [$this, 'sitelock_hide_weak_password_checkbox_css']);
    }

    /**
     * Validates the password strength during user creation based on the assigned role's requirements.
     *
     * This function checks if the password strength validation feature is enabled (`sitelock_password_strength_enabled`).
     * If enabled, it retrieves the user's role and the required password strength for that role.
     * It then validates the provided password against the required strength using the `zxcvbn` library.
     * If the password does not meet the required strength, an error is added to the `$errors` object.
     *
     * @param WP_Error $errors An object containing any validation errors encountered during user creation.
     * @param bool     $update Indicates whether the user is being updated (true) or created (false).
     * @param WP_User  $user   The user object being created or updated.
     *
     * @return void Adds an error to the `$errors` object if the password does not meet the required strength.
     */
    public function sitelock_validate_password_strength_on_user_creation($errors, $update, $user, $nonce = null)
    {
        // Check if sitelock_password_strength_enabled is enabled
        $sitelock_password_strength_enabled = get_option('sitelock_password_strength_enabled', 0);
        if ($sitelock_password_strength_enabled != 1 || !isset($user->user_pass)) {
            return;
        }

        // Verify the nonce for WordPress's default user profile form
        $nonce = $nonce ?? (isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '');
        if (isset($user->ID) && (!$nonce || !wp_verify_nonce($nonce, "update-user_{$user->ID}"))) {
            $errors->add('nonce_verification_failed', esc_html($this->sitelock_language_tokens['common_errors']['nonceVerificationFailed']));

            return;
        }

        // Get the user's role
        // Determine user roles based on the context
        if (isset($_POST['role'])) {
            // Scenario 1: Role is provided in the POST request (e.g., during user creation)
            $user_roles = [sanitize_text_field(wp_unslash($_POST['role']))];
        } elseif ($update === false) {
            // Scenario 2: Forgot password flow, use roles from the user object
            $user_roles = $user->roles;
        } else {
            // Scenario 3: Profile page password update, retrieve roles from the database
            $user_roles = get_userdata($user->ID)->roles;
        }
        $primary_role = $user_roles[0] ?? ''; // Get the first role (primary role)

        // Get the assigned password strength for the role
        $sitelock_password_strength_user_roles = get_option('sitelock_password_strength_user_roles', []);
        $required_strength                     = $sitelock_password_strength_user_roles[$primary_role] ?? 'medium'; // Default to medium

        // Get the password from the request
        $password = isset($_POST['pass1']) ? sanitize_text_field(wp_unslash($_POST['pass1'])) : ''; // WordPress uses 'pass1' for the password field

        // Validate the password strength
        $min_score = $this->sitelock_map_level_to_score($required_strength);

        if (!$this->sitelock_validate_password_strength($password, $min_score)) {
            $errors->add(
                'pass_strength',
                sprintf(
                    /* Translators: %s is replaced with the minimum strength requirement for the user's account role. */
                    esc_html($this->sitelock_language_tokens['common_errors']['passwordStrength']),
                    $primary_role
                )
            );
        }
    }

    /**
     * Validates the strength of a given password using the Zxcvbn PHP library.
     *
     * This function evaluates the password strength and checks if it meets the
     * minimum required score. The Zxcvbn library provides a score ranging from
     * 0 (weak) to 4 (strong) based on the password's complexity.
     *
     * @param  string $password  The password to be validated.
     * @param  int    $min_score The minimum score required for the password to be considered strong.
     * @return bool   Returns true if the password's score is greater than or equal to the minimum score, false otherwise.
     */
    public function sitelock_validate_password_strength($password, $min_score)
    {
        $passwordStrengthLib = new Zxcvbn();
        $result              = $passwordStrengthLib->passwordStrength($password);
        // score is 0 (weak) to 4 (strong)
        return ($result['score'] >= $min_score);
    }

    /**
     * Maps a security level to a corresponding numerical score.
     *
     * This function takes a security level as input and returns a numerical
     * score that represents the strength of the level. The mapping is as follows:
     * - 'weak' maps to 2
     * - 'medium' maps to 3
     * - 'strong' maps to 4
     * - Any other input defaults to 0 (no restriction).
     *
     * @param  string $level The security level ('weak', 'medium', 'strong').
     * @return int    The numerical score corresponding to the given security level.
     */
    public function sitelock_map_level_to_score($level)
    {
        switch ($level) {
            case 'weak': return 2;   // weak
            case 'medium': return 3; // medium
            case 'strong': return 4;   // strong
            default: return 0;       // no restriction
        }
    }

    /**
     * Enqueues the password strength meter script on specific admin pages.
     *
     * This function ensures that the password strength meter is loaded only on pages
     * where password fields are present, such as user profile, user edit, user creation,
     * and login pages, if the password strength validation feature is enabled.
     *
     * @param string $hook The current admin page hook.
     */
    public function sitelock_enqueue_password_strength_script($hook)
    {
        // Load only where password fields exist
        if (in_array($hook, ['profile.php', 'user-edit.php', 'user-new.php', 'wp-login.php']) && get_option('sitelock_password_strength_enabled') == '1') {
            wp_enqueue_script('password-strength-meter'); // WordPress native
            wp_enqueue_script(
                'sitelock-password-check',
                plugin_dir_url(__FILE__) . 'js/sitelock-password-check.js',
                ['jquery', 'password-strength-meter'],
                filemtime(plugin_dir_path(__FILE__) . 'js/sitelock-password-check.js'), // dynamically set version based on file modification time
                true
            );

            // Pass sitelock_password_strength_user_roles to JavaScript
            $sitelock_password_strength_user_roles = get_option('sitelock_password_strength_user_roles', []);
            wp_localize_script('sitelock-password-check', 'sitelockRoles', $sitelock_password_strength_user_roles);

            // Pass user role only for profile page
            if ($hook === 'profile.php') {
                $current_user = wp_get_current_user();
                wp_localize_script('sitelock-password-check', 'sitelockUserRoles', $current_user->roles ?? []);
            }
        }
    }

    /**
     * Enqueues the password strength meter script on the login page if enabled.
     */
    public function sitelock_enqueue_password_on_reset($errors, $user)
    {
        if (get_option('sitelock_password_strength_enabled') == '1') {
            wp_enqueue_script('password-strength-meter'); // WordPress native
            wp_enqueue_script(
                'sitelock-password-check',
                plugin_dir_url(__FILE__) . 'js/sitelock-password-check.js',
                ['jquery', 'password-strength-meter'],
                filemtime(plugin_dir_path(__FILE__) . 'js/sitelock-password-check.js'), // dynamically set version based on file modification time
                true
            );

            // Pass sitelock_password_strength_user_roles to JavaScript
            $sitelock_password_strength_user_roles = get_option('sitelock_password_strength_user_roles', []);
            wp_localize_script('sitelock-password-check', 'sitelockRoles', $sitelock_password_strength_user_roles);
            wp_localize_script('sitelock-password-check', 'sitelockUserRoles', $user->roles ?? []);

            // Generate a nonce for the password reset form using string interpolation
            $nonce = wp_create_nonce("update-user_{$user->ID}");

            // Pass the nonce as a parameter to the validation function
            $this->sitelock_validate_password_strength_on_user_creation($errors, false, $user, $nonce);
        }
    }

    // Hide the "weak password" checkbox if password strength validation is enabled
    public function sitelock_hide_weak_password_checkbox_css()
    {
        if (get_option('sitelock_password_strength_enabled') == '1') {
            echo '<style>.pw-weak { display: none !important; }</style>';
        }
    }
}

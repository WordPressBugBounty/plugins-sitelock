<?php

if (!defined('ABSPATH')) {
    exit;
}

// Load OTPhP library via Composer autoload
require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use OTPHP\TOTP;

class Sitelock_2FA_Settings
{
    private $settings;

    public const TOTP_ISSUER = 'SiteLock WP';

    /**
     * Constructor for the SiteLock 2FA Settings class.
     *
     * @param bool $enable_hooks Optional. Whether to initialize hooks. Default is true.
     */
    public function __construct($enable_hooks = true)
    {
        if ($enable_hooks) {
            $this->init_hooks();
        }
    }

    private function init_hooks()
    {
        $this->settings = get_option('sitelock_2fa_settings', []);

        add_action('add_option_sitelock_2fa_settings', [$this, 'process_2fa_settings_update'], 10, 2);
        add_action('update_option_sitelock_2fa_settings', [$this, 'process_2fa_settings_update'], 10, 2);

        if (!empty($this->settings['enable_2fa'])) {
            add_action('show_user_profile', [$this, 'display_2fa_links']);
            add_action('edit_user_profile', [$this, 'display_2fa_links']);
            add_action('wp_ajax_sitelock_disable_2fa', [$this, 'disable_2fa_code']);
            add_action('wp_ajax_sitelock_verify_2fa', [$this, 'verify_2fa_code']);
            /**
             * Currently not used, as there is no option in UI to regenerate the recovery code
             */
            //add_action('wp_ajax_sitelock_regenerate_backup_codes', [$this, 'regenerate_backup_codes']);
            add_action('admin_enqueue_scripts', [$this, 'sitelock_enqueue_2fa_setup_scripts']);
        }
    }

    public function display_2fa_links($user)
    {
        // Ensure the current user is viewing/editing their own profile
        if ($user->ID !== get_current_user_id()) {
            return;
        }

        // Ensure the current user can edit posts; if not, exit early
        if (!current_user_can('edit_posts')) {
            return;
        }

        $is_2fa_enabled = get_user_meta($user->ID, 'sitelock_2fa_enabled', true);

        include plugin_dir_path(__FILE__) . 'partials/settings/sitelock-admin-2fa-link.php';
    }

    public function display_2fa_settings($user)
    {
        $encrypted_secret       = get_user_meta($user->ID, 'sitelock_2fa_secret', true);
        $user_secret            = $encrypted_secret ? Sitelock_Crypto::decrypt($encrypted_secret) : '';
        $is_2fa_enabled         = get_user_meta($user->ID, 'sitelock_2fa_enabled', true);
        $encrypted_backup_codes = get_user_meta($user->ID, 'sitelock_2fa_backup_codes', true);
        $backup_codes           = array_map(function ($code) {
            return Sitelock_Crypto::decrypt($code);
        }, $encrypted_backup_codes ?: []);

        // Generate a secret only if 2FA is not already enabled
        if (!$is_2fa_enabled || !$user_secret || empty($user_secret)) {
            $totp             = TOTP::create(); // Generates random secret automatically
            $user_secret      = $totp->getSecret();
            $encrypted_secret = Sitelock_Crypto::encrypt($user_secret);
            if ($encrypted_secret !== false) {
                update_user_meta($user->ID, 'sitelock_2fa_secret', $encrypted_secret);
            }
        } else {
            $totp = TOTP::create($user_secret);
        }

        // Assign TOTP object with issuer and account name
        $home_url = home_url();
        if (function_exists('wp_parse_url')) {
            $domain = wp_parse_url($home_url, PHP_URL_HOST);
        } else {
            $parsed = parse_url($home_url);
            $domain = $parsed['host'] ?? '';
        }

        $site_label    = $domain ?: $home_url;
        $account_label = !empty($user->user_login) ? $user->user_login : $user->user_email;
        $label         = sprintf('%s - %s', $site_label, $account_label);
        $totp->setLabel($label);
        $totp->setIssuer(self::TOTP_ISSUER);

        $totp_uri = $totp->getProvisioningUri();

        $renderer = new ImageRenderer(new RendererStyle(256), new SvgImageBackEnd());
        $writer   = new Writer($renderer);
        $svg      = $writer->writeString($totp_uri);
        $qr_src   = 'data:image/svg+xml;base64,' . base64_encode($svg);

        // Generate backup codes if not set
        if (!$backup_codes || !is_array($backup_codes)) {
            $backup_codes = $this->generate_backup_codes($user->ID);
            if ($backup_codes === false) {
                wp_send_json_error(['message' => 'Failed to generate backup codes']);
            }
        }

        $user            = wp_get_current_user();
        $warning_message = get_transient('sitelock_2fa_setup_notice_'.$user->ID);

        $display_data = [
            'user_secret'     => $user_secret,
            'qr_src'          => $qr_src,
            'is_2fa_enabled'  => $is_2fa_enabled,
            'warning_message' => $warning_message,
        ];
        $transient_key = 'sitelock_2fa_verified_' . $user->ID;
        if (!empty(get_transient($transient_key))) {
            $display_data['backup_codes']    = $backup_codes;
            $code_expiration                 = (int) get_option('_transient_timeout_' . $transient_key);
            $remaining_minutes               = max(1, (int) floor(($code_expiration - time()) / 60));
            $display_data['code_expiration'] = $remaining_minutes;
        }

        return $display_data;
    }

    public function disable_2fa_code()
    {
        check_ajax_referer('sitelock_2fa_ajax_nonce', 'security');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error([
                'message' => 'User not logged in'
            ]);
            wp_die();
        }

        /**
         * disabled this check, because it will interrupt the user if he wants to change 2FA device as a part of account recovery process
         */
        // if($this->check_user_mandate_2fa($user_id)) {
        //     wp_send_json_error(['message' => 'You cannot disable 2FA. It is required for your role.']);
        //     return;
        // }
        delete_user_meta($user_id, 'sitelock_2fa_enabled');
        delete_user_meta($user_id, 'sitelock_2fa_secret');
        delete_user_meta($user_id, 'sitelock_2fa_backup_codes');
        delete_user_meta($user_id, 'sitelock_2fa_failed_attempts');

        wp_send_json_success([
            'message' => '2FA disabled successfully'
        ]);
    }

    public function process_2fa_settings_update($arg, $new_value)
    {
        if (isset($new_value['grace_period']) && is_numeric($new_value['grace_period'])) {
            $days             = intval($new_value['grace_period']);
            $grace_expiration = strtotime("+{$days} days");
            update_option('sitelock_2fa_grace_period', $grace_expiration);
        }

        if (!isset($new_value['enable_2fa']) || !$new_value['enable_2fa']) {
            delete_metadata('user', 0, 'sitelock_2fa_enabled', '', true);
            delete_metadata('user', 0, 'sitelock_2fa_secret', '', true);
            delete_metadata('user', 0, 'sitelock_2fa_backup_codes', '', true);
            delete_metadata('user', 0, 'sitelock_2fa_failed_attempts', '', true);
            $this->delete_2fa_rate_transients();
        }
    }

    /**
     * Delete all sitelock_2fa_rate_* transients.
     */
    public static function delete_2fa_rate_transients() {
        global $wpdb;

        // Query to find all transients matching the pattern
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $transients = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_sitelock_2fa_rate_') . '%'
            )
        );

        // Loop through and delete each transient
        foreach ($transients as $transient) {
            $key = str_replace('_transient_', '', $transient);
            delete_transient($key);
        }
    }

    public function check_user_mandate_2fa($user_id)
    {
    $sitelock_enabled_roles = get_option('sitelock_2fa_settings', []);
    $user = get_userdata($user_id);
    if (!$user) return false;

    $user_roles = $user->roles;

    foreach ($user_roles as $role) {
        // Never allow subscriber for 2FA
        if ($role === 'subscriber') continue;

        if (in_array($role, $sitelock_enabled_roles['mandatory_roles'])) {
            return true;
        }
    }

    return false;
}


    public function verify_2fa_code()
    {
        check_ajax_referer('sitelock_2fa_ajax_nonce', 'security');

        $user_id          = get_current_user_id();
        $encrypted_secret = get_user_meta($user_id, 'sitelock_2fa_secret', true);
        $submitted_code   = isset($_POST['code']) ? sanitize_text_field(wp_unslash($_POST['code'])) : '';

        if (!$encrypted_secret || empty($submitted_code)) {
            wp_send_json_error(['message' => 'Invalid request']);
        }

        $user_secret = Sitelock_Crypto::decrypt($encrypted_secret);
        if (!$user_secret) {
            wp_send_json_error(['message' => 'Failed to retrieve the 2FA secret']);
        }   

        $totp = TOTP::create($user_secret);
        if ($totp->verify($submitted_code)) {
            update_user_meta($user_id, 'sitelock_2fa_enabled', true);
            // Set a transient valid for 30 minutes for the specific user
            set_transient('sitelock_2fa_verified_' . $user_id, true, 30 * MINUTE_IN_SECONDS);
            wp_send_json_success(['message' => '2FA Enabled Successfully!']);
        } else {
            wp_send_json_error(['message' => 'Invalid 2FA Code']);
        }
    }

    private function generate_backup_codes($user_id)
    {
        $codes = [];
        for ($i = 0; $i < 5; $i++) {
            $codes[] = strtoupper(wp_generate_password(16, false));
        }

        // Encrypt each backup code
        $encrypted_codes = [];
        foreach ($codes as $code) {
            $encrypted = Sitelock_Crypto::encrypt($code);
            if ($encrypted === false) {
                // Fail early if encryption fails; callers should treat false as an error.
                return false;
            }
            $encrypted_codes[] = $encrypted;
        }

        update_user_meta($user_id, 'sitelock_2fa_backup_codes', $encrypted_codes);

        return $codes;
    }

    /**
     * Regenerate backup codes for the current user.
     * Currently not used, as there is no option in UI to regenerate the recovery code
     * @return void
     */
    public function regenerate_backup_codes()
    {
        check_ajax_referer('sitelock_2fa_ajax_nonce', 'security');
        $user_id   = get_current_user_id();
        $new_codes = $this->generate_backup_codes($user_id);
        if ($new_codes === false) {
            wp_send_json_error(['message' => 'Failed to generate backup codes']);
        }

        wp_send_json_success(['codes' => $new_codes]);
    }

    public function sitelock_enqueue_2fa_setup_scripts($hook_suffix)
    {
        wp_enqueue_script('sitelock-2fa-setup-js', plugin_dir_url(__FILE__) . 'js/sitelock-2fa-setup.js', ['jquery'], '1.0.0', true);
        if ('profile.php' == $hook_suffix || 'user-edit.php' == $hook_suffix) {
            wp_enqueue_style('sitelock-2fa-style', plugin_dir_url(__FILE__) . 'css/sitelock-2fa-setup.css', [], '1.0.0', 'all');
        }

        wp_localize_script('sitelock-2fa-setup-js', 'sitelock_2fa_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sitelock_2fa_ajax_nonce'),
        ]);
    }
}

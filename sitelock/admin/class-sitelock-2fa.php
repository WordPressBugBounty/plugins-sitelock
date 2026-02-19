<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__DIR__) . 'includes/class-sitelock-crypto.php'; // if not already
require_once plugin_dir_path(__DIR__) . 'vendor/autoload.php'; // OTPHP etc.

use OTPHP\TOTP;

class Sitelock_2FA
{
    // Define the maximum number of allowed 2FA reattempts before lockout
    private const MAX_2FA_ATTEMPTS     = 3;
    private const MAX_ATTEMPTS_TIMEOUT = 60; 
    private const MIN_2FA_SESSION_TIMEOUT = 10;
    private $session_token_cache = null; 
    
    /**
     * Static cache for provider IPs to avoid repeated file reads.
     * Persists across all instances during a single request lifecycle.
     * @var array|null
     */
    private static $cached_provider_ips = null;
    
    public function __construct()
    {
        // Hook into WP login action for custom action "sitelock-2fa"
        add_action('login_form_sitelock-2fa', [$this, 'render_2fa_page']);

        // Clear pending user cookie on logout
        add_action('wp_logout', 'sitelock_clear_pending_user_cookie');
    }
    
    /**
     * Lazy-load provider IPs on first use with static caching.
     *
     * @return array List of provider IPs
     */
    private function get_provider_ips()
    {
        if (self::$cached_provider_ips === null) {
            self::$cached_provider_ips = Sitelock_IP_Utility::load_ip_json(
                SITELOCK_PLUGIN_DIR . 'ip-data/provider-ips.json',
                __CLASS__
            );
        }
        
        return self::$cached_provider_ips;
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
        wp_enqueue_style('sitelock-2fa-style', $root_url . 'assets/css/sitelock-2fa.css', [], $style_default_version);
        
        wp_enqueue_script('sitelock-2fa-js', $root_url . 'assets/js/sitelock-2fa.js', ['jquery'], $script_default_version, true);

        // Provide some localized strings and nonce
        wp_localize_script('sitelock-2fa-js', 'sitelock2fa', [
            'nonce' => wp_create_nonce('sitelock_2fa_verify'),
            'i18n'  => [
                'invalid_code'     => 'Invalid authentication code.',
                'invalid_recovery' => 'Invalid recovery code.',
                'cant_access'      => 'Or use a backup code',
                'back'             => 'Or use a authenticator code',
            ],
        ]);
    }

    /**
     * Check if 2FA is valid for a user.
     */
    private function is_2fa_valid($user_id)
    {
        // Retrieve the user's 2FA secret and validate the TOTP code
        $totp_secret = get_user_meta($user_id, 'sitelock_2fa_secret', true);
        if (empty($totp_secret)) {
            return false;
        }

        // Validate TOTP code
        $totp = TOTP::create($totp_secret);
        $totp->setLabel(get_userdata($user_id)->user_email);
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is intentional as the interim-login parameter is not critical.
        $provided_code = isset($_POST['totp_code']) ? sanitize_text_field(wp_unslash($_POST['totp_code'])) : '';
        if (!empty($provided_code) && $totp->verify($provided_code)) {
            return true;
        }

        // Validate recovery code
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is intentional as the interim-login parameter is not critical.
        $provided_recovery_code = isset($_POST['recovery_code']) ? sanitize_text_field(wp_unslash($_POST['recovery_code'])) : '';
        if (!empty($provided_recovery_code)) {
            $recovery_codes = get_user_meta($user_id, 'sitelock_2fa_recovery_codes', true);
            if (is_array($recovery_codes) && in_array($provided_recovery_code, $recovery_codes, true)) {
                // Remove the used recovery code to prevent reuse
                $updated_recovery_codes = array_diff($recovery_codes, [$provided_recovery_code]);
                update_user_meta($user_id, 'sitelock_2fa_recovery_codes', $updated_recovery_codes);

                return true;
            }
        }

        return false;
    }

    public function render_2fa_page()
    {
        $settings = get_option('sitelock_2fa_settings', []);
        if (empty($settings['enable_2fa'])) {
            sitelock_clear_pending_user_cookie();
            
            if (is_user_logged_in()) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Processing form data without nonce verification.
                $redirect_to = ! empty( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : admin_url();
                wp_safe_redirect($redirect_to);
            } else {
                wp_safe_redirect(wp_login_url());
            }
            exit;
        }

        $totp_locked = false;

        // Retrieve 2FA rate limit data
        $data = $this->sitelock_get_2fa_rate();

        if (is_array($data)) {
            if ((int)$data['failed'] >= self::MAX_2FA_ATTEMPTS) {
                $remaining = (int)$data['lockout'] - time();

                if ($remaining > 0) {
                    $totp_locked = true;
                }
            }
        }

        if(!$totp_locked) {
            $user_id = $this->get_pending_user_or_redirect();
        }

        $this->check_rate_limit();

        // Handle POST (TOTP/recovery submission)
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_post_request($user_id);
        }

        // Load and clear any previous errors
        $errors = $this->load_errors_from_transients($user_id);

        // Enqueue assets (CSS + JS)
        $this->enqueue_assets();

        // Output HTML
        $this->render_template([
            'sitelock_2fa_authenticate_error' => $errors['totp'],
            'sitelock_2fa_recovery_error'     => $errors['recovery'],
            'site_name'                       => get_bloginfo('name'),
            'logo'                            => $this->get_logo_url(),
        ]);
    }

    private function get_pending_user_or_redirect()
    {
        $user_id = sitelock_get_pending_user_id();
        if (empty($user_id)) {
            wp_safe_redirect(sitelock_build_url_with_query_params(wp_login_url()));
            exit;
        }

        return (int)$user_id;
    }

    private function get_pending_session_token()
    {
        if ($this->session_token_cache !== null) {
            return $this->session_token_cache;
        }

        $pending_cookie = Sitelock_Secure_Cookie::get('sitelock_pending_user');
        
        if (is_array($pending_cookie) && !empty($pending_cookie['tid'])) {
             $session_token = $pending_cookie['tid'];
             $this->session_token_cache = $session_token;
             return $session_token;
        }

        return null;
    }

    /**
     * Get SiteLock 2FA Rate Limit Keys
     *
     * @return array
     */
    private function sitelock_get_2fa_rate_keys()
    {
        $keys = [];

        // 1. Session Token Key (Priority)
        $session_token = $this->get_pending_session_token();
        if (!empty($session_token)) {
            $keys['session'] = 'sitelock_2fa_rate_' . hash('sha256', $session_token);
        }

        // 2. Client Fingerprint Key (Fallback/Long-term)
        $ip = Sitelock_IP_Utility::get_client_ip($this->get_provider_ips());
        //$ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

        if (!empty($ip)) {
            //$fingerprint = hash('sha256', $ip . $ua);
            $fingerprint = hash('sha256', $ip);
            $keys['ip'] = 'sitelock_2fa_rate_' . $fingerprint;
        }

        return $keys;
    }


    /**
     * Get SiteLock 2FA Rate Limit
     *
     * @return mixed
     */
    private function sitelock_get_2fa_rate()
    {
        $keys = $this->sitelock_get_2fa_rate_keys();
        
        // Check Session Key first
        if (isset($keys['session']) && ($data = get_transient($keys['session']))) {
            return $data;
        }

        // Check IP Key second
        if (isset($keys['ip']) && ($data = get_transient($keys['ip']))) {
            return $data;
        }

        return false;
    }

    /**
     * Check if the current user session is locked out due to repeated failed 2FA attempts.
     */
    private function check_rate_limit()
    {
        $data = $this->sitelock_get_2fa_rate();

        if (!is_array($data)) {
            return;
        }

        // Check for lockout state
        if ((int)$data['failed'] >= self::MAX_2FA_ATTEMPTS) {
            $remaining = (int)$data['lockout'] - time();

            if ($remaining > 0) {
                $session_token = $this->get_pending_session_token();
                if(!empty($session_token)) {
                    $hash          = hash('sha256', $session_token);
                    delete_transient("sitelock_2fa_authenticate_error_$hash");
                    delete_transient("sitelock_2fa_recovery_error_$hash");
                }

                // Still locked out
                $this->enqueue_assets();
                
                $data = [
                    'lockout_remaining' => max(1, intval($remaining)),
                    'site_name'         => get_bloginfo('name'),
                    'logo'              => $this->get_logo_url(),
                    'lockout_period'    => self::MAX_ATTEMPTS_TIMEOUT,
                ];

                include plugin_dir_path(__DIR__) . 'pages/2fa-lockout-template.php';
                exit;
            }

            // Lockout expired — reset
            $data = ['failed' => 0, 'lockout' => 0];
            
            $keys = $this->sitelock_get_2fa_rate_keys();
            foreach ($keys as $key) {
                 set_transient($key, $data, max(self::MIN_2FA_SESSION_TIMEOUT, self::MAX_ATTEMPTS_TIMEOUT) * MINUTE_IN_SECONDS);
            }
        }
    }

    private function handle_post_request($user_id)
    {
        $nonce = isset($_POST['sitelock_2fa_nonce']) ? sanitize_text_field(wp_unslash($_POST['sitelock_2fa_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'sitelock_2fa_verify')) {
            wp_die('Invalid request. Please try again.');
        }

        // Extract user codes
        $totp_code     = isset($_POST['totp_code']) ? sanitize_text_field(wp_unslash($_POST['totp_code'])) : '';
        $recovery_code = isset($_POST['recovery_code']) ? str_replace(' ', '', sanitize_text_field(wp_unslash($_POST['recovery_code']))) : '';

        // Load secrets
        $secret        = get_user_meta($user_id, 'sitelock_2fa_secret', true);
        $stored_secret = $secret ? Sitelock_Crypto::decrypt($secret) : '';

        $recovery_meta        = get_user_meta($user_id, 'sitelock_2fa_backup_codes', true);
        $saved_recovery_codes = is_array($recovery_meta) ? array_map(fn ($c) => Sitelock_Crypto::decrypt($c), $recovery_meta) : [];

        if ($totp_code && $stored_secret) {
            $this->process_totp_code($user_id, $stored_secret, $totp_code);
        } elseif ($recovery_code) {
            $this->process_recovery_code($user_id, $recovery_code, $saved_recovery_codes);
        }
    }

    private function process_totp_code($user_id, $secret, $totp_code)
    {
        try {
            $totp = TOTP::create($secret);
            if ($totp->verify($totp_code)) {
                $this->sitelock_complete_login_after_2fa($user_id);
                $this->terminate_request();
            }
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(
                    sprintf(
                        'Sitelock_2FA TOTP verification error for user %d: %s in %s:%d',
                        (int) $user_id,
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine()
                    )
                );
            }
        }

        $session_token = $this->get_pending_session_token();
        if(!empty($session_token)) {
            $hash          = hash('sha256', $session_token);
            set_transient("sitelock_2fa_authenticate_error_$hash", 'The code you entered is <strong>not valid</strong>. Please try again.', MINUTE_IN_SECONDS);
        }
        $this->increment_fail_and_reload();
    }

    private function process_recovery_code($user_id, $code, $saved)
    {
        if (in_array($code, $saved, true)) {
            $updated = array_values(array_diff($saved, [$code]));
            
            $encrypted_updated = [];
            foreach ($updated as $c) {
                $encrypted = Sitelock_Crypto::encrypt($c);
                if ($encrypted === false) {
                     // If encryption fails, do not update the meta with broken values.
                     // Instead, surface an error and reload the 2FA page for another attempt.
                     $session_token = $this->get_pending_session_token();
                     if(!empty($session_token)) {
                         $hash          = hash('sha256', $session_token);
                         set_transient(
                            "sitelock_2fa_recovery_error_$hash", 
                            'We could not process your recovery code due to a temporary error. Please try again.', 
                            MINUTE_IN_SECONDS
                        );
                     }
                     $this->increment_fail_and_reload();
                     return;
                }
                $encrypted_updated[] = $encrypted;
            }

            update_user_meta($user_id, 'sitelock_2fa_backup_codes', $encrypted_updated);
            $this->sitelock_complete_login_after_2fa($user_id);
            $this->terminate_request();
        }

        $session_token = $this->get_pending_session_token();
        if(!empty($session_token)) {
            $hash          = hash('sha256', $session_token);
            set_transient("sitelock_2fa_recovery_error_$hash", 'The code you entered is <strong>not valid</strong>. Please try again.', MINUTE_IN_SECONDS);
        }
        $this->increment_fail_and_reload();
    }

    /**
     * Increment failed 2FA attempts for the current user session and reload the 2FA page.
     */
    private function increment_fail_and_reload()
    {
        $data = $this->sitelock_get_2fa_rate();

        // Initialize to default array if no rate-limit transient exists yet
        if (!is_array($data)) {
            $data = ['failed' => 0, 'lockout' => 0];
        }

        // Increment failure count
        if(isset($data['failed'])) {
            $data['failed'] = (int)$data['failed'] + 1;
        } else  {
            $data['failed'] = 1;
        }

        if ((int)$data['failed'] >= self::MAX_2FA_ATTEMPTS) {
            $data['lockout'] = time() + (self::MAX_ATTEMPTS_TIMEOUT * MINUTE_IN_SECONDS);
        }

        // Save the updated transient for min 10 (MIN_2FA_SESSION_TIMEOUT) minutes or the max attempts timeout whichever is greater
        $keys = $this->sitelock_get_2fa_rate_keys();
        foreach ($keys as $key) {
            set_transient($key, $data, max(self::MIN_2FA_SESSION_TIMEOUT, self::MAX_ATTEMPTS_TIMEOUT) * MINUTE_IN_SECONDS);
        }

        // Reload the 2FA page cleanly
        wp_safe_redirect(sitelock_build_url_with_query_params(add_query_arg('action', 'sitelock-2fa', wp_login_url())));
        $this->terminate_request();
    }

    private function load_errors_from_transients($user_id)
    {
        $errors = ['totp' => '', 'recovery' => ''];

        $session_token = $this->get_pending_session_token();
        if (empty($session_token)) {
            return $errors;
        }

        $hash = hash('sha256', $session_token);
        $keys = [
            'totp'     => "sitelock_2fa_authenticate_error_$hash",
            'recovery' => "sitelock_2fa_recovery_error_$hash",
        ];
        foreach ($keys as $type => $key) {
            if ($msg = get_transient($key)) {
                $errors[$type] = wp_kses_post($msg);
                delete_transient($key);
            }
        }

        return $errors;
    }

    private function render_template($data)
    {
        include plugin_dir_path(__DIR__) . 'pages/2fa-verify-template.php';
        $this->terminate_request();
    }

    /**
     * Complete login after successful 2FA verification.
     *
     * @param int $user_id Verified user ID
     */
    public function sitelock_complete_login_after_2fa($user_id)
    {
        if (! $user_id || ! ($user = get_user_by('id', $user_id))) {
            wp_safe_redirect(sitelock_build_url_with_query_params(wp_login_url()));
            exit;
        }

        // Delete the transient for rate limiting after successful 2FA
        $keys = $this->sitelock_get_2fa_rate_keys();
        foreach ($keys as $key) {
            delete_transient($key);
        }

        // Clear the pending user cookie now that 2FA is complete
        sitelock_clear_pending_user_cookie();

        // Set authenticated user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Using core WordPress filter.
        do_action('wp_login', $user->user_login, $user);

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified elsewhere in the code.
        $is_interim = (!empty($_REQUEST['interim-login'])) && $_REQUEST['interim-login'] == 1;

        // Check for redirect_to in request
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Processing form data without nonce verification.
        $redirect_source = ! empty( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : admin_url();
        $redirect_to     = sitelock_build_url_with_query_params( $redirect_source, ['redirect_to'] );

        $redirect_to = wp_validate_redirect($redirect_to, admin_url());
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Using core WordPress filter.
        $redirect_to = apply_filters('login_redirect', $redirect_to, '', $user);
        $redirect_to = apply_filters('sitelock_2fa_login_redirect', $redirect_to, $user);

        if (empty($redirect_to)) {
            $redirect_to = admin_url();
        }

        if ($is_interim) {
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <meta name="robots" content="noindex, nofollow">
            </head>
            <body>
                <script type="text/javascript">
                    (function() {
                        console.log('Sitelock 2FA: Interim login successful. Closing modal...');

                        function closeInterimLogin() {
                            try {
                                var parentWin = window.parent;
                                // Simple check: Are we in a frame?
                                if (parentWin === window) {
                                    window.location.href = "<?php echo esc_url($redirect_to); ?>";
                                    return;
                                }

                                var parentDoc = parentWin.document;

                                // A. Unlock Scrollbar (Unconditional)
                                if (parentDoc.body) {
                                    parentDoc.body.classList.remove('wp-auth-check-active');
                                    parentDoc.body.style.overflow = 'auto';
                                }
                                if (parentDoc.documentElement) {
                                    parentDoc.documentElement.classList.remove('wp-auth-check-active');
                                }
                                
                                // B. Hide the Modal Wrapper
                                var wrap = parentDoc.getElementById('wp-auth-check-wrap');
                                if (wrap) {
                                    wrap.style.display = 'none';
                                    wrap.classList.add('hidden');
                                }

                                try {
                                    // Supress "Unsaved Changes" / beforeunload (User Requested Fix)
                                    if (parentWin.jQuery) {
                                        parentWin.jQuery(parentWin).off('beforeunload');
                                    } else {
                                        parentWin.onbeforeunload = null;
                                    }

                                    // Try Native WP Close
                                    if (parentWin.wp && parentWin.wp.authCheck && parentWin.wp.authCheck.close) {
                                        parentWin.wp.authCheck.close();
                                    } 
                                    
                                    // Trigger jQuery Event
                                    if (parentWin.jQuery) {
                                        parentWin.jQuery(parentDoc).trigger('wp-auth-check-close');
                                    } else {
                                        parentDoc.dispatchEvent(new Event('wp-auth-check-close'));
                                    }
                                } catch (innerEx) {
                                    // Silect catch for optional state sync
                                }
                                
                            } catch (e) {
                                // Silent catch
                            }
                        }
                        
                        // Execute immediately
                        closeInterimLogin();
                        
                        // And verify in 500ms in case race conditions (e.g. animation)
                        setTimeout(closeInterimLogin, 500);
                    })();
                </script>

            </body>
            </html>

            <?php
            $this->terminate_request();
        }

        wp_safe_redirect($redirect_to);
        $this->terminate_request();
    }

    protected function terminate_request()
    {
        exit;
    }

    private function get_logo_url()
    {
        $custom_logo_id = function_exists('get_theme_mod') ? get_theme_mod('custom_logo') : 0;
        if ($custom_logo_id) {
            $img = wp_get_attachment_image_src($custom_logo_id, 'full');
            if (!empty($img[0])) {
                return esc_url($img[0]);
            }
        }

        $site_icon_url = get_site_icon_url();
        if (!empty($site_icon_url)) {
            return esc_url($site_icon_url);
        }

        // Fallback to a default logo URL or site name
        return esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo.svg');
    }

}

<?php

class AuthManager
{
    /**
     * API Helper
     *      object    $apiHelper    Instance of the API Helper class
     */
    public $apiHelper;

    /**
     * The version of this plugin.
     *
     * @since    3.1.2
     * @access   public
     * @var string $version    The current version of this plugin.
     */
    public $version;

    /**
     * Auth Key Tag
     *
     * @since    1.9.0
     * @access   public
     * @var string $wpslp_tag    Tag used for storing and retrieving data from db
     */
    public $wpslp_tag;

    /**
     * Auth Key and SAML Key
     *
     * @since    1.9.0
     * @access   public
     * @var array $wpslp_options    Stores values of auth key
     */
    public $wpslp_options;

    /**
     * Manually refresh all API calls (boolean)
     *
     * @since  2.0.0
     * @access public
     */
    public $refresh_api;

    /**
     * String to time value for setting the token expiration date
     *
     * @since   2.0.0
     * @access  public
     */
    public $when_to_expire_token;

    public const OPTION_KEY     = 'sitelock_verification_code';
    public const SESSION_ID_KEY = 'sitelock_session_id';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.9.0
     * @param string $version The version of this plugin.
     */
    public function __construct($version, $apiHelper)
    {
        // Set plugin version
        $this->version = $version;

        $this->apiHelper = $apiHelper;

        // Set option tag
        $this->wpslp_tag = 'wpslp_options';

        // Set default plugin options
        $this->wpslp_options = [
            'auth_key'  => false,
            'saml_key'  => false,
            'validated' => false,
        ];

        // Set token expiry window
        $this->when_to_expire_token = '+59 minutes';

        // attempt to set auth key
        $this->set_auth_key();
    }

    /**
     * Get Open Configuration
     *
     * @since   5.0.0
     */
    public function get_open_config()
    {
        $url = sitelock_api_url('secure') . '/_services/.well-known/openid-configuration';

        return $this->apiHelper->call_api($url);
    }

    /**
     * Sets auth key when received as request
     *
     * @since    5.0.0
     */
    public function refresh_token()
    {
        $license_key = get_option('sitelock_license_key', '');
        // Check for successful response
        if (!empty($license_key)) {
            $access_token_response = $this->exchange_LK_for_JWT($license_key);
            // Handle errors
            if (empty($access_token_response['access_token'])) {
                return false;
            } else {
                $jwt = $access_token_response['access_token'];
                // Update the token
                $this->save_new_token($jwt);
                // Get site ID from JWT audience claim
                $site_id = get_JWT_claim($jwt, 'sub');
                update_option('sitelock_site_id', $site_id);

                return true;
            }
        }

        return false;
    }

    /**
     *  Sets auth key when received as request
     *
     * @param $external_key
     * @return void
     *
     * @since    1.9.0
     */
    public function handle_auth($external_key = null)
    {
        // Verify nonce
        if (!$external_key && (!isset($_POST['sitelock_license_key_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sitelock_license_key_nonce'])), 'sitelock_license_key_action'))) {
            set_transient('sitelock_auth_error', 'Nonce verification failed.', 60); // Store error for 60 seconds
            header('Location: ' . admin_url() . 'admin.php?page=sitelock-settings&tab=connection-to-sitelock');
            exit;
        }

        if ($external_key) {
            $license_key = $external_key;
        } else {
            $license_key = !empty($_POST['sitelock_license_key']) ? sanitize_text_field(wp_unslash($_POST['sitelock_license_key'])) : '';
        }

        if (!empty($license_key)) {
            $access_token_response = $this->exchange_LK_for_JWT($license_key);
            // Handle errors
            if (empty($access_token_response['access_token'])) {
                if (isset($access_token_response['error_description']) && $access_token_response['error_description']) {
                    // Detailed error from API (may contain sensitive info)
                    $error_detail  = $access_token_response['error_description'];
                    $error_message = 'Authentication failed. Please try again or contact support.';
                } else {
                    $error_detail  = 'No error description returned by API.';
                    $error_message = 'An unknown error occurred. Please try again or contact support.';
                }
                // Log the detailed error for debugging (class context included)
                sitelock_log(
                    'error',
                    'Access Token Fetch Failed',
                    'API did not return access_token during authentication.',
                    ['error_detail' => $error_detail, 'response' => $access_token_response],
                    __CLASS__
                );

                update_option('sitelock_site_id', '');
                update_option('sitelock_license_key', '');
                set_transient('sitelock_auth_error', $error_message, 60); // Store error for 60 seconds
            } else {
                $jwt = $access_token_response['access_token'];
                // Update the token
                $this->save_new_token($jwt);
                // Get site ID from JWT audience claim
                $site_id = get_JWT_claim($jwt, 'sub');
                update_option('sitelock_site_id', $site_id);
                update_option('sitelock_license_key', $license_key);
                set_transient('sitelock_auth_success', 'Successfully activated your SiteLock account.', 60); // Store error for 60 seconds
            }
        } else {
            set_transient('sitelock_auth_error', 'License key is missing or invalid.', 60); // Store error for 60 seconds
        }
        // Redirect to the settings page
        header('Location: ' . admin_url() . 'admin.php?page=sitelock-settings&tab=connection-to-sitelock');
        exit;
    }

    /**
     * LAPI LK -> JWT exchange endpoint
     *
     * @param $license_key
     * @return mixed
     *
     * @since   5.0.0
     */
    private function exchange_LK_for_JWT($license_key)
    {
        $token_exchange_endpoint = sitelock_api_url() . '/_services/oauth/token';
        $code_verifier           = $this->get_verification_code(true);
        $state_session_id        = $this->get_session_id(true);
        $site_root               = get_site_hostname(true);

        $payload = [
            'grant_type'    => 'client_credentials',
            'client_id'     => $license_key,
            'scope'         => 'lapi',
            'code_verifier' => $code_verifier,
            'state'         => $state_session_id,
            'redirect_uri'  => "{$site_root}/?rest_route=/sitelock/v1/verify",
        ];

        $dpop_flag = sitelock_get_test_var('dpop');
        if (!empty($dpop_flag) && $dpop_flag === 'disabled') {
            $payload['dpop'] = 'disabled';
        }

        return $this->apiHelper->call_api($token_exchange_endpoint, 'POST', $payload);
    }

    /**
     * Accepts GET param 'license_key' for external (email) LK activation; re-uses handle_auth()
     *
     * @since   5.0.0
     */
    public function activate_email_key($nonce)
    {
        if (!isset($nonce) || !wp_verify_nonce(sanitize_text_field(wp_unslash($nonce)), 'activate_email_key_action')) {
            wp_die(esc_html__('Nonce verification failed. Please try again.1', 'sitelock-wordpress-plugin'));
        }
        $license_key = isset($_GET['license_key']) ? sanitize_text_field(wp_unslash($_GET['license_key'])) : '';
        $this->handle_auth($license_key);
    }

    /**
     * Attempts to retrieve auth key
     *
     * @since    1.9.0
     */
    public function set_auth_key()
    {
        if (get_option($this->wpslp_tag)) {
            $this->wpslp_options = get_option($this->wpslp_tag);
        } else {
            update_option($this->wpslp_tag, $this->wpslp_options);
        }
    }

    /**
     * Gets auth key from wpslp_options
     *
     * @since    1.9.0
     */
    public function get_auth_key()
    {
        $current_timestamp = time(); // Get the current timestamp
        if (isset($this->wpslp_options[ 'auth_key' ]) && $this->wpslp_options[ 'validated' ] && $this->wpslp_options[ 'validated' ] < $current_timestamp) {
            $this->refresh_token();
        }

        return isset($this->wpslp_options[ 'auth_key' ]) && !empty($this->wpslp_options[ 'auth_key' ]) ? $this->wpslp_options[ 'auth_key' ] : false;
    }

    /**
     * Remove refresh signal for the API
     *
     * @since      2.0.0
     */
    public function clear_refresh_api()
    {
        update_option('sitelock_refresh_api', '');
    }

    /**
    * Save new token
    *
    * @since   3.1.2
    * @param   object  $object     New token object
    */
    public function save_new_token($token = '')
    {
        if ($token != '') {
            // prepare auth key array
            //$this->wpslp_options[ 'auth_key'  ] = preg_replace( "/[^ \w]+/", "", $token );
            $this->wpslp_options[ 'auth_key'  ] = preg_replace("/[^\.\-\w]+/", '', $token);
            $this->wpslp_options[ 'validated' ] = strtotime($this->when_to_expire_token);
            // save auth key
            update_option($this->wpslp_tag, $this->wpslp_options);
        }

        return true;
    }

    /**
     * Get Secret
     *
     * @since   2.1.1
     */
    public function get_secret()
    {
        $secret = get_option('sitelock_secret');

        if (empty($secret)) {
            $secret = base64_encode(substr(hash('SHA256', microtime()), 4, 56));
        }

        update_option('sitelock_secret', $secret);

        return $secret;
    }

    /**
     * Retrieve session ID transient (regenerate if expired).
     *
     * @param bool $reset Optional. If true, forces regeneration of session ID.
     *
     * @return string
     */
    public function get_session_id($reset = false)
    {
        if ($reset) {
            delete_transient(self::SESSION_ID_KEY);
        }

        if ($reset || !($session_code = get_transient(self::SESSION_ID_KEY))) {
            $session_code = bin2hex(random_bytes(32));
            set_transient(self::SESSION_ID_KEY, $session_code, 5 * MINUTE_IN_SECONDS);
        }

        return $session_code;
    }

    /**
     * Retrieve verification code transient (regenerate if expired).
     *
     * @param bool $reset Optional. If true, forces regeneration of verification code.
     *
     * @return string
     */
    public function get_verification_code($reset = false)
    {
        if ($reset) {
            delete_transient(self::OPTION_KEY);
        }

        if ($reset || !($verification_code = get_transient(self::OPTION_KEY))) {
            $verification_code = bin2hex(random_bytes(32));
            set_transient(self::OPTION_KEY, $verification_code, 5 * MINUTE_IN_SECONDS);
        }

        return $verification_code;
    }
}

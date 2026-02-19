<?php
require_once SITELOCK_PLUGIN_DIR . 'includes/api/Helpers/class-api-helper.php';
require_once SITELOCK_PLUGIN_DIR . 'includes/api/class-auth-manager.php';

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
JWT::$leeway = 60;

class SiteLock_Verification_Service
{
    private $apiHelper;
    public $auth;

    public const OPTION_KEY     = 'sitelock_verification_code';
    public const SESSION_ID_KEY = 'sitelock_session_id';
    public const VERIFY_REST_NS = 'sitelock/v1';
    public const VERIFY_ROUTE   = '/verify';
    public const FILE_FALLBACK  = '/.well-known/sitelock-verify.txt';

    // SiteLock allowed IPs (adjust as needed)
    public $allowed_ips = [];

    // Additional IPs from providers if needed
    public $provider_ips = [];

    public function __construct($version) {
        add_action('rest_api_init', [$this, 'register_rest_endpoint']);

        $this->apiHelper            = new ApiHelper($version);
        $this->auth                 = new AuthManager($version, $this->apiHelper);

        $allowed_ips_override = sitelock_get_test_var('rest_allowed_ips');

        if (!empty($allowed_ips_override) && is_array($allowed_ips_override)) {
            $this->allowed_ips = $allowed_ips_override;
        } else {
            // Load SiteLock allowed IPs
            $this->allowed_ips = Sitelock_IP_Utility::load_ip_json(
                SITELOCK_PLUGIN_DIR . 'ip-data/rest-allowed-ips.json',
                __CLASS__
            );

            // Load provider IPs
            $this->provider_ips = Sitelock_IP_Utility::load_ip_json(
                SITELOCK_PLUGIN_DIR . 'ip-data/provider-ips.json',
                __CLASS__
            );
        }
    }


    /**
     * Register REST endpoint.
     */
    public function register_rest_endpoint()
    {
        register_rest_route(self::VERIFY_REST_NS, self::VERIFY_ROUTE, [
            'methods'             => ['GET','POST'],
            'callback'            => [$this, 'serve_code_rest'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Serve code via REST endpoint.
     */
    public function serve_code_rest($request)
    {
        if (!$this->is_https()) {
            return new WP_REST_Response(['error' => 'HTTPS required'], 403);
        }

        if (!$this->is_allowed_ip()) {
            return new WP_REST_Response(['error' => 'Forbidden'], 403);
        }

        $response = $this->validate_verification_request($request);
        if ($response['status'] === 200) {
            return new WP_REST_Response($response['data'], 200);
        } else {
            return new WP_REST_Response(['error' => $response['message']], 400);
        }
    }

    /**
     * Validate the verification request.
     *
     * @param  WP_REST_Request $request The REST API request object.
     * @return array           Response data with status and message or data.
     */
    private function validate_verification_request($request)
    {

        $dpop_jwt      = trim( $request->get_body() );

        if (empty($dpop_jwt)) {
            return [
                'status'  => 400,
                'message' => 'Missing required parameters: token.',
            ];
        }
        
        $open_config_cache_key = 'open_config';
        $open_config_cache_group = 'global';
        $open_config = wp_cache_get($open_config_cache_key, $open_config_cache_group);

        if ( empty( $open_config ) ) {
            $open_config = $this->auth->get_open_config();
            wp_cache_set( $open_config_cache_key, $open_config, $open_config_cache_group, 3600 );
        }
        // Validate open configuration and required jwks_uri.
        if ( ! is_array( $open_config ) || empty( $open_config['jwks_uri'] ) ) {
            return [
                'status'  => 500,
                'message' => 'Invalid OpenID configuration received. Please try again later.',
            ];
        }
        
        $jwt_cache_key   = 'open_config_jwks_uri';
        $jwt_cache_group = 'global';
        $jwks            = wp_cache_get( $jwt_cache_key, $jwt_cache_group );
        if (empty($jwks)) {
            $jwks = $this->auth->get_jwt_key($open_config['jwks_uri']);
            wp_cache_set($jwt_cache_key, $jwks, $jwt_cache_group, 3600);
        }
        // Ensure JWKS is an array; if it's JSON, attempt to decode.
        if (!is_array($jwks) && is_string($jwks)) {
            $decoded_jwks = json_decode($jwks, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $jwks = $decoded_jwks;
            }
        }
        // Validate JWKS structure before attempting to parse keys.
        if (empty($jwks) || !is_array($jwks)) {
            return [
                'status'  => 400,
                'message' => 'Invalid key set data.',
            ];
        }
        try {
            $jwt_keys = JWK::parseKeySet($jwks);
            if (empty($jwt_keys) || !is_array($jwt_keys)) {
                return [
                    'status'  => 400,
                    'message' => 'No valid keys available to verify token.',
                ];
            }
            $userData = JWT::decode($dpop_jwt, $jwt_keys);
        } catch (\Throwable $e) {
            return [
                'status'  => 400,
                'message' => 'Invalid or unverifiable token.',
            ];
        }

        // Validate required parameters
        if (empty($userData->state) || empty($userData->code_challenge)) {
            return [
                'status'  => 400,
                'message' => 'Missing required parameters: state or code_challenge.',
            ];
        }

        // Retrieve request parameters
        $state          = $userData->state;
        $code_challenge = $userData->code_challenge;

        // Retrieve stored values
        $stored_code       = get_transient(self::OPTION_KEY);
        $stored_session_id = get_transient(self::SESSION_ID_KEY);

       

        if (empty($stored_code) || empty($stored_session_id)) {
            return [
                'status'  => 400,
                'message' => 'Verification data or session not found. Please retry the verification process.',
            ];
        }

        // Hash the stored code using Base64 SHA256
        $hashed_code = sitelock_base64url_encode(hash('sha256', $stored_code, true));

        // Compare the hashed code with the provided code_challenge
        if ($hashed_code !== $code_challenge) {
            return [
                'status'  => 400,
                'message' => 'Invalid code_challenge.',
            ];
        }

        // Compare the state with the stored session ID
        if ($state !== $stored_session_id) {
            return [
                'status'  => 400,
                'message' => 'Invalid state parameter.',
            ];
        }

        // If everything matches, return success
        return [
            'status' => 200,
            'data'   => [
                'state'         => $state,
                'code_verifier' => $stored_code,
            ],
        ];
    }

    /**
     * Check if request is HTTPS.
     */
    private function is_https()
    {
        return is_ssl() || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    }

    /**
     * Check if request comes from allowed IP.
     */
    private function is_allowed_ip()
    {
        $remote_ip = '';
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $remote_ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }

        // 1. Check direct SiteLock allowed IPs (exact match)
        foreach ($this->allowed_ips as $allowed) {
            if (Sitelock_IP_Utility::ip_in_cidr($remote_ip, $allowed)) {
                return true;
            }
        }

        // 2. Check if coming from known proxy (Cloudflare, AWS, etc.)
        foreach ($this->provider_ips as $proxy_ip) {
            if (Sitelock_IP_Utility::ip_in_cidr($remote_ip, $proxy_ip)) {
                // Valid proxy detected — now get original client IP from X-Forwarded-For
                $left_most_ip = Sitelock_IP_Utility::get_leftmost_forwarded_ip();
                if ($this->is_ip_in_allowed_list($left_most_ip)) {
                    return true;
                }
            }
        }

        // None matched → block request
        return false;
    }

    /**
     * Check if an IP exists in allowed list (supports CIDR + exact)
     *
     * @param  string|null $ip The IP address to check.
     * @return bool        True if the IP is allowed, false otherwise.
     */
    private function is_ip_in_allowed_list($ip)
    {
        if (!$ip) {
            return false;
        }

        foreach ($this->allowed_ips as $allowed) {
            if (Sitelock_IP_Utility::ip_in_cidr($ip, $allowed)) {
                return true;
            }
        }

        return false;
    }

}

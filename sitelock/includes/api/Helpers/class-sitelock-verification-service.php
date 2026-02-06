<?php

class SiteLock_Verification_Service
{
    public const OPTION_KEY     = 'sitelock_verification_code';
    public const SESSION_ID_KEY = 'sitelock_session_id';
    public const VERIFY_REST_NS = 'sitelock/v1';
    public const VERIFY_ROUTE   = '/verify';
    public const FILE_FALLBACK  = '/.well-known/sitelock-verify.txt';

    // SiteLock allowed IPs (adjust as needed)
    public $allowed_ips = [];

    // Additional IPs from providers if needed
    public $provider_ips = [];

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_rest_endpoint']);

        $allowed_ips_override = sitelock_get_test_var('rest_allowed_ips');

        if (!empty($allowed_ips_override) && is_array($allowed_ips_override)) {
            $this->allowed_ips = $allowed_ips_override;
        } else {
            // Load SiteLock allowed IPs
            $this->allowed_ips = $this->load_ip_json(
                SITELOCK_PLUGIN_DIR . 'ip-data/rest-allowed-ips.json'
            );

            // Load provider IPs
            $this->provider_ips = $this->load_ip_json(
                SITELOCK_PLUGIN_DIR . 'ip-data/provider-ips.json'
            );
        }
    }


    /**
     * Load IP list from a JSON file and return as an array.
     *
     * @param string $file_path Full file path.
     * @return array            List of IPs or empty array.
     */
    private function load_ip_json($file_path) {
        if (!file_exists($file_path)) {
            return [];
        }

        $raw  = file_get_contents($file_path);
        if ($raw === false) {
            return [];
        }
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            sitelock_log(
                'error',
                'Invalid JSON',
                'Invalid JSON in ' . basename($file_path) . ': ' . json_last_error_msg(),
                ['file' => $file_path, 'raw_length' => strlen($raw)],
                __CLASS__
            );
            return [];
        }

        if (!is_array($data)) {
            sitelock_log(
                'error',
                'Unexpected Data Type',
                basename($file_path) . ' did not decode to an array.',
                ['file' => $file_path, 'decoded_type' => gettype($data)],
                __CLASS__
            );
            return [];
        }

        return $data;
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
        // Retrieve request parameters
        $state          = $request->get_param('state');
        $code_challenge = $request->get_param('code_challenge');

        // Retrieve stored values
        $stored_code       = get_transient(self::OPTION_KEY);
        $stored_session_id = get_transient(self::SESSION_ID_KEY);

        // Validate required parameters
        if (empty($state) || empty($code_challenge)) {
            return [
                'status'  => 400,
                'message' => 'Missing required parameters: state or code_challenge.',
            ];
        }

        if (empty($stored_code) || empty($stored_session_id)) {
            return [
                'status'  => 400,
                'message' => 'Verification data or session not found. Please retry the verification process.',
            ];
        }

        // Hash the stored code using Base64 SHA256
        $hashed_code = base64url_encode(hash('sha256', $stored_code, true));

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
            if (strpos($allowed, '/') !== false) {
                // CIDR format
                if ($this->ip_in_cidr($remote_ip, $allowed)) {
                    return true;
                }
            } elseif ($remote_ip === $allowed) {
                return true;
            }
        }
    
        // 2. Check if coming from known proxy (Cloudflare, AWS, etc.)
        foreach ($this->provider_ips as $proxy_ip) {
            if (strpos($proxy_ip, '/') !== false) {
                if ($this->ip_in_cidr($remote_ip, $proxy_ip)) {
                    // Valid proxy detected — now get original client IP from X-Forwarded-For
                    $left_most_ip = $this->get_leftmost_forwarded_ip();
                    if ($this->is_ip_in_allowed_list($left_most_ip)) {
                        return true;
                    }
                }
            } elseif ($remote_ip === $proxy_ip) {
                // Exact proxy IP — same logic: check forwarded IP
                $left_most_ip = $this->get_leftmost_forwarded_ip();
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
     * @param string|null $ip The IP address to check.
     * @return bool True if the IP is allowed, false otherwise.
     */
    private function is_ip_in_allowed_list($ip) {
        if (!$ip) {
            return false;
        }
    
        foreach ($this->allowed_ips as $allowed) {
            if (strpos($allowed, '/') !== false) {
                if ($this->ip_in_cidr($ip, $allowed)) {
                    return true;
                }
            } elseif ($ip === $allowed) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Extract left-most IP from X-Forwarded-For
     * @return string|null The left-most IP address, or null if not present.
     */
    private function get_leftmost_forwarded_ip() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded_for = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            $forwarded_ips = explode(',', $forwarded_for);
            return trim($forwarded_ips[0]);
        }
        return null;
    }
    

    /**
     * Check if an IP is within a CIDR range.
     *
     * @param  string $ip   The IP address to check.
     * @param  string $cidr The CIDR range (e.g., 203.0.113.0/24).
     * @return bool   True if the IP is within the range, false otherwise.
     */
    private function ip_in_cidr($ip, $cidr)
    {
        list($subnet, $mask) = explode('/', $cidr);
        $subnet_long         = ip2long($subnet);
        $ip_long             = ip2long($ip);
        if ($mask < 0 || $mask > 32) {
            return false; // Invalid mask, return false
        }
        $mask_long = ~((1 << (32 - $mask)) - 1);

        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }
}

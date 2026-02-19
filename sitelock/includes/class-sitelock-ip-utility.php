<?php
/**
 * SiteLock IP Utility Class
 *
 * @package Sitelock
 * @subpackage Sitelock/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sitelock_IP_Utility
{
    /**
     * Resolve the real client IP address.
     *
     * @param array $provider_ips List of trusted proxy IPs.
     * @return string Client IP address.
     */
    public static function get_client_ip($provider_ips = [])
    {
        $remote_ip = '';

        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $remote_ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }

        if (empty($remote_ip) || !filter_var($remote_ip, FILTER_VALIDATE_IP)) {
            return '';
        }

        /**
         * Check if REMOTE_ADDR belongs to a trusted proxy
         */
        foreach ($provider_ips as $proxy_ip) {
            if (strpos($proxy_ip, '/') !== false) {
                // CIDR proxy match
                if (self::ip_in_cidr($remote_ip, $proxy_ip)) {
                    return self::get_forwarded_or_fallback($remote_ip);
                }
            } elseif ($remote_ip === $proxy_ip) {
                // Exact proxy IP match
                return self::get_forwarded_or_fallback($remote_ip);
            }
        }

        /**
         * Not a trusted proxy → return direct IP
         */
        return $remote_ip;
    }

    /**
     * Get forwarded client IP if present & valid, otherwise fallback.
     *
     * @param string $fallback_ip
     * @return string
     */
    public static function get_forwarded_or_fallback($fallback_ip)
    {
        $forwarded_ip = self::get_leftmost_forwarded_ip();

        if ($forwarded_ip && filter_var($forwarded_ip, FILTER_VALIDATE_IP)) {
            return $forwarded_ip;
        }

        return $fallback_ip;
    }

    /**
     * Extract left-most client IP from X-Forwarded-For.
     *
     * @return string|null
     */
    public static function get_leftmost_forwarded_ip()
    {
        if (empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return null;
        }

        $forwarded_for = sanitize_text_field(
            wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])
        );

        // X-Forwarded-For = client, proxy1, proxy2
        $ips = explode(',', $forwarded_for);

        if (empty($ips[0])) {
            return null;
        }

        $client_ip = trim($ips[0]);

        return filter_var($client_ip, FILTER_VALIDATE_IP)
            ? $client_ip
            : null;
    }

    /**
     * Check if an IP is within a CIDR range.
     *
     * @param string $ip   The IP address to check.
     * @param string $cidr The CIDR range (e.g., 203.0.113.0/24).
     * @return bool   True if the IP is within the range, false otherwise.
     */
    public static function ip_in_cidr($ip, $cidr)
    {
        if (strpos($cidr, '/') === false) {
            return $ip === $cidr;
        }

        list($subnet, $mask) = explode('/', $cidr, 2);
        $subnet_long         = ip2long($subnet);
        $ip_long             = ip2long($ip);

        if ($subnet_long === false || $ip_long === false) {
            return false;
        }

        if (!is_numeric($mask)) {
            return false;
        }

        $mask = (int) $mask;
        if ($mask < 0 || $mask > 32) {
            return false;
        }

        $mask_long = ~((1 << (32 - $mask)) - 1);

        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }

    /**
     * Load IP list from a JSON file and return as an array.
     *
     * @param  string $file_path Full file path.
     * @param  string $context   Context for logging (usually class name).
     * @return array  List of IPs or empty array.
     */
    public static function load_ip_json($file_path, $context = '')
    {
        if (!file_exists($file_path)) {
            return [];
        }

        $raw = file_get_contents($file_path);
        if ($raw === false) {
            return [];
        }
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if (function_exists('sitelock_log')) {
                sitelock_log(
                    'error',
                    'Invalid JSON',
                    'Invalid JSON in ' . basename($file_path) . ': ' . json_last_error_msg(),
                    ['file' => $file_path, 'raw_length' => strlen($raw)],
                    $context
                );
            }

            return [];
        }

        if (!is_array($data)) {
            if (function_exists('sitelock_log')) {
                sitelock_log(
                    'error',
                    'Unexpected Data Type',
                    basename($file_path) . ' did not decode to an array.',
                    ['file' => $file_path, 'decoded_type' => gettype($data)],
                    $context
                );
            }

            return [];
        }

        return $data;
    }
}

<?php
/**
 * Secure cookie helper for encrypted plugin state.
 */
class Sitelock_Secure_Cookie
{
    /**
     * Set an encrypted cookie.
     *
     * @param  string       $name
     * @param  array|string $data
     * @param  int          $ttl  Seconds until expiration
     * @return bool True if cookie was successfully encrypted and set, false otherwise.
     */
    public static function set($name, $data, $ttl = 300)
    {
        $payload   = is_array($data) ? wp_json_encode($data) : (string) $data;
        $encrypted = Sitelock_Crypto::encrypt($payload);

        if ($encrypted === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log("SiteLock: Failed to encrypt cookie '{$name}'.");
            }

            return false;
        }

        return setcookie(
            $name,
            $encrypted,
            [
                'expires'  => ($ttl === 0) ? 0 : time() + $ttl,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict',
            ]
        );
    }

    /**
     * Retrieve and decrypt an encrypted cookie.
     *
     * @param  string     $name
     * @return mixed|null
     */
    public static function get($name)
    {
        if (empty($_COOKIE[$name])) {
            return null;
        }

        try {
            $decrypted = Sitelock_Crypto::decrypt(sanitize_text_field(wp_unslash($_COOKIE[$name])));
            $json      = json_decode($decrypted, true);

            return (json_last_error() === JSON_ERROR_NONE) ? $json : $decrypted;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Delete a secure cookie.
     *
     * @param string $name
     */
    public static function delete($name)
    {
        setcookie($name, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        unset($_COOKIE[$name]);
    }
}

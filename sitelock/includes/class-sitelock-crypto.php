<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper class for secure encryption and decryption across plugin.
 */
class Sitelock_Crypto
{
    /**
     * Encrypt a value securely with AES-256-CBC.
     *
     * @param  string $data Plaintext to encrypt
     * @return string Base64 encoded ciphertext with IV
     */
    public static function encrypt($data)
    {
        $key = hash('sha256', SECURE_AUTH_KEY, true); // derive 32-byte key
        $iv  = random_bytes(16); // random IV per encryption

        $ciphertext = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . $ciphertext);
    }

    /**
     * Decrypt a previously encrypted value.
     *
     * @param  string       $encrypted Base64 encoded string
     * @return string|false Decrypted string or false on failure
     */
    public static function decrypt($encrypted)
    {
        $key  = hash('sha256', SECURE_AUTH_KEY, true);
        $data = base64_decode($encrypted);

        if ($data === false || strlen($data) <= 16) {
            return false; // invalid payload
        }

        $iv         = substr($data, 0, 16);
        $ciphertext = substr($data, 16);

        return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    }
}

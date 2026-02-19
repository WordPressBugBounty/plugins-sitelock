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
     * Encrypt a value securely with AES-256-GCM.
     *
     * @param  string       $data Plaintext to encrypt
     * @return string|false Base64 encoded ciphertext with IV and tag, or false on failure
     */
    public static function encrypt($data)
    {
        $key = hash('sha256', SECURE_AUTH_KEY, true); // derive 32-byte key
        try {
            $iv  = random_bytes(12); // Standard IV length for GCM is 12 bytes
        } catch (\Exception $e) {
            return false;
        }
        $tag = '';

        $ciphertext = openssl_encrypt(
            $data,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            return false;
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt a previously encrypted value using AES-256-GCM.
     *
     * @param  string       $encrypted Base64 encoded string
     * @return string|false Decrypted string or false on failure
     */
    public static function decrypt($encrypted)
    {
        $key  = hash('sha256', SECURE_AUTH_KEY, true);
        $data = base64_decode($encrypted, true);

        if ($data === false || strlen($data) < 28) {
            return false; // invalid payload (minimum GCM: 12 IV + 16 Tag)
        }

        $iv         = substr($data, 0, 12);
        $tag        = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        return openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }
}

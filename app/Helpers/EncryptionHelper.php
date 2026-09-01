<?php

namespace App\Helpers;

use function config;

class EncryptionHelper
{
    private static $cipher;
    private static $secretKey;
    private static $ivLength;

    private static function initialize()
    {
        self::$cipher = config('app.cipher');
        self::$secretKey = config('app.app_encryption_key');
        self::$ivLength = 16;
    }

    /**
     * Encrypt a the string using a secret key.
     *
     * @param string $dataEntry
     * @return string
     */
    public static function encrypt($dataEntry)
    {
        self::initialize();

        // dd(self::$cipher, self::$secretKey, self::$ivLength);
        try {
            $key = hash('sha256', self::$secretKey, true); // Hash the secret key with SHA-256
            $iv = openssl_random_pseudo_bytes(self::$ivLength); // Generate a random IV Length
            $encryptedData = openssl_encrypt($dataEntry, self::$cipher, $key, 0, $iv);

            return base64_encode("$iv$encryptedData"); // Store IV + encrypted data

        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }
    /**
     * Decrypt an encrypted string using a secret key.
     *
     * @param string $encryptedData
     * @return string|null
     */
    public static function decrypt($encryptedData)
    {
        self::initialize();
        try {
            $key = hash('sha256', self::$secretKey, true); // Hash the key
            $decoded = base64_decode($encryptedData);
            // dd($decoded);
            $iv = substr($decoded, 0, self::$ivLength); // Extract IV
            $encrypted = substr($decoded, self::$ivLength); // Extract encrypted data
            return openssl_decrypt($encrypted, self::$cipher, $key, 0, $iv);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
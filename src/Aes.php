<?php

namespace PHPWebfuse;

use \PHPWebfuse\Utils;

/**
 * @author Senestro
 */
class Aes
{
    // PRIVATE VARIABLE
    // 
    // PUBLIC VARIABLES

    // PUBLIC METHODS
    
    /**
     * Prevent the constructor from being initialized
     */
    private function __construct() {
        
    }
    
    /**
     * Encrypt data to into raw binary string or base64 encoded string
     * @param string $content The content to encrypt
     * @param string $method The encryption cipher method
     * @param string $key The encryption key
     * @param bool $rawData True means to return the encrypted data as raw binary string or base64 encoded string
     * @return bool|string Returns a raw binary string or base64 encoded string on success, otherwise returns false
     */
    public static function encData(string $content, string $method = "aes-128-cbc", string $key = "", bool $rawData = false): bool|string
    {
        if (in_array($method, openssl_get_cipher_methods(), true) && !Utils::isEmptyString($content) && !Utils::isEmptyString($key)) {
            try {
                $content = trim($content);
                $key = trim($key);
                $ivLenght = openssl_cipher_iv_length($method);
                if (Utils::isInt($ivLenght)) {
                    $iv = openssl_random_pseudo_bytes($ivLenght);
                    $result = @openssl_encrypt($content, $method, $key, $options = OPENSSL_RAW_DATA, $iv);
                    if (Utils::isString($result)) {
                        $content = $key = "";
                        return $rawData ? $iv . $result : base64_encode($iv . $result);
                    }
                }
            } catch (\Throwable $e) {

            }
        }
        $content = $key = "";
        return false;
    }
    
    /**
     * Decrypt the encrypted the raw binary string or base64 encoded string to original data
     * @param string $content The encrypted content to decrypt
     * @param string $method The encryption cipher method
     * @param string $key The decryption key
     * @param bool $fromRaw True means the $data is a raw binary string, else a base64 encoded string
     * @return bool|string Returns the original data from an encrypted raw binary string or base64 encoded string, otherwise returns false
     */
    public static function decData(string $content, string $method = "aes-128-cbc", string $key = "", bool $fromRaw = false): bool|string
    {
        if (in_array($method, openssl_get_cipher_methods(), true) && Utils::isNotEmptyString($content) && Utils::isNotEmptyString($key)) {
            try {
                $content = trim($content);
                $key = trim($key);
                $content = $fromRaw ? $content : base64_decode($content);
                $ivLenght = openssl_cipher_iv_length($method);
                if (Utils::isInt($ivLenght)) {
                    $iv = substr($content, 0, $ivLenght);
                    $cipher = substr($content, $ivLenght);
                    $result = @openssl_decrypt($cipher, $method, $key, $options = OPENSSL_RAW_DATA, $iv);
                    if (Utils::isString($result)) {
                        $content = $cipher = "";
                        return $result;
                    }
                }
            } catch (\Throwable $e) {

            }
        }
        $data = $key = "";
        return false;
    }

    // PRIVATE METHODS
}

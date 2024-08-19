<?php

namespace PHPWebfuse;

use \PHPWebfuse\Utils;

/**
 * @author Senestro
 */
class Aes {
    // PRIVATE VARIABLE
    // PUBLIC VARIABLES
    // PUBLIC METHODS

    /**
     * Prevent the constructor from being initialized
     */
    private function __construct() {
        
    }

    /**
     * Encrypt data to into raw binary string
     * @param string $content The content to encrypt
     * @param string $key The encryption key
     * @param string $cm The encryption cipher method
     * @return bool|string Returns a raw binary string on success, otherwise returns false on failure
     * @throws \PHPWebfuse\Instance\Exceptions\Exception
     */
    public static function encData(string $content, string $key, string $cm = "aes-128-cbc"): bool|string {
        if(in_array($cm, openssl_get_cipher_methods(), true)) {
            $keysize = self::determineKeysizeFromCipherMethod($cm);
            $content = trim($content);
            $key = trim($key);
            if($keysize == \strlen($key)) {
                try {
                    $ivlenght = openssl_cipher_iv_length($cm);
                    if(Utils::isInt($ivlenght)) {
                        $iv = openssl_random_pseudo_bytes($ivlenght);
                        $result = @openssl_encrypt($content, $cm, $key, $options = OPENSSL_RAW_DATA, $iv);
                        $content = $key = "";
                        if(Utils::isString($result)) {
                            return $iv . $result;
                        } else {
                            return false;
                        }
                    }
                } catch(\Exception $e) {
                    throw Utils::throwException($e->getMessage());
                }
            } else {
                throw Utils::throwException("The required key size should be {$keysize} bytes in length");
            }
        } else {
            throw Utils::throwException("Unknown or invalid cipher method");
        }
    }

    /**
     * Decrypt the encrypted the raw binary string 
     * @param string $content The encrypted content to decrypt
     * @param string $key The decryption key
     * @param string $cm The encryption cipher method
     * @return bool|string Returns the original data from an encrypted raw binary string, otherwise returns false on failure
     * @throws \PHPWebfuse\Instance\Exceptions\Exception
     */
    public static function decData(string $content, string $key, string $cm = "aes-128-cbc"): bool|string {
        if(in_array($cm, openssl_get_cipher_methods(), true)) {
            $keysize = self::determineKeysizeFromCipherMethod($cm);
            $content = trim($content);
            $key = trim($key);
            if($keysize == \strlen($key)) {
                try {
                    $ivlenght = openssl_cipher_iv_length($cm);
                    if(Utils::isInt($ivlenght)) {
                        $iv = substr($content, 0, $ivlenght);
                        $cipher = substr($content, $ivlenght);
                        $result = @openssl_decrypt($cipher, $cm, $key, $options = OPENSSL_RAW_DATA, $iv);
                        $content = $key = $cipher = "";
                        if(Utils::isString($result)) {
                            return $result;
                        } else {
                            return false;
                        }
                    }
                } catch(\Exception $e) {
                    throw Utils::throwException($e->getMessage());
                }
            } else {
                throw Utils::throwException("The required key size should be {$keysize} bytes in length");
            }
        } else {
            throw Utils::throwException("Unknown or invalid cipher method");
        }
    }

    // PRIVATE METHODS

    /**
     * Determine key size base on cipher method
     * @param string $cm The cipher method
     * @return int Returns 0 when the key size couldn't be determined 
     */
    private static function determineKeysizeFromCipherMethod(string $cm): int {
        $size = 0;
        if(in_array($cm, openssl_get_cipher_methods(), true)) {
            // Determine the key size based on cipher method
            $cm = \strtolower($cm);
            switch($cm) {
                case "aes-128-cbc":
                case "aes-128-ecb":
                    $size = 16; // 128 bits
                    break;
                case "aes-192-cbc":
                case "aes-192-ecb":
                    $size = 24; // 192 bits
                    break;
                case "aes-256-cbc":
                case "aes-256-ecb":
                    $size = 32; // 256 bits
                    break;
                default:
                    $size = 0;
            }
        }
        return $size;
    }
}

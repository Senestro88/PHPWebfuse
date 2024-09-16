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
     * @throws \PHPWebfuse\Instance\Exceptions\InvalidArgumentException
     */
    public static function enc(string $content, string $key, string $cm = "aes-128-cbc"): bool|string {
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
                    throw new \PHPWebfuse\Instance\Exceptions\Exception($e->getMessage());
                }
            } else {
                throw new \PHPWebfuse\Instance\Exceptions\InvalidArgumentException("The required key size should be {$keysize} bytes in length");
            }
        } else {
            throw new \PHPWebfuse\Instance\Exceptions\Exception("Unknown or invalid cipher method");
        }
    }
    
    /**
     * Decrypt the encrypted the raw binary string
     * @param string $content The encrypted content to decrypt
     * @param string $key The decryption key
     * @param string $cm The encryption cipher method
     * @throws \PHPWebfuse\Instance\Exceptions\Exception
     * @throws \PHPWebfuse\Instance\Exceptions\InvalidArgumentException
     */
    public static function dec(string $content, string $key, string $cm = "aes-128-cbc"): bool|string {
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
                    throw new \PHPWebfuse\Instance\Exceptions\Exception($e->getMessage());
                }
            } else {
                throw new \PHPWebfuse\Instance\Exceptions\InvalidArgumentException("The required key size should be {$keysize} bytes in length");
            }
        } else {
            throw new \PHPWebfuse\Instance\Exceptions\Exception("Unknown or invalid cipher method");
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
            // Filtr out those cipher methods that have -128-, -192-, and -256- in them
            $methods = \array_filter(openssl_get_cipher_methods(), function($method) {
                return Utils::containText("-128-", $method) || Utils::containText("-192-", $method) || Utils::containText("-256-", $method);
            });
            // Determine the key size based on cipher method by checking if the cipher method provided contains -128-, -192-, and -256- in it
            $cm = \strtolower($cm);
            if(Utils::containText("-128-", $cm)) {
                $size = 16; // 128 bits
            } else if(Utils::containText("-192-", $cm)) {
                $size = 24; // 192 bits
            } else if(Utils::containText("-256-", $cm)) {
                $size = 32; // 256 bits
            }
        }
        return $size;
    }
}

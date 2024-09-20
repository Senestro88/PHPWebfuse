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
 * Encrypt data into a raw binary string
 * @param string $content The content to encrypt
 * @param string $key The encryption key
 * @param string $cm The encryption cipher method
 * @return bool|string Returns a raw binary string on success, otherwise returns false on failure
 * @throws \PHPWebfuse\Exceptions\Exception
 * @throws \PHPWebfuse\Exceptions\InvalidArgumentException
 */
public static function enc(string $content, string $key, string $cm = "aes-128-cbc"): bool|string {
    // Check if the provided cipher method is valid
    if (in_array($cm, openssl_get_cipher_methods(), true)) {
        // Determine the required key size based on the cipher method
        $keysize = self::determineKeysizeFromCipherMethod($cm);
        // Trim the content and key to remove any unnecessary characters
        $content = trim($content);
        $key = trim($key);
        // Check if the key size matches the required size
        if ($keysize == strlen($key)) {
            try {
                // Get the initialization vector length for the cipher method
                $ivlenght = openssl_cipher_iv_length($cm);
                if (Utils::isInt($ivlenght)) {
                    // Generate a random initialization vector
                    $iv = openssl_random_pseudo_bytes($ivlenght);
                    // Encrypt the content using the provided key and cipher method
                    $result = @openssl_encrypt($content, $cm, $key, $options = OPENSSL_RAW_DATA, $iv);
                    // Clear the content and key variables for security
                    $content = $key = "";
                    // Return the encrypted data, which includes the initialization vector and the encrypted content
                    if (Utils::isString($result)) {
                        return $iv . $result;
                    } else {
                        return false;
                    }
                }
            } catch (\Exception $e) {
                // Throw an exception if an error occurs during encryption
                Utils::throwException($e->getMessage());
            }
        } else {
            // Throw an exception if the key size does not match the required size
            Utils::throwInvalidArgumentException("The required key size should be {$keysize} bytes in length");
        }
    } else {
        // Throw an exception if the cipher method is unknown or invalid
        Utils::throwInvalidArgumentException("Unknown or invalid cipher method");
    }
}

/**
 * Decrypt the encrypted raw binary string
 * @param string $content The encrypted content to decrypt
 * @param string $key The decryption key
 * @param string $cm The encryption cipher method
 * @throws \PHPWebfuse\Exceptions\Exception
 * @throws \PHPWebfuse\Exceptions\InvalidArgumentException
 */
public static function dec(string $content, string $key, string $cm = "aes-128-cbc"): bool|string {
    // Check if the provided cipher method is valid
    if (in_array($cm, openssl_get_cipher_methods(), true)) {
        // Determine the required key size based on the cipher method
        $keysize = self::determineKeysizeFromCipherMethod($cm);
        // Trim the content and key to remove any unnecessary characters
        $content = trim($content);
        $key = trim($key);
        // Check if the key size matches the required size
        if ($keysize == strlen($key)) {
            try {
                // Get the initialization vector length for the cipher method
                $ivlenght = openssl_cipher_iv_length($cm);
                if (Utils::isInt($ivlenght)) {
                    // Extract the initialization vector from the encrypted content
                    $iv = substr($content, 0, $ivlenght);
                    // Extract the encrypted content
                    $cipher = substr($content, $ivlenght);
                    // Decrypt the content using the provided key and cipher method
                    $result = @openssl_decrypt($cipher, $cm, $key, $options = OPENSSL_RAW_DATA, $iv);
                    // Clear the content, key, and cipher variables for security
                    $content = $key = $cipher = "";
                    // Return the decrypted content
                    if (Utils::isString($result)) {
                        return $result;
                    } else {
                        return false;
                    }
                }
            } catch (\Exception $e) {
                // Throw an exception if an error occurs during decryption
                Utils::throwException($e->getMessage());
            }
        } else {
            // Throw an exception if the key size does not match the required size
            Utils::throwInvalidArgumentException("The required key size should be {$keysize} bytes in length");
        }
    } else {
        // Throw an exception if the cipher method is unknown or invalid
        Utils::throwInvalidArgumentException("Unknown or invalid cipher method");
    }
}

    // PRIVATE METHODS

    /**
     * Determine key size based on cipher method
     * @param string $cm The cipher method
     * @return int Returns 0 when the key size couldn't be determined
     */
    private static function determineKeysizeFromCipherMethod(string $cm): int {
        // Initialize key size to 0, default value when key size couldn't be determined
        $size = 0;
        // Check if the provided cipher method is valid
        if (in_array($cm, openssl_get_cipher_methods(), true)) {
            // Convert the cipher method to lowercase for case-insensitive comparison
            $cm = strtolower($cm);
            // Define an array to map key size patterns to their corresponding sizes
            $keySizes = [
                '-128-' => 16, // 128 bits
                '-192-' => 24, // 192 bits
                '-256-' => 32, // 256 bits
            ];
            // Iterate over the key size patterns and check if the cipher method matches any of them
            foreach ($keySizes as $pattern => $keySize) {
                if (Utils::containText($pattern, $cm)) {
                    // If a match is found, set the key size and break the loop
                    $size = $keySize;
                    break;
                }
            }
        }
        // Return the determined key size, or 0 if it couldn't be determined
        return $size;
    }
}

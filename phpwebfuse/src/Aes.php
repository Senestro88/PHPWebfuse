<?php

namespace PHPWebfuse;

/**
 * The PHPWebfuse 'Aes' Class
 */
class Aes
{
    // PRIVATE VARIABLES

    /**
     * @var \PHPWebfuse\Methods The default PHPWebfuse methods class
     */
    private $methods = null;

    /**
     * @var string The default cipher method for encryption and decryption
     */
    private $aesMethod = "aes-128-cbc";

    // PUBLIC METHODS

    /**
     * Construct new Advance encryption standard instance
     * @param ?string $aesMethod The cipher method. Use this to override the default cipher method, the default is null
     */
    public function __construct(?string $aesMethod = null)
    {
        $this->methods = new \PHPWebfuse\Methods();
        if ($this->methods->isString($aesMethod) && $this->methods->isNotEmptyString($aesMethod) && in_array($aesMethod, openssl_get_cipher_methods(), true)) {
            $this->aesMethod = $aesMethod;
        }
    }

    /**
     * Encrypt data to hex string
     * @param string $data The content to encrypt
     * @param string $key The encryption key
     * @return bool | string Returns a hex string on success, otherwise returns false
     */
    public function encData(string $data, string $key): bool|string
    {
        if (in_array($this->aesMethod, openssl_get_cipher_methods(), true) && !$this->methods->isEmptyString($data) && !$this->methods->isEmptyString($key)) {
            $data = trim($data);
            $key = trim($key);
            try {
                $ivLenght = openssl_cipher_iv_length($this->aesMethod);
                if ($this->methods->isInt($ivLenght)) {
                    $iv = openssl_random_pseudo_bytes($ivLenght);
                    $result = @openssl_encrypt($data, $this->aesMethod, $key, $options = OPENSSL_RAW_DATA, $iv);
                    if ($this->methods->isString($result)) {
                        $data = $key = "";
                        return bin2hex($iv . $result);
                    }
                }
            } catch (\Throwable $e) {

            }
        }
        $data = $key = "";
        return false;
    }

    /**
     * Decrypt encrypted hex string to original data
     * @param string $data The encrypted content to decrypt
     * @param string $key The decryption key
     * @return bool | string Returns the original data from an encrypted hex string, otherwise returns false
     */
    public function decData(string $data, string $key): bool|string
    {
        if (in_array($this->aesMethod, openssl_get_cipher_methods(), true) && $this->methods->isNotEmptyString($data) && $this->methods->isNotEmptyString($key)) {
            try {
                $data = trim($data);
                $key = trim($key);
                $data = hex2bin($data);
                $ivLenght = openssl_cipher_iv_length($this->aesMethod);
                if ($this->methods->isInt($ivLenght)) {
                    $iv = substr($data, 0, $ivLenght);
                    $cipher = substr($data, $ivLenght);
                    $result = @openssl_decrypt($cipher, $this->aesMethod, $key, $options = OPENSSL_RAW_DATA, $iv);
                    if ($this->methods->isString($result)) {
                        $data = $cipher = "";
                        return $result;
                    }
                }
            } catch (\Throwable $e) {

            }
        }
        $data = $key = "";
        return false;
    }
}

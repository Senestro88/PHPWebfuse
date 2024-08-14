<?php

namespace PHPWebfuse;

/**
 */
class Aes
{
    // PRIVATE VARIABLES

    /**
     * @var \PHPWebfuse\Methods
     */
    private \PHPWebfuse\Methods $methods;

    /**
     * @var string The default cipher method for encrypting and decrypting data
     */
    private string $cipherMethod = "aes-128-cbc";

    // PUBLIC METHODS

    /**
     * Construct a new instance of the Aes Class
     * @param ?string $cipherMethod The cipher method. Use this to override the default cipher method, the default is null
     */
    public function __construct(string $cipherMethod = "aes-128-cbc")
    {
        $this->methods = new \PHPWebfuse\Methods();
        if ($this->methods->isNotEmptyString($cipherMethod) && in_array($cipherMethod, openssl_get_cipher_methods(), true)) {
            $this->cipherMethod = $cipherMethod;
        }
    }

    /**
     * Encrypt data to into a hex string or base64 encoded string
     * @param string $data The content to encrypt
     * @param string $key The encryption key
     * @param bool $toHex True means to return the encrypted data as a hex string or base64 encoded string
     * @return bool|string Returns a hex string or base64 encoded string on success, otherwise returns false
     */
    public function encData(string $data, string $key, bool $toHex = true): bool|string
    {
        if (in_array($this->cipherMethod, openssl_get_cipher_methods(), true) && !$this->methods->isEmptyString($data) && !$this->methods->isEmptyString($key)) {
            try {
                $data = trim($data);
                $key = trim($key);
                $ivLenght = openssl_cipher_iv_length($this->cipherMethod);
                if ($this->methods->isInt($ivLenght)) {
                    $iv = openssl_random_pseudo_bytes($ivLenght);
                    $result = @openssl_encrypt($data, $this->cipherMethod, $key, $options = OPENSSL_RAW_DATA, $iv);
                    if ($this->methods->isString($result)) {
                        $data = $key = "";
                        return $toHex ? bin2hex($iv . $result) : base64_encode($iv . $result);
                    }
                }
            } catch (\Throwable $e) {

            }
        }
        $data = $key = "";
        return false;
    }

    /**
     * Decrypt the encrypted hex string or base64 encoded string to original data
     * @param string $data The encrypted content to decrypt
     * @param string $key The decryption key
     * @param bool $fromHex True means the $data is a hex string, else a base64 encoded string
     * @return bool|string Returns the original data from an encrypted hex string or base64 encoded string, otherwise returns false
     */
    public function decData(string $data, string $key, bool $fromHex = true): bool|string
    {
        if (in_array($this->cipherMethod, openssl_get_cipher_methods(), true) && $this->methods->isNotEmptyString($data) && $this->methods->isNotEmptyString($key)) {
            try {
                $data = trim($data);
                $key = trim($key);
                $data = $fromHex ? hex2bin($data) : base64_decode($data);
                $ivLenght = openssl_cipher_iv_length($this->cipherMethod);
                if ($this->methods->isInt($ivLenght)) {
                    $iv = substr($data, 0, $ivLenght);
                    $cipher = substr($data, $ivLenght);
                    $result = @openssl_decrypt($cipher, $this->cipherMethod, $key, $options = OPENSSL_RAW_DATA, $iv);
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

<?php
namespace PHPWebFuse;
/**
 * The PHPWebFuse 'Aes' Class
 */
class Aes extends \PHPWebFuse\Methods {
	// PRIVATE VARIABLES

	/**
	 * @var string - The cipher method for encryption and decryption
	 */
	private $method = "aes-128-cbc";

	// PUBLIC METHODS
	
	/**
	 * @param ?string $metthod - The cipher method. Use this to override the default cipher method, the default is null
	 */
	public function __construct(?string $method = null) {
		if (parent::isString($method) && parent::isNotEmptyString($method) && in_array($method, openssl_get_cipher_methods(), true)) {
			$this->method = $method;
		}
	}

	/**
	 * Encrypt data to hex string
	 *
	 * @param string $data - The content to encrypt
	 * @param string $key - The encryption key
	 * @return bool | string - Returns a hex string on success, otherwise rerurns false
	 */
	public function encData(string $data, string $key): bool | string {
		if (in_array($this->method, openssl_get_cipher_methods(), true) && !parent::isEmptyString($data) && !parent::isEmptyString($key)) {
			$data = trim($data);
			$key = trim($key);
			try {
				$ivLenght = openssl_cipher_iv_length($this->method);
				if (parent::isInt($ivLenght)) {
					$iv = openssl_random_pseudo_bytes($ivLenght);
					$result = @openssl_encrypt($data, $this->method, $key, $options = OPENSSL_RAW_DATA, $iv);
					if (parent::isString($result)) {
						$data = $key = "";
						return bin2hex($iv . $result);
					}
				}
			} catch (\Throwable $e) {}
		}
		$data = $key = "";
		return false;
	}

	/**
	 * Decrypt encrypted hex string to original data
	 *
	 * @param string $data - The encrypted content to decrypt
	 * @param string $key - The decryption key
	 * @return bool | string - Returns the original data from an encrypted hex string, otherwise returns false
	 */
	public function decData(string $data, string $key): bool | string {
		if (in_array($this->method, openssl_get_cipher_methods(), true) && parent::isNotEmptyString($data) && parent::isNotEmptyString($key)) {
			try {
				$data = trim($data);
				$key = trim($key);
				$data = hex2bin($data);
				$ivLenght = openssl_cipher_iv_length($this->method);
				if (parent::isInt($ivLenght)) {
					$iv = substr($data, 0, $ivLenght);
					$cipher = substr($data, $ivLenght);
					$result = @openssl_decrypt($cipher, $this->method, $key, $options = OPENSSL_RAW_DATA, $iv);
					if (parent::isString($result)) {
						$data = $cipher = "";
						return $result;
					}
				}
			} catch (\Throwable $e) {}
		}
		$data = $key = "";
		return false;
	}
}
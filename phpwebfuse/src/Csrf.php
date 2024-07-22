<?php
namespace PHPWebFuse;
/**
 *
 */
class Csrf extends \PHPWebFuse\Methods {
	// PUBLIC VARIABLES

	public $key = "1291707447071921";

	// PRIVATE VARIABLES

	private $aesClass = null;

	// PUBLIC METHODS
	
	/**
	 * @param ? string $key
	 * @return void
	 */
	public function __construct(?string $key = null) {
		if (parent::isString($key) && parent::isNotEmptyString($key)) {$this->key = $key;}
		$this->aesClass = new \PHPWebFuse\Aes();
	}

	/**
	 * Set the cross site request forgery key
	 *
	 * @param string $key
	 * @return void
	 */
	public function setKey(string $key): void {if (!parent::isEmptyString($key)) {$this->key = $key;}}

	/**
	 * Generate the cross site request forgery token passing the expiration minutes
	 *
	 * @param string $minutes
	 * @return string
	 */
	public function generateToken(int $minutes = 10): string {
		if (parent::isNonNull($this->aesClass) && parent::isNotEmptyString($this->key)) {
			try {
				$hex = $this->newHex();
				$salt = hash_hmac('sha256', $hex, $this->key);
				$expires = $this->setFutureMinutes($minutes);
				$json = parent::arrayToJson(array("data" => $hex, "salt" => $salt, "expires" => $expires));
				$enc = $this->aesClass->encData($json, $this->key);
				return $enc ? $enc : '';
			} catch (\Throwable $e) {}
		}
		return "";
	}

	/**
	 * Validate the generated cross site request forgery token
	 *
	 * @param string $token
	 * @return bool
	 */
	public function validateToken(string $token): bool {
		if (parent::isNonNull($this->aesClass) && parent::isNotEmptyString($token) && parent::isNotEmptyString($this->key)) {
			try {
				$dec = $this->aesClass->decData($token, $this->key);
				if ($dec) {
					$array = parent::jsonToArray($dec);
					$data = $array['data'] ?? '';
					$salt = $array['salt'] ?? '';
					$expires = (int) ($array['expires'] ?? 0);
					if (hash_equals($salt, hash_hmac('sha256', $data, $this->key))) {return $this->isMinuteInFuture($expires);}
				}
			} catch (\Throwable $e) {}
		}
		return false;
	}

	// PRIVATE METHODS
	private function newHex() {return bin2hex(openssl_random_pseudo_bytes(32));}

	private function getMinutesFromTime() {return round(time() / 60, 0, PHP_ROUND_HALF_DOWN);}

	private function setFutureMinutes(int $minutes) {return $this->getMinutesFromTime() + $minutes;}

	private function isMinuteInFuture(int $minutes) {return $minutes > $this->getMinutesFromTime();}
}
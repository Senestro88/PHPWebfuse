<?php

namespace PHPWebfuse;

/**
 * The PHPWebfuse 'Csrf' class
 */
class Csrf
{
    // PRIVATE VARIABLES

    /**
     * @var \PHPWebfuse\Methods The default PHPWebfuse methods class
     */
    private $methods = null;

    /**
     * @var \PHPWebfuse\Aes The default PHPWebfuse Aes class
     */
    private $aes = null;

    // PUBLIC VARIABLES

    /**
     * @var string The default Cross Site Request Forgery key but can be changed
     */
    public $crsfKey = "1291707447071921";

    // PUBLIC METHODS

    /**
     * Construct a new Cross Site Request Forgery instance
     * @param string|null $csrfKey
     */
    public function __construct(?string $csrfKey = null)
    {
        $this->methods = new \PHPWebfuse\Methods();
        $this->aes = new \PHPWebfuse\Aes();
        if ($this->methods->isString($csrfKey) && $this->methods->isNotEmptyString($csrfKey)) {
            $this->crsfKey = $csrfKey;
        }
    }

    /**
     * Set the cross site request forgery key
     * @param string $csrfKey
     * @return void
     */
    public function setKey(string $csrfKey): void
    {
        if (!$this->methods->isEmptyString($csrfKey)) {
            $this->crsfKey = $csrfKey;
        }
    }

    /**
     * Generate the cross site request forgery token passing the expiration minutes
     * @param int $minutes
     * @return string
     */
    public function generateToken(int $minutes = 10): string
    {
        if ($this->methods->isNonNull($this->aes) && $this->methods->isNotEmptyString($this->crsfKey)) {
            try {
                $hex = $this->newHex();
                $salt = hash_hmac('sha256', $hex, $this->crsfKey);
                $expires = $this->setFutureMinutes($minutes);
                $json = $this->methods->arrayToJson(array("data" => $hex, "salt" => $salt, "expires" => $expires));
                $enc = $this->aes->encData($json, $this->crsfKey);
                return $enc ? $enc : '';
            } catch (\Throwable $e) {

            }
        }
        return "";
    }

    /**
     * Validate the generated cross site request forgery token
     * @param string $generatedToken
     * @return bool
     */
    public function validateToken(string $generatedToken): bool
    {
        if ($this->methods->isNonNull($this->aes) && $this->methods->isNotEmptyString($generatedToken) && $this->methods->isNotEmptyString($this->crsfKey)) {
            try {
                $dec = $this->aes->decData($generatedToken, $this->crsfKey);
                if ($dec) {
                    $array = $this->methods->jsonToArray($dec);
                    $data = $array['data'] ?? '';
                    $salt = $array['salt'] ?? '';
                    $expires = (int) ($array['expires'] ?? 0);
                    if (hash_equals($salt, hash_hmac('sha256', $data, $this->crsfKey))) {
                        return $this->isMinuteInFuture($expires);
                    }
                }
            } catch (\Throwable $e) {

            }
        }
        return false;
    }

    // PRIVATE METHODS

    /**
     * Generate hex string
     * @return string
     */
    private function newHex(): string
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }

    /**
     * Get minute from time
     * @return float
     */
    private function getMinutesFromTime(): float
    {
        return round(time() / 60, 0, PHP_ROUND_HALF_DOWN);
    }

    /**
     * Set the future time
     * @param int $minutes
     * @return void
     */
    private function setFutureMinutes(int $minutes): float
    {
        return $this->getMinutesFromTime() + $minutes;
    }

    /**
     * Is the time from the future
     * @param int $minutes
     * @return bool
     */
    private function isMinuteInFuture(int $minutes): bool
    {
        return $minutes > $this->getMinutesFromTime();
    }
}

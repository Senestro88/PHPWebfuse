<?php

namespace PHPWebfuse\Instance;


use \PHPWebfuse\Utils;
use \PHPWebfuse\Aes;

/**
 * @author Senestro
 */
class Csrf
{
    // PRIVATE VARIABLES

    // PUBLIC VARIABLES

    /**
     * @var string The default Cross Site Request Forgery key
     */
    public string $crsfKey = "1291707447071921";

    // PUBLIC METHODS

    /**
     * Construct a new Cross Site Request Forgery instance
     * @param string|null $csrfKey
     */
    public function __construct(?string $csrfKey = null)
    {
        if (Utils::isString($csrfKey) && Utils::isNotEmptyString($csrfKey)) {
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
        if (!Utils::isEmptyString($csrfKey)) {
            $this->crsfKey = $csrfKey;
        }
    }

    /**
     * Generate the cross site request forgery token passing the expiration minutes
     * @param int $minutes Default to 10 (10 Minutes)
     * @return string
     */
    public function generateToken(int $minutes = 10): string
    {
        if (Utils::isNonNull($this->aesInstance) && Utils::isNotEmptyString($this->crsfKey)) {
            try {
                $hex = $this->newHex();
                $salt = hash_hmac('sha256', $hex, $this->crsfKey);
                $expires = $this->setFutureMinutes($minutes);
                $json = Utils::arrayToJson(array("data" => $hex, "salt" => $salt, "expires" => $expires));
                $enc = Aes::encData($json, "aes-128-cbc", $this->crsfKey);
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
        if (Utils::isNonNull($this->aesInstance) && Utils::isNotEmptyString($generatedToken) && Utils::isNotEmptyString($this->crsfKey)) {
            try {
                $dec = Aes::decData($generatedToken, "aes-128-cbc", $this->crsfKey);
                if ($dec) {
                    $array = Utils::jsonToArray($dec);
                    $data = $array['data'] ?? '';
                    $salt = $array['salt'] ?? '';
                    $expires = (int) ($array['expires'] ?? 0);
                    if (hash_equals($salt, hash_hmac('sha256', $data, $this->crsfKey))) {
                        return $this->isMinutesInFuture($expires);
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
     * Set the future time minutes
     * @param int $minutes
     * @return void
     */
    private function setFutureMinutes(int $minutes): float
    {
        return $this->getMinutesFromTime() + $minutes;
    }

    /**
     * Determine if the time minutes is from the future
     * @param int $minutes
     * @return bool
     */
    private function isMinutesInFuture(int $minutes): bool
    {
        return $minutes > $this->getMinutesFromTime();
    }
}

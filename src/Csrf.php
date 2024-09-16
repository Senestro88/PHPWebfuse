<?php

namespace PHPWebfuse;

use \PHPWebfuse\Utils;
use \PHPWebfuse\Aes;

/**
 * @author Senestro
 */
class Csrf
{
    // PRIVATE VARIABLES

    // PUBLIC VARIABLES

    // PUBLIC METHODS

    /**
     * Prevent the constructor from being initialized
     */
    private function __construct()
    {

    }

    /**
     * Generate the cross site request forgery token passing the expiration minutes
     * @param int $minutes Default to 10 (10 Minutes)
     * @return string
     */
    public static function generateToken(string $csrfKey, int $minutes = 10): string
    {
        if (Utils::isNotEmptyString($csrfKey)) {
            try {
                $hex = self::newHex();
                $salt = hash_hmac('sha256', $hex, $csrfKey);
                $expires = self::setFutureMinutes($minutes);
                $json = Utils::arrayToJson(array("data" => $hex, "salt" => $salt, "expires" => $expires));
                $enc = Aes::enc($json, "aes-128-cbc", $csrfKey);
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
    public static function validateToken(string $csrfKey, string $generatedToken): bool
    {
        if (Utils::isNotEmptyString($csrfKey) && Utils::isNotEmptyString($generatedToken)) {
            try {
                $dec = Aes::decData($generatedToken, "aes-128-cbc", $csrfKey);
                if ($dec) {
                    $array = Utils::jsonToArray($dec);
                    $data = $array['data'] ?? '';
                    $salt = $array['salt'] ?? '';
                    $expires = (int) ($array['expires'] ?? 0);
                    if (hash_equals($salt, hash_hmac('sha256', $data, $csrfKey))) {
                        return self::isMinutesInFuture($expires);
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
    private static function newHex(): string
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }

    /**
     * Get minute from time
     * @return float
     */
    private static function getMinutesFromTime(): float
    {
        return round(time() / 60, 0, PHP_ROUND_HALF_DOWN);
    }

    /**
     * Set the future time minutes
     * @param int $minutes
     * @return void
     */
    private static function setFutureMinutes(int $minutes): float
    {
        return self::getMinutesFromTime() + $minutes;
    }

    /**
     * Determine if the time minutes is from the future
     * @param int $minutes
     * @return bool
     */
    private static function isMinutesInFuture(int $minutes): bool
    {
        return $minutes > self::getMinutesFromTime();
    }
}

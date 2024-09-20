<?php

namespace PHPWebfuse;

use \PHPWebfuse\Utils;
use \PHPWebfuse\Aes;

/**
 * @author Senestro
 */
class Csrf {
    // PRIVATE VARIABLES

    // PUBLIC VARIABLES

    // PUBLIC METHODS

    /**
     * Prevent the constructor from being initialized
     */
    private function __construct() {
    }

    /**
     * Generate the cross site request forgery token passing the expiration minutes
     * @param string $key
     * @param int $minutes Default to 10 (10 Minutes)
     * @return string
     */
    public static function generateToken(string $csrfKey, int $minutes = 10): string {
        if (Utils::isNotEmptyString($csrfKey)) {
            try {
                $hex = self::newHex();
                $salt = hash_hmac('sha256', $hex, $csrfKey);
                $expires = self::setFutureMinutesFromMinutes($minutes);
                $json = Utils::arrayToJson(array("token" => $hex, "salt" => $salt, "expires" => $expires));
                $enc = Aes::enc($json, $csrfKey, "aes-128-cbc");
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
    public static function validateToken(string $csrfKey, string $generatedToken): bool {
        if (Utils::isNotEmptyString($csrfKey) && Utils::isNotEmptyString($generatedToken)) {
            try {
                $dec = Aes::dec($generatedToken, $csrfKey, "aes-128-cbc");
                if ($dec) {
                    $array = Utils::jsonToArray($dec);
                    $token = $array['token'] ?? '';
                    $salt = $array['salt'] ?? '';
                    $expires = (int) ($array['expires'] ?? 0);
                    if (hash_equals($salt, hash_hmac('sha256', $token, $csrfKey))) {
                        return self::isCsrfMinutesInFuture($expires);
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
    private static function newHex(): string {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }

    /**
     * Get minute from time
     * @return float
     */
    private static function getMinutesFromTime(): float {
        return round(time() / 60, 0, PHP_ROUND_HALF_DOWN);
    }

    /**
     * Set the future time minutes
     * @param int $minutes
     * @return void
     */
    private static function setFutureMinutesFromMinutes(int $minutes): float {
        return self::getMinutesFromTime() + $minutes;
    }

    /**
     * Determine if the time minutes is from the future
     * @param int $minutes
     * @return bool
     */
    private static function isCsrfMinutesInFuture(int $minutes): bool {
        return $minutes > self::getMinutesFromTime();
    }
}

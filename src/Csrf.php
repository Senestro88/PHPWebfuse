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
    public static \Throwable $lastThrowable;

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
                return $enc ?: '';
            } catch (\Throwable $e) {
                self::setThrowable($e);
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
                    return hash_equals($salt, hash_hmac('sha256', $token, $csrfKey)) ? self::isCsrfMinutesInFuture($expires) : false;
                }
            } catch (\Throwable $e) {
                self::setThrowable($e);
            }
        }
        return false;
    }


    /**
     * Retrieves the last throwable instance.
     *
     * This method returns the most recent throwable instance that was stored
     * using the `setThrowable` method. If no throwable has been set, it returns null.
     *
     * @return \Throwable|null The last throwable instance or null if none is set.
     */
    public static function getThrowable(): ?\Throwable {
        return self::$lastThrowable;
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

    /**
     * Sets the last throwable instance.
     *
     * This method allows storing the most recent exception or error object 
     * that implements the Throwable interface. It is useful for tracking
     * or logging errors globally.
     *
     * @param \Throwable $e The throwable instance to be stored.
     * @return void
     */
    private static function setThrowable(\Throwable $e): void {
        self::$lastThrowable = $e;
    }
}

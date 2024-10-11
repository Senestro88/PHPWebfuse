<?php

namespace PHPWebfuse;

use \PHPWebfuse\Utils;
use \PHPWebfuse\Aes;
use \PHPWebfuse\Exceptions\Session;

/**
 * @author Senestro
 */
class CsrfSession {
    // Private constants
    private static $name = "X-SESS-CSRF-TOKEN";
    private static $password = "1291707412917074";
    private static $lastMessage = "";

    // Prevent the constructor from being initialized
    private function __construct() {
    }

    /**
     * Get the CSRF token name
     * @return string
     */
    public static function getName(): string {
        return self::$name;
    }

    /**
     * Get the last session message
     * @return string The session last message
     */
    public static function getMessage(): string {
        return self::$lastMessage;
    }

    /**
     * Set the CSRF token in the session
     * @param mixed $expires The time to mark the token as expired  (In minutes)
     */
    public static function setToken(int $expires = 1440): void {
        if (self::sessionIdValid()) {
            $generatedToken = self::generateToken($expires);
            if (!empty($generatedToken)) {
                // Set the token in the session
                $_SESSION[self::$name] = base64_encode($generatedToken);
                if (!isset($_SESSION[self::$name])) {
                    self::$lastMessage = "Failed to set the token session.";
                }
            } else {
                self::$lastMessage = "Failed to generated session token.";
            }
        } else {
            self::$lastMessage = "Failed to set session token, session is not started.";
        }
    }

    /**
     * Get the CSRF token from the session
     * @return string|null
     */
    public static function getToken(): ?string {
        return self::sessionIdValid() && isset($_SESSION[self::$name]) && is_string($_SESSION[self::$name]) && !empty($_SESSION[self::$name]) ? $_SESSION[self::$name] : null;
    }

    /**
     * Validate the CSRF token
     * @return bool
     */
    public static function validateToken(): bool {
        if (self::sessionIdValid()) {
            $sessionToken = self::getToken();
            if (!self::isNull($sessionToken)) {
                $isValid = Csrf::validateToken(self::$password, base64_decode($sessionToken));
                if ($isValid) {
                    self::unsetToken();
                } else {
                    self::$lastMessage = "Failed to validated session token, session token is not valid or has expired.";
                }
                return $isValid;
            } else {
                self::$lastMessage = "Failed to validated session token, unable to get the session token.";
            }
        } else {
            self::$lastMessage = "Failed to validate session token, session is not started.";
        }
        return false;
    }

    /**
     * Validate a generated CSRF token
     * @param string $generatedToken
     * @return bool
     */
    public static function isValidToken(string $generatedToken): bool {
        if (!empty($generatedToken)) {
            $isValid = Csrf::validateToken(self::$password, base64_decode($generatedToken));
            if ($isValid) {
                self::unsetToken();
            } else {
                self::$lastMessage = "Failed to validated token, token is not valid or has expired.";
            }
            return $isValid;
        } else {
            self::$lastMessage = "Failed to validate token, the token provided is empty.";
        }
        return false;
    }

    /**
     * Echo the CSRF token in a form
     */
    public static function echoTokenInForm(): void {
        if (self::sessionIdValid()) {
            $sessionToken = self::getToken();
            if (!self::isNull($sessionToken)) {
                echo "<input type='hidden' name='" . self::$name . "' id='" . self::$name . "' value='" . $sessionToken . " />";
            } else {
                self::$lastMessage = "Failed to echo token in html form, unable to get the session token.";
            }
        } else {
            self::$lastMessage = "Failed to echo token in html form, the token provided is empty.";
        }
    }

    /**
     * Echo the CSRF token in the HTML head
     */
    public static function echoTokenInHtmlHead(): void {
        if (self::sessionIdValid()) {
            $sessionToken = self::getToken();
            if (!self::isNull($sessionToken)) {
                echo "<meta name='" . self::$name . "' content='" . $sessionToken . " />";
            } else {
                self::$lastMessage = "Failed to echo token in html head, unable to get the session token.";
            }
        } else {
            self::$lastMessage = "Failed to echo token in html head, the token provided is empty.";
        }
    }

    /**
     * Validate the CSRF token from a POST request
     * @return bool
     */
    public static function validateTokenFromPost(): bool {
        if (getenv("REQUEST_METHOD") === "POST" && isset($_POST[self::$name]) && !empty($_POST[self::$name])) {
            $isValid = Csrf::validateToken(self::$password, base64_decode((string) $_POST[self::$name]));
            if ($isValid) {
                self::unsetToken();
            } else {
                self::$lastMessage = "Failed to validated token from post rquest, token is not valid or has expired.";
            }
            return $isValid;
        } else {
            self::$lastMessage = "Failed to validated token from post request, the token provided is empty or not a POST request.";
        }
        return false;
    }

    /**
     * Validate the CSRF token from headers
     * @return bool
     */
    public static function validateTokenFromHeaders(): bool {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers[self::$name]) && !empty($headers[self::$name])) {
                $isValid = Csrf::validateToken(self::$password, base64_decode((string) $headers[self::$name]));
                if ($isValid) {
                    self::unsetToken();
                } else {
                    self::$lastMessage = "Failed to validated token from request header, token is not valid or has expired.";
                }
                return $isValid;
            }
        } else {
            self::$lastMessage = "Failed to validated token from request header, the token provided is empty or not in header request.";
        }
        return false;
    }

    // PRIVATE METHODS

    /**
     * Generate a CSRF token
     * @param mixed $expires The time to mark the token as expired (In minutes)
     * @return string
     */
    private static function generateToken(int $expires = 1440): string {
        return Csrf::generateToken(self::$password, $expires);
    }

    /**
     * Check if the session ID is valid
     * @return bool
     */
    private static function sessionIdValid(): bool {
        $seesionId = session_id();
        return is_string($seesionId) && !empty($seesionId);
    }

    /**
     * Unset the CSRF token
     */
    private static function unsetToken(): void {
        if (self::sessionIdValid()) {
            if (isset($_SESSION[self::$name])) {
                unset($_SESSION[self::$name]);
            }
        }
    }

    /**
     * Check if a value is null
     * @param mixed $arg
     * @return bool
     */
    private static function isNull(mixed $arg): bool {
        return $arg === null;
    }
}

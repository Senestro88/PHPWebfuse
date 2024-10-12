<?php

namespace PHPWebfuse;

use PHPWebfuse\File;
use PHPWebfuse\Path;

/**
 * @author Senestro
 */
class Utils {
    // PRIVATE VARIABLES
    // PRIVATE CONSTANTS
    // PUBLIC CONSTANT VARIABLES

    /**
     * @var array The default character considered invalid
     */
    public const INVALID_CHARS = array("\\", "/", ":", ";", " ", "*", "?", "\"", "<", ">", "|", ",", "'");

    /**
     * @var int Files permission
     */
    public const FILE_PERMISSION = 0644;

    /**
     * @var int Directories permission
     */
    public const DIRECTORY_PERMISSION = 0755;

    /**
     * @var int Default image width for conversion
     */
    public const IMAGE_WIDTH = 450;

    /**
     * @var int Default image height for conversion
     */
    public const IMAGE_HEIGHT = 400;

    /**
     * @var string Default user agent
     */
    public const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36";

    /**
     * @var string Default timezone
     */
    public const TIMEZONE = "Africa/Lagos";

    /**
     * @var string Default GMT
     */
    public const GMT = "+01:00";

    /**
     * @var array Default errors stylesheet
     */
    public const ERRORS_CSS = array(
        'exception' => "width: 100%; padding: 5px; height: auto; position: relative; display: block; text-align: left; word-break: break-word; overflow-wrap: break-word; color: #d22c3c; background: transparent; font-size: 90%; margin: 5px auto; border: none; border-bottom: 2px dashed red; font-weight: normal;",
        'error' => "width: 100%; padding: 5px; height: auto; position: relative; display: block; text-align: left; word-break: break-word; overflow-wrap: break-word; color: black; background: transparent; font-size: 90%; margin: 5px auto; border: none; border-bottom: 2px dashed #da8d00; font-weight: normal;",
    );

    /**
     * @var array Common localhost addresses
     */
    public const LOCALHOST_DEFAULT_ADDRESSES = array('localhost', '127.0.0.1', '::1', '');

    /**
     * @var array Excluded private IP address ranges
     */
    public const PRIVATE_IP_ADDRESS_RANGES = array('10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '169.254.0.0/16', '127.0.0.0/8');

    /**
     * @var bool Wether to check for Ip in private Ip ranges
     */
    public const CHECK_IP_ADDRESS_IN_RANGE = false;

    // PUBLIC METHODS

    /**
     * Prevent the constructor from being initialized
     */
    private function __construct() {
    }

    /**
     * Get the operating system
     * @return string
     */
    public static function getOS(): string {
        $os = strtolower(PHP_OS);
        if (substr($os, 0, 3) === "win") {
            return "Windows";
        } elseif (substr($os, 0, 4) == "unix") {
            return "Unix";
        } elseif (substr($os, 0, 5) == "linux") {
            return "Linux";
        }
        return "Unknown";
    }

    /**
     * Determine if resource is stream
     * @param mixed $resource
     * @return bool
     */
    public static function isResourceStream(mixed $resource): bool {
        return self::isResource($resource) && @get_resource_type($resource) == "stream";
    }

    /**
     * Determine if resource is curl
     *
     * @param mixed $resource
     * @return bool
     */
    public static function isResourceCurl(mixed $resource): bool {
        return self::isResource($resource) && @get_resource_type($resource) == "curl";
    }

    /**
     * Sets the current process to unlimited execution time and unlimited memory limit
     *
     * @return void
     */
    public static function unlimitedWorkflow(): void {
        @ini_set("memory_limit", "-1");
        @ini_set("max_execution_time", "0");
        @set_time_limit(0);
    }

    /**
     * Create a web browser cookie
     * @param string $name
     * @param string $value
     * @param int $days
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $sameSite
     * @return bool
     */
    public static function createCookie(string $name, string $value, int $days, string $path, string $domain, bool $secure, bool $httpOnly, string $sameSite): bool {
        $expires = strtotime('+' . $days . ' days');
        return @setcookie($name, $value, array('expires' => $expires, 'path' => $path, 'domain' => $domain, 'secure' => $secure, 'httponly' => $httpOnly, 'samesite' => ucfirst($sameSite)));
    }

    /**
     * Delete a web browser cookie
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $sameSite
     * @return bool
     */
    public static function deleteCookie(string $name, string $path, string $domain, bool $secure, bool $httpOnly, string $sameSite): bool {
        if (isset($_COOKIE) && isset($_COOKIE[$name])) {
            $expires = strtotime('2010');
            $setcookie = @setcookie($name, "", array('expires' => $expires, 'path' => $path, 'domain' => $domain, 'secure' => $secure, 'httponly' => $httpOnly, 'samesite' => ucfirst($sameSite)));
            if (self::isTrue($setcookie)) {
                try {
                    unset($_COOKIE['' . $name . '']);
                } catch (\Throwable $e) {
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the value of a cookie
     *
     * @param string $name The name of the cookie
     * @return string The value of the cookie, or an empty string if it doesn't exist
     */
    public function getCookieValue(string $name): string {
        // Check if the cookie exists
        if (isset($_COOKIE) && isset($_COOKIE[$name])) {
            // Return the value of the cookie
            return (string) $_COOKIE[$name];
        }
        // Return an empty string if the cookie doesn't exist
        return "";
    }

    /**
     * Get the readable permission of a file
     * @param string $path The path to the file or directory
     * @return string
     */
    public static function getReadablePermission(string $path): string {
        // Convert numeric mode to symbolic representation
        $info = '';
        if (self::isExists($path)) {
            // Get the file permissions as a numeric mode
            $perms = fileperms($path);
            // Determine file type
            $fileType = $perms & 0xF000;
            if ($fileType === 0xC000) {
                $info = 's'; // Socket
            } elseif ($fileType === 0xA000) {
                $info = 'l'; // Symbolic link
            } elseif ($fileType === 0x8000) {
                $info = 'r'; // Regular
            } elseif ($fileType === 0x6000) {
                $info = 'b'; // Block special
            } elseif ($fileType === 0x4000) {
                $info = 'd'; // File directory
            } elseif ($fileType === 0x2000) {
                $info = 'c'; // Character special
            } elseif ($fileType === 0x1000) {
                $info = 'p'; // FIFO pipe
            } else {
                $info = 'u'; // Unknown
            }
            // Owner permissions
            $info .= (($perms & 0x0100) ? 'r' : '-') . (($perms & 0x0080) ? 'w' : '-') . (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
            // Group permissions
            $info .= (($perms & 0x0020) ? 'r' : '-') . (($perms & 0x0010) ? 'w' : '-') . (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
            // Others permissions
            $info .= (($perms & 0x0004) ? 'r' : '-') . (($perms & 0x0002) ? 'w' : '-') . (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
        }
        return $info;
    }

    /**
     * Get the permission of a file
     * @param string $path The path to the file or directory
     * @return string
     */
    public static function getPermission(string $path): string {
        if (self::isExists($path) && self::isInt(@fileperms(self::resolvePath($path)))) {
            return substr(sprintf('%o', @fileperms(self::resolvePath($path))), -4);
        }
        return "";
    }

    /**
     * Get directory name of a file or directory
     *
     * @param string $path
     * @return string
     */
    public static function getFilename(string $path): string {
        return isset(pathinfo($path)['dirname']) ? pathinfo($path)['dirname'] : $path;
    }

    /**
     * Get the owner of the file
     * @param string $file
     * @return int|false
     */
    public static function getOwner(string $file): int|false {
        return @fileowner($file);
    }

    /**
     * Get the group of the file
     * @param string $file
     * @return int|false
     */
    public static function getGroup(string $file): int|false {
        return @filegroup($file);
    }

    /**
     * Get the inode number of the file
     * @param string $file
     * @return int|false
     */
    public static function getInode(string $file): int|false {
        return @fileinode($file);
    }

    /**
     * Get the link target of the file
     * @param string $file
     * @return string|false
     */
    public static function getSymLinkTarget(string $file): string|false {
        return @readlink($file);
    }

    /**
     * Get the real path of the file
     * @param string $file
     * @return string|false
     */
    public static function getRealPath(string $file): string|false {
        return @self::resolvePath($file);
    }

    /**
     * Get the owner name of the file
     * @param string $file
     * @return mixed
     */
    public static function getOwnerName(string $file): mixed {
        return function_exists("posix_getpwuid") ? @posix_getpwuid(self::getOwner($file))['name'] : self::getOwner($file);
    }

    /**
     * Get the group name of the file
     * @param string $file
     * @return mixed
     */
    public static function getGroupName(string $file): mixed {
        return function_exists("posix_getpwuid") ? @posix_getgrgid(self::getGroup($file))['name'] : self::getGroup($file);
    }

    /**
     * Changes file group
     * @param string $file
     * @param string|int $group
     * @return bool
     */
    public static function changeGroup(string $file, string|int $group): bool {
        if (File::isFile($file)) {
            return @chgrp($file, $group);
        }
        return false;
    }

    /**
     * Changes file owner
     * @param string $file
     * @param string|int $owner
     * @return bool
     */
    public static function changeOwner(string $file, string|int $owner = ''): bool {
        if (File::isFile($file)) {
            return @chown($file, $owner);
        }
        return false;
    }

    /**
     * Matches the given filename
     * @param string $filename
     * @param string $pattern
     * @return bool
     */
    public static function matchFilename(string $filename, string $pattern): bool {
        $inverted = false;
        if ($pattern[0] == '!') {
            $pattern = substr($pattern, 1);
            $inverted = true;
        }
        return fnmatch($pattern, $filename) == ($inverted ? false : true);
    }

    /**
     * Convert a path name extension to either lowercase or uppercase
     * @param string $path
     * @param bool $toLowercase
     * @return string
     */
    public static function convertExtension(string $path, bool $toLowercase = true): string {
        $extension = File::getExtension($path);
        if (self::isNotEmptyString($extension)) {
            return File::removeExtension($path) . "." . ($toLowercase ? strtolower($extension) : strtoupper($extension));
        }
        return $path;
    }

    /**
     * Safe base64 encode a string
     * @param string $string
     * @return string
     */
    public static function safeEncode(string $string): string {
        return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
    }

    /**
     * Safe base64 decode a string
     * @param string $string
     * @return string
     */
    public static function safeDecode(string $string): string {
        return @base64_decode(str_pad(strtr($string, '-_', '+/'), (strlen($string) % 4), '=', STR_PAD_RIGHT));
    }

    /**
     * Strip tags and convert special characters to HTML entities
     * @param string $string
     * @return string
     */
    public static function clean(string $string): string {
        return strip_tags(htmlspecialchars($string));
    }

    /**
     * Replace invalid characters @see INVALID_CHARS
     * It's case-insensitive
     * @param string $string
     * @return string
     */
    public static function replaceInvalidChars(string $string): string {
        return isset($string) ? str_ireplace(self::INVALID_CHARS, array('-'), $string) : false;
    }

    /**
     * Remove special characters from string
     * @param string|null $string
     * @return string
     */
    public static function removeSpecialChars(?string $string = null): string {
        return isset($string) ? preg_replace('/[^A-Za-z0-9]/', '', $string) : false;
    }

    /**
     * Get the protocol (http or https)
     * @return string
     */
    public static function protocol(): string {
        return getenv("HTTPS") !== null && getenv("HTTPS") === 'on' ? "https" : "http";
    }

    /**
     * Get the server protocol (HTTP/1.1)
     * @return string
     */
    public static function serverProtocol(): string {
        return getenv("SERVER_PROTOCOL");
    }

    /**
     *
     * @return string
     * @return stringGet the host (localhost)
     * @return string
     */
    public static function host(): string {
        return getenv('HTTP_HOST');
    }

    /**
     * Get the http referer
     * @return string
     */
    public static function referer(): string {
        return getenv("HTTP_REFERER");
    }

    /**
     * Get the server name (localhost)
     * @return string
     */
    public static function serverName(): string {
        return getenv("SERVER_NAME");
    }

    /**
     * Get the php self value (/index.php)
     * @return string
     */
    public static function self(): string {
        return getenv("PHP_SELF");
    }

    /**
     * Get the script filename (C:/xampp/htdocs/index.php)
     * @return string
     */
    public static function scriptFilename(): string {
        return getenv("SCRIPT_FILENAME");
    }

    /**
     * Get the script filename (/index.php)
     * @return string
     */
    public static function scriptName(): string {
        return getenv("SCRIPT_NAME");
    }

    /**
     * Get the unix timestamp
     * @return int
     */
    public static function unixTimestamp(): int {
        return time();
    }

    /**
     * Get the current url
     * @return string
     */
    public static function currentUrl(): string {
        return self::protocol() . "://" . self::host();
    }

    /**
     * Get the complete current url with referer
     * @return string
     */
    public static function completeCurrentUrl(): string {
        return self::currentUrl() . Path::left_delete_dir_separator(self::requestURI());
    }

    /**
     * Get the http user agent (Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36)
     * @return string
     */
    public static function userAgent(): string {
        return getenv("HTTP_USER_AGENT");
    }

    /**
     * Check if ssl is active
     * @return bool
     */
    public static function sslActive(): bool {
        return (getenv('HTTPS') == 'on' || getenv('HTTPS') == '1' || getenv('SERVER_PORT') == '443');
    }

    /**
     * Creates a new session variable
     *
     * @param string $name  The name of the session variable
     * @param string $value The value of the session variable
     * @return bool Whether the session variable was successfully created
     */
    public function createSession(string $name, string $value): bool {
        // Check if the session is already started
        if (isset($_SESSION)) {
            // Set the session variable
            $_SESSION[$name] = $value;
        }
        // Return true if the session variable was successfully created
        return isset($_SESSION) && isset($_SESSION[$name]);
    }

    /**
     * Deletes a session variable
     *
     * @param string $name The name of the session variable to delete
     * @return bool Whether the session variable was successfully deleted
     */
    public function deleteSession(string $name): bool {
        // Check if the session is already started and the variable exists
        if (isset($_SESSION) && isset($_SESSION[$name])) {
            // Unset the session variable
            unset($_SESSION[$name]);
        }
        // Return true if the session variable was successfully deleted
        return !isset($_SESSION) || !isset($_SESSION[$name]);
    }

    /**
     * Gets the value of a session variable
     *
     * @param string $name The name of the session variable
     * @return string The value of the session variable, or an empty string if it doesn't exist
     */
    public function getSessionValue(string $name): string {
        // Check if the session variable exists
        if (isset($_SESSION) && isset($_SESSION[$name])) {
            // Return the value of the session variable
            return (string) $_SESSION[$name];
        }
        // Return an empty string if the session variable doesn't exist
        return "";
    }

    /**
     * Determine if argument is a function
     * @param mixed $arg
     * @return bool
     */
    public static function isFunction(mixed $arg): bool {
        return ($arg instanceof \Closure) && is_callable($arg);
    }

    /**
     * Get the request url
     * @return string
     */
    public static function requestURI(): string {
        if (getenv('REQUEST_URI') !== null) {
            return getenv('REQUEST_URI');
        } elseif (getenv('SCRIPT_NAME') !== null) {
            return getenv('SCRIPT_NAME') . (empty(getenv('QUERY_STRING')) ? '' : '?' . getenv('QUERY_STRING'));
        } elseif (getenv('PHP_SELF') !== null) {
            return getenv('PHP_SELF') . (empty(getenv('QUERY_STRING')) ? '' : '?' . getenv('QUERY_STRING'));
        }
        return '';
    }

    /**
     * Convert array to json
     * @param array $array
     * @return string
     */
    public static function arrayToJson(array $array): string {
        return json_encode($array, JSON_FORCE_OBJECT);
    }

    /**
     * Convert string to json
     * @param string $string
     * @return string
     */
    public static function stringToJson(string $string): string {
        return json_encode($string, JSON_FORCE_OBJECT, 2147483646);
    }

    /**
     * Convert json to array
     * @param string $json
     * @return array
     */
    public static function jsonToArray(string $json): array {
        $decoded = json_decode($json, true, 2147483646, JSON_OBJECT_AS_ARRAY);
        return \is_array($decoded) ? $decoded : array();
    }

    /**
     * Convert array to string
     * @param array $array
     * @param string $separator The character to use as the separator
     * @return string
     */
    public static function arrayToString(array $array, string $separator = ", "): string {
        return self::isArray($array) ? implode($separator, $array) : "";
    }

    /**
     * Base64 encode a string and remove it padding
     * @param string $data
     * @return string
     */
    public static function base64_encode_no_padding(string $data): string {
        $encoded = base64_encode($data);
        return rtrim($encoded, '=');
    }

    /**
     * Base64 decode an encoded string from base64_encode_no_padding @see base64_encode_no_padding
     * @param string $string
     * @return string
     */
    public static function base64_decode_no_padding(string $string): string {
        // Add padding back if necessary
        $length = strlen($string) % 4;
        if ($length > 0) {
            $string .= str_repeat('=', 4 - $length);
        }
        return base64_decode($string);
    }

    /**
     * Base64 encode a string by replacing \n with \r\n
     * @param string $string
     * @return string
     */
    public static function base64_encode_crlf(string $string): string {
        $encoded = base64_encode($string);
        return str_replace("\n", "\r\n", $encoded);
    }

    /**
     * Base64 decode an encoded string from base64_encode_crlf @see base64_encode_crlf
     * @param string $string
     * @return string
     */
    public static function base64_decode_crlf(string $string): string {
        $string = str_replace("\r\n", "\n", $string);
        return base64_decode($string);
    }

    /**
     * Base64 encode a string making it url safe
     * @param string $string
     * @return string
     */
    public static function base64_encode_url_safe(string $string): string {
        $encoded = strtr(base64_encode($string), '+/', '-_');
        return rtrim($encoded, '=');
    }

    /**
     * Base64 decode an encoded string from base64_encode_url_safe @see base64_encode_url_safe
     * @param string $string
     * @return string
     */
    public static function base64_decode_url_safe(string $string): string {
        $string = strtr($string, '-_', '+/');
        return base64_decode($string);
    }

    /**
     * Base64 encode a string into one line
     * @param string $string
     * @return string
     */
    public static function base64_encode_no_wrap(string $string): string {
        $encoded = base64_encode($string);
        return str_replace("\n", '', $encoded);
    }

    /**
     * Base64 decode an encoded string from base64_encode_no_wrap @see base64_encode_no_wrap
     * @param string $string
     * @return string
     */
    public static function base64_decode_no_wrap(string $string): string {
        $string = str_replace("\n", '', $string);
        return base64_decode($string);
    }

    /**
     * Set file or directory permission
     * @param string $path
     * @param bool $recursive
     * @return bool
     */
    public static function setPermissions(string $path, bool $recursive = false): bool {
        if (File::isFile($path)) {
            $path = self::resolvePath($path);
            return @chmod($path, self::FILE_PERMISSION);
        } elseif (File::isFile($path)) {
            if (self::isTrue($recursive)) {
                $i = new \RecursiveIteratorIterator(new \RecursiveFileectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($i as $list) {
                    $list = self::resolvePath($list->getRealPath());
                    if (File::isFile($list)) {
                        @chmod($list, self::FILE_PERMISSION);
                    } elseif (File::isFile($list)) {
                        @chmod($list, self::DIRECTORY_PERMISSION);
                    }
                }
            }
            return @chmod($path, self::DIRECTORY_PERMISSION);
        }
        return false;
    }

    /**
     * Delete a file or directory
     * @param string $path
     * @return bool
     */
    public static function delete(string $path): bool {
        if (\is_file($path)) {
            return File::deleteFile($path);
        } elseif (\is_dir($path)) {
            return File::deleteFile($path);
        }
        return false;
    }

    /**
     * Set mb internal encoding
     * @statical array $encodings
     * @param bool $reset Wether to reset
     * @param string $internalEncoding The internal encoding to set to
     * @return void
     */
    public static function setMBInternalEncoding($reset = false, ?string $internalEncoding = \null): void {
        if (self::isTrue(function_exists('mb_internal_encoding'))) {
            static $encodings = [];
            $overloaded = (bool) ((int) ini_get('mbstring.func_overload') & 2);
            if (!$overloaded) {
                if (!$reset) {
                    $encoding = mb_internal_encoding();
                    array_push($encodings, $encoding);
                    mb_internal_encoding(is_string($internalEncoding) ? $internalEncoding : 'ISO-8859-1');
                } elseif ($reset && $encodings) {
                    $encoding = array_pop($encodings);
                    mb_internal_encoding($encoding);
                }
            }
        }
    }

    /**
     * Check if headers are sent to browser
     * @return bool
     */
    public static function headersSent(): bool {
        if (headers_sent() === true) {
            return true;
        }
        return false;
    }

    /**
     * Generate a random unique string
     * @param string $which If value is not 'key' an id will be generated, else random characters
     * @return string
     */
    public static function randUnique(string $which = "key"): string {
        if (strtolower($which) == 'key') {
            return hash_hmac('sha256', bin2hex(random_bytes(16)), '');
        }
        return str_shuffle(mt_rand(100000, 999999) . self::unixTimestamp());
    }

    /**
     * Get the request header by key
     * @param string $key
     * @return string
     */
    public static function getHeader(string $key): string {
        $heades = getallheaders();
        if (isset($heades[$key])) {
            return (string) $heades[$key];
        }
        return "";
    }

    /**
     * Get the authorization header
     * @return string
     */
    public static function getAuthorizationHeader(): string {
        return self::getHeader("Authorization");
    }

    /**
     * Parse a json string
     * @param string $string
     * @return string
     * @throws \Exception
     */
    public static function parseJSON(string $string): string {
        $parsed = @json_decode($string ?: '{}');
        $errors = array(
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
            JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX => 'Syntax error',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
            JSON_ERROR_RECURSION => 'One or more recursive references in the value to be encoded',
            JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given',
            JSON_ERROR_INVALID_PROPERTY_NAME => 'A property name that cannot be encoded was given',
            JSON_ERROR_UTF16 => 'Malformed UTF-16 characters, possibly incorrectly encoded',
        );
        if (isset($errors[json_last_error()])) {
            throw new \Exception($errors[json_last_error()]);
        }
        return $parsed;
    }

    /**
     * Scale a width
     * @param int $originalWidth
     * @param int $originalHeight
     * @param int $width
     * @param int $height
     * @return array
     */
    public static function scaleIDemention(int $originalWidth, int $originalHeight, int $width, int $height): array {
        if ($originalWidth > $width && ($originalWidth / $width) > ($originalHeight / $height)) {
            $width = $originalWidth * ($width / $originalWidth);
            $height = $originalHeight * ($height / $originalWidth);
        } elseif ($originalHeight > $height) {
            $width = $originalWidth * ($height / $originalHeight);
            $height = $originalHeight * ($height / $originalHeight);
        } else {
            $height = $originalHeight;
            $width = $originalWidth;
        }
        return [round($width), round($height)];
    }

    /**
     * Convert an image
     * @param string $source
     * @param string $extension
     * @param bool $useWandH
     * @param bool $scaleIDemention
     * @param string $file If empty, the source will be used
     * @return bool
     */
    public static function convertImage(string $source, string $extension = "webp", bool $useWandH = false, bool $scaleIDemention = false, string $file = ""): bool {
        $validExtensions = array("webp", "png", "jpg", "gif");
        if (in_array($extension, $validExtensions)) {
            $sourceData = @getimagesize($source);
            if (self::isNotFalse($sourceData)) {
                $width = $sourceData[0];
                $height = $sourceData[1];
                $mime = $sourceData['mime'];
                $image = false;
                switch ($mime) {
                    case 'image/jpeg':
                    case 'image/jpg':
                        $image = @imagecreatefromjpeg($source);
                        break;
                    case 'image/png':
                        $image = @imagecreatefrompng($source);
                        break;
                    case 'image/webp':
                        $image = @imagecreatefromwebp($source);
                        break;
                    case 'image/gif':
                        $image = @imagecreatefromgif($source);
                        break;
                    default:
                        $image = false;
                }
                if (self::isNotFalse($image)) {
                    $imageWidth = $useWandH ? $width : self::IMAGE_WIDTH;
                    $imageHeight = $useWandH ? $height : self::IMAGE_HEIGHT;
                    if ($scaleIDemention) {
                        list($imageWidth, $imageHeight) = self::scaleIDemention($width, $height, $imageWidth, $imageHeight);
                    }
                    $color = @imagecreatetruecolor($imageWidth, $imageHeight);
                    if (self::isNotFalse($color)) {
                        @imagecopyresampled($color, $image, 0, 0, 0, 0, $imageWidth, $imageHeight, $width, $height);
                        $outputPath = self::isNotEmptyString($file) ? $file : $source;
                        $saved = false;
                        switch ($extension) {
                            case "webp":
                                $saved = @imagewebp($color, $outputPath, 100);
                                break;
                            case "png":
                                $saved = @imagepng($color, $outputPath, 0);
                                break;
                            case "jpg":
                                $saved = @imagejpeg($color, $outputPath);
                                break;
                            default:
                                $saved = @imagegif($color, $outputPath);
                        }
                        @imagedestroy($color);
                        return $saved;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Hash a string
     * @param string $string
     * @return string
     */
    public static function hashString(string $string): string {
        return password_hash($string, PASSWORD_BCRYPT, array('cost' => 12));
    }

    /**
     * Check if string is the same with $hash when hashed by verifying it
     * @param string $string
     * @param string $hash
     * @return bool
     */
    public static function hashVerified(string $string, string $hash): bool {
        return password_verify($string, $hash);
    }

    /**
     * Check if the hash needs to be re-hashed
     * @param string $hash
     * @return bool
     */
    public static function hashNeedsRehash(string $hash): bool {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, array('cost' => 12));
    }

    /**
     * Checks if a string contains a specified substring.
     * @param string $substring The substring to search for.
     * @param string $string The string to search within.
     * @return bool True if $substring is found in $string, false otherwise.
     */
    public static function containText(string $substring, string $string): bool {
        return strpos($string, $substring) !== false;
    }

    /**
     * Checks if a string starts with a specified substring.
     * @param string $substring The substring to check for at the beginning of $string.
     * @param string $string The string to check.
     * @return bool True if $string starts with $start, false otherwise.
     */
    public static function startsWith(string $substring, string $string): bool {
        $substring = trim($substring);
        return strncmp($string, $substring, strlen($substring)) === 0;
    }

    /**
     * Checks if a string ends with a specified substring.
     * @param string $substring The substring to check for at the end of $string.
     * @param string $string The string to check.
     * @return bool True if $string ends with $end, false otherwise.
     */
    public static function endsWith(string $substring, string $string): bool {
        $substring = trim($substring);
        return strrpos($string, $substring) === (strlen($string) - strlen($substring));
    }

    /**
     * Format a bytes to human readable
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public static function formatSize(int $bytes, int $precision = 2): string {
        if ($bytes > 0) {
            $base = log($bytes, 1024);
            $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
            return round(pow(1024, ($base - floor($base))), $precision) . ' ' . $suffixes[floor($base)];
        }
        return "0 B";
    }

    /**
     * Hide email with starts
     * @param string $email
     * @return string
     */
    public static function hideEmailWithStarts(string $email): string {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $explodedEmail = explode("@", $email);
            $name = implode("@", array_slice($explodedEmail, 0, count($explodedEmail) - 1));
            $len = floor(strlen($name) / 2);
            return substr($name, 0, $len) . str_repeat("*", $len) . "@" . end($explodedEmail);
        }
        return $email;
    }

    /**
     * Generates a random text of the specified length.
     * 
     * @param int $length The length of the random text. Defaults to 10.
     * @return string The generated random text.
     */
    public static function generateRandomText(int $length = 10): string {
        // Define the characters to use for the random text
        $characters = md5('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        // Initialize the random text
        $random = '';
        // Ensure the length is within the valid range (10-200)
        $length = $length < 10 ? 10 : min(200, $length);
        // Generate the random text
        for ($i = 0; $i < $length; $i++) {
            // Select a random character from the defined characters
            $random .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $random;
    }

    /**
     * Download a file
     * @param string $file
     * @return bool
     */
    public static function downloadFile(string $file): bool {
        if (File::isFile($file) && self::headersSent() !== true) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . File::getFIlesizeInBytes($file));
            flush();
            readfile($file);
            return true;
        }
        return false;
    }

    /**
     * Generate a unique id
     * @return int
     */
    public static function generateUniqueId(): int {
        $uId = str_shuffle(mt_rand(100000, 999999) . self::unixTimestamp());
        return substr($uId, 0, 10);
    }

    /**
     * Create a stream context
     * @param string $context Only 'http, 'curl', 'ssl'' are supported
     * @param string $method
     * @return mixed
     */
    public static function createStreamContext(string $context = "http", string $method = "HEAD"): mixed {
        $options = array();
        if ($context == "http" or $context == "curl") {
            if ($context == "http") {
                $options['http'] = array('user_agent ' => self::USER_AGENT, 'method' => $method, 'max_redirects' => 0, 'ignore_errors' => true, 'timeout' => 3, 'follow_location' => 0);
            } else {
                $options['curl'] = array('user_agent ' => self::USER_AGENT, 'method' => $method, 'max_redirects' => 0, 'curl_verify_ssl_host' => false, "curl_verify_ssl_peer" => false);
            }
            $options['ssl'] = array('verify_peer' => false, 'verify_peer_name' => false, 'disable_compression' => true);
        }
        return stream_context_create($options);
    }

    /**
     * Check if a remote file exists
     * @param string $remoteFilename
     * @return bool
     */
    public static function remoteFileExist(string $remoteFilename): bool {
        try {
            $getFile = @file_get_contents($remoteFilename, false, self::createStreamContext(), 0, 5);
            if (self::isNotFalse($getFile)) {
                return true;
            }
        } catch (\Throwable $e) {
        }
        return false;
    }

    /**
     * Get the host name from a url
     * @param string $url
     * @param bool $includeProtocol
     * @return string
     */
    public static function getHostFromUrl(string $url, bool $includeProtocol = true): string {
        $parsedUrl = parse_url($url);
        $scheme  = $includeProtocol ? (\is_array($parsedUrl) ? (isset($parsedUrl['scheme']) ? (string) $parsedUrl['scheme'] : "http") : "") : "";
        $host = \is_array($parsedUrl) ? (isset($parsedUrl['host']) ? (string) $parsedUrl['host'] : "") : "";
        return $scheme . (!empty($scheme) ? "://" : "") . $host;
    }

    /**
     * Get the path name from a url
     * @param string $url
     * @return string
     */
    public static function getPathFromUrl(string $url): string {
        $parsedUrl = parse_url($url);
        $path  = \is_array($parsedUrl) ? (isset($parsedUrl['path']) ? (string) $parsedUrl['path'] : "") : "";
        return $path;
    }

    /**
     * Get the query string name from a url
     * @param string $url
     * @return string
     */
    public static function getQueryStringFromUrl(string $url): string {
        $parsedUrl = parse_url($url);
        $query  = \is_array($parsedUrl) ? (isset($parsedUrl['query']) ? (string) $parsedUrl['query'] : "") : "";
        return $query;
    }

    /**
     * Build a query param from a query string
     * @param string $queryString
     * @return array
     */
    public static function buildQueryParamFromQueryString(string $string): array {
        parse_str(parse_url($string, PHP_URL_QUERY), $param);
        return $param;
    }

    /**
     * Check if string is empty
     * @param string $string
     * @return bool
     */
    public static function isEmptyString(string $string): bool {
        return self::isString($string) && self::isEmpty($string);
    }

    /**
     * Check if string is not empty
     * @param string $string
     * @return bool
     */
    public static function isNotEmptyString(string $string): bool {
        return self::isString($string) && self::isNotEmpty($string);
    }

    /**
     * Check if argument is a string
     * @param mixed $arg
     * @return bool
     */
    public static function isString(mixed $arg): bool {
        return is_string($arg);
    }

    /**
     * Check if argument is not a string
     * @param mixed $arg
     * @return bool
     */
    public static function isNotString(mixed $arg): bool {
        return !is_string($arg);
    }

    /**
     * Determine if argument is empty
     * @param mixed $arg
     * @return bool
     */
    public static function isEmpty(mixed $arg): bool {
        return @empty($arg);
    }

    /**
     * Determine if argument is not empty
     * @param mixed $arg
     * @return bool
     */
    public static function isNotEmpty(mixed $arg): bool {
        return !self::isEmpty($arg);
    }

    /**
     * Checks if a $value is in an $array
     * @param mixed $value
     * @param array $array
     * @return bool
     */
    public static function inArray(mixed $value, array $array): bool {
        return @in_array($value, $array);
    }

    /**
     * Checks if a $value is not in an $array
     * @param mixed $value
     * @param array $array
     * @return bool
     */
    public static function isNotInArray(mixed $value, array $array): bool {
        return !self::inArray($value, $array);
    }

    /**
     * Check whether the argument is an array
     * @param mixed $arg
     * @return bool
     */
    public static function isArray(mixed $arg): bool {
        return is_array($arg);
    }

    /**
     * Check whether the argument is not an array
     * @param mixed $arg
     * @return bool
     */
    public static function isNotArray(mixed $arg): bool {
        return !self::isArray($arg);
    }

    /**
     * Check is array is empty
     * @param array $array
     * @return bool
     */
    public static function isEmptyArray(array $array): bool {
        return self::isArray($array) && self::isEmpty($array);
    }

    /**
     * Check is array is not empty
     * @param array $array
     * @return bool
     */
    public static function isNotEmptyArray(array $array): bool {
        return !self::isEmptyArray($array);
    }

    /**
     * Check if argument is Boolean
     * @param mixed $arg
     * @return bool
     */
    public static function isBool(mixed $arg): bool {
        return @is_bool($arg);
    }

    /**
     * Check if argument is not Boolean
     * @param mixed $arg
     * @return bool
     */
    public static function isNotBool(mixed $arg): bool {
        return !self::isBool($arg);
    }

    /**
     * Check if argument is Integer
     * @param mixed $arg
     * @return bool
     */
    public static function isInt(mixed $arg): bool {
        return @is_int($arg);
    }

    /**
     * Check if argument is not Integer
     * @param mixed $arg
     * @return bool
     */
    public static function isNotInt(mixed $arg): bool {
        return !self::isInt($arg);
    }

    /**
     * Check if argument is Null
     * @param mixed $arg
     * @return bool
     */
    public static function isNull(mixed $arg): bool {
        return @is_null($arg);
    }

    /**
     * Check if argument is not Null
     * @param mixed $arg
     * @return bool
     */
    public static function isNonNull(mixed $arg): bool {
        return !self::isNull($arg);
    }

    /**
     * Check if argument is the true value of True
     * @param mixed $arg
     * @return bool
     */
    public static function isTrue(mixed $arg): bool {
        return $arg === true;
    }

    /**
     * Check if argument is not the true value of True
     * @param mixed $arg
     * @return bool
     */
    public static function isNotTrue(mixed $arg): bool {
        return !self::isTrue($arg);
    }

    /**
     * Check if argument is the true value of False
     * @param mixed $arg
     * @return bool
     */
    public static function isFalse(mixed $arg): bool {
        return $arg === false;
    }

    /**
     * Check if argument is not the true value of False
     * @param mixed $arg
     * @return bool
     */
    public static function isNotFalse(mixed $arg): bool {
        return !self::isFalse($arg);
    }

    /**
     * Check if argument is Float
     * @param mixed $arg
     * @return bool
     */
    public static function isFloat(mixed $arg): bool {
        return @is_float($arg);
    }

    /**
     * Check if argument is not Float
     * @param mixed $arg
     * @return bool
     */
    public static function isNotFloat(mixed $arg): bool {
        return !self::isFloat($arg);
    }

    /**
     * Check if argument is Numeric
     * @param mixed $arg
     * @return bool
     */
    public static function isNumeric(mixed $arg): bool {
        return @is_numeric($arg);
    }

    /**
     * Check if argument is not Float
     * @param mixed $arg
     * @return bool
     */
    public static function isNotNumeric(mixed $arg): bool {
        return !self::isNumeric($arg);
    }

    /**
     * Check if argument is Resource
     * @param mixed $arg
     * @return bool
     */
    public static function isResource(mixed $arg): bool {
        return @is_resource($arg);
    }

    /**
     * Check if argument is not Resource
     * @param mixed $arg
     * @return bool
     */
    public static function isNotResource(mixed $arg): bool {
        return !self::isResource($arg);
    }

    /**
     * Get file size in bytes
     * @param string $file
     * @return int|false
     */
    public static function getSize(string $file): int|false {
        return @filesize($file);
    }

    /**
     * Get file modification time
     * @param string $file
     * @return int|false
     */
    public static function getMtime(string $file): int|false {
        return @filemtime($file);
    }

    /**
     * Get file mime content type
     * @param string $file
     * @return string|false
     */
    public static function getMime(string $file): string|false {
        return @mime_content_type($file);
    }

    /**
     * Checks whether a file or directory exists
     * @param string $file
     * @return bool
     */
    public static function isExists(string $file): bool {
        return @file_exists($file);
    }

    /**
     * Tells whether a file exists and is readable
     * @param string $file
     * @return bool
     */
    public static function isReadable(string $file): bool {
        return @is_readable($file);
    }

    /**
     * Tells whether the filename is executable
     * @param string $file
     * @return bool
     */
    public static function isExecutable(string $file): bool {
        return @is_executable($file);
    }

    /**
     * Tells whether the filename is writable
     * @param string $file
     * @return bool
     */
    public static function isWritable(string $file): bool {
        return @is_writable($file);
    }

    /**
     * Load an extension
     * @param string $extension
     * @return bool
     */
    public static function loadExtension(string $extension): bool {
        if (extension_loaded($extension)) {
            return true;
        }
        if (function_exists('dl') === false || ini_get('enable_dl') != 1) {
            return false;
        }
        if (strtolower(substr(PHP_OS, 0, 3)) === "win") {
            $suffix = ".dll";
        } elseif (PHP_OS == 'HP-UX') {
            $suffix = ".sl";
        } elseif (PHP_OS == 'AIX') {
            $suffix = ".a";
        } elseif (PHP_OS == 'OSX') {
            $suffix = ".bundle";
        } else {
            $suffix = '.so';
        }
        return @dl('php_' . $extension . '' . $suffix) || @dl($extension . '' . $suffix);
    }

    /**
     * Clear the browser cache
     * @return bool
     */
    public static function clearCache(): bool {
        try {
            @header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
            @header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            @header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            @header("Cache-Control: post-check=0, pre-check=0", false);
            @header("Pragma: no-cache");
            return true;
        } catch (\Throwable $e) {
        }
        return false;
    }

    /**
     * Format a number
     * @param float $num
     * @return float|string
     */
    public static function formatInt(float $num): float|string {
        if ($num > 0 && self::inArray("NumberFormatter", get_declared_classes())) {
            $formater = new \NumberFormatter('en_US', \NumberFormatter::PADDING_POSITION);
            return $formater->format($num);
        }
        return $num;
    }

    /**
     * Replace GET parameter with value
     * @param string $param
     * @param mixed $value
     * @return string
     */
    public static function replaceUrlParamValue(string $param, mixed $value): string {
        $currentUrl = \str_replace("\\", "/", self::completeCurrentUrl());
        $parts = parse_url($currentUrl);
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $path =  isset($parts['path']) ? $parts['path'] : "";
        parse_str(isset($parts['query']) ? $parts['query'] : "", $params);
        $params[$param] = $value;
        $newParams = http_build_query($params);
        $path = str_replace(array("//", "\\\\", "\\",  "/\\", "\\/"), "/", $path);
        return $scheme . '://' . $host . '' . $path . '?' . $newParams;
    }

    /**
     * Reverse a string
     * @param string $string
     * @return string
     */
    public static function reverseString(string $string): string {
        return strrev($string);
    }

    /**
     * MB revers a string
     * @param string $string
     * @param string|null $encoding
     * @return string
     */
    public static function mb_reverseString(string $string, ?string $encoding = null): string {
        $chars = mb_str_split($string, 1, $encoding ?: mb_internal_encoding());
        return implode('', array_reverse($chars));
    }

    /**
     * Gets only numbers from string
     * @param string $string
     * @return string|array|null
     */
    public static function onlyDigits(string $string): string|array|null {
        return self::isString($string) ? @preg_replace('/[^0-9]/', '', $string) : null;
    }

    /**
     * Get only string from string
     * @param string $string
     * @return string|array|null
     */
    public static function onlyString(string $string): string|array|null {
        return self::isString($string) ? @preg_replace('/[0-9]/', '', $string) : null;
    }

    /**
     * The current path url
     * @return string
     */
    public static function currentPathURL(): string {
        $ccUrl = self::completeCurrentUrl();
        $parse = parse_url($ccUrl);
        $scheme = $parse['scheme'];
        $host = $parse['host'];
        $path = Path::arrange_dir_separators_v2($parse['path'], true);
        return $scheme . '://' . $host . '' . $path;
    }

    /**
     * Convert special characters to HTML entities
     * @param string $string
     * @param string $encoding
     * @return string
     */
    public static function xssafe(string $string, string $encoding = 'UTF-8'): string {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML401, $encoding);
    }

    /**
     * Create a temporary filename
     * @param string $extension
     * @param string $prepend The text to append to the filename
     * @param string $append The text to prepend to the filename
     * @return string|false
     */
    public static function createTemporaryFilename(string $extension, string $prepend = "", string $append = ""): string|false {
        $extension = self::isNotEmptyString($extension) ? $extension : 'tmp';
        $prepend = self::isNotEmptyString($prepend) ? $prepend . '_' : '';
        $append = self::isNotEmptyString($append) ? '_' . $append : '';
        $path = Path::insert_dir_separator(sys_get_temp_dir());
        $file = $path . '' . $prepend . '' . substr(self::randUnique("key"), 0, 16) . '' . $append . '.' . $extension;
        return File::createFile($file) ? $file : false;
    }

    /**
     * Create a random file basename
     * @param string $extension
     * @param string $prepend The text to append to the filename
     * @param string $append The text to prepend to the filename
     * @return string|false
     */
    public static function generateRandomFilename(string $extension, string $prepend = "", string $append = ""): string|false {
        $extension = self::isNotEmptyString($extension) ? $extension : 'tmp';
        $prepend = self::isNotEmptyString($prepend) ? $prepend . '_' : '';
        $append = self::isNotEmptyString($append) ? '_' . $append : '';
        return $prepend . '' . substr(self::randUnique("key"), 0, 16) . '' . $append . '.' . $extension;
    }

    /**
     * Execute a command
     * @param string $command
     * @return array|string
     */
    public static function executeCommand(string $command): array|string {
        $output = "";
        if (self::isNotEmptyString($command)) {
            $command = escapeshellcmd($command);
            $output = self::executeCommandUsingExec($command);
        }
        return $output;
    }

    /**
     * Execute a command using popen
     * @param string $command
     * @return string
     */
    public static function executeCommandUsingPopen(string $command): string {
        $output = "";
        if (self::isNotEmptyString($command) && function_exists('popen')) {
            $handle = @popen($command, 'r');
            if (self::isResource($handle)) {
                $content = @stream_get_contents($handle);
                if (self::isString($content)) {
                    $output = $content;
                }
                @pclose($handle);
            }
        }
        return $output;
    }

    /**
     * Execute a command using proc_open
     * @param string $command
     * @return string
     */
    public static function executeCommandUsingProcopen(string $command): string {
        $output = "";
        if (self::isNotEmptyString($command) && function_exists('proc_open')) {
            $errorFilename = self::createTemporaryFilename("proc", "execute_command_error_output");
            $descriptorspec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("file", $errorFilename, "a"));
            $process = @proc_open($command, $descriptorspec, $pipes);
            if (is_resource($process)) {
                // Writeable handle connected to child stdin
                if (isset($pipes[0])) {
                    @fclose($pipes[0]);
                }
                // Readable handle connected to child stdout
                if (isset($pipes[1])) {
                    $content = @stream_get_contents($pipes[1]);
                    fclose($pipes[1]);
                    if (self::isString($content)) {
                        $output = $content;
                    }
                }
                @proc_close($process);
            }
        }
        return $output;
    }

    /**
     * Execute a command using exec
     * @param string $command
     * @return array
     */
    public static function executeCommandUsingExec(string $command): array {
        $output = array();
        if (self::isNotEmptyString($command) && function_exists('exec')) {
            $content = array();
            $resultcode = 0;
            @exec($command, $content, $resultcode);
            if (self::isArray($content)) {
                $output = array_values($content);
            }
        }
        return $output;
    }

    /**
     * Execute a command using shell_exec
     * @param string $command
     * @return string
     */
    public static function executeCommandUsingShellexec(string $command): string {
        $output = "";
        if (self::isNotEmptyString($command) && function_exists('shell_exec')) {
            $content = shell_exec($command);
            if (self::isString($content)) {
                $output = $content;
            }
        }
        return $output;
    }

    /**
     * Execute a command using system
     * @param string $command
     * @return string
     */
    public static function executeCommandUsingSystem(string $command): string {
        $output = "";
        if (self::isNotEmptyString($command) && function_exists('system')) {
            $resultcode = 0;
            $content = system($command, $resultcode);
            if (self::isString($content)) {
                $output = $content;
            }
        }
        return $output;
    }

    /**
     * Execute a command using passthru
     * @param string $command
     * @return string
     */
    public static function executeCommandUsingPassthru(string $command): string {
        $output = "";
        if (self::isNotEmptyString($command) && function_exists('passthru')) {
            $resultcode = 0;
            ob_start();
            passthru($command, $resultcode);
            $content = ob_get_contents();
            if (self::isString($content)) {
                $output = $content;
            }
            // Use this instead of ob_flush()
            ob_end_clean();
        }
        return $output;
    }

    /**
     * Get the current directory
     * @return string
     */
    public static function getCwd(): string {
        return @getcwd();
    }

    /**
     * Change the current directory
     * @param string $dirname
     * @return bool
     */
    public static function chFile(string $dirname): bool {
        if (File::isFile($dirname)) {
            @chdir($dirname);
        }
        return self::getCwd() === $dirname;
    }

    /**
     * Load a plugin
     * @param string $plugin
     * @return void
     * @throws \Exception
     */
    public static function loadPlugin(string $plugin): void {
        $dirname = Path::insert_dir_separator(Path::arrange_dir_separators_v2(PHPWEBFUSE['DIRECTORIES']['PLUGINS'], true));
        $plugin = Path::arrange_dir_separators_v2($plugin);
        $extension = File::getExtension($plugin);
        $name = self::isNotEmptyString($extension) && strtolower($extension) == "php" ? $plugin : $plugin . '.php';
        $plugin = $dirname . '' . $name;
        if (File::isFile($plugin)) {
            require_once $plugin;
        } else {
            throw new \Exception("The plugin \"" . $plugin . "\" doesn't exist.");
        }
    }

    /**
     * Loaf a library
     * @param string $lib
     * @return void
     * @throws \Exception
     */
    public static function loadLib(string $lib): void {
        $dirname = Path::insert_dir_separator(Path::arrange_dir_separators_v2(PHPWEBFUSE['DIRECTORIES']['LIBRARIES'], true));
        $lib = Path::arrange_dir_separators_v2($lib);
        $extension = File::getExtension($lib);
        $name = self::isNotEmptyString($extension) && strtolower($extension) == "php" ? $lib : $lib . '.php';
        $lib = $dirname . '' . $name;
        if (File::isFile($lib)) {
            require_once $lib;
        } else {
            throw new \Exception("The lib \"" . $lib . "\" doesn't exist.");
        }
    }

    /**
     * Header direct
     * @param string $url
     * @return void
     * @throws \Exception
     */
    public static function directTo(string $url): void {
        if (!headers_sent()) {
            @header("location: " . $url);
            exit;
        } else {
            throw new \Exception("Can't direct to \"" . $url . "\", headers has already been sent.");
        }
    }

    /**
     * Registers an error handler to log errors and display them on the screen.
     *
     * @param string|null $errorDir The directory where error messages should be logged.
     * @param string|null $errorName The name of the error log file.
     */
    public static function registerErrorHandler(?string $errorDir = null, ?string $errorName = null): void {
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($errorDir, $errorName) {
            if (!(error_reporting() & $errno)) {
                // This error code is not included in error_reporting, so let it fall through to the standard PHP error handler
                return false;
            }
            $errstr = htmlspecialchars($errstr);
            echo "<div style='" . self::ERRORS_CSS['error'] . "'>" . $errno . " :: " . (self::isTrue(self::isLocalhost()) ? "<b>Filename >></b> " . $errfile . " <b>Line >></b> " . $errline . " <b>Message >></b> " : "") . "" . $errstr . "</div>";
            $errorDir = is_string($errorDir) && is_dir($errorDir) && is_readable($errorDir) ? Path::insert_dir_separator($errorDir) : null;
            $errorName = is_string($errorName) && !empty($errorName) ? basename($errorName) : null;
            if ($errorDir && $errorName) {
                File::saveContentToFile($errorDir . $errorName, strip_tags($errno . " ::Filename >> " . $errfile . " ::Line >> " . $errline . " ::Message >> " . $errstr . " ::Date >> " . date("F jS, Y", time()) . " @ " . date("h:i A", time())), true, true);
            }
        });
    }

    /**
     * Registers an exception handler to log exceptions and display them on the screen.
     *
     * @param string|null $exceptionDir The directory where exception messages should be logged.
     * @param string|null $exceptionName The name of the exception log file.
     */
    public static function registerExceptionHandler(?string $exceptionDir = null, ?string $exceptionName = null): void {
        set_exception_handler(function (\Throwable $ex) use ($exceptionDir, $exceptionName) {
            echo "<div style='" . self::ERRORS_CSS['exception'] . "'>" . (self::isTrue(self::isLocalhost()) ? "<b>Filename >></b> " . $ex->getFile() . " <b>Line >></b> " . $ex->getLIne() . " <b>Message >></b> " : "") . "" . $ex->getMessage() . "</div>";
            $exceptionDir = is_string($exceptionDir) && is_dir($exceptionDir) && is_readable($exceptionDir) ? Path::insert_dir_separator($exceptionDir) : null;
            $exceptionName = is_string($exceptionName) && !empty($exceptionName) ? basename($exceptionName) : null;
            if ($exceptionDir && $exceptionName) {
                File::saveContentToFile($exceptionDir . $exceptionName, strip_tags("Filename >> " . $ex->getFile() . " ::Line >> " . $ex->getLIne() . " ::Message >> " . $ex->getMessage() . " ::Date >> " . date("F jS, Y", time()) . " @ " . date("h:i A", time())), true, true);
            }
        });
    }

    /**
     * Check if running on a localhost web server
     * @return bool
     */
    public static function isLocalhost(): bool {
        return self::inArray(self::getIPAddress(), (array) self::LOCALHOST_DEFAULT_ADDRESSES);
    }

    /**
     * Validate IPv4 IP address and check if IP address is not in private IP address range @see CHECK_IP_ADDRESS_IN_RANGE
     * @param string $ip
     * @return bool
     */
    public static function validateIPAddress(string $ip): bool {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (self::isTrue(self::CHECK_IP_ADDRESS_IN_RANGE)) {
                foreach (self::PRIVATE_IP_ADDRESS_RANGES as $range) {
                    if (self::isIPInPrivateRange($ip, $range)) {
                        return false;
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Get the IP address
     * @return string
     */
    public static function getIPAddress(): string {
        $headersToCheck = array('HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_X_FORWARDED_HOST', 'REMOTE_ADDR');
        foreach ($headersToCheck as $header) {
            $determinedHeader = getenv($header);
            if (self::isNotEmpty($determinedHeader)) {
                if ($header == "HTTP_X_FORWARDED_FOR") {
                    $ipAddresses = explode(',', $determinedHeader);
                    foreach ($ipAddresses as $realIp) {
                        if (self::validateIPAddress((string) $realIp)) {
                            return $realIp;
                        }
                    }
                } elseif (self::validateIPAddress((string) $determinedHeader)) {
                    return $determinedHeader;
                }
            }
        }
        return "";
    }

    /**
     * Check if IP address is in range of private IP address
     * @param string $ip
     * @param string $range
     * @return bool
     */
    public static function isIPInPrivateRange(string $ip, string $range): bool {
        if (self::isFalse(strpos($range, '/'))) {
            $range .= '/32';
        }
        list($subnet, $mask) = explode('/', $range);
        $subnet = ip2long($subnet);
        $ip = ip2long($ip);
        $mask = -1 << (32 - $mask);
        $subnet &= $mask; // Calculate the base address of the subnet
        return ($ip & $mask) == $subnet;
    }

    /**
     * Get the temporary directory
     * @return string
     */
    public static function getTmpFile(): string {
        return @sys_get_temp_dir();
    }

    /**
     * Get the upload directory
     * @return string
     */
    public static function getUploadFile(): string {
        return @ini_get('upload_tmp_dir');
    }

    /**
     * Get the default directory
     * @return string
     */
    public static function getCurrentFileFile(): string {
        return @dirname(__FILE__);
    }

    /**
     * Gets last access time of file
     * @param string $file
     * @return int
     */
    public static function accessTime(string $file): int {
        if (File::isFile($file)) {
            return @fileatime($file);
        }
        return 0;
    }

    /**
     * Gets file modification time
     * @param string $file
     * @return int
     */
    public static function modificationTime(string $file): int {
        if (File::isFile($file)) {
            return @filemtime($file);
        }
        return 0;
    }

    /**
     * Gets inode change time of file
     * @param string $file
     * @return int
     */
    public static function changeTime(string $file): int {
        if (File::isFile($file)) {
            return @filectime($file);
        }
        return 0;
    }

    /**
     * A readable unix time
     * @param string|int $unix
     * @return string
     */
    public static function readableUnix(string|int $unix): string {
        if (self::isNumeric($unix)) {
            return @date("l, F jS, Y g:i:s A", $unix);
        }
        return "";
    }

    /**
     * Create a hard link
     * @param string $target
     * @param string $link
     * @return bool
     */
    public static function createHardLink(string $target, string $link): bool {
        if (self::isExists($target)) {
            return @link($target, $link);
        }
        return false;
    }

    /**
     * Creates a symbolic link
     * @param string $target
     * @param string $link
     * @return bool
     */
    public static function createSymLink(string $target, string $link): bool {
        if (self::isExists($target)) {
            return @symlink($target, $link);
        }
        return false;
    }

    /**
     * Calculate remaining time from $unix
     * @param int $unix
     * @return int
     */
    public static function calculateRemainingDaysFromUnix(int $unix) {
        $days = 0;
        if ($unix > time()) {
            $devided = (($unix - time()) / 86400);
            return round($devided, 0);
        }
        return $days;
    }

    /**
     * Calculate elapsed time from $unix
     * @param int $unix
     * @return int
     */
    public static function calculateElapsedDaysFromUnix(int $unix) {
        $days = 0;
        if (time() > $unix) {
            $devided = ((time() - $unix) / 86400);
            return round($devided, 0);
        }
        return $days;
    }

    /**
     * Convert image to base64
     * @param string $file
     * @return string
     */
    public static function convertImageToBase64(string $file): string {
        $base64Image = "";
        // Supported image extensions
        $extensions = array('gif', 'jpg', 'jpeg', 'png', 'webp');
        // Check if the file exists and is readable
        if (File::isFile($file) && self::isReadable($file)) {
            // Get the file extension
            $extension = strtolower(File::getExtension($file));
            // Check if the file extension is supported
            if (self::isNotEmptyString($extension) && self::inArray($extension, $extensions)) {
                // Get the image content and encode the image content to base64
                $base64Encode = base64_encode(@\file_get_contents($file));
                // Add the appropriate data URI prefix
                $base64Image = 'data:' . mime_content_type($file) . ';base64,' . $base64Encode;
            }
        }
        return $base64Image;
    }

    /**
     * Validate mobile number. Return false on failure, otherwise a string representing the validated number
     * @param int|string $number
     * @param string $shortcode
     * @return bool|string
     */
    public static function validateMobileNumber(int|string $number, string $shortcode = "ng"): bool|string {
        self::loadPlugin("CountriesList");
        $classesExists = class_exists("\libphonenumber\PhoneNumberUtil") && class_exists("\libphonenumber\PhoneNumberFormat") && class_exists("\libphonenumber\NumberParseException");
        if (class_exists("\CountriesList") && $classesExists && self::isNumeric($number)) {
            $countriesList = new \CountriesList();
            $shortcodes = $countriesList->getCountriesShortCode();
            if (isset($shortcodes[strtoupper($shortcode)])) {
                try {
                    $shortcode = strtoupper($shortcode);
                    $util = \libphonenumber\PhoneNumberUtil::getInstance();
                    $parse = $util->parseAndKeepRawInput($number, $shortcode);
                    $isValid = $util->isValidNumber($parse);
                    return self::isTrue($isValid) ? trim($util->format($parse, \libphonenumber\PhoneNumberFormat::E164)) : false;
                } catch (\libphonenumber\NumberParseException $e) {
                }
            }
        }
        return false;
    }

    /**
     * Initialize new \SleekDB\Store
     * @param string $database
     * @param string $path
     * @param array $options
     * @return \SleekDB\Store|bool
     */
    public static function sleekDatabase(string $database, string $path, array $options = array()): \SleekDB\Store|bool {
        if (self::isNotEmptyString($database) && File::createFile($path) && class_exists("\SleekDB\Store")) {
            $options = self::isEmptyArray($options) ? array('auto_cache' => false, 'timeout' => false, 'primary_key' => 'id', 'folder_permissions' => 0777) : $options;
            return new \SleekDB\Store($database, self::resolvePath($path), $options);
        }
        return false;
    }

    /**
     * Get the audio duration. Returns 00:00:00 on default event if it's not an audio file or doesn't exist
     * @param string $file
     * @return string
     */
    public static function getAudioDuration(string $file): string {
        $duration = "00:00:00";
        if (File::isFile($file)) {
            try {
                $rand = rand(0, 1);
                if ($rand == 0) {
                    if (class_exists("\JamesHeinrich\GetID3\GetID3")) {
                        $getID3 = new \JamesHeinrich\GetID3\GetID3();
                        $analyze = @$getID3->analyze(self::resolvePath($file));
                        if (isset($analyze['playtime_seconds'])) {
                            $duration = gmdate("H:i:s", (int) $analyze['playtime_seconds']);
                        }
                    }
                } else {
                    if (!in_array("\Mp3Info", get_declared_classes())) {
                        self::loadLib("Mp3Info" . DIRECTORY_SEPARATOR . "Mp3Info");
                    }
                    $info = new \Mp3Info(self::resolvePath($file));
                    $duration = gmdate("H:i:s", (int) $info->duration);
                }
            } catch (\Throwable $e) {
            }
        }
        return $duration;
    }

    /**
     * Set audio meta tags
     * @param string $audioname
     * @param string $covername
     * @param array $options
     * @return bool
     */
    public static function setAudioMetaTags(string $audioname, string $covername, array $options = array()): bool {
        if (File::isFile($audioname) && File::isFile($covername)) {
            $audioname = self::resolvePath($audioname);
            $covername = self::resolvePath($covername);
            $classesExists = class_exists("\JamesHeinrich\GetID3\GetID3") && class_exists("\JamesHeinrich\GetID3\WriteTags");
            if (self::isTrue($classesExists) && self::isNotEmptyArray($options)) {
                $getID3 = new \JamesHeinrich\GetID3\GetID3();
                $writer = new \JamesHeinrich\GetID3\WriteTags();
                $encoding = 'UTF-8';
                $getID3->setOption(array('encoding' => $encoding));
                $writer->filename = $audioname;
                $writer->tagformats = array('id3v1', 'id3v2.3');
                $writer->overwrite_tags = true;
                $writer->tag_encoding = $encoding;
                $writer->remove_other_tags = true;
                $data = array();
                if (isset($options['title'])) {
                    $data['title'] = array($options['title']);
                }
                if (isset($options['artist'])) {
                    $data['artist'] = array($options['artist']);
                }
                if (isset($options['album'])) {
                    $data['album'] = array($options['album']);
                }
                if (isset($options['year'])) {
                    $data['year'] = array($options['year']);
                }
                if (isset($options['genre'])) {
                    $data['genre'] = array($options['genre']);
                }
                if (isset($options['comment'])) {
                    $data['comment'] = array($options['comment']);
                }
                if (isset($options['track_number'])) {
                    $data['track_number'] = array($options['track_number']);
                }
                if (isset($options['popularimeter'])) {
                    $data['popularimeter'] = array('email' => "email", 'rating' => 128, 'data' => 0);
                }
                if (isset($options['unique_file_identifier'])) {
                    $data['unique_file_identifier'] = array('ownerid' => "email", 'data' => md5(time()));
                }
                $tempPathname = Path::insert_dir_separator(Path::arrange_dir_separators_v2(PHPWEBFUSE['DIRECTORIES']['DATA'] . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'temp'));
                if (File::createFile($tempPathname)) {
                    $random = self::generateRandomFilename("png");
                    $_covername = $tempPathname . '' . $random;
                    if (self::convertImage($covername, "png", false, true, $_covername)) {
                        $covername = $_covername;
                    }
                    $data['attached_picture'][0]['data'] = File::getFileContent($covername);
                    $data['attached_picture'][0]['picturetypeid'] = 3;
                    $data['attached_picture'][0]['description'] = isset($options['comment']) ? $options['comment'] : "";
                    $data['attached_picture'][0]['mime'] = mime_content_type($covername);
                    $writer->tag_data = $data;
                    File::deleteFile($_covername);
                    return @$writer->WriteTags();
                }
            }
        }
        return false;
    }

    /**
     * Clean PHPWebfuse temporary files
     * @return void
     */
    public static function cleanPHPWebfuseTempFiles(): void {
        $paths = File::searchDir(PHPWEBFUSE['DIRECTORIES']['ROOT'], array("temp"));
        foreach ($paths as $index => $path) {
            if (File::isFile($path)) {
                File::emptyDirectory($path);
            }
        }
    }

    /**
     * Initialize MobileDetect class
     * @return false|\MobileDetect
     */
    public static function intMobileDetect(): false|\MobileDetect {
        if (!class_exists("\MobileDetect")) {
            self::loadPlugin("MobileDetect");
        }
        return class_exists("\Detection\MobileDetect") ? new \MobileDetect() : false;
    }

    /**
     * Get the browser name
     * @return string
     */
    public static function getBrowser(): string {
        $browser = "";
        $md = self::intMobileDetect();
        if (self::isNotFalse($md)) {
            $browser = $md->getBrowser();
        }
        return $browser;
    }

    /**
     * Ge the device name
     * @return string
     */
    public static function getDevice(): string {
        $devices = "";
        $md = self::intMobileDetect();
        if (self::isNotFalse($md)) {
            $devices = $md->getDevice();
        }
        return $devices;
    }

    /**
     * Get the device operating system
     * @return string
     */
    public static function getDeviceOsName(): string {
        $os = "";
        $md = self::intMobileDetect();
        if (self::isNotFalse($md)) {
            $os = $md->getDeviceOsName();
        }
        return $os;
    }

    /**
     * Get the device brand
     * @return string
     */
    public static function getDeviceBrand(): string {
        $brand = "";
        $md = self::intMobileDetect();
        if (self::isNotFalse($md)) {
            $brand = $md->getDeviceBrand();
        }
        return $brand;
    }

    /**
     * Get ip address information
     * @return array
     */
    public static function getIPInfo(): array {
        $info = array();
        $md = self::intMobileDetect();
        if (self::isNotFalse($md)) {
            $info = $md->getIPInfo();
        }
        return $info;
    }

    /**
     * Check if you are connected to internet
     * @return bool
     */
    public static function isConnectedToInternet() {
        $socket = false;
        try {
            $socket = @fsockopen("www.google.com", 443, $errno, $errstr, 30);
        } catch (\Throwable $e) {
        }
        if ($socket !== false) {
            @fclose($socket);
            return true;
        }
        return false;
    }

    /**
     * Resolve a path using realpath()
     * @param string $path
     * @return string
     */
    public static function resolvePath(string $path): string {
        $file = realpath($path);
        return self::isBool($file) ? $path : $file;
    }

    /**
     * Debug trace
     * @param string $message
     * @return type
     */
    public static function debugTrace(string $message) {
        $trace = array_shift(debug_backtrace());
        return die($trace["file"] . ": Line " . $trace["line"] . ": " . $message);
    }

    /**
     * Validate google re-captcha
     * @param string $serverkey
     * @param string $token
     * @return bool|array
     */
    public static function validatedGRecaptcha(string $serverkey, string $token): bool|array {
        if (self::isNotEmptyString($serverkey) && self::isNotEmptyString($token) && self::isNumeric($token)) {
            try {
                $data = array("secret" => $serverkey, 'response' => $token, 'remoteip' => self::getIPAddress());
                $options = array('http' => array('header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'method' => "POST", 'content' => http_build_query($data)));
                $serverresponse = @file_get_contents("https://google.com/recaptcha/api/siteverify", false, stream_context_create($options));
                if (self::isNotFalse($serverresponse)) {
                    return self::jsonToArray($serverresponse);
                }
            } catch (\Throwable $e) {
            }
        }
        return false;
    }

    /**
     * Generate a QR code
     * @param string $content
     * @param string $filename
     * @return string|bool
     */
    public static function generateQrCode(string $content, string $filename): string|bool {
        $result = false;
        if (!defined('QR_MODE_NUL')) {
            self::loadLib("phpqrcode" . DIRECTORY_SEPARATOR . "qrlib");
        }
        if (class_exists("\QRcode")) {
            $extension = File::getExtension($filename);
            $filename = self::isNotEmptyString($extension) ? (strtolower($extension) !== "png" ? $filename . ".png" : $filename) : $filename . ".png";
            \QRcode::png($content, $filename, QR_ECLEVEL_Q, 20, 2);
            if (File::isFile($filename)) {
                clearstatcache(false, $filename);
                $result = self::resolvePath($filename);
            }
        }
        return $result;
    }

    /**
     * Get the current script file
     * @return string
     */
    public static function getScriptFile(): string {
        return @getenv('SCRIPT_FILENAME');
    }

    /**
     * Get the current script name
     * @return string
     */
    public static function getScriptName(): string {
        return @getenv('SCRIPT_NAME');
    }

    /**
     * Get the current script path
     * @return string
     */
    public static function getScriptPath(): string {
        return @dirname(self::getScriptFile());
    }

    /**
     * Get the current script URL
     * @return string
     */
    public static function getScriptUrl(): string {
        return self::protocol() . '://' . @getenv('HTTP_HOST') . self::getScriptName();
    }

    /**
     * Get the current request URI
     * @return string
     */
    public static function getRequestUri(): string {
        return @getenv('REQUEST_URI');
    }

    /**
     * Get the current request method
     * @return string
     */
    public static function getRequestMethod(): string {
        return @getenv('REQUEST_METHOD');
    }

    /**
     * Get the current request time
     * @return int
     */
    public static function getRequestTime(): int {
        return @getenv('REQUEST_TIME');
    }

    /**
     * Get the current request time in seconds
     * @return float
     */
    public static function getRequestTimeFloat(): float {
        return @getenv('REQUEST_TIME_FLOAT');
    }

    /**
     * Get the current query string
     * @return string
     */
    public static function getQueryString(): string {
        return @getenv('QUERY_STRING');
    }

    /**
     * Get the current HTTP accept
     * @return string
     */
    public static function getHttpAccept(): string {
        return @getenv('HTTP_ACCEPT');
    }

    /**
     * Get the current HTTP accept charset
     * @return string
     */
    public static function getHttpAcceptCharset(): string {
        return @getenv('HTTP_ACCEPT_CHARSET');
    }

    /**
     * Get the current HTTP accept encoding
     * @return string
     */
    public static function getHttpAcceptEncoding(): string {
        return @getenv('HTTP_ACCEPT_ENCODING');
    }

    /**
     * Get the current HTTP accept language
     * @return string
     */
    public static function getHttpAcceptLanguage(): string {
        return @getenv('HTTP_ACCEPT_LANGUAGE');
    }

    /**
     * Get the current HTTP connection
     * @return string
     */
    public static function getHttpConnection(): string {
        return @getenv('HTTP_CONNECTION');
    }

    /**
     * Get the current HTTP host
     * @return string
     */
    public static function getHttpHost(): string {
        return @getenv('HTTP_HOST');
    }

    /**
     * Get the current HTTP referer
     * @return string
     */
    public static function getHttpReferer(): string {
        return @getenv('HTTP_REFERER');
    }

    /**
     * Get the current HTTP user agent
     * @return string
     */
    public static function getHttpUserAgent(): string {
        return @getenv('HTTP_USER_AGENT');
    }

    /**
     * Get the current HTTP X-Requested-With
     * @return string
     */
    public static function getHttpXRequestedWith(): string {
        return @getenv('HTTP_X_REQUESTED_WITH');
    }

    /**
     * Get the current HTTP X-Forwarded-For
     * @return string
     */
    public static function getHttpXForwardedFor(): string {
        return @getenv('HTTP_X_FORWARDED_FOR');
    }

    /**
     * Get the current HTTP X-Forwarded-Host
     * @return string
     */
    public static function getHttpXForwardedHost(): string {
        return @getenv('HTTP_X_FORWARDED_HOST');
    }

    /**
     * Get the current HTTP X-Forwarded-Proto
     * @return string
     */
    public static function getHttpXForwardedProto(): string {
        return @getenv('HTTP_X_FORWARDED_PROTO');
    }

    /**
     * Get the current HTTP X-Forwarded-Port
     * @return string
     */
    public static function getHttpXForwardedPort(): string {
        return @getenv('HTTP_X_FORWARDED_PORT');
    }

    /**
     * Get the current HTTP X-Forwarded-Server
     * @return string
     */
    public static function getHttpXForwardedServer(): string {
        return @getenv('HTTP_X_FORWARDED_SERVER');
    }

    /**
     * Get the current HTTP X-Forwarded-For-IP
     * @return string
     */
    public static function getHttpXForwardedForIp(): string {
        return @getenv('HTTP_X_FORWARDED_FOR_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Proto-IP
     * @return string
     */
    public static function getHttpXForwardedProtoIp(): string {
        return @getenv('HTTP_X_FORWARDED_PROTO_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Host-IP
     * @return string
     */
    public static function getHttpXForwardedHostIp(): string {
        return @getenv('HTTP_X_FORWARDED_HOST_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Port-IP
     * @return string
     */
    public static function getHttpXForwardedPortIp(): string {
        return @getenv('HTTP_X_FORWARDED_PORT_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Server-IP
     * @return string
     */
    public static function getHttpXForwardedServerIp(): string {
        return @getenv('HTTP_X_FORWARDED_SERVER_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-For-Client-IP
     * @return string
     */
    public static function getHttpXForwardedForClientIp(): string {
        return @getenv('HTTP_X_FORWARDED_FOR_CLIENT_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Proto-Client-IP
     * @return string
     */
    public static function getHttpXForwardedProtoClientIp(): string {
        return @getenv('HTTP_X_FORWARDED_PROTO_CLIENT_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Host-Client-IP
     * @return string
     */
    public static function getHttpXForwardedHostClientIp(): string {
        return @getenv('HTTP_X_FORWARDED_HOST_CLIENT_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Port-Client-IP
     * @return string
     */
    public static function getHttpXForwardedPortClientIp(): string {
        return @getenv('HTTP_X_FORWARDED_PORT_CLIENT_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Server-Client-IP
     * @return string
     */
    public static function getHttpXForwardedServerClientIp(): string {
        return @getenv('HTTP_X_FORWARDED_SERVER_CLIENT_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-For-Client
     * @return string
     */
    public static function getHttpXForwardedForClient(): string {
        return @getenv('HTTP_X_FORWARDED_FOR_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-Proto-Client
     * @return string
     */
    public static function getHttpXForwardedProtoClient(): string {
        return @getenv('HTTP_X_FORWARDED_PROTO_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-Host-Client
     * @return string
     */
    public static function getHttpXForwardedHostClient(): string {
        return @getenv('HTTP_X_FORWARDED_HOST_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-Port-Client
     * @return string
     */
    public static function getHttpXForwardedPortClient(): string {
        return @getenv('HTTP_X_FORWARDED_PORT_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-Server-Client
     * @return string
     */
    public static function getHttpXForwardedServerClient(): string {
        return @getenv('HTTP_X_FORWARDED_SERVER_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-For-Client-IP-Client
     * @return string
     */
    public static function getHttpXForwardedForClientIpClient(): string {
        return @getenv('HTTP_X_FORWARDED_FOR_CLIENT_IP_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-Proto-Client-IP-Client
     * @return string
     */
    public static function getHttpXForwardedProtoClientIpClient(): string {
        return @getenv('HTTP_X_FORWARDED_PROTO_CLIENT_IP_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-Host-Client-IP-Client
     * @return string
     */
    public static function getHttpXForwardedHostClientIpClient(): string {
        return @getenv('HTTP_X_FORWARDED_HOST_CLIENT_IP_CLIENT');
    }

    /**
     * Loads environment variables from .env to getenv(), $_ENV and $_SERVER automatically.
     * @param string $inPath The directory to load the .env file from
     * @param bool $overwrite Wether to overwrite existing .env variables
     * @return void
     */
    public static function loadEnvVars(string $inPath, bool $overwrite = true): void {
        $dotenv = $overwrite ? \Dotenv\Dotenv::createMutable($inPath) : \Dotenv\Dotenv::createImmutable($inPath);
        $dotenv->safeLoad();
    }

    /**
     * Throws an Exception
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @return \PHPWebfuse\Exceptions\Exception
     */
    public static function throwException(string $message, int $code = 0, ?\Throwable $previous = null): void {
        throw new \PHPWebfuse\Exceptions\Exception($message, $code, $previous);
    }

    /**
     * Throws an IOException
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @return \PHPWebfuse\Exceptions\IOException
     */
    public static function throwIOException(string $message, int $code = 0, ?\Throwable $previous = null): void {
        throw new \PHPWebfuse\Exceptions\IOException($message, $code, $previous);
    }

    /**
     * Throws an InvalidArgumentException
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @return \PHPWebfuse\Exceptions\InvalidArgumentException
     */
    public static function throwInvalidArgumentException(string $message, int $code = 0, ?\Throwable $previous = null): void {
        throw new \PHPWebfuse\Exceptions\InvalidArgumentException($message, $code, $previous);
    }

    /**
     * Throws a Session exception
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @return \PHPWebfuse\Exceptions\Session
     */
    public static function throwSessionException(string $message, int $code = 0, ?\Throwable $previous = null): void {
        throw new \PHPWebfuse\Exceptions\Session($message, $code, $previous);
    }

    /**
     * Throws a Token exception
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @return \PHPWebfuse\Exceptions\Token
     */
    public static function throwTokenException(string $message, int $code = 0, ?\Throwable $previous = null): void {
        throw new \PHPWebfuse\Exceptions\Token($message, $code, $previous);
    }

    /**
     * Convert bytes into an array of KB, MB, and GB
     * @param int $bytes
     * @return array
     */
    public static function convertBytes(int $bytes): array {
        $kb = $bytes / 1024;
        $mb = $bytes / (1024 * 1024);
        $gb = $bytes / (1024 * 1024 * 1024);
        return array(
            'KB' => $kb,
            'MB' => $mb,
            'GB' => $gb,
        );
    }

    /**
     * Alias of convertBytes()
     * @param int $bytes
     * @return array
     */
    public static function convertBytesToHumanReadable(int $bytes): array {
        $kb = $bytes / 1024;
        $mb = $bytes / (1024 * 1024);
        $gb = $bytes / (1024 * 1024 * 1024);
        return array(
            'KB' => $kb,
            'MB' => $mb,
            'GB' => $gb,
        );
    }

    /**
     * Sort files first then folders
     * @param array $lists
     * @return array
     */
    public static function sortFilesFirst(array $lists): array {
        usort($lists, function ($a, $b) use ($lists) {
            if (is_file($a) && is_dir($b)) {
                // File comes first
                return -1;
            } elseif (is_dir($a) && is_file($b)) {
                // Directory comes second
                return 1;
            } else {
                // Retain order
                return 0;
            }
        });
        return $lists;
    }

    /**
     * Extract a phar archive
     * @param string $filename
     * @param string $toDirectory
     * @return bool|string Return true on success or false/string on failure
     * @throws \PHPWebfuse\Exceptions\Exception
     */
    public static function extractPhar(string $filename, string $toDirectory): bool|string {
        $pharReadonly = ini_get('phar.readonly');
        if (is_string($pharReadonly) && (strtolower($pharReadonly) == "on" || $pharReadonly == "1" || $pharReadonly == 1)) {
            throw new \Exception('Extracting of Phar archives is disabled in php.ini. Please make sure that "phar.readonly" is set to "off".');
        } else {
            if (is_file($filename) && is_readable($filename)) {
                $filename = \realpath($filename);
                if (Utils::endsWith(".phar", strtolower($filename))) {
                    try {
                        $phar = new \Phar($filename, 0);
                        if (File::createDir($toDirectory)) {
                            if ($phar->isCompressed()) {
                                $phar->decompressFiles();
                            }
                            if ($phar->extractTo(\realpath($toDirectory), null, true)) {
                                return true;
                            } else {
                                return "Failed to extract the phar achive " . $filename . " to " . $toDirectory . "";
                            }
                        } else {
                            return "Unable to create the extraction directory or check if it exists.";
                        }
                    } catch (\Throwable $e) {
                        return $e->getMessage();
                    }
                } else {
                    return "The filename must be a valid phar archive.";
                }
            } else {
                return "The filename must be a valid and readable.";
            }
        }
        return false;
    }

    /**
     * Gets the root path of the application, either as a URL or a filesystem path.
     *
     * @param bool $urlForm Whether to return the path as a URL (true) or a filesystem path (false).
     * @param bool $endForwardslash Whether to add a trailing forward slash to the path.
     * @return string The root path.
     */
    public static function getDocumentRoot(bool $urlForm = false, bool $endForwardslash = true): string {
        return $urlForm ? Utils::currentUrl() . ($endForwardslash ? '/' : '') : Path::insert_dir_separator(getenv('DOCUMENT_ROOT'), $endForwardslash);
    }

    /**
     * Gets a path relative to the root path.
     *
     * @param string $name The relative path.
     * @param bool $urlForm Whether to return the path as a URL (true) or a filesystem path (false).
     * @param bool $endForwardslash Whether to add a trailing forward slash to the path.
     * @return string The absolute path.
     */
    public static function getFromDocumentRoot(string $name, bool $urlForm = false, bool $endForwardslash = true): string {
        $rootPath = $urlForm ? self::getDocumentRoot(true) : self::getDocumentRoot(false);
        $name = Path::arrange_dir_separators_v2($name, false);
        $name = $urlForm ? str_replace('\\', '/', $name) : $name;
        return $rootPath . $name . ($endForwardslash ? ($urlForm ? '/' : DIRECTORY_SEPARATOR) : '');
    }

    /**
     * Generate a bootstrap HTML error message
     *
     * @param string $message The error message to display
     * @param bool $useEcho Whether to echo the message directly or return it as a string
     * @return string The HTML error message
     */
    public static function bootstrapErrorMessage(string $message, bool $useEcho = true): string {
        $html = "<div class='container alert alert-danger'>$message</div>";
        if ($useEcho) {
            // Echo the message directly if $useEcho is true
            echo $html;
            return ""; // Return an empty string if echoing
        }
        // Return the HTML string if not echoing
        return $html;
    }

    /**
     * Generate a bootstrap HTML success message
     *
     * @param string $message The success message to display
     * @param bool $useEcho Whether to echo the message directly or return it as a string
     * @return string The HTML success message
     */
    public static function bootstrapSuccessMessage(string $message, bool $useEcho = true): string {
        $html = "<div class='container alert alert-success'>$message</div>";
        if ($useEcho) {
            // Echo the message directly if $useEcho is true
            echo $html;
            return ""; // Return an empty string if echoing
        }
        // Return the HTML string if not echoing
        return $html;
    }

    /**
     * Generate a bootstrap HTML info message
     *
     * @param string $message The info message to display
     * @param bool $useEcho Whether to echo the message directly or return it as a string
     * @return string The HTML info message
     */
    public static function bootstrapInfoMessage(string $message, bool $useEcho = true): string {
        $html = "<div class='container alert alert-info'>$message</div>";
        if ($useEcho) {
            // Echo the message directly if $useEcho is true
            echo $html;
            return ""; // Return an empty string if echoing
        }
        // Return the HTML string if not echoing
        return $html;
    }

    /**
     * Generate a bootstrap HTML normal message
     *
     * @param string $message The normal message to display
     * @param bool $useEcho Whether to echo the message directly or return it as a string
     * @return string The HTML normal message
     */
    public static function bootstrapNormalMessage(string $message, bool $useEcho = true): string {
        $html = "<div class='container alert alert-default'>$message</div>";
        if ($useEcho) {
            // Echo the message directly if $useEcho is true
            echo $html;
            return ""; // Return an empty string if echoing
        }
        // Return the HTML string if not echoing
        return $html;
    }

    // PRIVATE METHOD

    private function errorHandler(int $errno, string $errstr, string $errfile, string $errline, array $errcontext) {
        $level = error_reporting();
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        } else {
            // $errstr may need to be escaped:
            $errstr = htmlspecialchars($errstr);
            switch ($errno) {
                case E_USER_ERROR:
                    echo "<b>USER ERROR</b> [" . $errno . "] " . $errstr . "<br />\n";
                    echo " Fatal error on line " . $errline . " in file " . $errfile . "";
                    echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
                    echo "Aborting...<br />\n";
                    exit(1);
                case E_USER_WARNING:
                    echo "<b>USER WARNING</b> [" . $errno . "] " . $errstr . "<br />\n";
                    break;
                case E_USER_NOTICE:
                    echo "<b>USER NOTICE</b> [" . $errno . "] " . $errstr . "<br />\n";
                    break;
                default:
                    echo "Unknown error type: [" . $errno . "] " . $errstr . "<br />\n";
                    break;
            }
            return true;
        }
    }
}

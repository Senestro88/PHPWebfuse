<?php

namespace PHPWebfuse;

use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\GuzzleException;
use \PHPWebfuse\Utils;
use \Psr\Http\Message\ResponseInterface;
use \PHPWebfuse\Exceptions\Exception;

/**
 * @author Senestro
 */
class Http {
    // PRIVATE VARIABLE

    /**
     * @var array The default request methods allowed
     */
    private static $REQUEST_METHODS = array('GET', 'POST');

    // PUBLIC VARIABLES
    public static \Throwable $lastThrowable;

    /**
     * @var string Any request last error are stored here
     */
    public static string $errorMessage = "";

    // PUBLIC METHODS


    /**
     * Prevent the constructor from being initialized
     */
    private function __construct() {
    }


    /**
     * Make an HTTP request using GET or POST method.
     * 
     * @param string $method The HTTP method (GET, POST, etc.)
     * @param string $url The full URL to send the request to.
     * @param array $headers Optional. Array of HTTP headers to include in the request.
     * @param array $params Optional. Array of query parameters for GET or form parameters for POST.
     * @param array $attachments Optional. Array of file attachments for a POST request.
     * 
     * @return string|bool Returns the response body as a string on success, or false on failure.
     * @throws \PHPWebfuse\Exceptions\Exception Throws an exception if the content length exceeds the limit or other errors occur.
     */
    public static function request(string $method, string $url, array $headers = array(), array $params = array(), array $attachments = array()): string|bool {
        // Ensure the HTTP method is uppercase and trimmed of extra spaces
        $method = strtoupper(trim($method));
        // Check if the method is valid (e.g., GET, POST)
        if (Utils::isNotInArray($method, self::$REQUEST_METHODS)) {
            // Set an error message if the method is not supported
            self::$errorMessage = "The request method must be one of the following: " . implode(", ", self::$REQUEST_METHODS);
        }
        // Check if the cURL extension is loaded
        elseif (!function_exists('curl_init')) {
            // Set an error message if cURL is not available
            self::$errorMessage = "Curl extension isn't loaded";
        } else {
            try {
                // Extract the domain and path from the provided URL
                $domain = Utils::getHostFromUrl($url);
                $path = Utils::getPathFromUrl($url);
                // Set up cURL options
                $options = array(
                    "connect_timeout" => 3.0, // Connection timeout in seconds
                    "force_ip_resolve" => "v4", // Force IPv4 resolution
                    'on_headers' => function (ResponseInterface $response) {
                        // Ensure content length is not too large
                        $expectedContentLength = 1073741824; // 1GB max content length
                        if ($response->getHeaderLine('Content-Length') > $expectedContentLength) {
                            throw new Exception('The content length is too big (' . Utils::formatSize($expectedContentLength) . ')!');
                        }
                    },
                    'progress' => function ($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) {
                        // Progress callback (optional, currently empty)
                    },
                    'verify' => false // Skip SSL verification
                );
                // Create a new HTTP client with the base URI (domain)
                $client = new Client(['base_uri' => $domain]);
                // Handle GET request
                if ($method == "GET") {
                    // Build query string from URL and merge it with additional parameters
                    $query = Utils::buildQueryParamFromQueryString(Utils::getQueryStringFromUrl($url));
                    $response = $client->request('GET', $path, \array_merge(array(
                        "query" => \array_merge($query, $params)
                    ), $options));
                }
                // Handle POST request with file attachments
                else {
                    // Prepare multipart data for file uploads
                    $multipart = array();
                    foreach ($attachments as $attachment) {
                        if (\is_file($attachment)) {
                            // Attach files to the POST request
                            $multipart[] = array(
                                "name" => File::removeExtension(\basename($attachment)),
                                "filename" => \basename($attachment),
                                "content" => File::getFileContent($attachment)
                            );
                        }
                        // Handle attachments passed as arrays (with name, filename, content, headers)
                        else if (\is_array($attachment)) {
                            $name = isset($attachment['name']) ? (string) $attachment['name'] : "";
                            $filename = isset($attachment['filename']) ? (string) $attachment['filename'] : "";
                            $contents = isset($attachment['contents']) ? (string) $attachment['contents'] : "";
                            $multipart[] = array(
                                "name" => $name,
                                "filename" => $filename,
                                "contents" => $contents,
                                "headers" => isset($attachment['headers']) ? (string) $attachment['headers'] : ""
                            );
                        }
                    }
                    // Make POST request with form parameters, multipart attachments, and headers
                    $response = $client->request('POST', $path, \array_merge(array(
                        'form_params' => $params,
                        'multipart' => $multipart,
                        "headers" => $headers
                    ), $options));
                }
                // Extract the status code (e.g., 200) and reason phrase (e.g., "OK") from the response
                $code = $response->getStatusCode(); // Status code (e.g., 200)
                $reason = $response->getReasonPhrase(); // Reason phrase (e.g., "OK")
                // Get the response body as a string
                $body = (string) $response->getBody();
                // Return the response body
                return $body;
            }
            // Catch any exceptions or errors during the request
            catch (\Throwable $e) {
                // Store the error message
                self::setThrowable($e);
            }
        }
        // Return false if the request fails
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

<?php

namespace PHPWebfuse;

/**
 */
class Http
{
    // PRIVATE VARIABLES

    /**
     * @var \PHPWebfuse\Methods
     */
    private \PHPWebfuse\Methods $methods;

    // PRIVATE CONSTANTS

    /**
     * @var const The default request methods allowed
     */
    private const REQUEST_METHODS = array('GET', 'POST', 'HEAD');

    // PUBLIC VARIABLES

    /**
     * @var string Any request last error are stored here
     */
    public string $message = "";

    // PUBLIC METHODS

    /**
     * Construct new Http instance
     */
    public function __construct()
    {
        $this->methods = new \PHPWebfuse\Methods();
    }

    /**
     * Make an HTTP request
     * @param string $method The request method
     * @param string $url The request url
     * @param array|null $headers The request headers
     * @param array $params The request parameters
     * @return mixed
     */
    public function request(string $method, string $url, ?array $headers = null, array $params = []): mixed
    {
        $method = strtoupper(trim($method));
        if ($this->methods->isNotInArray($method, self::REQUEST_METHODS)) {
            $this->message = "The request method must be one of the following: " . implode(", ", self::REQUEST_METHODS);
        } elseif (!function_exists('curl_init')) {
            $this->message = "Curl extension isn't loaded";
        } else {
            $curl = curl_init();
            if ($this->methods->isFalse($curl)) {
                $this->message = "Can't initialize Curl handle";
            } else {
                $_headers = [];
                if (self::isArray($headers)) {
                    foreach ($headers as $key => $value) {
                        $_headers[] = $key . ": " . $value;
                    }
                }
                if (isset($_headers['Content-Type']) && $method == "POST") {
                    $_headers['Content-Type'] = "multipart/form-data";
                }
                $options = [
                    CURLOPT_SSLVERSION => 0, // Default SSL Version
                    CURLOPT_SSL_VERIFYPEER => false, // Stop cURL from verifying the peer's certificate
                    CURLOPT_SSL_VERIFYSTATUS => false, // Do not verify the certificate's status.
                    CURLOPT_PROXY_SSL_VERIFYPEER => false, // Stop cURL from verifying the peer's certificate
                    CURLOPT_USERAGENT => $this->methods->USER_AGENT,
                    CURLOPT_HEADER => false, // Include the header in the output
                    CURLOPT_RETURNTRANSFER => true, // To return the transfer as a string of the return value of curl_exec() instead of outputting it directly
                    CURLOPT_FOLLOWLOCATION => true, // Follow any "Location: " header that the server sends as part of the HTTP header
                    CURLOPT_MAXREDIRS => 3, // The maximum amount of HTTP redirections to follow
                    CURLOPT_CONNECTTIMEOUT => 60, // The number of seconds to wait while trying to connect. Use 0 to wait indefinitely.
                    CURLOPT_TIMEOUT => 60, // The maximum number of seconds to allow cURL functions to execute.
                    CURLOPT_HTTPHEADER => $_headers, // An array of HTTP header fields to set
                    CURLOPT_FORBID_REUSE => true, // To force the connection to explicitly close when it has finished processing, and not be pooled for reuse
                    CURLOPT_FRESH_CONNECT => true, // To force the use of a new connection instead of a cached one.
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                ];

                if ($method == "POST") {
                    $options[CURLOPT_URL] = $url;
                    $options[CURLOPT_POST] = true;
                    $options[CURLOPT_POSTFIELDS] = http_build_query($params);
                } elseif ($method == "GET") {
                    $options[CURLOPT_HTTPGET] = true;
                    $options[CURLOPT_URL] = $url . '?' . http_build_query($params);
                    $options[CURLOPT_CUSTOMREQUEST] = $method;
                } elseif ($method == "HEAD") {
                    $options[CURLOPT_URL] = $url;
                    $options[CURLOPT_CUSTOMREQUEST] = $method;
                    $options[CURLOPT_NOBODY] = true;
                }

                if ($this->methods->isFalse(curl_setopt_array($curl, $options))) {
                    $this->message = "Failed to set Curl options";
                } else {
                    $response = curl_exec($curl);
                    if ($this->methods->isFalse($response)) {
                        $this->message = "Failed to execute Curl session: " . curl_error($curl);
                    } else {
                        curl_close($curl);
                        return $response;
                    }
                }
            }
        }
        return false;
    }
}

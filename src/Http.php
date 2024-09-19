<?php

namespace PHPWebfuse;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use \PHPWebfuse\Utils;
use Psr\Http\Message\ResponseInterface;

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

    /**
     * @var string Any request last error are stored here
     */
    public static string $message = "";

    // PUBLIC METHODS


    /**
     * Prevent the constructor from being initialized
     */
    private function __construct() {
    }


    public static function request(string $method, string $url, array $headers = array(), array $params = array(), array $attachments = array()): mixed {
        $method = strtoupper(trim($method));
        if (Utils::isNotInArray($method, self::$REQUEST_METHODS)) {
            self::$message = "The request method must be one of the following: " . implode(", ", self::$REQUEST_METHODS);
        } elseif (!function_exists('curl_init')) {
            self::$message = "Curl extension isn't loaded";
        } else {
            try {
                $domain = Utils::getHostFromUrl($url);
                $path = Utils::getPathFromUrl($url);
                $options = array(
                    "connect_timeout" => 3.0,
                    "force_ip_resolve" => "v4",
                    'on_headers' => function (ResponseInterface $response) {
                        $expectedContentlength = 1073741824;
                        if ($response->getHeaderLine('Content-Length') > $expectedContentlength) {
                            throw new \Exception('The content length is too big (' . Utils::formatSize($expectedContentlength) . ')!');
                        }
                    },
                    'progress' => function ($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) {
                    },
                    'verify' => false
                );
                // Create a client with a base URI
                $client = new Client(['base_uri' => $domain]);
                if ($method == "GET") {
                    $query = Utils::buildQueryParamFromQueryString(Utils::getQueryStringFromUrl($url));
                    $response = $client->request('GET', $path, \array_merge(array(
                        "query" => \array_merge($query, $params)
                    ), $options));
                } else {
                    $multipart = array();
                    foreach ($attachments as $attachment) {
                        if (\is_file($attachment)) {
                            $multipart[] = array("name" => File::removeExtension(\basename($attachment)), "filename" => \basename($attachment), "content" => File::getFileContent($attachment));
                        } else if (\is_array($attachment)) {
                            $name = isset($attachment['name']) ? (string) $attachment['name'] : "";
                            $filename = isset($attachment['filename']) ? (string) $attachment['filename'] : "";
                            $contents = isset($attachment['contents']) ? (string) $attachment['contents'] : "";
                            $multipart[] = array("name" => $name, "filename" => $filename, "contents" => $contents, "headers" => isset($attachment['headers']) ? (string) $attachment['headers'] : "");
                        }
                    }
                    $response = $client->request('POST', $path, \array_merge(array(
                        'form_params' => $params,
                        'multipart' => $multipart,
                        "headers" => $headers
                    ), $options));
                }
                $code = $response->getStatusCode(); // 200
                $reason = $response->getReasonPhrase(); // OK
                $body = (string) $response->getBody();
                return $body;
            } catch (\Throwable $e) {
                self::$message = $e->getMessage();
            }
        }
        return false;
    }

    public static function getMessage(): string {
        return self::$message;
    }

    // PRIVATE METHODS
}

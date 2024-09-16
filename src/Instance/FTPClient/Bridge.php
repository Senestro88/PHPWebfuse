<?php

namespace PHPWebfuse\Instance\FTPClient;

/**
 * @author Senestro
 */
class Bridge
{
    /* PRIVATE VARIABLES */

    /**
     * The connection stream
     * @var \FTP\Connection
     */
    private \FTP\Connection $connection;
    
    /**
     * Error message are stored here
     * @var string|null
     */
    private ?string $errorMessage = null;

    /**
     * The constructor
     * @param \FTP\Connection $connection
     */
    public function __construct(\FTP\Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Forward the method call to FTP functions
     * @param string $name
     * @param array $arguments
     * @throws \PHPWebfuse\Instance\Exceptions\Exception
     */
    public function __call(string $name, array $arguments = array())
    {
        $function = "ftp_{$name}";
        if (!function_exists($function)) {
            throw new \PHPWebfuse\Instance\Exceptions\Exception("{$function} is not a valid FTP function");
        } else {
            // Add the stream to the beginning of the arguments
            array_unshift($arguments, $this->connection);
            // Clear the previous error message
            $this->errorMessage = '';
            // Set the error handler
            set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
                // This error code is not included in error_reporting, so let it fall through to the standard PHP error handler
                if (!(error_reporting() & $errno)) {
                    return false;
                } else {
                    $this->errorMessage = $errstr;
                }
            });
            // Call the FTP function
            $returnValue = call_user_func_array($function, $arguments);
            // Restor the error handler to default
            restore_error_handler();
            // Return the value
            return $returnValue;
        }
    }

    /**
     * Gets the last FTP error message sent by the remote server.
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage ?: '';
    }
}

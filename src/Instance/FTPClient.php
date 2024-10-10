<?php

namespace PHPWebfuse\Instance;

use PHPWebfuse\Utils;
use PHPWebfuse\File;
use PHPWebfuse\Path;

/**
 * @author Senestro
 */
class FTPClient
{
    /* PUBLIC VARIABLES */

    /**
     * Logs array
     * @var array
     */
    public $logs = array();

    /**
     * The host to connect to
     * @var string
     */
    private $host = "";

    /**
     * The connection port
     * @var int
     */
    private $port = 21;

    /**
     * The connection timeout
     * @var int
     */
    private $timeout = 90;

    /**
     * The username for login when connected
     * @var string
     */
    private $username = "";

    /**
     * The password for login when connected
     * @var string
     */
    private $password = "";

    /**
     * The remote system type
     * @var string
     */
    private $rst = "unix";
    /* PRIVATE VARIABLES */

    /**
     * The remote directory separator
     * @var string
     */
    private string $rds = "/";

    /**
     * @var \FTP\Connection
     */
    private ?\FTP\Connection $connection = null;

    /**
     * @var \PHPWebfuse\Instance\FTPClient\Bridge
     */
    private ?\PHPWebfuse\Instance\FTPClient\Bridge $bridge = null;

    /* PUBLIC METHODS */

    /**
     * TConstruct a new FTPClient instance
     * @throws \PHPWebfuse\Exceptions\Exception
     */
    public function __construct()
    {
        $this->connection = null;
        $this->bridge = null;
        if(!extension_loaded('ftp')) {
            throw new \PHPWebfuse\Exceptions\Exception('FTP extension is not loaded!');
        }
    }

    /**
     * This closes the connection
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Open a new FTP connection (None SSL Mode)
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @return bool
     */
    public function connect(string $host, int $port = 21, int $timeout = 90): bool
    {
        return $this->FTPConnect($host, false, $port, $timeout);
    }

    /**
     * Open a new FTP connection (SSL Mode)
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @return bool
     */
    public function sslConnect(string $host, int $port = 21, int $timeout = 90): bool
    {
        return $this->FTPConnect($host, true, $port, $timeout);
    }

    /**
     * Login to the FTP server
     * @param string $username
     * @param string $password
     * @param bool $enablePassiveMode True to enable passive mode
     * @return bool
     */
    public function login(string $username, string $password, bool $enablePassiveMode = true): bool
    {
        if($this->isValid()) {
            if($this->FTPLogin($username, $password) && $this->enablePassiveMode($enablePassiveMode)) {
                $this->username = $username;
                $this->password = $password;
                // Get and set the remote system type
                $systype = $this->bridge->systype();
                if(is_string($systype)) {
                    $systype = strtolower($systype);
                    $this->rst = $systype;
                }
                $this->log("Successfully logged in to the FTP server using username: {$username} and password: {$password}");
                return true;
            } else {
                $this->log("Failed to log in to the FTP server using username: {$username} and password: {$password}", "error");
            }
        }
        return false;
    }

    /**
     * To enable passive mode
     * @param bool $enable
     * @return bool
     */
    public function enablePassiveMode(bool $enable = true): bool
    {
        return $this->isValid() ? $this->bridge->pasv($enable) : false;
    }

    /**
     * Disconnected the FTP connection
     * @return void
     */
    public function disconnect(): void
    {
        if($this->isValid()) {
            try {
                $this->bridge->close();
                $this->bridge = null;
                $this->connection = null;
                $this->log("Succesfully disconnected.");
            } catch(\Throwable $e) {
                $this->log($e->getMessage(), "error");
            }
        }
    }

    /**
     * Send an arbitrary command to the server.
     * The result is formatted as:
     * array('response' => string, 'code' => int, 'message' => string, 'body' => string, 'endmessage' => string, 'success' => bool, 'responsecode'=>int);
     * @param string $command
     * @return array
     * @throws \PHPWebfuse\Exceptions\Exception
     */
    public function raw(string $command): array
    {
        if($this->isValid()) {
            $command = trim($command);
            if(!$raw = $this->bridge->raw($command)) {
                throw new \Exception("Failed to send the command [{$command}] to the server.");
            }
            $code = $message = $body = $endmessage = null;
            // Get the response code
            if(preg_match('/^\d+/', $raw[0], $matches) !== false) {
                $code = (int) $matches[0];
            }
            // Get the message
            if(preg_match('/[A-z ]+.*/', $raw[0], $matches) !== false) {
                $message = $matches[0];
            }
            // If the response is multiline response then search for the body and the endmessage
            $count = count($raw);
            if($count > 1) {
                $body = array_slice($raw, 1, -1);
                $endmessage = $raw[$count - 1];
            }
            return array('response' => $raw, 'code' => $code, 'message' => $message, 'body' => $body, 'endmessage' => $endmessage, 'success' => $code ? $code < 400 : false, "responsecode" => $code);
        }
        return array();
    }

    /**
     * Get the current working directory name
     * @return string | null
     */
    public function getCurrentDir(): ?string
    {
        if($this->isValid()) {
            $dir = $this->bridge->pwd();
            if(is_string($dir)) {
                return $dir;
            } else {
                $this->log("Failed to retrieve the current working directory name.", "error");
            }
        }
        return null;
    }

    /**
     * Change the current working directory name
     * @param string $dir
     * @return bool
     */
    public function changeDir(string $dir): bool
    {
        if($this->isValid()) {
            if(!$this->bridge->chdir($this->arrangeRDS($dir))) {
                $this->log("Failed to change the working directory to: {$dir}", "error");
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Change to the parent directory
     * @return bool
     */
    public function upDir(): bool
    {
        return $this->isValid() ? $this->bridge->cdup() : false;
    }

    /**
     * Checks whether if the giving file is a directory or not
     * @param string $dir
     * @return bool
     */
    public function isDir(string $dir): bool
    {
        if($this->isValid()) {
            // Get the current directory
            $current = $this->getCurrentDir();
            // Try to change to the directory
            if(\is_string($current) && $this->changeDir($dir)) {
                // It's a directory, change back to the original directory
                $this->raw("CWD " . $current);
                return true;
            }
        }
        return false;
    }

    /**
     * Check if it's a file
     * @param string $file
     * @return bool
     */
    public function isFile(string $file): bool
    {
        if($this->isValid()) {
            $size = $this->bridge->size($this->arrangeRDS($file));
            return $size != -1;
        }
        return false;
    }

    /**
     * Empty or clean a directory
     * @param string $dir
     * @return bool
     */
    public function cleanDir(string $dir): bool
    {
        if($this->isValid() && $this->isDir($dir)) {
            $dir = $this->arrangeRDS($dir); // Arrange the path
            $files = $this->sortFilesFirstForList($this->list($dir, true, true));
            foreach($files as $info) {
                $realpath = $info->getRealPath();
                if($this->isFile($realpath)) {
                    // Delete file
                    if(!$this->bridge->delete($realpath)) {
                        $this->log("Failed to clean the directory \"{$dir}\", unable to delete the file \"{$realpath}\"", "error");
                        return false;
                    }
                } elseif($this->isDir($realpath)) {
                    // Delete directory
                    if(!$this->bridge->rmdir($realpath)) {
                        $this->log("Failed to clean the directory \"{$dir}\", unable to delete the directory \"{$realpath}\"", "error");
                        return false;
                    }
                }
            }
            return $this->isDirEmpty($dir);
        }
        return false;
    }

    /**
     * Delete a directory
     * @param string $dir
     * @return bool
     */
    public function deleteDir(string $dir): bool
    {
        if($this->isValid()) {
            if($this->cleanDir($dir)) {
                if(!$this->bridge->rmdir($this->arrangeRDS($dir))) {
                    $this->log("Failed to delete the directory \"{$dir}\"", "error");
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a detailed list of files in the given directory
     * @param string $dir
     * @param bool $recursive
     * @param bool $showHidden
     * @param bool $asc
     * @return array
     */
    public function list(string $dir, bool $recursive = false, bool $showHidden = false, bool $asc = true): array
    {
        $list = array();
        if($this->isValid()) {
            $dir = $this->arrangeRDS($dir);
            // Retrieve the raw list of directory contents
            $lines = $this->bridge->rawlist($dir);
            // Ensure the raw list is an array
            if(is_array($lines)) {
                foreach($lines as $line) {
                    $info = new \PHPWebfuse\Instance\FTPClient\File($this, $dir, $line);
                    $list[$info->getKey()] = $info;
                    // Recursively list directory contents if recursive is true
                    if($recursive && $info->isDir()) {
                        foreach($this->list($info->getRealPath()) as $lk => $li) {
                            $list[$lk] = $li;
                        }
                    }
                }
            }
        }
        if(!empty($list)) {
            // Loop to remove hidden when list hideen is false
            if(!$showHidden) {
                foreach($list as $lk => $li) {
                    if(Utils::startsWith(".", $li->getBasename())) {
                        unset($list[$lk]);
                    }
                }
            }
            // Sort the list in ascending or descending order
            if($asc && function_exists('ksort ')) {
                ksort($list);
            } elseif(!$asc && function_exists('krsort ')) {
                krsort($list);
            }
        }
        // Return the list
        return $list;
    }

    /**
     * Count the items base on file type (file, directory, link, unknown).
     * @param string $dir
     * @param bool $recursive
     * @param bool $includeHidden
     * @param string|null $type
     * @return int
     */
    public function countItems(string $dir, bool $recursive = false, bool $includeHidden = true, ?string $type = null): int
    {
        $list = $this->list($dir, $recursive, $includeHidden);
        $count = 0;
        foreach($list as $info) {
            if($type === null || $info->getType() == $type) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Count directories
     * @param string $dir
     * @param bool $recursive
     * @param bool $includeHidden
     * @return int
     */
    public function countDirectories(string $dir, bool $recursive = false, bool $includeHidden = true): int
    {
        return $this->countItems($dir, $recursive, $includeHidden, "directory");
    }

    /**
     * Count files
     * @param string $dir
     * @param bool $recursive
     * @param bool $includeHidden
     * @return int
     */
    public function countFiles(string $dir, bool $recursive = false, bool $includeHidden = true): int
    {
        return $this->countItems($dir, $recursive, $includeHidden, "file");
    }

    /**
     * Count links
     * @param string $dir
     * @param bool $recursive
     * @param bool $includeHidden
     * @return int
     */
    public function countLinks(string $dir, bool $recursive = false, bool $includeHidden = true): int
    {
        return $this->countItems($dir, $recursive, $includeHidden, "link");
    }

    /**
     * Count unknown files
     * @param string $dir
     * @param bool $recursive
     * @param bool $includeHidden
     * @return int
     */
    public function countUnknown(string $dir, bool $recursive = false, bool $includeHidden = true): int
    {
        return $this->countItems($dir, $recursive, $includeHidden, "unknown");
    }

    /**
     * Check if a directory is empty.
     * @param string $file
     * @return bool
     */
    public function isDirEmpty(string $file): bool
    {
        return $this->countItems($file, false, true) < 1;
    }

    /**
     * Get all the logs
     * @return array
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Requests execution of a command on the FTP server
     * @param string $command
     * @return bool
     */
    public function exec(string $command): bool
    {
        return $this->isValid() ? $this->bridge->exec($command) : false;
    }

    /**
     * Sends a SITE command to the server
     * @param string $command
     * @return bool
     */
    public function site(string $command): bool
    {
        return $this->isValid() ? $this->bridge->site($command) : false;
    }

    /**
     * Get the help information of the remote FTP server.
     * @return array
     */
    public function help(): array
    {
        return $this->raw('help');
    }

    /**
     * Deletes a regular remote file on the server.
     * @param string $file
     * @return bool
     */
    public function deleteFile(string $file): bool
    {
        if($this->isValid() && $this->isFile($file)) {
            if(!$this->bridge->delete($this->arrangeRDS($file))) {
                $this->log("Failed to delete the file \"{$file}\"", "error");
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Gets last modified time of a remote file or directory
     * @param string $file
     * @param string|null $format
     * @return int|string
     */
    public function lastModifiedTime(string $file, ?string $format = null): int|string
    {
        if($this->isValid() && $this->isFile($file)) {
            $time = $this->bridge->mdtm($this->arrangeRDS($file));
            if($time != -1) {
                return is_string($format) && Utils::isNotEmptyString($format) ? date($format, $time) : $time;
            }
        }
        return 0;
    }

    /**
     * Gets remote directory size.
     * @param string $dir
     * @return int
     */
    public function dirSize(string $dir): int
    {
        $lists = $this->list($dir, true, true);
        $size = 0;
        foreach($lists as $info) {
            if(!$info->isDir()) {
                $size += (int) $info->getSize();
            }
        }
        return $size;
    }

    /**
     * Gets a regular remote file size.
     * @param string $file
     * @param bool $format
     * @return int
     */
    public function getSize(string $file, bool $format = false): int|string
    {
        if($this->isValid() && $this->isFile($file)) {
            $size = $this->bridge->size($this->arrangeRDS($file));
            if($size != -1) {
                return $format ? Utils::formatSize((int) $size) : $size;
            }
        }
        return 0;
    }

    /**
     * Renames a remote or move file/directory
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function rename(string $from, string $to): bool
    {
        return $this->isValid() ? $this->bridge->rename($this->arrangeRDS($from), $this->arrangeRDS($to)) : false;
    }

    /**
     * An alias of rename
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function move(string $from, string $to): bool
    {
        return $this->rename($from, $to);
    }

    /**
     * Moves a remote file/directory to another path.
     * @param string $source
     * @param string $dir
     * @return bool
     */
    public function moveInto(string $source, string $dir): bool
    {
        return $this->rename($source, $this->arrangeRDS(Path::merge($dir, basename($source))));
    }

    /**
     * Sends a request to the server to keep the control channel alive and prevent the server from disconnecting the session.
     * @return bool
     */
    public function requestKeepAlive(): bool
    {
        return (bool) $this->raw("NOOP")['success'];
    }

    /**
     * Sends a request to FTP server to allocate a space for the next file transfer.
     * @param int $bytes
     * @return bool
     */
    public function allocateSpace(int $bytes): bool
    {
        return $this->isValid() ? $this->bridge->alloc($bytes) : false;
    }

    /**
     * Reads the remote file content and returns the data as a string
     * @param string $file
     * @param int $mode
     * @param int $offset
     * @return string
     */
    public function getContent(string $file, int $mode = FTP_BINARY, int $offset = 0): string
    {
        if($this->isValid() && $this->isFile($file)) {
            $file = $this->arrangeRDS($file);
            $temp = tempnam(sys_get_temp_dir(), $file);
            $content = $this->bridge->get($temp, $file, $mode, $offset);
            if($content === true) {
                try {
                    return @file_get_contents($temp);
                } finally {
                    unlink($temp);
                }
            }
        }
        return "";
    }

    /**
     * Creates an FTP file if it's doesn't exist
     * @param string $file
     * @param string $content
     * @param int $mode
     * @return bool
     */
    public function createFile(string $file, ?string $content = null, int $mode = FTP_BINARY): bool
    {
        if($this->isValid() && !$this->isFile($file)) {
            $file = $this->arrangeRDS($file);
            $handle = @fopen('php://temp', 'w');
            if(!is_bool($handle)) {
                if(Utils::isNonNull($content) && Utils::isNotEmptyString($content)) {
                    // Write the content to the temporary file
                    fwrite($handle, $content);
                }
                // Rewind the pointer of the temporary file
                rewind($handle);
                // Upload the temporary file to the remote file
                $result = $this->bridge->fput($file, $handle, $mode);
                // Close the temporary file
                fclose($handle);
                return $result;
            }
        }
        return false;
    }

    /**
     * Save content (override) to an existing file
     * @param string $file
     * @param string $content
     * @param int $mode
     * @return bool
     */
    public function saveContent(string $file, string $content, int $mode = FTP_BINARY): bool
    {
        if($this->isValid() && $this->isFile($file)) {
            $file = $this->arrangeRDS($file);
            // Open a temporary stream for writing
            $handle = @fopen('php://temp', 'w');
            if(!is_bool($handle)) {
                // Write the content to the temporary file
                fwrite($handle, $content);
                // Rewind the pointer of the temporary file
                rewind($handle);
                // Upload the temporary file to the remote file
                $result = $this->bridge->fput($file, $handle, $mode);
                // Close the temporary file
                fclose($handle);
                return $result;
            }
        }
        return false;
    }

    /**
     * Creates a directory on the FTP server.
     * @param string $dir
     * @return bool
     */
    public function createDir(string $dir): bool
    {
        if($this->isValid()) {
            if($this->isDir($dir)) {
                return true;
            } else {
                $cd = $this->getCurrentDir();
                $parts = array_filter(explode("/", $this->arrangeRDS($dir)));
                foreach($parts as $part) {
                    if(!$this->bridge->chdir($part)) {
                        $this->bridge->mkdir($part);
                        $this->bridge->chmod(0775, $part);
                        $this->bridge->chdir($part);
                    }
                }
                $this->bridge->chdir($cd);
                return true;
            }
        }
        return false;
    }

    /**
     * Starts uploading the giving local file to the FTP server.
     * @param string $localfile
     * @param string $dir The directory to upload the file
     * @param string|null $name The name to override when uploading file
     * @param int $mode
     * @param int $offset
     * @return bool
     */
    public function uploadFile(string $localfile, string $dir, ?string $name = null, int $mode = FTP_BINARY, int $offset = 0): bool
    {
        // Check if the current instance is valid, the remote directory exists, and the local file exists
        if($this->isValid() && $this->isDir($dir) && File::isFile($localfile)) {
            $localfile = $this->arrangeLDS($localfile);
            $dir = $this->arrangeRDS($dir);
            // Determine the destination path on the remote server
            $destination = $dir . $this->rds . (
                // Use the provided name if it's non-null and non-empty, otherwise use the basename of the local file
                Utils::isNonNull($name) && Utils::isNotEmptyString($name) ? $name : basename($localfile)
            );
            // Arrange the remote path to be in the correct format
            $destination = $this->arrangeRDS($destination);
            // Upload the file to the remote server using the adapter
            return $this->bridge->put($destination, $localfile, $mode, $offset);
        }
        // Return false if any of the checks fail
        return false;
    }

    /**
     * Starts downloading a remote file.
     * @param string $file
     * @param string $localdir The directory to download the file
     * @param string|null $localname $name The name to override when downloading file
     * @param int $mode
     * @param int $offset
     * @return bool
     */
    public function downloadFile(string $file, string $localdir, ?string $localname = null, int $mode = FTP_BINARY, int $offset = 0): bool
    {
        // Check if the current instance is valid, the remote file is not a directory, and the local directory can be created
        if($this->isValid() && $this->isFile($file) && File::createDir($localdir)) {
            $localdir = $this->arrangeLDS($localdir);
            $file = $this->arrangeRDS($file);
            // Determine the destination path on the local machine
            $destination = $localdir . DIRECTORY_SEPARATOR . (
                // Use the provided local name if it's non-null and non-empty, otherwise use the basename of the remote file
                Utils::isNonNull($localname) && Utils::isNotEmptyString($localname) ? $localname : basename($file)
            );
            // Arrange the local path to be in the correct format
            $destination = $this->arrangeLDS($destination);
            // Download the file from the remote server using the adapter
            return $this->bridge->get($destination, $file, $mode, $offset);
        }
        // Return false if any of the checks fail
        return false;
    }

    /**
     * Download complete remote directory to local directory
     * @param string $dir
     * @param string $localdir
     * @return bool
     */
    public function downloadDir(string $dir, string $localdir): bool
    {
        // Check if the current instance is valid, the remote directory exists, and the local directory can be created or created
        if($this->isValid() && $this->isDir($dir) && File::createDir($localdir)) {
            $localdir = $this->arrangeLDS($localdir);
            $dir = $this->arrangeRDS($dir);
            // Arrange the local directory path
            $localdir = $this->arrangeLDS(Utils::resolvePath($localdir));
            // Create fisrt level directory on local filesystem
            $localdir = $this->arrangeLDS(Path::merge($localdir, basename($dir)));
            File::createDir($localdir);
            return $this->downloadDirContents($dir, $localdir);
        }
        // Return false if any of the checks fail
        return false;
    }

    /**
     * Upload complete local directory to remote directory
     * @param string $localdir
     * @param string $dir
     * @return bool
     */
    public function uploadDir(string $localdir, string $dir): bool
    {
        // Check if the current instance is valid and the local directory exists
        if($this->isValid() && $this->isDir($dir) && File::isDir($localdir)) {
            // Arrange the local directory path
            $localdir = $this->arrangeLDS($localdir);
            // Set the remote root directory path by appending the basename of the local directory
            $dir = $this->arrangeRDS(Path::merge($dir, basename($localdir)));
            // Create the remote root directory
            $this->createDir($dir);
            // Recursively scan the local directory to get a list of files and directories
            $files = array_reverse($this->sortFilesFirst(File::scanDirRecursively($localdir)));
            // Loop through the sorted list and handle directories and files separately
            foreach($files as $file) {
                // Determine the remote destination path
                $remotedest = $dir . str_replace($localdir, "", $file);
                // If the local item is a directory, create it on the remote server
                if(is_dir($file)) {
                    $this->createDir($remotedest);
                } else {
                    $this->bridge->put($remotedest, $file, FTP_BINARY);
                }
            }
            // Return true if the upload was successful
            return true;
        }
        // Return false if any of the checks fail
        return false;
    }

    /**
     * Sets permissions on FTP file or directory.
     * @param string $file
     * @param int $mode
     * @return bool
     */
    public function setPermission(string $file, int $mode): bool
    {
        return $this->isValid() ? $this->bridge->chmod(octdec(str_pad($mode, 4, '0', STR_PAD_LEFT)), $file) : false;
    }

    /**
     * Get the FTP adapter
     * @return \PHPWebfuse\Instance\FTPClient\Bridge|null
     */
    public function getBridge(): ?\PHPWebfuse\Instance\FTPClient\Bridge
    {
        return $this->bridge;
    }

    // PRIVATE FUNCTIONS

    /**
     * Check if the stream is a valid FTP stream
     * @return bool
     */
    private function isValidStream(): bool
    {
        return Utils::isNonNull($this->connection) && (is_resource($this->connection) || $this->connection instanceof \FTP\Connection);
    }

    /**
     * Check if the bridge is a valid bridge from \PHPWebfuse\Instance\FTPClient\Bridge
     * @return bool
     */
    private function isValidBridge(): bool
    {
        return Utils::isNonNull($this->bridge) && $this->bridge instanceof \PHPWebfuse\Instance\FTPClient\Bridge;
    }

    /**
     * Check if both the connection and adapter are set
     * @return bool
     */
    private function isValid(): bool
    {
        return $this->isValidStream() && $this->isValidBridge();
    }

    /**
     * Open a new FTP connection
     * @param string $host
     * @param bool $secure If to use a secure connection
     * @param int $port
     * @param int $timeout
     * @return bool
     */
    private function FTPConnect(string $host, bool $secure = false, int $port = 21, int $timeout = 90): bool
    {
        if(Utils::isNull($this->connection)) {
            $connection = $secure ? @ftp_ssl_connect($host, $port, $timeout) : @ftp_connect($host, $port, $timeout);
            if(is_resource($connection) || $connection instanceof \FTP\Connection) {
                $this->connection = $connection;
                $this->host = $host;
                $this->port = $port;
                $this->timeout = $timeout;
                $this->setBridge($this->connection);
                $this->log("Successfully connected to the FTP server.");
            } else {
                $this->log("Failed to connect to the FTP server @ " . $host, "error");
            }
        }
        return Utils::isNonNull($this->connection);
    }

    /**
     * Set the FTP bridge
     * @param \FTP\Connection $connection
     */
    private function setBridge(\FTP\Connection $connection)
    {
        $this->bridge = new \PHPWebfuse\Instance\FTPClient\Bridge($connection);
    }

    /**
     * Log the user in
     * @param string $username
     * @param string $password
     * @return bool
     */
    private function FTPLogin(string $username, string $password): bool
    {
        return $this->isValid() ? $this->bridge->login($username, $password) : false;
    }

    /**
     * Arrange remote directory separator by replacing multiple separators joined together (\\ or //) to single separator
     * @param string $path
     * @return string
     */
    private function arrangeRDS(string $path): string
    {
        return Path::arrange_dir_separators_v2($path, false, $this->rds);
    }

    /**
     * Arrange local directory separator by replacing multiple separators joined together (\\ or //) to single separator
     * @param string $path
     * @return string
     */
    private function arrangeLDS(string $path): string
    {
        return Path::arrange_dir_separators_v2($path);
    }

    /**
     * Sort files first then folders for list()
     * @param array $lists
     * @return array
     */
    private function sortFilesFirstForList(array $lists): array
    {
        usort($lists, function (\PHPWebfuse\Instance\FTPClient\File $a, \PHPWebfuse\Instance\FTPClient\File $b) use ($lists) {
            if(!$a->isDir() && $b->isDir()) {
                // File comes first
                return -1;
            } elseif($a->isDir() && !$b->isDir()) {
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
     * Count logs container specific key sub string
     * @param string $ks
     * @return int
     */
    private function clogs(string $ks): int
    {
        $count = 0;
        foreach($this->logs as $index => $value) {
            if(Utils::containText($ks, $index)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get logs container specific key sub string
     * @param string $ks
     * @return array
     */
    private function glogs(string $ks): array
    {
        $logs = array();
        foreach($this->logs as $index => $value) {
            if(Utils::containText($ks, $index)) {
                $logs[$index] = $value;
            }
        }
        return $logs;
    }

    /**
     * Add message to log
     * @param string $message
     * @param string $k
     * @return void
     */
    private function log(string $message, string $k = "message"): void
    {
        $ks = \strtoupper($k) . "_";
        $count = $this->clogs($ks);
        $this->logs[$ks . ($count + 1)] = $message;
    }

    /**
    * Sort files first then folders
    * @param array $lists
    * @return array
    */
    private function sortFilesFirst(array $lists): array
    {
        usort($lists, function ($a, $b) use ($lists) {
            if(is_file($a) && is_dir($b)) {
                // File comes first
                return -1;
            } elseif(is_dir($a) && is_file($b)) {
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
     * Check if the system type is unix type
     * @return bool
     */
    private function isUnixType(): bool
    {
        return Utils::containText("unix", $this->rst) || Utils::containText("linux", $this->rst);
    }

    /**
     * Download directory contents to local directory
     * @param string $dir
     * @param string $localdir
     * @return bool
     */
    private function downloadDirContents(string $dir, string $localdir): bool
    {
        $downloadedAll = false;
        if($this->isValid() && $this->isDir($dir)) {
            $files = $this->bridge->nlist($dir);
            if(is_array($files)) {
                $toDownload = 0;
                $downloaded = 0;
                foreach($files as $file) {
                    # To prevent an infinite loop
                    if($file != "." && $file != "..") {
                        $toDownload++;
                        $localPath = Path::arrange_dir_separators_v2(Path::merge($localdir, basename($file)));
                        if($this->isDir($file)) {
                            // Create directory on local filesystem
                            File::createDir($localPath);
                            // Recursive part
                            if($this->downloadDirContents($file, $localPath)) {
                                $downloaded++;
                            }
                        } else {
                            // Download files
                            if($this->bridge->get($localPath, $file, FTP_BINARY)) {
                                $downloaded++;
                            }
                        }
                    }
                }
                // Check all files and folders have been downloaded
                if($toDownload === $downloaded) {
                    $downloadedAll = true;
                }
            }
        }
        return $downloadedAll;
    }
}

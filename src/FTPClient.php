<?php

namespace PHPWebfuse;

/**
 */
class FTPClient
{
    /* PUBLIC VARIABLES */

    /**
     * @var \PHPWebfuse\Methods
     */
    private \PHPWebfuse\Methods $methods;

    /**
     * @var \PHPWebfuse\Path
     */
    private \PHPWebfuse\Path $path;

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
     * The remote path separator
     * @var string
     */
    private $RPS = "/";

    /**
     * The remote system type
     * @var string
     */
    private $RST = "unix";
    /* PRIVATE VARIABLES */

    /**
     * @var \FTP\Connection
     */
    private ?\FTP\Connection $connection;

    /**
     * @var \PHPWebfuse\FTPClient\FTPAdapter
     */
    private ?\PHPWebfuse\FTPClient\FTPAdapter $adapter;

    /**
     * @var \PHPWebfuse\FTPClient\FTPPath
     */
    private \PHPWebfuse\FTPClient\FTPPath $FtpPath;

    private bool $connected = false;
    private bool $loggedIn = false;

    /* PUBLIC METHODS */

    /**
     * TConstruct a new FTPClient instance
     * @throws \Exception
     */
    public function __construct()
    {
        $this->methods = new \PHPWebfuse\Methods();
        $this->path = new \PHPWebfuse\Path();
        $this->FtpPath = new \PHPWebfuse\FTPClient\FTPPath($this->RPS);
        $this->connection = null;
        $this->adapter = null;
        if (!extension_loaded('ftp')) {
            throw new \Exception('FTP extension is not loaded!');
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
     * @param bool $enablePassive True to enable passive mode
     * @return bool
     */
    public function login(string $username, string $password, bool $enablePassive = true): bool
    {
        if ($this->isValid() && $this->FTPLogin($username, $password) && $this->enablePassiveMode($enablePassive)) {
            $this->username = $username;
            $this->password = $password;
            // Get and set the remote path separator
            $systype = $this->adapter->systype();
            if (is_string($systype)) {
                $systype = strtolower($systype);
                $this->RST = $systype;
                if ($this->methods->containText("unix", $systype) || $this->methods->containText("linux", $systype)) {
                    $this->RPS = "/";
                } elseif ($this->methods->containText("windows", $systype) || $this->methods->containText("win", $systype) || $this->methods->containText("ms", $systype)) {
                    $this->RPS = "\\";
                }
            }
            $this->FtpPath = new \PHPWebfuse\FTPClient\FTPPath($this->RPS);
            return true;
        }
        return false;
    }

    /**
     * To enable passive mode
     * @param bool $enable
     * @return type
     */
    public function enablePassiveMode(bool $enable = true)
    {
        return $this->isValid() && $this->loggedIn ? $this->adapter->pasv($enable) : false;
    }

    /**
     * Disconnected the FTP connection
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->isValid() && $this->connected) {
            try {
                $this->adapter->close();
                $this->adapter = null;
            } catch (\Throwable $e) {
                $this->setErrorMessage($e->getMessage());
            }
            $this->connection = null;
            $this->connected = false;
            $this->loggedIn = false;
        }
    }

    /**
     * Get the current working directory name
     * @return string | null
     * @throws \Exception
     */
    public function getCurrentDir(): ?string
    {
        if ($this->isValid() && $this->loggedIn) {
            $currentdir = $this->adapter->pwd();
            if (is_string($currentdir)) {
                return $currentdir;
            }
        }
        return null;
    }

    /**
     * Change the current working directory name
     * @param string $remotedir
     * @return bool
     * @throws \Exception
     */
    public function changeDir(string $remotedir): bool
    {
        return $this->isValid()  && $this->loggedIn ? $this->adapter->chdir($remotedir) : false;
    }

    /**
     * Change to the parent directory
     * @return bool
     * @throws \Exception
     */
    public function upDir(): bool
    {
        return $this->isValid() && $this->loggedIn ? $this->adapter->cdup() : false;
    }

    /**
     * Checks whether if the giving file is a directory or not
     * @param string $remotedir
     * @return bool
     */
    public function isDir(string $remotedir): bool
    {
        if ($this->isValid() && $this->loggedIn) {
            $currentdir = $this->getCurrentDir();
            if ($this->adapter->chdir($remotedir)) {
                $this->adapter->chdir($currentdir);
                return true;
            }
        }
        return false;
    }

    /**
     * Check if it's a file
     * @param string $remotefile
     * @return bool
     */
    public function isFile(string $remotefile): bool
    {
        if ($this->isValid() && $this->loggedIn) {
            $size = $this->adapter->size($remotefile);
            return $size != -1;
        }
        return false;
    }

    /**
     * Empty or clean a directory
     * @param string $remotedir
     * @return bool
     */
    public function cleanDir(string $remotedir): bool
    {
        if ($this->isValid() && $this->loggedIn && $this->isDir($remotedir)) {
            $list = $this->list($remotedir, true, true);
            $list = $this->sortFilesFirstForList($list);
            foreach ($list as $info) {
                $realPath = $info->getRealPath();
                if (!$info->isDir()) {
                    // Delete file
                    $this->adapter->delete($realPath);
                } else {
                    // Change back to the parent directory
                    $this->adapter->chdir("..");
                    // Recursively delete the contents of the directory
                    $this->cleanDir($realPath);
                    // After emptying the directory, delete the directory itself
                    $this->adapter->rmdir($realPath);
                }
            }
            return $this->isDirEmpty($remotedir);
        }
        return false;
    }

    /**
     * Removes a directory
     * @param string $remotedir
     * @return bool
     */
    public function deleteDir(string $remotedir): bool
    {
        if ($this->isValid() && $this->loggedIn && $this->isDir($remotedir)) {
            return $this->cleanDir($remotedir) && $this->adapter->rmdir($remotedir);
        }
        return false;
    }

    /**
     * Send an arbitrary command to the server.
     * The result is formatted as:
     * array('response' => string, 'code' => int, 'message' => string, 'body' => string, 'endmessage' => string, 'success' => bool, 'responsecode'=>int);
     * @param string $command
     * @return array
     * @throws \Exception
     */
    public function raw(string $command): array
    {
        if ($this->isValid() && $this->loggedIn) {
            $command = trim($command);
            if (!$raw = $this->adapter->raw($command)) {
                throw new \Exception("Failed to send the command [{$command}] to the server.");
            }
            $code = $message = $body = $endmessage = null;
            // Get the response code
            if (preg_match('/^\d+/', $raw[0], $matches) !== false) {
                $code = (int) $matches[0];
            }
            // Get the message
            if (preg_match('/[A-z ]+.*/', $raw[0], $matches) !== false) {
                $message = $matches[0];
            }
            // If the response is multiline response then search for the body and the endmessage
            $count = count($raw);
            if ($count > 1) {
                $body = array_slice($raw, 1, -1);
                $endmessage = $raw[$count - 1];
            }
            return array('response' => $raw, 'code' => $code, 'message' => $message, 'body' => $body, 'endmessage' => $endmessage, 'success' => $code ? $code < 400 : false, "responsecode" => $code);
        }
        return array();
    }

    /**
     * Requests execution of a command on the FTP server
     * @param string $command
     * @return bool
     */
    public function exec(string $command): bool
    {
        return $this->isValid() && $this->loggedIn ? $this->adapter->exec($command) : false;
    }

    /**
     * Sends a SITE command to the server
     * @param string $command
     * @return bool
     */
    public function site(string $command): bool
    {
        return $this->isValid() && $this->loggedIn ? $this->adapter->site($command) : false;
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
     * Returns a detailed list of files in the given directory
     * @param string $remotedir
     * @param bool $recursive
     * @param bool $showHidden
     * @param bool $asc
     * @return array
     */
    public function list(string $remotedir, bool $recursive = false, bool $showHidden = false, bool $asc = true): array
    {
        $list = array();
        $remotedir = $remotedir == "." ? $this->RPS : $remotedir;
        if ($this->isValid() && $this->loggedIn) {
            // Retrieve the raw list of directory contents
            $remotedirlines = $this->adapter->rawlist($remotedir);
            // Ensure the raw list is an array
            if (is_array($remotedirlines)) {
                foreach ($remotedirlines as $line) {
                    $info = new \PHPWebfuse\FTPClient\FTPFile($this, $this->adapter, $remotedir, $line, $this->RPS);
                    $list[$info->getKey()] = $info;
                    // Recursively list directory contents if recursive is true
                    if ($recursive && $info->isDir()) {
                        foreach ($this->list($info->getRealPath()) as $lk => $li) {
                            $list[$lk] = $li;
                        }
                    }
                }
            }
        }
        if (!empty($list)) {
            // Loop to remove hidden when list hideen is false
            if (!$showHidden) {
                foreach ($list as $lk => $li) {
                    if ($this->methods->startsWith(".", $li->getBasename())) {
                        unset($list[$lk]);
                        continue;
                    }
                }
            }
            // Sort the list in ascending or descending order
            if ($asc && function_exists('ksort ')) {
                ksort($list);
            } elseif (!$asc && function_exists('krsort ')) {
                krsort($list);
            }
        }
        // Return the list
        return $list;
    }

    /**
     * Count the items base on file type (file, directory, link, unknown).
     * @param string $remotedir
     * @param bool $recursive
     * @param bool $showHidden
     * @param string|null $type
     * @return int
     */
    public function countItems(string $remotedir, bool $recursive = false, bool $showHidden = true, ?string $type = null): int
    {
        $list = $this->list($remotedir, $recursive, $showHidden);
        $count = 0;
        foreach ($list as $info) {
            if ($type === null || $info->getType() == $type) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Count directories
     * @param string $remotedir
     * @param bool $recursive
     * @param bool $showHidden
     * @return int
     */
    public function countDirectories(string $remotedir, bool $recursive = false, bool $showHidden = true): int
    {
        return $this->countItems($remotedir, $recursive, $showHidden, "directory");
    }

    /**
     * Count files
     * @param string $remotedir
     * @param bool $recursive
     * @param bool $showHidden
     * @return int
     */
    public function countFiles(string $remotedir, bool $recursive = false, bool $showHidden = true): int
    {
        return $this->countItems($remotedir, $recursive, $showHidden, "file");
    }

    /**
     * Count links
     * @param string $remotedir
     * @param bool $recursive
     * @param bool $showHidden
     * @return int
     */
    public function countLinks(string $remotedir, bool $recursive = false, bool $showHidden = true): int
    {
        return $this->countItems($remotedir, $recursive, $showHidden, "link");
    }

    /**
     * Count unknown files
     * @param string $remotedir
     * @param bool $recursive
     * @param bool $showHidden
     * @return int
     */
    public function countUnknown(string $remotedir, bool $recursive = false, bool $showHidden = true): int
    {
        return $this->countItems($remotedir, $recursive, $showHidden, "unknown");
    }

    /**
     * Deletes a regular remote file on the server.
     * @param string $remotefile
     * @return bool
     */
    public function deleteFile(string $remotefile): bool
    {
        if ($this->isValid() && $this->loggedIn && $this->isFile($remotefile)) {
            return $this->adapter->delete($remotefile);
        }
        return false;
    }

    /**
     * Check if a directory is empty.
     * @param string $remotefile
     * @return bool
     */
    public function isDirEmpty(string $remotefile): bool
    {
        return $this->countItems($remotefile, false, true) < 1;
    }

    /**
     * Gets last modified time of a remote file or directory
     * @param string $remotefile
     * @param string|null $format
     * @return int|string
     */
    public function lastModifiedTime(string $remotefile, ?string $format = null): int|string
    {
        if ($this->isValid() && $this->loggedIn && $this->isFile($remotefile)) {
            $time = $this->adapter->mdtm($remotefile);
            if ($time != -1) {
                return is_string($format) && $this->methods->isNotEmptyString($format) ? date($format, $time) : $time;
            }
        }
        return 0;
    }

    /**
     * Gets remote directory size.
     * @param string $remotedir
     * @return int
     */
    public function dirSize(string $remotedir): int
    {
        $lists = $this->list($remotedir, true, true);
        $size = 0;
        foreach ($lists as $info) {
            if (!$info->isDir()) {
                $size += (int) $info->getSize();
            }
        }
        return $size;
    }

    /**
     * Gets a regular remote file size.
     * @param string $remotefile
     * @param bool $format
     * @return int
     */
    public function getSize(string $remotefile, bool $format = false): int|string
    {
        if ($this->isValid() && $this->loggedIn && $this->isFile($remotefile)) {
            $size = $this->adapter->size($remotefile);
            if ($size != -1) {
                return $format ? $this->methods->formatSize((int) $size) : $size;
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
        return $this->isValid() && $this->loggedIn ? $this->adapter->rename($from, $to) : false;
    }

    /**
     * An alias of rename
     * @param string $from
     * @param string $to
     * @return type
     */
    public function move(string $from, string $to): bool
    {
        return $this->isValid() && $this->loggedIn ? $this->adapter->rename($from, $to) : false;
    }

    /**
     * Moves a remote file/directory to another path.
     * @param string $remotesource
     * @param string $remotedir
     * @return bool
     */
    public function moveInto(string $remotesource, string $remotedir): bool
    {
        return $this->rename($remotesource, $this->arrangeRPath($remotedir . $this->RPS . basename($remotesource)));
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
        return $this->isValid() && $this->loggedIn ? $this->adapter->alloc($bytes) : false;
    }

    /**
     * Reads the remote file content and returns the data as a string
     * @param string $remotefile
     * @param int $mode
     * @param int $offset
     * @return string
     */
    public function getContent(string $remotefile, int $mode = FTP_BINARY, int $offset = 0): string
    {
        if ($this->isValid() && $this->loggedIn && $this->isFile($remotefile)) {
            $temp = tempnam(sys_get_temp_dir(), $remotefile);
            $content = $this->adapter->get($temp, $remotefile, $mode, $offset);
            if ($content === true) {
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
     * @param string $remotefile
     * @param type $content
     * @param int $mode
     * @return bool
     */
    public function createFile(string $remotefile, ?string $content = null, int $mode = FTP_BINARY): bool
    {
        if ($this->isValid() && $this->loggedIn && !$this->isFile($remotefile)) {
            $handle = @fopen('php://temp', 'w');
            if (!is_bool($handle)) {
                if ($this->methods->isNonNull($content) && $this->methods->isNotEmptyString($content)) {
                    // Write the content to the temporary file
                    fwrite($handle, $content);
                }
                // Rewind the pointer of the temporary file
                rewind($handle);
                // Upload the temporary file to the remote file
                $result = $this->adapter->fput($remotefile, $handle, $mode);
                // Close the temporary file
                fclose($handle);
                return $result;
            }
        }
        return false;
    }

    /**
     * Save content (override) to an existing file
     * @param string $remotefile
     * @param string $content
     * @param int $mode
     * @return bool
     */
    public function saveContent(string $remotefile, string $content, int $mode = FTP_BINARY): bool
    {
        if ($this->isValid() && $this->loggedIn && $this->isFile($remotefile)) {
            // Open a temporary stream for writing
            $handle = @fopen('php://temp', 'w');
            if (!is_bool($handle)) {
                // Write the content to the temporary file
                fwrite($handle, $content);
                // Rewind the pointer of the temporary file
                rewind($handle);
                // Upload the temporary file to the remote file
                $result = $this->adapter->fput($remotefile, $handle, $mode);
                // Close the temporary file
                fclose($handle);
                return $result;
            }
        }
        return false;
    }

    /**
     * Creates a directory on the FTP server.
     * @param string $remotedir
     * @return bool
     */
    public function createDir(string $remotedir): bool
    {
        if ($this->isValid() && $this->loggedIn) {
            if ($this->isDir($remotedir)) {
                return true;
            } else {
                $currentdir = $this->getCurrentDir();
                $parts = array_filter(explode($this->RPS, $remotedir));
                foreach ($parts as $part) {
                    if (!$this->adapter->chdir($part)) {
                        $this->adapter->mkdir($part);
                        $this->adapter->chmod(0775, $part);
                        $this->adapter->chdir($part);
                    }
                }
                $this->adapter->chdir($currentdir);
                return true;
            }
        }
        return false;
    }

    /**
     * Starts uploading the giving local file to the FTP server.
     * @param string $localfile
     * @param string $remotedir The directory to upload the file
     * @param string|null $name The name to override when uploading file
     * @param int $mode
     * @param int $offset
     * @return bool
     */
    public function uploadFile(string $localfile, string $remotedir, ?string $name = null, int $mode = FTP_BINARY, int $offset = 0): bool
    {
        // Check if the current instance is valid, the remote directory exists, and the local file exists
        if ($this->isValid() && $this->loggedIn && $this->isDir($remotedir) && $this->methods->isFile($localfile)) {
            // Determine the destination path on the remote server
            $destination = $remotedir . $this->RPS . (
                // Use the provided name if it's non-null and non-empty, otherwise use the basename of the local file
                $this->methods->isNonNull($name) && $this->methods->isNotEmptyString($name) ? $name : basename($localfile)
            );
            // Arrange the remote path to be in the correct format
            $destination = $this->arrangeRPath($destination);
            // Upload the file to the remote server using the adapter
            return $this->adapter->put($destination, $localfile, $mode, $offset);
        }
        // Return false if any of the checks fail
        return false;
    }

    /**
     * Starts downloading a remote file.
     * @param string $remotefile
     * @param string $localdir The directory to download the file
     * @param string|null $localname $name The name to override when downloading file
     * @param int $mode
     * @param int $offset
     * @return bool
     */
    public function downloadFile(string $remotefile, string $localdir, ?string $localname = null, int $mode = FTP_BINARY, int $offset = 0): bool
    {
        // Check if the current instance is valid, the remote file is not a directory, and the local directory can be created
        if ($this->isValid() && $this->loggedIn && $this->isFile($remotefile) && $this->methods->makeDir($localdir)) {
            // Determine the destination path on the local machine
            $destination = $localdir . DIRECTORY_SEPARATOR . (
                // Use the provided local name if it's non-null and non-empty, otherwise use the basename of the remote file
                $this->methods->isNonNull($localname) && $this->methods->isNotEmptyString($localname) ? $localname : basename($remotefile)
            );
            // Arrange the local path to be in the correct format
            $destination = $this->arrangeLPath($destination);
            // Download the file from the remote server using the adapter
            return $this->adapter->get($destination, $remotefile, $mode, $offset);
        }
        // Return false if any of the checks fail
        return false;
    }

    /**
     * Download complete remote directory to local directory
     * @param string $remotedir
     * @param string $localdir
     * @return bool
     */
    public function downloadDir(string $remotedir, string $localdir): bool
    {
        // Check if the current instance is valid, the remote directory exists, and the local directory can be created or created
        if ($this->isValid() && $this->loggedIn && $this->isDir($remotedir) && $this->methods->makeDir($localdir)) {
            // Arrange the local directory path and append a directory separator
            $localdir = $this->arrangeLPath($this->methods->resolvePath($localdir));
            // Create fisrt level directory on local filesystem
            $localdir = $this->arrangeLPath($localdir . DIRECTORY_SEPARATOR . basename($remotedir));
            $this->methods->makeDir($localdir);
            return $this->downloadDirContents($remotedir, $localdir);
        }
        // Return false if any of the checks fail
        return false;
    }

    /**
     * Upload complete local directory to remote directory
     * @param string $localdir
     * @param string $remotedir
     * @return bool
     */
    public function uploadDir(string $localdir, string $remotedir): bool
    {
        // Check if the current instance is valid and the local directory exists
        if ($this->isValid() && $this->loggedIn && $this->isDir($remotedir) && $this->methods->isDir($localdir)) {
            // Arrange the local directory path
            $localdir = $this->arrangeLPath($localdir);
            // Set the remote root directory path by appending the basename of the local directory
            $remoterootdir = $this->arrangeRPath($remotedir . $this->RPS . basename($localdir));
            // Create the remote root directory
            $this->createDir($remoterootdir);
            // Recursively scan the local directory to get a list of files and directories
            $files = array_reverse($this->sortFilesFirst($this->methods->scanDirRecursively($localdir)));
            // Loop through the sorted list and handle directories and files separately
            foreach ($files as $file) {
                // Determine the remote destination path
                $remotedest = $remoterootdir . str_replace($localdir, "", $file);
                // If the local item is a directory, create it on the remote server
                if (is_dir($file)) {
                    $this->createDir($remotedest);
                } else {
                    $this->adapter->put($remotedest, $file, FTP_BINARY);
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
     * @param string $remotefile
     * @param int $mode
     * @return bool
     */
    public function setPermission(string $remotefile, int $mode): bool
    {
        return $this->isValid() && $this->loggedIn ? $this->adapter->chmod(octdec(str_pad($mode, 4, '0', STR_PAD_LEFT)), $remotefile) : false;
    }

    /**
     * Get the last error message
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Get the FTP adapter
     * @return \PHPWebfuse\FTPClient\FTPAdapter|null
     */
    public function getAdapter(): ?\PHPWebfuse\FTPClient\FTPAdapter
    {
        return $this->adapter;
    }

    // PRIVATE FUNCTIONS

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
        if(!$this->connected) {
            $stream = $secure ? @ftp_ssl_connect($host, $port, $timeout) : @ftp_connect($host, $port, $timeout);
            if (is_resource($stream) || $stream instanceof \FTP\Connection) {
                $this->connection = $stream;
                $this->host = $host;
                $this->port = $port;
                $this->timeout = $timeout;
                $this->setAdapter(new \PHPWebfuse\FTPClient\FTPAdapter($this->connection));
                $this->connected = true;
            }
            $this->connected = false;
        }
        return $this->connected;
    }

    /**
     * Log the user in
     * @param string $username
     * @param string $password
     * @return bool
     */
    private function FTPLogin(string $username, string $password): ?bool
    {
        return $this->loggedIn ? $this->loggedIn : ($this->isValid() && $this->connected ? $this->adapter->login($username, $password) : false);
    }

    /**
     * Check if the stream is a valid FTP stream
     * @return bool
     */
    private function isValidStream(): bool
    {
        return $this->methods->isNonNull($this->connection) && (is_resource($this->connection) || $this->connection instanceof \FTP\Connection);
    }

    /**
     * Check if the adapter is a valid \PHPWebfuse\FTPClient\FTPAdapter
     * @return bool
     */
    private function isValidAdapter(): bool
    {
        return $this->adapter !== null || $this->adapter instanceof \PHPWebfuse\FTPClient\FTPAdapter;
    }

    /**
     * Check if both the connection and adapter are set
     * @return bool
     */
    private function isValid(): bool
    {
        return $this->isValidStream() && $this->isValidAdapter();
    }

    /**
     * Set the FTP adapter
     * @param \PHPWebfuse\FTPClient\FTPAdapter $adapter
     */
    private function setAdapter(\PHPWebfuse\FTPClient\FTPAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Get the FTP adapter error message
     * @return string|null
     */
    private function getAdapterErrorMessage(): ?string
    {
        if ($this->isValidAdapter()) {
            return $this->adapter->getErrorMessage();
        }
        return null;
    }

    /**
     * Get or determine a path separator from path
     * @param string $path
     * @return string
     */
    private function getSepFromPath(string $path): string
    {
        $sep = "/";
        if ($this->methods->containText("\\", $path)) {
            $sep = '\\';
        } else {
            $sep = '/';
        }
        return $sep;
    }

    /**
     * Arrange local path separators
     * @param string $path
     * @return string
     */
    private function arrangeLPath(string $path, bool $closeEdges = false): string
    {
        return $this->path->arrange_dir_separators($path, $closeEdges);
    }

    /**
     * Arrange remote path separators
     * @param string $path
     * @return string
     */
    private function arrangeRPath(string $path, bool $closeEdges = false): string
    {
        return $this->FtpPath->arrange_dir_separators($path, $closeEdges);
    }

    /**
     * Sort files first then folders for list()
     * @param array $lists
     * @return array
     */
    private function sortFilesFirstForList(array $lists): array
    {
        usort($lists, function (\PHPWebfuse\FTPClient\FTPFile $a, \PHPWebfuse\FTPClient\FTPFile $b) use ($lists) {
            if (!$a->isDir() && $b->isDir()) {
                // File comes first
                return -1;
            } elseif ($a->isDir() && !$b->isDir()) {
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
     * Sort files first then folders
     * @param array $lists
     * @return array
     */
    private function sortFilesFirst(array $lists): array
    {
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
     * Check if the system type is unix type
     * @return bool
     */
    private function isUnixType(): bool
    {
        return $this->methods->containText("unix", $this->RST) || $this->methods->containText("linux", $this->RST);
    }

    /**
     * Download directory contents to local directory
     * @param string $remotedir
     * @param string $localdir
     * @return bool
     */
    private function downloadDirContents(string $remotedir, string $localdir): bool
    {
        $downloadedAll = false;
        if ($this->isValid() && $this->loggedIn && $this->isDir($remotedir)) {
            $files = $this->adapter->nlist($remotedir);
            if (is_array($files)) {
                $toDownload = 0;
                $downloaded = 0;
                foreach ($files as $file) {
                    # To prevent an infinite loop
                    if ($file != "." && $file != "..") {
                        $toDownload++;
                        $localPath = $this->arrangeLPath($localdir . DIRECTORY_SEPARATOR . basename($file));
                        if ($this->isDir($file)) {
                            // Create directory on local filesystem
                            $this->methods->makeDir($localPath);
                            // Recursive part
                            if ($this->downloadDirContents($file, $localPath)) {
                                $downloaded++;
                            }
                        } else {
                            // Download files
                            if ($this->adapter->get($localPath, $file, FTP_BINARY)) {
                                $downloaded++;
                            }
                        }
                    }
                }
                // Check all files and folders have been downloaded
                if ($toDownload === $downloaded) {
                    $downloadedAll = true;
                }
            }
        }
        return $downloadedAll;
    }
}

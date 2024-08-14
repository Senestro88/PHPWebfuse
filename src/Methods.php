<?php

namespace PHPWebfuse;

/**
 * The PHPWebfuse 'Methods' class
 */
class Methods
{
    // PRIVATE VARIABLES

    /**
     * @var \PHPWebfuse\Path The default PHPWebfuse path class
     */
    private \PHPWebfuse\Path $path;

    /**
     * @var \DateTime Date time
     */
    private \DateTime $datetime;

    // PRIVATE CONSTANTS

    /**
     * @var const The default read and write chunk size (2MB)
     */
    private const CHUNK_SIZE = 2097152;

    /**
     * @var const The default read and write chunk size when encrypting or decrypting a file (5MB)
     */
    private const ENC_FILE_INTO_PARTS_CHUNK_SIZE = 5242880;

    /**
     * @var const The default character considered invalid
     */
    private const INVALID_CHARS = array("\\", "/", ":", ";", " ", "*", "?", "\"", "<", ">", "|", ",", "'");

    /**
     * @var const Files permission
     */
    private const FILE_PERMISSION = 0644;

    /**
     * @var const Directories permission
     */
    private const DIRECTORY_PERMISSION = 0755;

    /**
     * @var const Default image width for conversion
     */
    private const IMAGE_WIDTH = 450;

    /**
     * @var const Default image height for conversion
     */
    private const IMAGE_HEIGHT = 400;

    // PRIVATE CONSTANT VARIABLES

    /**
     * @var const Default user agent
     */
    public const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36";

    /**
     * @var const Default timezone
     */
    public const TIMEZONE = "Africa/Lagos";

    /**
     * @var const Default GMT
     */
    public const GMT = "+01:00";

    /**
     * @var cosnt Default errors stylesheet
     */
    public const ERRORS_CSS = array(
        'exception' => "width: 100%; padding: 5px; height: auto; position: relative; display: block; text-align: left; word-break: break-word; overflow-wrap: break-word; color: #d22c3c; background: transparent; font-size: 90%; margin: 5px auto; border: none; border-bottom: 2px dashed red; font-weight: normal;",
        'error' => "width: 100%; padding: 5px; height: auto; position: relative; display: block; text-align: left; word-break: break-word; overflow-wrap: break-word; color: black; background: transparent; font-size: 90%; margin: 5px auto; border: none; border-bottom: 2px dashed #da8d00; font-weight: normal;",
    );

    /**
     * @var const Common localhost addresses
     */
    public const LOCALHOST_DEFAULT_ADDRESSES = array('localhost', '127.0.0.1', '::1', '');

    /**
     * @var const Excluded private IP address ranges
     */
    public const PRIVATE_IP_ADDRESS_RANGES = array('10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '169.254.0.0/16', '127.0.0.0/8');

    /**
     * @var const Wether to check for Ip in private Ip ranges
     */
    public const CHECK_IP_ADDRESS_IN_RANGE = false;

    // PUBLIC METHODS

    public function __construct()
    {
        $this->path = new \PHPWebfuse\Path();
        $this->datetime = new \DateTime('now', new \DateTimeZone(self::TIMEZONE));
    }

    /**
     * Get the file size in bytes
     * @param string $absolutePath
     * @return int
     */
    public function getFIlesizeInBytes(string $absolutePath): int
    {
        $bytes = 0;
        if ($this->isFile($absolutePath)) {
            $absolutePath = $this->resolvePath($absolutePath);
            clearstatcache(false, $absolutePath);
            $size = @filesize($absolutePath);
            if ($this->isInt($size)) {
                $bytes = $size;
            } else {
                $handle = @fopen($absolutePath, 'rb');
                if ($this->isResource($handle)) {
                    while (($buffer = fgets($handle, self::CHUNK_SIZE)) !== false) {
                        $bytes += strlen($buffer);
                    }
                }
                fclose($handle);
            }
        }
        return $bytes;
    }

    /**
     * Create a file
     * @param string $path
     * @return bool
     */
    public function createFile(string $path): bool
    {
        $created = $this->isFile($path);
        if ($this->isFalse($created)) {
            $handle = @fopen($path, "w");
            if ($this->isResource($handle)) {
                fclose($handle);
                $this->setPermissions($path);
                $created = true;
            }
        }
        return $this->isTrue($created);
    }

    /**
     * Save content to file
     *
     * @param string $absolutePath
     * @param string $content
     * @param bool $append: Default to 'false'. Wether to append the content to the filename
     * @param bool $newline: Default to 'false'. Wether to append the content on a new line if $append is true
     * @return bool
     */
    public function saveContentToFile(string $absolutePath, string $content, bool $append = false, bool $newline = false): bool
    {
        $saved = false;
        if ($this->isFile($absolutePath) || ($this->isNotFile($absolutePath) && $this->createFile($absolutePath))) {
            $handle = @fopen($absolutePath, $append ? "a" : "w");
            if ($this->isResource($handle) && flock($handle, LOCK_EX | LOCK_SH)) {
                if ($this->isTrue($append) && $this->isTrue($newline) && $this->getFIlesizeInBytes($absolutePath) >= 1) {
                    $content = "\n" . $content;
                }
                $saved = $this->writeContentToHandle($handle, $content);
                // Flush output before releasing the lock
                fflush($handle);
                // Release the lock
                flock($handle, LOCK_UN);
                // Close the handle
                @fclose($handle);
            }
        }
        return $saved;
    }

    /**
     * Get file content
     *
     * @param string $absolutePath
     * @return string
     */
    public function getFileContent(string $absolutePath): string
    {
        $content = '';
        if ($this->isFile($absolutePath)) {
            $handle = @fopen($absolutePath, 'rb');
            if ($this->isResource($handle) && flock($handle, LOCK_EX | LOCK_SH)) {
                $start = null;
                $timeout = @ini_get('default_socket_timeout');
                while (!$this->safeFeof($handle, $start) && (microtime(true) - $start) < $timeout) {
                    $read = fread($handle, self::CHUNK_SIZE);
                    if ($this->isString($read)) {
                        $content .= $read;
                    } else {
                        break;
                    }
                }
                flock($handle, LOCK_UN);
                @fclose($handle);
            }
        }
        return str_ireplace(PHP_EOL, "\n", $content);
    }

    /**
     * Write content to handle
     *
     * @param mixed $handle
     * @param string $content
     * @return bool
     */
    public function writeContentToHandle(mixed $handle, string $content): bool
    {
        $offset = 0;
        if ($this->isResource($handle)) {
            while ($offset < strlen($content)) {
                $chunk = substr($content, $offset, self::CHUNK_SIZE);
                if ((@fwrite($handle, $chunk)) === false) {
                    break;
                }
                $offset += self::CHUNK_SIZE;
            }
        }
        return $offset >= 1;
    }

    /**
     * Get the operating system
     * @return string
     */
    public function getOS(): string
    {
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
     *
     * @param mixed $resource
     * @return bool
     */
    public function isResourceStream(mixed $resource): bool
    {
        return $this->isResource($resource) && @get_resource_type($resource) == "stream";
    }

    /**
     * Determine if resource is curl
     *
     * @param mixed $resource
     * @return bool
     */
    public function isResourceCurl(mixed $resource): bool
    {
        return $this->isResource($resource) && @get_resource_type($resource) == "curl";
    }

    /**
     * Sets the current process to unlimited execution time and unlimited memory limit
     *
     * @return void
     */
    public function unlimitedWorkflow(): void
    {
        @ini_set("memory_limit", "-1");
        @ini_set("max_execution_time", "0");
        @set_time_limit(0);
    }

    /**
     * Create a web browser cookie
     *
     * @param string $name
     * @param string $value
     * @param int $days
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @param string $samesite
     * @return bool
     */
    public function createCookie(string $name, string $value, int $days, string $path, string $domain, bool $secure, bool $httponly, string $samesite): bool
    {
        $expires = strtotime('+' . $days . ' days');
        return @setcookie($name, $value, array('expires' => $expires, 'path' => $path, 'domain' => $domain, 'secure' => $secure, 'httponly' => $httponly, 'samesite' => ucfirst($samesite)));
    }

    /**
     * Delete a web browser cookie
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @param string $samesite
     * @return bool
     */
    public function deleteCookie(string $name, string $path, string $domain, bool $secure, bool $httponly, string $samesite): bool
    {
        if (isset($_COOKIE[$name])) {
            $expires = strtotime('2010');
            $setcookie = @setcookie($name, "", array('expires' => $expires, 'path' => $path, 'domain' => $domain, 'secure' => $secure, 'httponly' => $httponly, 'samesite' => ucfirst($samesite)));
            if ($this->isTrue($setcookie)) {
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
     * Get the readable permission of a file
     *
     * @param string $absolutePath
     * @return string
     */
    public function getReadablePermission(string $absolutePath): string
    {
        // Convert numeric mode to symbolic representation
        $info = '';
        if ($this->isExists($absolutePath)) {
            // Get the file permissions as a numeric mode
            $perms = fileperms($absolutePath);
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
                $info = 'd'; // Directory
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
     *
     * @param string $absolutePath
     * @return string
     */
    public function getPermission(string $absolutePath): string
    {
        if ($this->isFile($absolutePath) && $this->isInt(@fileperms($this->resolvePath($absolutePath)))) {
            return substr(sprintf('%o', @fileperms($this->resolvePath($absolutePath))), -4);
        }
        return "";
    }

    /**
     * Get a file type
     *
     * @param string $absolutePath
     * @return string | bool
     */
    public function getFileType(string $absolutePath): string|bool
    {
        if ($this->isFile($absolutePath)) {
            return @filetype($this->resolvePath($absolutePath));
        }
        return "unknown";
    }

    /**
     * Gives information about a file or symbolic link
     *
     * @param string $absolutePath
     * @param string $key
     * @return array
     */
    public function getStats(string $absolutePath, string $key): array
    {
        if ($this->isFile($absolutePath)) {
            $stat = @lstat($absolutePath);
            $stats = array();
            if ($this->isArray($stat)) {
                foreach ($stat as $k => $v) {
                    if ($this->isString($k)) {
                        $stats[$k] = $v;
                    }
                }
                if ($this->isNotEmptyString($key) && isset($stats[$key])) {
                    return $stats[$key];
                }
                $stat = null;
                return $stats;
            }
        }
        return array();
    }

    /**
     * Get a file info
     *
     * @param string $absolutePath
     * @return array
     */
    public function getFileInfo(string $absolutePath): array
    {
        if ($this->isFile($absolutePath)) {
            $array = array();
            $i = new \SplFileInfo($absolutePath);
            $array['realpath'] = $i->getRealPath();
            $array['dirname'] = $i->getPath();
            $array['basename'] = $i->getBasename();
            $array['extension'] = $i->getExtension();
            $array['filename'] = $i->getBasename("." . $i->getExtension());
            $array['size'] = array('raw' => $i->getSize(), 'readable' => $this->formatSize($this->getFIlesizeInBytes($absolutePath)));
            $array['atime'] = array('raw' => $i->getATime(), 'readable' => $this->readableUnix($i->getATime()));
            $array['mtime'] = array('raw' => $i->getMTime(), 'readable' => $this->readableUnix($i->getMTime()));
            $array['ctime'] = array('raw' => $i->getCTime(), 'readable' => $this->readableUnix($i->getCTime()));
            $array['mime'] = $this->getMime($absolutePath);
            $array['type'] = $i->getType();
            $array['permission'] = array('raw' => $this->getPermission($absolutePath), 'readable' => $this->getReadablePermission($absolutePath));
            $array['owner'] = array('raw' => $i->getOwner(), 'readable' => (function_exists("posix_getpwuid") ? posix_getpwuid($i->getOwner()) : ""));
            $array['group'] = array('raw' => $i->getGroup(), 'readable' => (function_exists("posix_getgrgid") ? posix_getgrgid($i->getGroup()) : ""));
            if ($i->isLink()) {
                $array['target'] = $i->getLinkTarget();
            }
            $array['executable'] = $i->isExecutable();
            $array['readable'] = $i->isReadable();
            $array['writable'] = $i->isWritable();
            return array_filter($array);
        }
        return array();
    }

    /**
     * Touch a file (Sets access and modification time of file)
     *
     * @param string $absolutePath
     * @param int $mtime: Defaults to 'null'
     * @param int $atime: Defaults to 'null'
     * @return bool
     */
    public function touchFile(string $absolutePath, ?int $mtime = null, ?int $atime = null): bool
    {
        return @touch($absolutePath, $mtime, $atime);
    }

    /**
     * Get directory name of a file or directory
     *
     * @param string $path
     * @return string
     */
    public function getDirname(string $path): string
    {
        return isset(pathinfo($path)['dirname']) ? pathinfo($path)['dirname'] : $path;
    }

    /**
     * Get file or directory extension
     *
     * @param string $path
     * @return string
     */
    public function getExtension(string $path): string
    {
        return isset(pathinfo($path)['extension']) ? pathinfo($path)['extension'] : "";
    }

    /**
     * Get the owner of the file
     * @param string $absolutePath
     * @return int|false
     */
    public function getOwner(string $absolutePath): int|false
    {
        return @fileowner($absolutePath);
    }

    /**
     * Get the group of the file
     * @param string $absolutePath
     * @return int|false
     */
    public function getGroup(string $absolutePath): int|false
    {
        return @filegroup($absolutePath);
    }

    /**
     * Get the inode number of the file
     * @param string $absolutePath
     * @return int|false
     */
    public function getInode(string $absolutePath): int|false
    {
        return @fileinode($absolutePath);
    }

    /**
     * Get the type of the file
     * @param string $absolutePath
     * @return string|false
     */
    public function getType(string $absolutePath): string|false
    {
        return @filetype($absolutePath);
    }

    /**
     * Get the link target of the file
     * @param string $absolutePath
     * @return string|false
     */
    public function getSymLinkTarget(string $absolutePath): string|false
    {
        return @readlink($absolutePath);
    }

    /**
     * Get the real path of the file
     * @param string $absolutePath
     * @return string|false
     */
    public function getRealPath(string $absolutePath): string|false
    {
        return @$this->resolvePath($absolutePath);
    }

    /**
     * Get the owner name of the file
     * @param string $absolutePath
     * @return mixed
     */
    public function getOwnerName(string $absolutePath): mixed
    {
        return function_exists("posix_getpwuid") ? @posix_getpwuid($this->getOwner($absolutePath))['name'] : $this->getOwner($absolutePath);
    }

    /**
     * Get the group name of the file
     * @param string $absolutePath
     * @return mixed
     */
    public function getGroupName(string $absolutePath): mixed
    {
        return function_exists("posix_getpwuid") ? @posix_getgrgid($this->getGroup($absolutePath))['name'] : $this->getGroup($absolutePath);
    }

    /**
     * Changes file group
     * @param string $absolutePath
     * @param string|int $group
     * @return bool
     */
    public function changeGroup(string $absolutePath, string|int $group): bool
    {
        if ($this->isFile($absolutePath)) {
            return @chgrp($absolutePath, $group);
        }
        return false;
    }

    /**
     * Changes file owner
     * @param string $absolutePath
     * @param string|int $owner
     * @return bool
     */
    public function changeOwner(string $absolutePath, string|int $owner = ''): bool
    {
        if ($this->isFile($absolutePath)) {
            return @chown($absolutePath, $owner);
        }
        return false;
    }

    /**
     * Remove extension from a path name
     * @param string $path
     * @return string
     */
    public function removeExtension(string $path): string
    {
        $extension = $this->getExtension($path);
        if ($this->isNotEmptyString($extension)) {
            return substr($path, 0, -(strlen($extension) + 1));
        }
        return $path;
    }

    /**
     * Gets the size of a directory
     * @param string $path
     * @param bool $recursive
     * @return int
     */
    public function getDirSize(string $path, bool $recursive = true): int
    {
        $size = 0;
        $files = $this->isTrue($recursive) ? $this->scanDirRecursively($path) : $this->scanDir($path);
        foreach ($files as $index => $value) {
            if ($this->isFile($value) && $this->isReadable($value)) {
                $size += $this->getFIlesizeInBytes($value);
                clearstatcache(false, $value);
            }
        }
        $files = null;
        unset($files);
        return $size;
    }

    /**
     * Matches the given filename
     * @param string $filename
     * @param string $pattern
     * @return bool
     */
    public function matchFilename(string $filename, string $pattern): bool
    {
        $inverted = false;
        if ($pattern[0] == '!') {
            $pattern = substr($pattern, 1);
            $inverted = true;
        }
        return fnmatch($pattern, $filename) == ($inverted ? false : true);
    }

    /**
     * Open a directory recursively and list out the files
     * @param string $path
     * @return array
     */
    public function scanDirRecursively(string $path): array
    {
        $lists = array();
        if ($this->isNotEmptyString($path) && $this->isDir($path) && $this->isReadable($path)) {
            $path = $this->resolvePath($path);
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::CURRENT_AS_FILEINFO), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $list) {
                $lists[] = $this->resolvePath($list->getRealPath());
            }
            $i = null;
            unset($i);
        }
        return $lists;
    }

    /**
     * Scan a directory and return files list
     * @param string $path
     * @return array
     */
    public function scanDir(string $path): array
    {
        $lists = array();
        if ($this->isDir($path) && $this->isReadable($path)) {
            $path = $this->resolvePath($path);
            $iterator = new \IteratorIterator(new \DirectoryIterator($path));
            foreach ($iterator as $list) {
                if(!$list->isDot()) {
                    $lists[] = $this->resolvePath($list->getRealPath());
                }
            }

        }
        return $lists;
    }

    public function scanDirForPattern(string $path, string $pattern = "", bool $recursive = false): array
    {
        $lists = array();
        if ($this->isNotEmptyString($path) && $this->isDir($path) && $this->isReadable($path)) {
            $path = $this->resolvePath($path);
            $iterator = $recursive ? new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::CURRENT_AS_SELF) : new \DirectoryIterator($path);
            if(!$this->isEmptyString($pattern)) {
                $CallbackFilterIterator = $recursive ? "\RecursiveCallbackFilterIterator" : "\CallbackFilterIterator";
                $iterator = new $CallbackFilterIterator($iterator, function ($current) use ($pattern) {
                    // TRUE to accept the current item to the iterator, FALSE otherwise
                    if ($current->isDir() && !$current->isDot()) {
                        return true;
                    } else {
                        return $this->matchFilename($current->getRealPath(), $pattern);
                    }
                });
            }
            $iterator = $recursive ? new \RecursiveIteratorIterator($iterator) : new \IteratorIterator($iterator);
            foreach ($iterator as $key => $list) {
                $lists[] = $this->resolvePath($list->getRealPath());
            }
        }

        return $lists;
    }

    /**
     * Gets the information of file in a directory
     * @param string $path
     * @param bool $recursive
     * @return array
     */
    public function getDirFilesInfo(string $path, bool $recursive = true): array
    {
        $array = array();
        $files = $this->isTrue($recursive) ? $this->scanDirRecursively($path) : $this->scanDir($path);
        foreach ($files as $index => $file) {
            if ($this->isFile($file)) {
                $array[] = $this->getFileInfo($file);
            } elseif ($this->isDir($file)) {

            }
        }
        $files = null;
        unset($files);
        return $array;
    }

    /**
     * Search a directory
     * @param string $path
     * @param array $matches
     * @param bool $asExtensions If true, it assume $matches are extensions to match against else match filenames containing the matches
     * @param bool $recursive
     * @return array
     */
    public function searchDir(string $path, array $matches = array(), bool $asExtensions = false, bool $recursive = true): array
    {
        $results = array();
        // Get files list, either recursively or non-recursively
        $files = $recursive ? $this->scanDirRecursively($path) : $this->scanDir($path);
        // Iterate through each file in the directory
        foreach ($files as $file) {
            $info = $this->getFileInfo($file);
            // Check each match pattern
            foreach ($matches as $match) {
                if ($asExtensions) {
                    // If searching by extension, check the file extension
                    $extension = $info['extension'] ?? "";
                    if ($this->endsWith(strtolower($extension), strtolower($match))) {
                        $results[] = $file;
                    }
                } else {
                    // Otherwise, check if the file name contains the match string
                    if ($this->containText(strtolower($match), strtolower($file))) {
                        $results[] = $file;
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Convert a path name extension to either lowercase or uppercase
     * @param string $path
     * @param bool $toLowercase
     * @return string
     */
    public function convertExtension(string $path, bool $toLowercase = true): string
    {
        $extension = $this->getExtension($path);
        if ($this->isNotEmptyString($extension)) {
            return $this->removeExtension($path) . "." . ($toLowercase ? strtolower($extension) : strtoupper($extension));
        }
        return $path;
    }

    /**
     * Delete a file based on extensions
     * @param string $path
     * @param array $extensions
     * @param bool $recursive: Default to 'true'
     * @return void
     */
    public function deleteFilesBasedOnExtension(string $path, array $extensions = array(), bool $recursive = true): void
    {
        if ($this->isDir($path) && $this->isReadable($path)) {
            $path = $this->path->insert_dir_separator($this->resolvePath($path));
            foreach ($extensions as $extension) {
                if ($this->isTrue($recursive)) {
                    $i = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
                    foreach ($i as $list) {
                        $list = $this->resolvePath($list->getRealPath());
                        $info = $this->getFileInfo($list);
                        $si = (isset($info['extension']) ? $info['extension'] : "");
                        if (strtolower($si) == strtolower($extension)) {
                            $this->deleteFile($list);
                        }
                    }
                } else {
                    $glob = glob($path . '*.' . $extension);
                    if ($this->isNotFalse($glob) && $this->isArray($glob)) {
                        foreach ($glob as $list) {
                            $this->deleteFile($list);
                        }
                    }
                }
            }
        }
    }

    /**
     * Safe base64 encode a string
     * @param string $string
     * @return string
     */
    public function safeEncode(string $string): string
    {
        return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
    }

    /**
     * Safe base64 decode a string
     * @param string $string
     * @return string
     */
    public function safeDecode(string $string): string
    {
        return @base64_decode(str_pad(strtr($string, '-_', '+/'), (strlen($string) % 4), '=', STR_PAD_RIGHT));
    }

    /**
     * Strip tags and convert special characters to HTML entities
     * @param string $string
     * @return string
     */
    public function clean(string $string): string
    {
        return strip_tags(htmlspecialchars($string));
    }

    /**
     * Replace invalid characters @see INVALID_CHARS
     * It's case-insensitive
     * @param string $string
     * @return string
     */
    public function replaceInvalidChars(string $string): string
    {
        return isset($string) ? str_ireplace(self::INVALID_CHARS, array('-'), $string) : false;
    }

    /**
     * Remove special characters from string
     * @param string|null $string
     * @return string
     */
    public function removeSpecialChars(?string $string = null): string
    {
        return isset($string) ? preg_replace('/[^A-Za-z0-9]/', '', $string) : false;
    }

    /**
     * Get the protocol (http or https)
     * @return string
     */
    public function protocol(): string
    {
        return getenv("HTTPS") !== null && getenv("HTTPS") === 'on' ? "https" : "http";
    }

    /**
     * Get the server protocol (HTTP/1.1)
     * @return string
     */
    public function serverProtocol(): string
    {
        return getenv("SERVER_PROTOCOL");
    }

    /**
     *
     * @return string
     * @return stringGet the host (localhost)
     * @return string
     */
    public function host(): string
    {
        return getenv('HTTP_HOST');
    }

    /**
     * Get the http referer
     * @return string
     */
    public function referer(): string
    {
        return getenv("HTTP_REFERER");
    }

    /**
     * Get the server name (localhost)
     * @return string
     */
    public function serverName(): string
    {
        return getenv("SERVER_NAME");
    }

    /**
     * Get the php self value (/index.php)
     * @return string
     */
    public function self(): string
    {
        return getenv("PHP_SELF");
    }

    /**
     * Get the script filename (C:/xampp/htdocs/index.php)
     * @return string
     */
    public function scriptFilename(): string
    {
        return getenv("SCRIPT_FILENAME");
    }

    /**
     * Get the script filename (/index.php)
     * @return string
     */
    public function scriptName(): string
    {
        return getenv("SCRIPT_NAME");
    }

    /**
     * Get the unix timestamp
     * @return int
     */
    public function unixTimestamp(): int
    {
        return time();
    }

    /**
     * Get the current url
     * @return string
     */
    public function currentUrl(): string
    {
        return $this->protocol() . "://" . $this->host();
    }

    /**
     * Get the complete current url with referer
     * @return string
     */
    public function completeCurrentUrl(): string
    {
        return $this->currentUrl() . $this->path->left_delete_dir_separator($this->requestURI());
    }

    /**
     * Get the http user agent (Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36)
     * @return string
     */
    public function userAgent(): string
    {
        return getenv("HTTP_USER_AGENT");
    }

    /**
     * Check if ssl is active
     * @return bool
     */
    public function sslActive(): bool
    {
        return (getenv('HTTPS') == 'on' || getenv('HTTPS') == '1' || getenv('SERVER_PORT') == '443');
    }

    /**
     * Create a session
     * @param string $name
     * @param string $value
     * @return bool
     */
    public function createSession(string $name, string $value): bool
    {
        @session_start();
        $_SESSION[$name] = $value;
        return isset($_SESSION[$name]);
    }

    /**
     * Delete a session
     * @param string $name
     * @return bool
     */
    public function deleteSession(string $name): bool
    {
        @session_start();
        unset($_SESSION[$name]);
        return !isset($_SESSION[$name]);
    }

    /**
     * Determine if argument is a function
     * @param mixed $arg
     * @return bool
     */
    public function isFunction(mixed $arg): bool
    {
        return ($arg instanceof Closure) && is_callable($arg);
    }

    /**
     * Get the request url
     * @return string
     */
    public function requestURI(): string
    {
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
    public function arrayToJson(array $array): string
    {
        return json_encode($array, JSON_FORCE_OBJECT);
    }

    /**
     * Convert string to json
     * @param string $string
     * @return string
     */
    public function stringToJson(string $string): string
    {
        return json_encode($string, JSON_FORCE_OBJECT);
    }

    /**
     * Convert json to array
     * @param string $json
     * @return array
     */
    public function jsonToArray(string $json): array
    {
        return json_decode($json, JSON_OBJECT_AS_ARRAY) ?? [];
    }

    /**
     * Convert array to string
     * @param array $array
     * @param string $separator The character to use as the separator
     * @return string
     */
    public function arrayToString(array $array, string $separator = ", "): string
    {
        return $this->isArray($array) ? implode($separator, $array) : "";
    }

    /**
     * Base64 encode a string and remove it padding
     * @param string $data
     * @return string
     */
    public function base64_encode_no_padding(string $data): string
    {
        $encoded = base64_encode($data);
        return rtrim($encoded, '=');
    }

    /**
     * Base64 decode an encoded string from base64_encode_no_padding @see base64_encode_no_padding
     * @param string $string
     * @return string
     */
    public function base64_decode_no_padding(string $string): string
    {
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
    public function base64_encode_crlf(string $string): string
    {
        $encoded = base64_encode($string);
        return str_replace("\n", "\r\n", $encoded);
    }

    /**
     * Base64 decode an encoded string from base64_encode_crlf @see base64_encode_crlf
     * @param string $string
     * @return string
     */
    public function base64_decode_crlf(string $string): string
    {
        $string = str_replace("\r\n", "\n", $string);
        return base64_decode($string);
    }

    /**
     * Base64 encode a string making it url safe
     * @param string $string
     * @return string
     */
    public function base64_encode_url_safe(string $string): string
    {
        $encoded = strtr(base64_encode($string), '+/', '-_');
        return rtrim($encoded, '=');
    }

    /**
     * Base64 decode an encoded string from base64_encode_url_safe @see base64_encode_url_safe
     * @param string $string
     * @return string
     */
    public function base64_decode_url_safe(string $string): string
    {
        $string = strtr($string, '-_', '+/');
        return base64_decode($string);
    }

    /**
     * Base64 encode a string into one line
     * @param string $string
     * @return string
     */
    public function base64_encode_no_wrap(string $string): string
    {
        $encoded = base64_encode($string);
        return str_replace("\n", '', $encoded);
    }

    /**
     * Base64 decode an encoded string from base64_encode_no_wrap @see base64_encode_no_wrap
     * @param string $string
     * @return string
     */
    public function base64_decode_no_wrap(string $string): string
    {
        $string = str_replace("\n", '', $string);
        return base64_decode($string);
    }

    /**
     * Set file or directory permission
     * @param string $path
     * @param bool $recursive
     * @return bool
     */
    public function setPermissions(string $path, bool $recursive = false): bool
    {
        if ($this->isFile($path)) {
            $path = $this->resolvePath($path);
            return @chmod($path, self::FILE_PERMISSION);
        } elseif ($this->isDir($path)) {
            if ($this->isTrue($recursive)) {
                $i = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($i as $list) {
                    $list = $this->resolvePath($list->getRealPath());
                    if ($this->isFile($list)) {
                        @chmod($list, self::FILE_PERMISSION);
                    } elseif ($this->isDir($list)) {
                        @chmod($list, self::DIRECTORY_PERMISSION);
                    }
                }
            }
            return @chmod($path, self::DIRECTORY_PERMISSION);
        }
        return false;
    }

    /**
     * Make a directory. This function will return true if the directory already exist
     * @param string $path
     * @return bool
     */
    public function makeDir(string $path): bool
    {
        if ($this->isDir($path)) {
            $this->setPermissions($path);
            return true;
        } else {
            try {
                if ($this->isTrue(@mkdir($path, self::DIRECTORY_PERMISSION, true))) {
                    $this->setPermissions($path);
                    return true;
                }
            } catch (\Throwable $e) {

            }
        }
        return false;
    }

    /**
     * Delete a file
     * @param string $absolutePath
     * @return bool
     */
    public function deleteFile(string $absolutePath): bool
    {
        if ($this->isFile($absolutePath)) {
            return @unlink($absolutePath);
        }
        return false;
    }

    /**
     * Delete a directory
     * @param string $path
     * @return bool
     */
    public function deleteDir(string $path): bool
    {
        return $this->emptyDirectory($path, true);
    }

    /**
     * Delete a file or directory
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        if ($this->isFile($path)) {
            return $this->deleteFile($path);
        } elseif ($this->isDir($path)) {
            return $this->deleteDir($path);
        }
        return false;
    }

    /**
     * Empty a directory
     * @param string $path
     * @param bool $delete Wether to delete the directory is self after deleting the directory contents
     * @return bool
     */
    public function emptyDirectory(string $path, bool $delete = false): bool
    {
        if ($this->isDir($path)) {
            $i = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($i as $list) {
                $list = $this->resolvePath($list->getRealPath());
                if ($this->isFile($list)) {
                    $this->deleteFile($list);
                } elseif ($this->isDir($list)) {
                    @rmdir($list);
                }
            }
            if ($this->isTrue($delete)) {
                return @rmdir($path);
            }
            return true;
        }
        return false;
    }

    /**
     * Set mb internal encoding
     * @staticvar array $encodings
     * @param type $reset Wether to reset
     * @return void
     */
    public function setMBInternalEncoding($reset = false): void
    {
        if ($this->isFalse(function_exists('mb_internal_encoding'))) {
            return;
        }
        static $encodings = [];
        $overloaded = (bool) ((int) ini_get('mbstring.func_overload') & 2);
        if (!$overloaded) {
            return;
        }
        if (!$reset) {
            $encoding = mb_internal_encoding();
            array_push($encodings, $encoding);
            mb_internal_encoding('ISO-8859-1');
        } elseif ($reset && $encodings) {
            $encoding = array_pop($encodings);
            mb_internal_encoding($encoding);
        }
    }

    /**
     * Check if headers are sent to browser
     * @return bool
     */
    public function headersSent(): bool
    {
        if (headers_sent() === true) {
            return true;
        }return false;
    }

    /**
     * Copy a file to destination
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function copyFile(string $source, string $destination): bool
    {
        if ($this->isFile($destination)) {
            return true;
        } elseif ($this->isFile($source)) {
            clearstatcache(false, $source);
            if (@copy($source, $destination)) {
                $this->setPermissions($destination);
                return true;
            }
        }
        return false;
    }

    /**
     * Copy a file to the destination directory
     * @param string $source
     * @param string $dir
     * @return bool
     */
    public function copyFileToDir(string $source, string $dir): bool
    {
        if ($this->isFile($source)) {
            $dir = $this->path->insert_dir_separator($dir);
            if ($this->isNotDir($dir)) {
                $this->makeDir($dir);
            }
            $destination = $dir . basename($source);
            if ($this->isFile($destination)) {
                return true;
            } elseif (@copy($source, $destination)) {
                $this->setPermissions($destination);
                return true;
            }
        }
        return false;
    }

    /**
     * Move a file or directory to destination
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function moveFile(string $source, string $destination): bool
    {
        if ($this->isFile($destination)) {
            return true;
        } elseif ($this->isFile($source)) {
            clearstatcache(false, $source);
            if (@rename($source, $destination)) {
                if ($source !== $destination) {
                    $this->deleteFile($source);
                }
                $this->setPermissions($destination);
                return true;
            }
        }
        return false;
    }

    /**
     * Move a file or directory to destination directory
     * @param string $absolutePath
     * @param string $dir
     * @return bool
     */
    public function moveFileToDir(string $absolutePath, string $dir): bool
    {
        if ($this->isFile($absolutePath)) {
            $dir = $this->path->insert_dir_separator($dir);
            if ($this->isNotDir($dir)) {
                $this->makeDir($dir);
            }
            $destination = $dir . basename($absolutePath);
            if ($this->isFile($destination)) {
                return true;
            } elseif (@rename($absolutePath, $destination)) {
                if ($absolutePath !== $destination) {
                    $this->deleteFile($absolutePath);
                }
                $this->setPermissions($destination);
                return true;
            }
        }
        return false;
    }

    /**
     * Generate a random unique string
     * @param string $which If value is not 'key' an id will be generated, else random characters
     * @return string
     */
    public function randUnique(string $which = "key"): string
    {
        if (strtolower($which) == 'key') {
            return hash_hmac('sha256', bin2hex(random_bytes(16)), '');
        }
        return str_shuffle(mt_rand(100000, 999999) . $this->unixTimestamp());
    }

    /**
     * Get the request header by key
     * @param string $key
     * @return string
     */
    public function getHeader(string $key): string
    {
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
    public function getAuthorizationHeader(): string
    {
        return $this->getHeader("Authorization");
    }

    /**
     * Parse a json string
     * @param string $string
     * @return string
     * @throws \Exception
     */
    public function parseJSON(string $string): string
    {
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
    public function scaleIDemention(int $originalWidth, int $originalHeight, int $width, int $height): array
    {
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
     * @param string $absolutePath If empty, the source will be used
     * @return bool
     */
    public function convertImage(string $source, string $extension = "webp", bool $useWandH = false, bool $scaleIDemention = false, string $absolutePath = ""): bool
    {
        $validExtensions = array("webp", "png", "jpg", "gif");
        if (in_array($extension, $validExtensions)) {
            $sourceData = @getimagesize($source);
            if ($this->isNotFalse($sourceData)) {
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
                if ($this->isNotFalse($image)) {
                    $imageWidth = $useWandH ? $width : self::IMAGE_WIDTH;
                    $imageHeight = $useWandH ? $height : self::IMAGE_HEIGHT;
                    if ($scaleIDemention) {
                        list($imageWidth, $imageHeight) = $this->scaleIDemention($width, $height, $imageWidth, $imageHeight);
                    }
                    $color = @imagecreatetruecolor($imageWidth, $imageHeight);
                    if ($this->isNotFalse($color)) {
                        @imagecopyresampled($color, $image, 0, 0, 0, 0, $imageWidth, $imageHeight, $width, $height);
                        $outputPath = $this->isNotEmptyString($absolutePath) ? $absolutePath : $source;
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
    public function hashString(string $string): string
    {
        return password_hash($string, PASSWORD_BCRYPT, array('cost' => 12));
    }

    /**
     * Check if string is the same with $hash when hashed by verifying it
     * @param string $string
     * @param string $hash
     * @return bool
     */
    public function hashVerified(string $string, string $hash): bool
    {
        return password_verify($string, $hash);
    }

    /**
     * Check if the hash needs to be re-hashed
     * @param string $hash
     * @return bool
     */
    public function hashNeedsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, array('cost' => 12));
    }

    /**
     * Check if $string contains $text
     * @param string $text
     * @param string $string
     * @return bool
     */
    public function containText(string $text, string $string): bool
    {
        return $this->isNotFalse(strpos($string, $text));
    }

    /**
     * Check if $string starts with $text
     * @param string $start
     * @param string $string
     * @return bool
     */
    public function startsWith(string $start, string $string): bool
    {
        $start = trim($start);
        return substr($string, 0, strlen($start)) === $start;
    }

    /**
     * Check if $string ends with $text
     * @param string $end
     * @param string $string
     * @return bool
     */
    public function endsWith(string $end, string $string): bool
    {
        $end = trim($end);
        return substr($string, -strlen($end)) === $end;
    }

    /**
     * Format a bytes to human readable
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatSize(int $bytes, int $precision = 2): string
    {
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
    public function hideEmailWithStarts(string $email): string
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $explodedEmail = explode("@", $email);
            $name = implode("@", array_slice($explodedEmail, 0, count($explodedEmail) - 1));
            $len = floor(strlen($name) / 2);
            return substr($name, 0, $len) . str_repeat("*", $len) . "@" . end($explodedEmail);
        }
        return $email;
    }

    /**
     * Randomize the string
     * @param string $string
     * @return string
     */
    public function randomizeString(string $string): string
    {
        if (empty($string)) {
            $string = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        }
        for ($i = 1; $i <= 10; $i++) {
            $string = str_shuffle(strrev($string));
        }
        return $string;
    }

    /**
     * Download a file
     * @param string $absolutePath
     * @return bool
     */
    public function downloadFile(string $absolutePath): bool
    {
        if ($this->isFile($absolutePath) && $this->headersSent() !== true) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($absolutePath));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . $this->getFIlesizeInBytes($absolutePath));
            flush();
            readfile($a);
            return true;
        }
        return false;
    }

    /**
     * Generate a unique id
     * @return int
     */
    public function generateUniqueId(): int
    {
        $uId = str_shuffle(mt_rand(100000, 999999) . $this->unixTimestamp());
        return substr($uId, 0, 10);
    }

    /**
     * Create a stream context
     * @param string $context Only 'http, 'curl', 'ssl'' are supported
     * @param string $method
     * @return mixed
     */
    public function createStreamContext(string $context = "http", string $method = "HEAD"): mixed
    {
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
    public function remoteFileExist(string $remoteFilename): bool
    {
        try {
            $getFile = @file_get_contents($remoteFilename, false, $this->createStreamContext(), 0, 5);
            if ($this->isNotFalse($getFile)) {
                return true;
            }
        } catch (\Throwable $e) {

        }
        return false;
    }

    /**
     * Get the domain name from a subdomain
     * @param string $host
     * @return string
     */
    public function getDomain(string $host): string
    {
        $domain = strtolower(trim($host));
        $count = substr_count($domain, '.');
        if ($count === 2) {
            if (strlen(explode('.', $domain)[1]) > 3) {
                $domain = explode('.', $domain, 2)[1];
            }
        } elseif ($count > 2) {
            $domain = $this->getDomain(explode('.', $domain, 2)[1]);
        }
        return $domain;
    }

    /**
     * Check if string is empty
     * @param string $string
     * @return bool
     */
    public function isEmptyString(string $string): bool
    {
        return $this->isString($string) && $this->isEmpty($string);
    }

    /**
     * Check if string is not empty
     * @param string $string
     * @return bool
     */
    public function isNotEmptyString(string $string): bool
    {
        return $this->isString($string) && $this->isNotEmpty($string);
    }

    /**
     * Check if argument is a string
     * @param mixed $arg
     * @return bool
     */
    public function isString(mixed $arg): bool
    {
        return is_string($arg);
    }

    /**
     * Check if argument is not a string
     * @param mixed $arg
     * @return bool
     */
    public function isNotString(mixed $arg): bool
    {
        return !is_string($arg);
    }

    /**
     * Determine if argument is empty
     * @param mixed $arg
     * @return bool
     */
    public function isEmpty(mixed $arg): bool
    {
        return @empty($arg);
    }

    /**
     * Determine if argument is not empty
     * @param mixed $arg
     * @return bool
     */
    public function isNotEmpty(mixed $arg): bool
    {
        return !$this->isEmpty($arg);
    }

    /**
     * Checks if a $value is in an $array
     * @param mixed $value
     * @param array $array
     * @return bool
     */
    public function inArray(mixed $value, array $array): bool
    {
        return @in_array($value, $array);
    }

    /**
     * Checks if a $value is not in an $array
     * @param mixed $value
     * @param array $array
     * @return bool
     */
    public function isNotInArray(mixed $value, array $array): bool
    {
        return !$this->inArray($value, $array);
    }

    /**
     * Check whether the argument is an array
     * @param mixed $arg
     * @return bool
     */
    public function isArray(mixed $arg): bool
    {
        return is_array($arg);
    }

    /**
     * Check whether the argument is not an array
     * @param mixed $arg
     * @return bool
     */
    public function isNotArray(mixed $arg): bool
    {
        return !$this->isArray($arg);
    }

    /**
     * Check is array is empty
     * @param array $array
     * @return bool
     */
    public function isEmptyArray(array $array): bool
    {
        return $this->isArray($array) && $this->isEmpty($array);
    }

    /**
     * Check is array is not empty
     * @param array $array
     * @return bool
     */
    public function isNotEmptyArray(array $array): bool
    {
        return !$this->isEmptyArray($array);
    }

    /**
     * Check if argument is Boolean
     * @param mixed $arg
     * @return bool
     */
    public function isBool(mixed $arg): bool
    {
        return @is_bool($arg);
    }

    /**
     * Check if argument is not Boolean
     * @param mixed $arg
     * @return bool
     */
    public function isNotBool(mixed $arg): bool
    {
        return !$this->isBool($arg);
    }

    /**
     * Check if argument is Integer
     * @param mixed $arg
     * @return bool
     */
    public function isInt(mixed $arg): bool
    {
        return @is_int($arg);
    }

    /**
     * Check if argument is not Integer
     * @param mixed $arg
     * @return bool
     */
    public function isNotInt(mixed $arg): bool
    {
        return !$this->isInt($arg);
    }

    /**
     * Check if argument is Null
     * @param mixed $arg
     * @return bool
     */
    public function isNull(mixed $arg): bool
    {
        return @is_null($arg);
    }

    /**
     * Check if argument is not Null
     * @param mixed $arg
     * @return bool
     */
    public function isNonNull(mixed $arg): bool
    {
        return !$this->isNull($arg);
    }

    /**
     * Check if argument is the true value of True
     * @param mixed $arg
     * @return bool
     */
    public function isTrue(mixed $arg): bool
    {
        return $arg === true;
    }

    /**
     * Check if argument is not the true value of True
     * @param mixed $arg
     * @return bool
     */
    public function isNotTrue(mixed $arg): bool
    {
        return !$this->isTrue($arg);
    }

    /**
     * Check if argument is the true value of False
     * @param mixed $arg
     * @return bool
     */
    public function isFalse(mixed $arg): bool
    {
        return $arg === false;
    }

    /**
     * Check if argument is not the true value of False
     * @param mixed $arg
     * @return bool
     */
    public function isNotFalse(mixed $arg): bool
    {
        return !$this->isFalse($arg);
    }

    /**
     * Check if argument is Float
     * @param mixed $arg
     * @return bool
     */
    public function isFloat(mixed $arg): bool
    {
        return @is_float($arg);
    }

    /**
     * Check if argument is not Float
     * @param mixed $arg
     * @return bool
     */
    public function isNotFloat(mixed $arg): bool
    {
        return !$this->isFloat($arg);
    }

    /**
     * Check if argument is Numeric
     * @param mixed $arg
     * @return bool
     */
    public function isNumeric(mixed $arg): bool
    {
        return @is_numeric($arg);
    }

    /**
     * Check if argument is not Float
     * @param mixed $arg
     * @return bool
     */
    public function isNotNumeric(mixed $arg): bool
    {
        return !$this->isNumeric($arg);
    }

    /**
     * Check if argument is Resource
     * @param mixed $arg
     * @return bool
     */
    public function isResource(mixed $arg): bool
    {
        return @is_resource($arg);
    }

    /**
     * Check if argument is not Resource
     * @param mixed $arg
     * @return bool
     */
    public function isNotResource(mixed $arg): bool
    {
        return !$this->isResource($arg);
    }

    /**
     * Get file size in bytes
     * @param string $absolutePath
     * @return int|false
     */
    public function getSize(string $absolutePath): int|false
    {
        return @filesize($absolutePath);
    }

    /**
     * Get file modification time
     * @param string $absolutePath
     * @return int|false
     */
    public function getMtime(string $absolutePath): int|false
    {
        return @filemtime($absolutePath);
    }

    /**
     * Get file mime content type
     * @param string $absolutePath
     * @return string|false
     */
    public function getMime(string $absolutePath): string|false
    {
        return @mime_content_type($absolutePath);
    }

    /**
     * Checks whether a file or directory exists
     * @param string $absolutePath
     * @return bool
     */
    public function isExists(string $absolutePath): bool
    {
        return @file_exists($absolutePath);
    }

    /**
     * Tells whether a file exists and is readable
     * @param string $absolutePath
     * @return bool
     */
    public function isReadable(string $absolutePath): bool
    {
        return @is_readable($absolutePath);
    }

    /**
     * Tells whether the filename is executable
     * @param string $absolutePath
     * @return bool
     */
    public function isExecutable(string $absolutePath): bool
    {
        return @is_executable($absolutePath);
    }

    /**
     * Tells whether the filename is writable
     * @param string $absolutePath
     * @return bool
     */
    public function isWritable(string $absolutePath): bool
    {
        return @is_writable($absolutePath);
    }

    /**
     * Tells whether the filename is a regular file
     * @param string $absolutePath
     * @return bool
     */
    public function isFile(string $absolutePath): bool
    {
        return @is_file($absolutePath);
    }

    /**
     * Tells whether the filename is not a regular file
     * @param string $absolutePath
     * @return bool
     */
    public function isNotFile(string $absolutePath): bool
    {
        return !$this->isFile($absolutePath);
    }

    /**
     * Tells whether the filename is a directory
     * @param string $dirname
     * @return bool
     */
    public function isDir(string $dirname): bool
    {
        return @is_dir($dirname);
    }

    /**
     * Tells whether the filename is not a directory
     * @param string $dirname
     * @return bool
     */
    public function isNotDir(string $dirname): bool
    {
        return !$this->isDir($dirname);
    }

    /**
     * Tells whether the filename is a symbolic link
     * @param string $absolutePath
     * @return bool
     */
    public function isLink(string $absolutePath): bool
    {
        return @is_link($absolutePath);
    }

    /**
     * Tells whether the filename is not a symbolic link
     * @param string $absolutePath
     * @return bool
     */
    public function isNotLink(string $absolutePath): bool
    {
        return !$this->isLink($absolutePath);
    }

    /**
     * Check if directory is empty
     * @param string $dirname
     * @return bool
     */
    public function isEmptyDir(string $dirname): bool
    {
        return ($this->isDir($dirname)) ? !(new \FilesystemIterator($dirname))->valid() : false;
    }

    /**
     * Check if directory is not empty
     * @param string $dirname
     * @return bool
     */
    public function isNotEmptyDir(string $dirname): bool
    {
        return !$this->isEmptyDir($dirname);
    }

    /**
     * Encrypt (AES) file into parts and save in directory
     * @param string $sourceFile The file to encrypt
     * @param string $toPath The directory to save the parts
     * @param string $key The encryption key
     * @param string $iv The encryption iv
     * @param string $method The encryption method
     * @return bool
     */
    public function encFileIntoParts(string $sourceFile, string $toPath, string $key, string $iv, string $method = "aes-128-cbc"): bool
    {
        if (in_array($method, openssl_get_cipher_methods())) {
            try {
                if ($this->isFile($sourceFile)) {
                    if ($this->isNotDir($toPath)) {
                        $this->makeDir($toPath);
                    }
                    $chunkSize = self::ENC_FILE_INTO_PARTS_CHUNK_SIZE;
                    $index = 1;
                    $startBytes = 0;
                    $totalBytes = $this->getFIlesizeInBytes($sourceFile);
                    while ($startBytes < $totalBytes) {
                        $remainingBytes = $totalBytes - $startBytes;
                        $chunkBytes = min($chunkSize, $remainingBytes);
                        $plainText = @file_get_contents($sourceFile, false, null, $startBytes, $chunkBytes);
                        if ($plainText !== false) {
                            $absolutePath = $this->path->insert_dir_separator($toPath) . '' . $index . '.part';
                            $index += 1;
                            $startBytes += $chunkBytes;
                            $encryptedText = @openssl_encrypt($plainText, $method, $key, $option = OPENSSL_RAW_DATA, $iv);
                            if ($encryptedText !== false) {
                                $this->saveContentToFile($absolutePath, $encryptedText);
                            }
                        }
                    }
                    return true;
                }
            } catch (Throwable $e) {

            }
        }
        return false;
    }

    /**
     * Decrypt (AES) a directory parts into a single file @see encFileIntoParts
     * @param string $sourcePath The parts directory
     * @param string $toFilename The filename to append parts to it
     * @param string $key The decryption key
     * @param string $iv The decryption iv
     * @param string $method The decryption method
     * @return bool
     */
    public function decPartsIntoFile(string $sourcePath, string $toFilename, string $key, string $iv, string $method = "aes-128-cbc"): bool
    {
        if (in_array($method, openssl_get_cipher_methods())) {
            try {
                if ($this->isDir($sourcePath)) {
                    if ($this->isFile($toFilename)) {
                        $this->deleteFile($toFilename);
                    }
                    $dirFiles = @scandir($sourcePath, $sortingOrder = SCANDIR_SORT_NONE);
                    $numOfParts = 0;
                    if ($dirFiles != false) {
                        foreach ($dirFiles as $currentFile) {
                            if (preg_match('/^\d+\.part$/', $currentFile)) {
                                $numOfParts++;
                            }
                        }
                    }
                    if ($numOfParts >= 1) {
                        for ($index = 1; $index <= $numOfParts; $index++) {
                            $absolutePath = $this->path->insert_dir_separator($sourcePath) . '' . $index . '.part';
                            if ($this->isFile($absolutePath)) {
                                $cipherText = @file_get_contents($absolutePath, false, null, 0, null);
                                if ($this->isNotFalse($cipherText)) {
                                    $this->deleteFile($absolutePath);
                                    $decryptedText = @openssl_decrypt($cipherText, $method, $key, $option = OPENSSL_RAW_DATA, $iv);
                                    if ($decryptedText !== false) {
                                        $this->saveContentToFile($toFilename, $decryptedText, true);
                                    }
                                }
                            }
                        }
                        return true;
                    }
                }
            } catch (Throwable $e) {

            }
        }
        return false;
    }

    /**
     * Load an extension
     * @param string $extension
     * @return bool
     */
    public function loadExtension(string $extension): bool
    {
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
    public function clearCache(): bool
    {
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
    public function formatInt(float $num): float|string
    {
        if ($num > 0 && $this->inArray("NumberFormatter", get_declared_classes())) {
            $formater = new \NumberFormatter('en_US', \NumberFormatter::PADDING_POSITION);
            return $formater->format($num);
        }
        return $num;
    }

    /**
     * Rename a file or directory
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function renameFile(string $source, string $destination): bool
    {
        if ($this->isFile($source) && $this->isNotFile($destination)) {
            return @rename($source, $destination);
        }
        return false;
    }

    /**
     * Replace GET parameter with value
     * @param string $param
     * @param mixed $value
     * @return string
     */
    public function replaceUrlParamValue(string $param, mixed $value): string
    {
        $currentUrl = $this->completeCurrentUrl();
        $parts = parse_url($currentUrl);
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $path = $parts['path'];
        parse_str(isset($parts['query']) ? $parts['query'] : "", $params);
        $params[$param] = $value;
        $newParams = http_build_query($params);
        $path = str_replace(array("//", "\\\\"), array("/", "\\"), $path);
        return $scheme . '://' . $host . '' . $path . '?' . $newParams;
    }

    /**
     * Reverse a string
     * @param string $string
     * @return string
     */
    public function reverseString(string $string): string
    {
        return strrev($string);
    }

    /**
     * MB revers a string
     * @param string $string
     * @param string|null $encoding
     * @return string
     */
    public function mb_reverseString(string $string, ?string $encoding = null): string
    {
        $chars = mb_str_split($string, 1, $encoding ?: mb_internal_encoding());
        return implode('', array_reverse($chars));
    }

    /**
     * Gets only numbers from string
     * @param string $string
     * @return string|array|null
     */
    public function onlyDigits(string $string): string|array|null
    {
        return $this->isString($string) ? @preg_replace('/[^0-9]/', '', $string) : null;
    }

    /**
     * Get only string from string
     * @param string $string
     * @return string|array|null
     */
    public function onlyString(string $string): string|array|null
    {
        return $this->isString($string) ? @preg_replace('/[0-9]/', '', $string) : null;
    }

    /**
     * The current path url
     * @return string
     */
    public function currentPathURL(): string
    {
        $ccUrl = $this->completeCurrentUrl();
        $parse = parse_url($ccUrl);
        $scheme = $parse['scheme'];
        $host = $parse['host'];
        $path = $this->path->arrange_dir_separators($parse['path'], true);
        return $scheme . '://' . $host . '' . $path;
    }

    /**
     * Convert special characters to HTML entities
     * @param string $string
     * @param string $encoding
     * @return string
     */
    public function xssafe(string $string, string $encoding = 'UTF-8'): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML401, $encoding);
    }

    /**
     * Create a temporary filename
     * @param string $extension
     * @param string $prepend The text to append to the filename
     * @param string $append The text to prepend to the filename
     * @return string|false
     */
    public function createTemporaryFilename(string $extension, string $prepend = "", string $append = ""): string|false
    {
        $extension = $this->isNotEmptyString($extension) ? $extension : 'tmp';
        $prepend = $this->isNotEmptyString($prepend) ? $prepend . '_' : '';
        $append = $this->isNotEmptyString($append) ? '_' . $append : '';
        $path = $this->path->insert_dir_separator(sys_get_temp_dir());
        $absolutePath = $path . '' . $prepend . '' . substr($this->randUnique("key"), 0, 16) . '' . $append . '.' . $extension;
        return $this->createFile($absolutePath) ? $absolutePath : false;
    }

    /**
     * Create a random file basename
     * @param string $extension
     * @param string $prepend The text to append to the filename
     * @param string $append The text to prepend to the filename
     * @return string|false
     */
    public function generateRandomFilename(string $extension, string $prepend = "", string $append = ""): string|false
    {
        $extension = $this->isNotEmptyString($extension) ? $extension : 'tmp';
        $prepend = $this->isNotEmptyString($prepend) ? $prepend . '_' : '';
        $append = $this->isNotEmptyString($append) ? '_' . $append : '';
        return $prepend . '' . substr($this->randUnique("key"), 0, 16) . '' . $append . '.' . $extension;
    }

    /**
     * Safe file end of file
     *
     * $timeout = @ini_get('default_socket_timeout');
     * while (!$this->safeFeof($handle, $start) && (microtime(true) - $start) < $timeout) {}
     *
     * @param type $handle
     * @param type $start
     * @return bool
     */
    public function safeFeof($handle, &$start = null): bool
    {
        $start = microtime(true);
        return feof($handle);
    }

    /**
     * Execute a command
     * @param string $command
     * @return array|string
     */
    public function executeCommand(string $command): array|string
    {
        $output = "";
        if ($this->isNotEmptyString($command)) {
            $command = escapeshellcmd($command);
            $output = $this->executeCommandUsingExec($command);
        }
        return $output;
    }

    /**
     * Execute a command using popen
     * @param string $command
     * @return string
     */
    public function executeCommandUsingPopen(string $command): string
    {
        $output = "";
        if ($this->isNotEmptyString($command) && function_exists('popen')) {
            $handle = @popen($command, 'r');
            if ($this->isResource($handle)) {
                $content = @stream_get_contents($handle);
                if ($this->isString($content)) {
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
    public function executeCommandUsingProcopen(string $command): string
    {
        $output = "";
        if ($this->isNotEmptyString($command) && function_exists('proc_open')) {
            $errorFilename = $this->createTemporaryFilename("proc", "execute_command_error_output");
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
                    if ($this->isString($content)) {
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
    public function executeCommandUsingExec(string $command): array
    {
        $output = array();
        if ($this->isNotEmptyString($command) && function_exists('exec')) {
            $content = array();
            $resultcode = 0;
            @exec($command, $content, $resultcode);
            if ($this->isArray($content)) {
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
    public function executeCommandUsingShellexec(string $command): string
    {
        $output = "";
        if ($this->isNotEmptyString($command) && function_exists('shell_exec')) {
            $content = shell_exec($command);
            if ($this->isString($content)) {
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
    public function executeCommandUsingSystem(string $command): string
    {
        $output = "";
        if ($this->isNotEmptyString($command) && function_exists('system')) {
            $resultcode = 0;
            $content = system($command, $resultcode);
            if ($this->isString($content)) {
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
    public function executeCommandUsingPassthru(string $command): string
    {
        $output = "";
        if ($this->isNotEmptyString($command) && function_exists('passthru')) {
            $resultcode = 0;
            ob_start();
            passthru($command, $resultcode);
            $content = ob_get_contents();
            if ($this->isString($content)) {
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
    public function getCwd(): string
    {
        return @getcwd();
    }

    /**
     * Change the current directory
     * @param string $dirname
     * @return bool
     */
    public function chDir(string $dirname): bool
    {
        if ($this->isDir($dirname)) {
            @chdir($dirname);
        }
        return $this->getCwd() === $dirname;
    }

    /**
     * Load a plugin
     * @param string $plugin
     * @return void
     * @throws \Exception
     */
    public function loadPlugin(string $plugin): void
    {
        $dirname = $this->path->insert_dir_separator($this->path->arrange_dir_separators(PHPWebfuse['directories']['plugins']));
        $plugin = $this->path->arrange_dir_separators($plugin);
        $extension = $this->getExtension($plugin);
        $name = $this->isNotEmptyString($extension) && strtolower($extension) == "php" ? $plugin : $plugin . '.php';
        $plugin = $dirname . '' . $name;
        if ($this->isFile($plugin)) {
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
    public function loadLib(string $lib): void
    {
        $dirname = $this->path->insert_dir_separator($this->path->arrange_dir_separators(PHPWebfuse['directories']['libraries']));
        $lib = $this->path->arrange_dir_separators($lib);
        $extension = $this->getExtension($lib);
        $name = $this->isNotEmptyString($extension) && strtolower($extension) == "php" ? $lib : $lib . '.php';
        $lib = $dirname . '' . $name;
        if ($this->isFile($lib)) {
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
    public function directTo(string $url): void
    {
        if (!headers_sent()) {
            @header("location: " . $url);
            exit;
        } else {
            throw new \Exception("Can't direct to \"" . $url . "\", headers has already been sent.");
        }
    }

    /**
     * Register error handler
     * @return void
     */
    public function registerErrorHandler(): void
    {
        @set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            if (!(error_reporting() & $errno)) {
                return false;
            } // This error code is not included in error_reporting, so let it fall through to the standard PHP error handler
            $errstr = htmlspecialchars($errstr);
            $errorFilename = $this->path->insert_dir_separator(PHPWebfuse['directories']['root']) . "\$error-messages.log";
            $this->saveContentToFile($errorFilename, strip_tags($errno . " ::Filename >> " . $errfile . " ::Line >> " . $errline . " ::Message >> " . $errstr . " ::Date >> " . date("F jS, Y", time()) . " @ " . date("h:i A", time())), true, true);
            echo "<div style='" . self::ERRORS_CSS['error'] . "'>" . $errno . " :: " . ($this->isTrue($this->isLocalhost()) ? "<b>Filename >></b> " . $errfile . " <b>Line >></b> " . $errline . " <b>Message >></b> " : "") . "" . $errstr . "</div>";
        });
    }

    /**
     * Register exception handler
     * @return void
     */
    public function registerExceptionHandler(): void
    {
        @set_exception_handler(function (\Throwable $ex) {
            $exceptionFilename = $this->path->insert_dir_separator(PHPWebfuse['directories']['root']) . "\$exception-messages.log";
            $this->saveContentToFile($exceptionFilename, strip_tags("Filename >> " . $ex->getFile() . " ::Line >> " . $ex->getLIne() . " ::Message >> " . $ex->getMessage() . " ::Date >> " . date("F jS, Y", time()) . " @ " . date("h:i A", time())), true, true);
            echo "<div style='" . self::ERRORS_CSS['exception'] . "'>" . ($this->isTrue($this->isLocalhost()) ? "<b>Filename >></b> " . $ex->getFile() . " <b>Line >></b> " . $ex->getLIne() . " <b>Message >></b> " : "") . "" . $ex->getMessage() . "</div>";
        });
    }

    /**
     * Check if running on a localhost web server
     * @return bool
     */
    public function isLocalhost(): bool
    {
        return $this->inArray($this->getIPAddress(), (array) self::LOCALHOST_DEFAULT_ADDRESSES);
    }

    /**
     * Validate IPv4 IP address and check if IP address is not in private IP address range @see CHECK_IP_ADDRESS_IN_RANGE
     * @param string $ip
     * @return bool
     */
    public function validateIPAddress(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($this->isTrue(self::CHECK_IP_ADDRESS_IN_RANGE)) {
                foreach (self::PRIVATE_IP_ADDRESS_RANGES as $range) {
                    if ($this->isIPInPrivateRange($ip, $range)) {
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
    public function getIPAddress(): string
    {
        $headersToCheck = array('HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_X_FORWARDED_HOST', 'REMOTE_ADDR');
        foreach ($headersToCheck as $header) {
            $determinedHeader = getenv($header);
            if ($this->isNotEmpty($determinedHeader)) {
                if ($header == "HTTP_X_FORWARDED_FOR") {
                    $ipAddresses = explode(',', $determinedHeader);
                    foreach ($ipAddresses as $realIp) {
                        if ($this->validateIPAddress((string) $realIp)) {
                            return $realIp;
                        }
                    }
                } elseif ($this->validateIPAddress((string) $determinedHeader)) {
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
    public function isIPInPrivateRange(string $ip, string $range): bool
    {
        if ($this->isFalse(strpos($range, '/'))) {
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
    public function getTmpDir(): string
    {
        return @sys_get_temp_dir();
    }

    /**
     * Get the upload directory
     * @return string
     */
    public function getUploadDir(): string
    {
        return @ini_get('upload_tmp_dir');
    }

    /**
     * Get the default directory
     * @return string
     */
    public function getCurrentFileDir(): string
    {
        return @dirname(__FILE__);
    }

    /**
     * Gets last access time of file
     * @param string $absolutePath
     * @return int
     */
    public function accessTime(string $absolutePath): int
    {
        if ($this->isFile($absolutePath)) {
            return @fileatime($absolutePath);
        }return 0;
    }

    /**
     * Gets file modification time
     * @param string $absolutePath
     * @return int
     */
    public function modificationTime(string $absolutePath): int
    {
        if ($this->isFile($absolutePath)) {
            return @filemtime($absolutePath);
        }return 0;
    }

    /**
     * Gets inode change time of file
     * @param string $absolutePath
     * @return int
     */
    public function changeTime(string $absolutePath): int
    {
        if ($this->isFile($absolutePath)) {
            return @filectime($absolutePath);
        }return 0;
    }

    /**
     * A readable unix time
     * @param string|int $unix
     * @return string
     */
    public function readableUnix(string|int $unix): string
    {
        if ($this->isNumeric($unix)) {
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
    public function createHardLink(string $target, string $link): bool
    {
        if ($this->isExists($target)) {
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
    public function createSymLink(string $target, string $link): bool
    {
        if ($this->isExists($target)) {
            return @symlink($target, $link);
        }
        return false;
    }

    /**
     * Calculate remaining time from $unix
     * @param int $unix
     * @return int
     */
    public function calculateRemainingDaysFromUnix(int $unix)
    {
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
    public function calculateElapsedDaysFromUnix(int $unix)
    {
        $days = 0;
        if (time() > $unix) {
            $devided = ((time() - $unix) / 86400);
            return round($devided, 0);
        }
        return $days;
    }

    /**
     * Convert image to base64
     * @param string $absolutePath
     * @return string
     */
    public function convertImageToBase64(string $absolutePath): string
    {
        $base64Image = "";
        // Supported image extensions
        $extensions = array('gif', 'jpg', 'jpeg', 'png');
        // Check if the file exists and is readable
        if ($this->isFile($absolutePath) && $this->isReadable($absolutePath)) {
            // Get the file extension
            $extension = strtolower($this->getExtension($absolutePath));
            // Check if the file extension is supported
            if ($this->isNotEmptyString($extension) && $this->inArray($extension, $extensions)) {
                // Get the image content and encode the image content to base64
                $base64Encode = base64_encode($this->getFileContent($absolutePath));
                // Add the appropriate data URI prefix
                $base64Image = 'data:' . mime_content_type($absolutePath) . ';base64,' . $base64Encode;
            }
        }
        return $base64Image;
    }

    /**
     * Validate mobile number. Return true on success, otherwise false or string representing error message
     * @param int|string $number
     * @param string $shortcode
     * @return bool|string
     */
    public function validateMobileNumber(int|string $number, string $shortcode = "ng"): bool|string
    {
        $this->loadPlugin("CountriesList");
        $classesExists = class_exists("\libphonenumber\PhoneNumberUtil") && class_exists("\libphonenumber\PhoneNumberFormat") && class_exists("\libphonenumber\NumberParseException");
        if (class_exists("\CountriesList") && $classesExists && $this->isNumeric($number)) {
            $countriesList = new \CountriesList();
            $shortcodes = $countriesList->getCountriesShortCode();
            if (isset($shortcodes[strtoupper($shortcode)])) {
                try {
                    $shortcode = strtoupper($shortcode);
                    $util = \libphonenumber\PhoneNumberUtil::getInstance();
                    $parse = $util->parseAndKeepRawInput($number, $shortcode);
                    $isValid = $util->isValidNumber($parse);
                    if ($this->isTrue($isValid)) {
                        return trim($util->format($parse, \libphonenumber\PhoneNumberFormat::E164));
                    }
                } catch (\libphonenumber\NumberParseException $e) {
                    return $e->getMessage();
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
    public function sleekDatabase(string $database, string $path, array $options = array()): \SleekDB\Store|bool
    {
        if ($this->isNotEmptyString($database) && $this->makeDir($path) && class_exists("\SleekDB\Store")) {
            $options = $this->isEmptyArray($options) ? array('auto_cache' => false, 'timeout' => false, 'primary_key' => 'id', 'folder_permissions' => 0777) : $options;
            return new \SleekDB\Store($database, $this->resolvePath($path), $options);
        }
        return false;
    }

    /**
     * Get the audio duration. Returns 00:00:00 on default event if it's not an audio file or doesn't exist
     * @param string $absolutePath
     * @return string
     */
    public function getAudioDuration(string $absolutePath): string
    {
        $duration = "00:00:00";
        if ($this->isFile($absolutePath)) {
            try {
                $rand = rand(0, 1);
                if ($rand == 0) {
                    if (class_exists("\JamesHeinrich\GetID3\GetID3")) {
                        $getID3 = new \JamesHeinrich\GetID3\GetID3();
                        $analyze = @$getID3->analyze($this->resolvePath($absolutePath));
                        if (isset($analyze['playtime_seconds'])) {
                            $duration = gmdate("H:i:s", (int) $analyze['playtime_seconds']);
                        }
                    }
                } else {
                    if (!in_array("\Mp3Info", get_declared_classes())) {
                        $this->loadLib("Mp3Info" . DIRECTORY_SEPARATOR . "Mp3Info");
                    }
                    $info = new \Mp3Info($this->resolvePath($absolutePath));
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
    public function setAudioMetaTags(string $audioname, string $covername, array $options = array()): bool
    {
        if ($this->isFile($audioname) && $this->isFile($covername)) {
            $audioname = $this->resolvePath($audioname);
            $covername = $this->resolvePath($covername);
            $this->unlimitedWorkflow();
            $classesExists = class_exists("\JamesHeinrich\GetID3\GetID3") && class_exists("\JamesHeinrich\GetID3\WriteTags");
            if ($this->isTrue($classesExists) && $this->isNotEmptyArray($options)) {
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
                $tempPathname = $this->path->insert_dir_separator($this->path->arrange_dir_separators(PHPWebfuse['directories']['data'] . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'temp'));
                if ($this->makeDir($tempPathname)) {
                    $random = $this->generateRandomFilename("png");
                    $_covername = $tempPathname . '' . $random;
                    if ($this->convertImage($covername, "png", false, true, $_covername)) {
                        $covername = $_covername;
                    }
                    $data['attached_picture'][0]['data'] = $this->getFileContent($covername);
                    $data['attached_picture'][0]['picturetypeid'] = 3;
                    $data['attached_picture'][0]['description'] = isset($options['comment']) ? $options['comment'] : "";
                    $data['attached_picture'][0]['mime'] = mime_content_type($covername);
                    $writer->tag_data = $data;
                    $this->deleteFile($_covername);
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
    public function cleanPHPWebfuseTempDirs(): void
    {
        $paths = $this->searchDir(PHPWebfuse['directories']['root'], array("temp"));
        foreach ($paths as $index => $path) {
            if ($this->isDir($path)) {
                $this->emptyDirectory($path);
            }
        }
    }

    /**
     * Initialize MobileDetect class
     * @return false|object
     */
    public function intMobileDetect(): false|object
    {
        if (!class_exists("\MobileDetect")) {
            $this->loadPlugin("MobileDetect");
        }
        return class_exists("\Detection\MobileDetect") ? new \MobileDetect(new \Detection\MobileDetect()) : false;
    }

    /**
     * Get the browser name
     * @return string
     */
    public function getBrowser(): string
    {
        $browser = "";
        $md = $this->intMobileDetect();
        if ($this->isNotFalse($md)) {
            $browser = $md->getBrowser();
        }
        return $browser;
    }

    /**
     * Ge the device name
     * @return string
     */
    public function getDevice(): string
    {
        $devices = "";
        $md = $this->intMobileDetect();
        if ($this->isNotFalse($md)) {
            $devices = $md->getDevice();
        }
        return $devices;
    }

    /**
     * Get the device operating system
     * @return string
     */
    public function getDeviceOsName(): string
    {
        $os = "";
        $md = $this->intMobileDetect();
        if ($this->isNotFalse($md)) {
            $os = $md->getDeviceOsName();
        }
        return $os;
    }

    /**
     * Get the device brand
     * @return string
     */
    public function getDeviceBrand(): string
    {
        $brand = "";
        $md = $this->intMobileDetect();
        if ($this->isNotFalse($md)) {
            $brand = $md->getDeviceBrand();
        }
        return $brand;
    }

    /**
     * Get ip address information
     * @return array
     */
    public function getIPInfo(): array
    {
        $info = array();
        $md = $this->intMobileDetect();
        if ($this->isNotFalse($md)) {
            $info = $md->getIPInfo();
        }
        return $info;
    }

    /**
     * Check if you are connected to internet
     * @return bool
     */
    public function isConnectedToInternet()
    {
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
    public function resolvePath(string $path): string
    {
        $absolutePath = realpath($path);
        return $this->isBool($absolutePath) ? $path : $absolutePath;
    }

    /**
     * Debug trace
     * @param string $message
     * @return type
     */
    public function debugTrace(string $message)
    {
        $trace = array_shift(debug_backtrace());
        return die($trace["file"] . ": Line " . $trace["line"] . ": " . $message);
    }

    /**
     * Validate google re-captcha
     * @param string $serverkey
     * @param string $token
     * @return bool|array
     */
    public function validatedGRecaptcha(string $serverkey, string $token): bool|array
    {
        if ($this->isNotEmptyString($serverkey) && $this->isNotEmptyString($token) && $this->isNumeric($token)) {
            try {
                $data = array("secret" => $serverkey, 'response' => $token, 'remoteip' => $this->getIPAddress());
                $options = array('http' => array('header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'method' => "POST", 'content' => http_build_query($data)));
                $serverresponse = @file_get_contents("https://google.com/recaptcha/api/siteverify", false, stream_context_create($options));
                if ($this->isNotFalse($serverresponse)) {
                    return $this->jsonToArray($serverresponse);
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
    public function generateQrCode(string $content, string $filename): string|bool
    {
        $result = false;
        if (!defined('QR_MODE_NUL')) {
            $this->loadLib("phpqrcode" . DIRECTORY_SEPARATOR . "qrlib");
        }
        if (class_exists("\QRcode")) {
            $extension = $this->getExtension($filename);
            $filename = $this->isNotEmptyString($extension) ? (strtolower($extension) !== "png" ? $filename . ".png" : $filename) : $filename . ".png";
            \QRcode::png($content, $filename, QR_ECLEVEL_Q, 20, 2);
            if ($this->isFile($filename)) {
                clearstatcache(false, $filename);
                $result = $this->resolvePath($filename);
            }
        }
        return $result;
    }

    /**
     * Get the current script file
     * @return string
     */
    public function getScriptFile(): string
    {
        return @getenv('SCRIPT_FILENAME');
    }

    /**
     * Get the current script name
     * @return string
     */
    public function getScriptName(): string
    {
        return @getenv('SCRIPT_NAME');
    }

    /**
     * Get the current script path
     * @return string
     */
    public function getScriptPath(): string
    {
        return @dirname($this->getScriptFile());
    }

    /**
     * Get the current script URL
     * @return string
     */
    public function getScriptUrl(): string
    {
        return $this->protocol() . '://' . @getenv('HTTP_HOST') . $this->getScriptName();
    }

    /**
     * Get the current request URI
     * @return string
     */
    public function getRequestUri(): string
    {
        return @getenv('REQUEST_URI');
    }

    /**
     * Get the current request method
     * @return string
     */
    public function getRequestMethod(): string
    {
        return @getenv('REQUEST_METHOD');
    }

    /**
     * Get the current request time
     * @return int
     */
    public function getRequestTime(): int
    {
        return @getenv('REQUEST_TIME');
    }

    /**
     * Get the current request time in seconds
     * @return float
     */
    public function getRequestTimeFloat(): float
    {
        return @getenv('REQUEST_TIME_FLOAT');
    }

    /**
     * Get the current query string
     * @return string
     */
    public function getQueryString(): string
    {
        return @getenv('QUERY_STRING');
    }

    /**
     * Get the current HTTP accept
     * @return string
     */
    public function getHttpAccept(): string
    {
        return @getenv('HTTP_ACCEPT');
    }

    /**
     * Get the current HTTP accept charset
     * @return string
     */
    public function getHttpAcceptCharset(): string
    {
        return @getenv('HTTP_ACCEPT_CHARSET');
    }

    /**
     * Get the current HTTP accept encoding
     * @return string
     */
    public function getHttpAcceptEncoding(): string
    {
        return @getenv('HTTP_ACCEPT_ENCODING');
    }

    /**
     * Get the current HTTP accept language
     * @return string
     */
    public function getHttpAcceptLanguage(): string
    {
        return @getenv('HTTP_ACCEPT_LANGUAGE');
    }

    /**
     * Get the current HTTP connection
     * @return string
     */
    public function getHttpConnection(): string
    {
        return @getenv('HTTP_CONNECTION');
    }

    /**
     * Get the current HTTP host
     * @return string
     */
    public function getHttpHost(): string
    {
        return @getenv('HTTP_HOST');
    }

    /**
     * Get the current HTTP referer
     * @return string
     */
    public function getHttpReferer(): string
    {
        return @getenv('HTTP_REFERER');
    }

    /**
     * Get the current HTTP user agent
     * @return string
     */
    public function getHttpUserAgent(): string
    {
        return @getenv('HTTP_USER_AGENT');
    }

    /**
     * Get the current HTTP X-Requested-With
     * @return string
     */
    public function getHttpXRequestedWith(): string
    {
        return @getenv('HTTP_X_REQUESTED_WITH');
    }

    /**
     * Get the current HTTP X-Forwarded-For
     * @return string
     */
    public function getHttpXForwardedFor(): string
    {
        return @getenv('HTTP_X_FORWARDED_FOR');
    }

    /**
     * Get the current HTTP X-Forwarded-Host
     * @return string
     */
    public function getHttpXForwardedHost(): string
    {
        return @getenv('HTTP_X_FORWARDED_HOST');
    }

    /**
     * Get the current HTTP X-Forwarded-Proto
     * @return string
     */
    public function getHttpXForwardedProto(): string
    {
        return @getenv('HTTP_X_FORWARDED_PROTO');
    }

    /**
     * Get the current HTTP X-Forwarded-Port
     * @return string
     */
    public function getHttpXForwardedPort(): string
    {
        return @getenv('HTTP_X_FORWARDED_PORT');
    }

    /**
     * Get the current HTTP X-Forwarded-Server
     * @return string
     */
    public function getHttpXForwardedServer(): string
    {
        return @getenv('HTTP_X_FORWARDED_SERVER');
    }

    /**
     * Get the current HTTP X-Forwarded-For-IP
     * @return string
     */
    public function getHttpXForwardedForIp(): string
    {
        return @getenv('HTTP_X_FORWARDED_FOR_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Proto-IP
     * @return string
     */
    public function getHttpXForwardedProtoIp(): string
    {
        return @getenv('HTTP_X_FORWARDED_PROTO_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Host-IP
     * @return string
     */
    public function getHttpXForwardedHostIp(): string
    {
        return @getenv('HTTP_X_FORWARDED_HOST_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Port-IP
     * @return string
     */
    public function getHttpXForwardedPortIp(): string
    {
        return @getenv('HTTP_X_FORWARDED_PORT_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Server-IP
     * @return string
     */
    public function getHttpXForwardedServerIp(): string
    {
        return @getenv('HTTP_X_FORWARDED_SERVER_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-For-Client-IP
     * @return string
     */
    public function getHttpXForwardedForClientIp(): string
    {
        return @getenv('HTTP_X_FORWARDED_FOR_CLIENT_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Proto-Client-IP
     * @return string
     */
    public function getHttpXForwardedProtoClientIp(): string
    {
        return @getenv('HTTP_X_FORWARDED_PROTO_CLIENT_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Host-Client-IP
     * @return string
     */
    public function getHttpXForwardedHostClientIp(): string
    {
        return @getenv('HTTP_X_FORWARDED_HOST_CLIENT_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Port-Client-IP
     * @return string
     */
    public function getHttpXForwardedPortClientIp(): string
    {
        return @getenv('HTTP_X_FORWARDED_PORT_CLIENT_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-Server-Client-IP
     * @return string
     */
    public function getHttpXForwardedServerClientIp(): string
    {
        return @getenv('HTTP_X_FORWARDED_SERVER_CLIENT_IP');
    }

    /**
     * Get the current HTTP X-Forwarded-For-Client
     * @return string
     */
    public function getHttpXForwardedForClient(): string
    {
        return @getenv('HTTP_X_FORWARDED_FOR_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-Proto-Client
     * @return string
     */
    public function getHttpXForwardedProtoClient(): string
    {
        return @getenv('HTTP_X_FORWARDED_PROTO_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-Host-Client
     * @return string
     */
    public function getHttpXForwardedHostClient(): string
    {
        return @getenv('HTTP_X_FORWARDED_HOST_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-Port-Client
     * @return string
     */
    public function getHttpXForwardedPortClient(): string
    {
        return @getenv('HTTP_X_FORWARDED_PORT_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-Server-Client
     * @return string
     */
    public function getHttpXForwardedServerClient(): string
    {
        return @getenv('HTTP_X_FORWARDED_SERVER_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-For-Client-IP-Client
     * @return string
     */
    public function getHttpXForwardedForClientIpClient(): string
    {
        return @getenv('HTTP_X_FORWARDED_FOR_CLIENT_IP_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-Proto-Client-IP-Client
     * @return string
     */
    public function getHttpXForwardedProtoClientIpClient(): string
    {
        return @getenv('HTTP_X_FORWARDED_PROTO_CLIENT_IP_CLIENT');
    }

    /**
     * Get the current HTTP X-Forwarded-Host-Client-IP-Client
     * @return string
     */
    public function getHttpXForwardedHostClientIpClient(): string
    {
        return @getenv('HTTP_X_FORWARDED_HOST_CLIENT_IP_CLIENT');
    }
    
    /**
     * Loads environment variables from .env to getenv(), $_ENV and $_SERVER automatically.
     * @param string $inPath The directory to load the .env file from
     * @param bool $overwrite Wether to overwrite existing .env variables
     * @return void
     */
    public function loadEnvVars(string $inPath, bool $overwrite = true): void {
        $dotenv = $overwrite ? \Dotenv\Dotenv::createMutable($inPath) : \Dotenv\Dotenv::createImmutable($inPath);
        $dotenv->safeLoad();
    }

    // PRIVATE METHOD

    private function errorHandler(int $errno, string $errstr, string $errfile, string $errline, array $errcontext)
    {
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

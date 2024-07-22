<?php
namespace PHPWebFuse;
/**
 *
 */
class Methods extends \PHPWebFuse\Path {
	// PRIVATE CONSTANTS

	// The default read and write chunk size (2MB)
	private const chunckSize = 2097152;

	// The default read and write chunk size when encrypting or decrypting a file (5MB)
	private const EncFileIntoPartsChunckSize = 5242880;

	// The default character considered invalid
	private const invalidChars = array("\\", "/", ":", ";", " ", "*", "?", "\"", "<", ">", "|", ",", "'");

	// Files permission
	private const FilePerms = 0644;

	// Directories permission
	private const DirPerms = 0755;

	// Default image width and height for conversion
	private const imageWidth = 450;
	private const imageHeight = 400;

	// PRIVATE CONSTANT VARIABLES

	// Default user agent
	public const userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36";

	// Default timezone
	public const timezone = "Africa/Lagos";

	// Default GMT
	public const GMT = "+01:00";

	public const ErrorsCss = array(
		'exception' => "width: 100%; padding: 5px; height: auto; position: relative; display: block; text-align: left; word-break: break-word; overflow-wrap: break-word; color: #d22c3c; background: transparent; font-size: 90%; margin: 5px auto; border: none; border-bottom: 2px dashed red; font-weight: normal;",
		'error' => "width: 100%; padding: 5px; height: auto; position: relative; display: block; text-align: left; word-break: break-word; overflow-wrap: break-word; color: black; background: transparent; font-size: 90%; margin: 5px auto; border: none; border-bottom: 2px dashed #da8d00; font-weight: normal;",
	);

	// Common localhost addresses
	public const localhostAddresses = array('localhost', '127.0.0.1', '::1', '');

	// Excluded private IP address ranges
	public const privateIPRanges = array('10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '169.254.0.0/16', '127.0.0.0/8');
	// Wether to check for Ip in private Ip ranges
	public const checkIPRange = false;

	// PUBLIC METHODS

	public function __construct() {$datetime = new \DateTime('now', new \DateTimeZone(self::timezone));}

	/**
	 * Get the file size in bytes
	 *
	 * @param string $realPath
	 * @return int
	 */
	public function getFIlesizeInBytes(string $realPath): int {
		$bytes = 0;
		if ($this->isFile($realPath)) {
			$realPath = $this->resolvePath($realPath);
			clearstatcache(false, $realPath);
			$size = @filesize($realPath);
			if ($this->isInt($size)) {$bytes = $size;} else {
				$handle = @fopen($realPath, 'rb');
				if ($this->isResource($handle)) {
					while (($buffer = fgets($handle, self::chunckSize)) !== false) {
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
	 *
	 * @param string $pathname
	 * @return bool
	 */
	public function createFile(string $pathname): bool {
		$created = $this->isFile($pathname);
		if ($this->isFalse($created)) {
			$handle = @fopen($pathname, "w");
			if ($this->isResource($handle)) {
				fclose($handle);
				$this->setPermissions($pathname);
				$created = true;
			}
		}
		return $this->isTrue($created);
	}

	/**
	 * Save content to file
	 *
	 * @param string $realPath
	 * @param string $content
	 * @param bool $append: Default to 'false'. Wether to append the content to the filename
	 * @param bool $newline: Default to 'false'. Wether to append the content on a new line if $append is true
	 * @return bool
	 */
	public function saveContentToFile(string $realPath, string $content, bool $append = false, bool $newline = false): bool {
		$saved = false;
		if ($this->isFile($realPath) || ($this->isNotFile($realPath) && $this->createFile($realPath))) {
			$handle = @fopen($realPath, $append ? "a" : "w");
			if ($this->isResource($handle) && flock($handle, LOCK_EX | LOCK_SH)) {
				if ($this->isTrue($append) && $this->isTrue($newline) && $this->getFIlesizeInBytes($realPath) >= 1) {$content = "\n" . $content;}
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
	 * @param string $realPath
	 * @return string
	 */
	public function getFileContent(string $realPath): string {
		$content = '';
		if ($this->isFile($realPath)) {
			$handle = @fopen($realPath, 'rb');
			if ($this->isResource($handle) && flock($handle, LOCK_EX | LOCK_SH)) {
				$start = NULL;
				$timeout = @ini_get('default_socket_timeout');
				while (!$this->safeFeof($handle, $start) && (microtime(true) - $start) < $timeout) {
					$read = fread($handle, self::chunckSize);
					if ($this->isString($read)) {$content .= $read;} else {break;}
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
	public function writeContentToHandle(mixed $handle, string $content): bool {
		$offset = 0;
		if ($this->isResource($handle)) {
			while ($offset < strlen($content)) {
				$chunk = substr($content, $offset, self::chunckSize);
				if ((@fwrite($handle, $chunk)) === false) {break;}
				$offset += self::chunckSize;
			}
		}
		return $offset >= 1;
	}

	/**
	 * Get the operating system
	 * @return string
	 */
	public function getOS(): string {
		$os = strtolower(PHP_OS);
		if (substr($os, 0, 3) === "win") {return "Windows";} else if (substr($os, 0, 4) == "unix") {return "Unix";} else if (substr($os, 0, 5) == "linux") {return "Linux";}
		return "Unknown";
	}

	/**
	 * Determine if resource is stream
	 *
	 * @param mixed $resource
	 * @return bool
	 */
	public function isResourceStream(mixed $resource): bool {
		return $this->isResource($resource) && @get_resource_type($resource) == "stream";
	}

	/**
	 * Determine if resource is curl
	 *
	 * @param mixed $resource
	 * @return bool
	 */
	public function isResourceCurl(mixed $resource): bool {
		return $this->isResource($resource) && @get_resource_type($resource) == "curl";
	}

	/**
	 * Sets the current process to unlimited execution time and unlimited memory limit
	 *
	 * @return void
	 */
	public function unlimitedWorkflow(): void {
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
	public function createCookie(string $name, string $value, int $days, string $path, string $domain, bool $secure, bool $httponly, string $samesite): bool {
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
	public function deleteCookie(string $name, string $path, string $domain, bool $secure, bool $httponly, string $samesite): bool {
		if (isset($_COOKIE[$name])) {
			$expires = strtotime('2010');
			$setcookie = @setcookie($name, "", array('expires' => $expires, 'path' => $path, 'domain' => $domain, 'secure' => $secure, 'httponly' => $httponly, 'samesite' => ucfirst($samesite)));
			if ($this->isTrue($setcookie)) {
				try {unset($_COOKIE['' . $name . '']);} catch (\Throwable $e) {}
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the readable permission of a file
	 *
	 * @param string $realPath
	 * @return string
	 */
	public function getReadablePermission(string $realPath): string {
		// Convert numeric mode to symbolic representation
		$info = '';
		if ($this->isExists($realPath)) {
			// Get the file permissions as a numeric mode
			$perms = fileperms($realPath);
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
	 * @param string $realPath
	 * @return string
	 */
	public function getPermission(string $realPath): string {
		if ($this->isFile($realPath) && $this->isInt(@fileperms($this->resolvePath($realPath)))) {return substr(sprintf('%o', @fileperms($this->resolvePath($realPath))), -4);}
		return "";
	}

	/**
	 * Get a file type
	 *
	 * @param string $realPath
	 * @return string | bool
	 */
	public function getFileType(string $realPath): string | bool {
		if ($this->isFile($realPath)) {return @filetype($this->resolvePath($realPath));}
		return "unknown";
	}

	/**
	 * Gives information about a file or symbolic link
	 *
	 * @param string $realPath
	 * @param string $key
	 * @return array
	 */
	public function getStats(string $realPath, string $key): array {
		if ($this->isFile($realPath)) {
			$stat = @lstat($realPath);
			$stats = array();
			if ($this->isArray($stat)) {
				foreach ($stat as $k => $v) {if ($this->isString($k)) {$stats[$k] = $v;}}
				if ($this->isNotEmptyString($key) && isset($stats[$key])) {return $stats[$key];}
				$stat = null;
				return $stats;
			}
		}
		return array();
	}

	/**
	 * Get a file info
	 *
	 * @param string $realPath
	 * @return array
	 */
	public function getFileInfo(string $realPath): array {
		if ($this->isFile($realPath)) {
			$array = array();
			$i = new \SplFileInfo($realPath);
			$array['realpath'] = $i->getRealPath();
			$array['dirname'] = $i->getPath();
			$array['basename'] = $i->getBasename();
			$array['extension'] = $i->getExtension();
			$array['filename'] = $i->getBasename("." . $i->getExtension());
			$array['size'] = array('raw' => $i->getSize(), 'readable' => $this->formatSize($this->getFIlesizeInBytes($realPath)));
			$array['atime'] = array('raw' => $i->getATime(), 'readable' => $this->readableUnix($i->getATime()));
			$array['mtime'] = array('raw' => $i->getMTime(), 'readable' => $this->readableUnix($i->getMTime()));
			$array['ctime'] = array('raw' => $i->getCTime(), 'readable' => $this->readableUnix($i->getCTime()));
			$array['mime'] = $this->getMime($realPath);
			$array['type'] = $i->getType();
			$array['permission'] = array('raw' => $this->getPermission($realPath), 'readable' => $this->getReadablePermission($realPath));
			$array['owner'] = array('raw' => $i->getOwner(), 'readable' => (function_exists("posix_getpwuid") ? posix_getpwuid($i->getOwner()) : ""));
			$array['group'] = array('raw' => $i->getGroup(), 'readable' => (function_exists("posix_getgrgid") ? posix_getgrgid($i->getGroup()) : ""));
			if ($i->isLink()) {$array['target'] = $i->getLinkTarget();}
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
	 * @param string $realPath
	 * @param int $mtime: Defaults to 'null'
	 * @param int $atime: Defaults to 'null'
	 * @return bool
	 */
	public function touchFile(string $realPath, ?int $mtime = null, ?int $atime = null): bool {return @touch($realPath, $mtime, $atime);}

	/**
	 * Get directory name of a file or directory
	 *
	 * @param string $pathname
	 * @return string
	 */
	public function getDirname(string $pathname): string {
		return isset(pathinfo($pathname)['dirname']) ? pathinfo($pathname)['dirname'] : $pathname;
	}

	/**
	 * Get file or directory extension
	 *
	 * @param string $pathname
	 * @return string
	 */
	public function getExtension(string $pathname): string {
		return isset(pathinfo($pathname)['extension']) ? pathinfo($pathname)['extension'] : "";
	}

	// Get the owner of the file
	public function getOwner(string $realPath): int | false {
		return @fileowner($realPath);
	}

	// Get the group of the file
	public function getGroup(string $realPath): int | false {
		return @filegroup($realPath);
	}

	// Get the inode number of the file
	public function getInode(string $realPath): int | false {
		return @fileinode($realPath);
	}

	// Get the type of the file
	public function getType(string $realPath): string | false {
		return @filetype($realPath);
	}

	// Get the link target of the file
	public function getSymLinkTarget(string $realPath): string | false {
		return @readlink($realPath);
	}

	// Get the real path of the file
	public function getRealPath(string $realPath): string | false {
		return @$this->resolvePath($realPath);
	}

	// Get the owner name of the file
	public function getOwnerName(string $realPath): mixed {
		return function_exists("posix_getpwuid") ? @posix_getpwuid($this->getOwner($realPath))['name'] : $this->getOwner($realPath);
	}

	// Get the group name of the file
	public function getGroupName(string $realPath): mixed {
		return function_exists("posix_getpwuid") ? @posix_getgrgid($this->getGroup($realPath))['name'] : $this->getGroup($realPath);
	}

	// Changes file group
	public function changeGroup(string $realPath, string | int $group): bool {
		if ($this->isFile($realPath)) {return @chgrp($realPath, $group);}
		return false;
	}
	// Changes file owner
	public function changeOwner(string $realPath, string | int $owner = ''): bool {
		if ($this->isFile($realPath)) {return @chown($realPath, $owner);}
		return false;
	}

	// Remove extension from a path name
	public function removeExtension(string $pathname): string {
		$extension = $this->getExtension($pathname);
		if ($this->isNotEmptyString($extension)) {return substr($pathname, 0, -(strlen($extension) + 1));}
		return $pathname;
	}

	// Gets the size of a directory
	public function getDirSize(string $pathname, bool $recursive = true): int {
		$size = 0;
		$files = $this->isTrue($recursive) ? $this->scanDirRecursively($pathname) : $this->scanDir($pathname);
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

	// Open a directory recursively and list out the files
	public function scanDirRecursively(string $pathname): array {
		if ($this->isDir($pathname) && $this->isReadable($pathname)) {
			$i = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pathname, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
			$array = array();
			foreach ($i as $list) {$array[] = $this->resolvePath($list->getRealPath());}
			$i = null;
			unset($i);
			return $array;
		}
		return array();
	}

	public function scanDir(string $pathname): array {
		if ($this->isDir($pathname) && $this->isReadable($pathname)) {
			$scandir = @scandir($pathname);
			if ($this->isArray($scandir)) {
				$array = array();
				foreach ($scandir as $name) {
					if ($this->isTrue($this->isNotInArray($name, array(".", "..")))) {
						$array[] = parent::INSERT_DIR_SEPARATOR(parent::ARRANGE_DIR_SEPARATOR($pathname)) . '' . $name;
					}
				}
				unset($scandir);
				return $array;
			}
		}
		return array();
	}

	// Gets the information of file in a directory
	public function getDirFilesInfo(string $pathname, bool $recursive = true): array {
		$array = array();
		$files = $this->isTrue($recursive) ? $this->scanDirRecursively($pathname) : $this->scanDir($pathname);
		foreach ($files as $index => $file) {if ($this->isFile($file)) {$array[] = $this->getFileInfo($file);} else if ($this->isDir($file)) {}}
		$files = null;
		unset($files);
		return $array;
	}

	public function searchDir(string $pathname, array $matches = array(), bool $asExtensions = false, bool $recursive = true): array {
		$results = array();
		// Get files list, either recursively or non-recursively
		$files = $recursive ? $this->scanDirRecursively($pathname) : $this->scanDir($pathname);
		// Iterate through each file in the directory
		foreach ($files as $file) {
			$info = $this->getFileInfo($file);
			// Check each match pattern
			foreach ($matches as $match) {
				if ($asExtensions) {
					// If searching by extension, check the file extension
					$extension = $info['extension'] ?? "";
					if ($this->endsWith(strtolower($extension), strtolower($match))) {$results[] = $file;}
				} else {
					// Otherwise, check if the file name contains the match string
					if ($this->containText(strtolower($match), strtolower($file))) {$results[] = $file;}
				}
			}
		}
		return $results;
	}

	// Convert a path name extension to either lowercase or uppercase
	public function convertExtension(string $pathname, bool $toLowercase = true): string {
		$extension = $this->getExtension($pathname);
		if ($this->isNotEmptyString($extension)) {return $this->removeExtension($pathname) . "." . ($toLowercase ? strtolower($extension) : strtoupper($extension));}
		return $pathname;
	}

	/**
	 * Delete a file based on extension
	 *
	 * @param string $pathname
	 * @param array $extensions
	 * @param bool $recursive: Default to 'true'
	 * @return void
	 */
	public function deleteFilesBasedOnExtension(string $pathname, array $extensions = array(), bool $recursive = true): void {
		if ($this->isDir($pathname) && $this->isReadable($pathname)) {
			$pathname = parent::INSERT_DIR_SEPARATOR($this->resolvePath($pathname));
			foreach ($extensions as $extension) {
				if ($this->isTrue($recursive)) {
					$i = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pathname, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
					foreach ($i as $list) {
						$list = $this->resolvePath($list->getRealPath());
						$info = $this->getFileInfo($list);
						$si = (isset($info['extension']) ? $info['extension'] : "");
						if (strtolower($si) == strtolower($extension)) {$this->deleteFile($list);}
					}
				} else {
					$glob = glob($pathname . '*.' . $extension);
					if ($this->isNotFalse($glob) && $this->isArray($glob)) {foreach ($glob as $list) {$this->deleteFile($list);}}
				}
			}
		}
	}

	public function safeEncode(string $string): string {return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');}
	public function safeDecode(string $string): string {return @base64_decode(str_pad(strtr($string, '-_', '+/'), (strlen($string) % 4), '=', STR_PAD_RIGHT));}
	public function clean(string $string): string {return strip_tags(htmlspecialchars($string));}
	public function replaceInvalidChars(string $string): string {return isset($string) ? str_ireplace(self::invalidChars, array('-'), $string) : false;}
	public function removeSpecialChars(?string $string = null): string {return isset($string) ? preg_replace('/[^A-Za-z0-9]/', '', $string) : false;}

	public function protocol() {return getenv("HTTPS") !== null && getenv("HTTPS") === 'on' ? "https" : "http";}
	public function serverProtocol() {return getenv("SERVER_PROTOCOL");}
	public function host() {return getenv('HTTP_HOST');}
	public function referer() {return getenv("HTTP_REFERER");}
	public function serverName() {return getenv("SERVER_NAME");}
	public function self() {return getenv("PHP_SELF");}
	public function scriptFilename() {return getenv("SCRIPT_FILENAME");}
	public function scriptName() {return getenv("SCRIPT_NAME");}
	public function unixTimestamp() {return time();}
	public function currentUrl() {return $this->protocol() . "://" . $this->host();}
	public function completeCurrentUrl() {return $this->currentUrl() . parent::LEFT_DEL_DIR_SEPARATOR($this->requestURI());}
	public function userAgent() {return getenv("HTTP_USER_AGENT");}
	public function sslActive() {return (getenv('HTTPS') == 'on' || getenv('HTTPS') == '1' || getenv('SERVER_PORT') == '443');}
	public function createSession(string $name, string $value): bool {
		@session_start();
		$_SESSION[$name] = $value;
		return isset($_SESSION[$name]);
	}
	public function deleteSession(string $name): bool {
		@session_start();
		unset($_SESSION[$name]);
		return !isset($_SESSION[$name]);
	}
	public function isFunction(mixed $fn): bool {return ($fn instanceof Closure) && is_callable($fn);}
	public function requestURI() {
		if (getenv('REQUEST_URI') !== null) {
			return getenv('REQUEST_URI');
		} elseif (getenv('SCRIPT_NAME') !== null) {
			return getenv('SCRIPT_NAME') . (empty(getenv('QUERY_STRING')) ? '' : '?' . getenv('QUERY_STRING'));
		} elseif (getenv('PHP_SELF') !== null) {
			return getenv('PHP_SELF') . (empty(getenv('QUERY_STRING')) ? '' : '?' . getenv('QUERY_STRING'));
		}
		return '';
	}

	public function arrayToJson(array $array): string {return json_encode($array, JSON_FORCE_OBJECT);}
	public function stringToJson(string $string): string {return json_encode($string, JSON_FORCE_OBJECT);}
	public function jsonToArray(string $json): array {return json_decode($json, JSON_OBJECT_AS_ARRAY) ?? [];}
	public function arrayToString(array $array, string $imploder = ", "): string {return $this->isArray($array) ? implode($imploder, $array) : "";}

	public function base64_encode_no_padding(string $data): string {
		$encoded = base64_encode($data);
		return rtrim($encoded, '=');
	}

	public function base64_decode_no_padding(string $data): string {
		// Add padding back if necessary
		$length = strlen($data) % 4;
		if ($length > 0) {$data .= str_repeat('=', 4 - $length);}
		return base64_decode($data);
	}

	public function base64_encode_crlf(string $data): string {
		$encoded = base64_encode($data);
		return str_replace("\n", "\r\n", $encoded);
	}

	public function base64_decode_crlf(string $data): string {
		$data = str_replace("\r\n", "\n", $data);
		return base64_decode($data);
	}

	public function base64_encode_url_safe(string $data): string {
		$encoded = strtr(base64_encode($data), '+/', '-_');
		return rtrim($encoded, '=');
	}

	public function base64_decode_url_safe(string $data): string {
		$data = strtr($data, '-_', '+/');
		return base64_decode($data);
	}

	public function base64_encode_no_wrap(string $data): string {
		$encoded = base64_encode($data);
		return str_replace("\n", '', $encoded);
	}

	public function base64_decode_no_wrap(string $data): string {
		$data = str_replace("\n", '', $data);
		return base64_decode($data);
	}

	public function setPermissions(string $pathname, bool $recursive = false): bool {
		if ($this->isFile($pathname)) {
			$pathname = $this->resolvePath($pathname);
			return @chmod($pathname, self::FilePerms);
		} else if ($this->isDir($pathname)) {
			if ($this->isTrue($recursive)) {
				$i = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pathname, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
				foreach ($i as $list) {
					$list = $this->resolvePath($list->getRealPath());
					if ($this->isFile($list)) {
						@chmod($list, self::FilePerms);
					} else if ($this->isDir($list)) {
						@chmod($list, self::DirPerms);
					}
				}
			}
			return @chmod($pathname, self::DirPerms);
		}
		return false;
	}

	public function makeDir(string $pathname): bool {
		if ($this->isDir($pathname)) {
			$this->setPermissions($pathname);
			return true;
		} else {
			try {
				if ($this->isTrue(@mkdir($pathname, self::DirPerms, true))) {
					$this->setPermissions($pathname);
					return true;
				}
			} catch (\Throwable $e) {}
		}
		return false;
	}

	public function deleteFile(string $realPath): bool {
		if ($this->isFile($realPath)) {return @unlink($realPath);}
		return false;
	}

	public function deleteDir(string $pathname): bool {
		return $this->emptyDirectory($pathname, true);
	}

	public function delete(string $pathname): bool {
		if ($this->isFile($pathname)) {
			return $this->deleteFile($pathname);
		} else if ($this->isDir($pathname)) {
			return $this->deleteDir($pathname);
		}
		return false;
	}

	public function emptyDirectory(string $pathname, bool $delete = false): bool {
		if ($this->isDir($pathname)) {
			$i = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pathname, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
			foreach ($i as $list) {
				$list = $this->resolvePath($list->getRealPath());
				if ($this->isFile($list)) {$this->deleteFile($list);} else if ($this->isDir($list)) {@rmdir($list);}
			}
			if ($this->isTrue($delete)) {return @rmdir($pathname);}
			return true;
		}
		return false;
	}

	public function setMBInternalEncoding($reset = false): void {
		if ($this->isFalse(function_exists('mb_internal_encoding'))) {return;}
		static $encodings = [];
		static $overloaded = null;
		if (is_null($overloaded)) {$overloaded = (bool) ((int) ini_get('mbstring.func_overload') & 2);}
		if (!$overloaded) {return;}
		if (!$reset) {
			$encoding = mb_internal_encoding();
			array_push($encodings, $encoding);
			mb_internal_encoding('ISO-8859-1');
		} elseif ($reset && $encodings) {
			$encoding = array_pop($encodings);
			mb_internal_encoding($encoding);
		}
	}

	public function headersSent(): bool {if (headers_sent() === true) {return true;}return false;}

	public function copyFile(string $source, string $destination): bool {
		if ($this->isFile($destination)) {return true;} else if ($this->isFile($source)) {
			clearstatcache(false, $source);
			if (@copy($source, $destination)) {
				$this->setPermissions($destination);
				return true;
			}
		}
		return false;
	}

	public function copyFileToDir(string $realPath, string $dir): bool {
		if ($this->isFile($realPath)) {
			$dir = parent::INSERT_DIR_SEPARATOR($dir);
			if ($this->isNotDir($dir)) {$this->makeDir($dir);}
			$destination = $dir . basename($realPath);
			if ($this->isFile($destination)) {return true;} else if (@copy($realPath, $destination)) {
				$this->setPermissions($destination);
				return true;
			}
		}
		return false;
	}

	public function moveFile(string $source, string $destination): bool {
		if ($this->isFile($destination)) {return true;} else if ($this->isFile($source)) {
			clearstatcache(false, $source);
			if (@rename($source, $destination)) {
				if ($source !== $destination) {$this->deleteFile($source);}
				$this->setPermissions($destination);
				return true;
			}
		}
		return false;
	}

	public function moveFileToDir(string $realPath, string $dir): bool {
		if ($this->isFile($realPath)) {
			$dir = parent::INSERT_DIR_SEPARATOR($dir);
			if ($this->isNotDir($dir)) {$this->makeDir($dir);}
			$destination = $dir . basename($realPath);
			if ($this->isFile($destination)) {return true;} else if (@rename($realPath, $destination)) {
				if ($realPath !== $destination) {$this->deleteFile($realPath);}
				$this->setPermissions($destination);
				return true;
			}
		}
		return false;
	}

	public function randUnique(string $which = "key"): string {
		if (strtolower($which) == 'key') {return hash_hmac('sha256', bin2hex(random_bytes(16)), '');}
		return str_shuffle(mt_rand(100000, 999999) . $this->unixTimestamp());
	}

	public function getHeader(string $key): string {
		$heades = getallheaders();
		if (isset($heades[$key])) {return (string) $heades[$key];}
		return "";
	}

	public function getAuthorizationHeader(): string {return $this->getHeader("Authorization");}

	public function parseJSON(string $string): string {
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
		if (isset($errors[json_last_error()])) {throw new \Exception($errors[json_last_error()]);}
		return $parsed;
	}

	public function scaleIDemention(int $width, int $height, int $w, int $h): array {
		if ($width > $w && ($width / $w) > ($height / $h)) {
			$w = $width * ($w / $width);
			$h = $height * ($h / $width);
		} else if ($height > $h) {
			$w = $width * ($h / $height);
			$h = $height * ($h / $height);
		} else {
			$h = $height;
			$w = $width;
		}
		return [round($w), round($h)];
	}

	public function convertImage(string $source, string $extension = "webp", bool $useWandH = false, bool $scaleIDemention = false, string $realPath = ""): bool {
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
					$imageWidth = $useWandH ? $width : self::imageWidth;
					$imageHeight = $useWandH ? $height : self::imageHeight;
					if ($scaleIDemention) {
						list($imageWidth, $imageHeight) = $this->scaleIDemention($width, $height, $imageWidth, $imageHeight);
					}
					$color = @imagecreatetruecolor($imageWidth, $imageHeight);
					if ($this->isNotFalse($color)) {
						@imagecopyresampled($color, $image, 0, 0, 0, 0, $imageWidth, $imageHeight, $width, $height);
						$outputPath = $this->isNotEmptyString($realPath) ? $realPath : $source;
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

	public function hashString(string $string): string {return password_hash($string, PASSWORD_BCRYPT, array('cost' => 12));}
	public function hashVerified(string $string, string $hash): bool {return password_verify($string, $hash);}
	public function hashNeedsRehash(string $hash): bool {return password_needs_rehash($hash, PASSWORD_BCRYPT, array('cost' => 12));}

	public function containText(string $text, string $string): bool {return $this->isNotFalse(strpos($string, $text));}

	public function startsWith(string $start, string $string): bool {
		$start = trim($start);
		return substr($string, 0, strlen($start)) === $start;
	}

	public function endsWith(string $end, string $string): bool {
		$end = trim($end);
		return substr($string, -strlen($end)) === $end;
	}

	public function formatSize(int $size, int $precision = 2): string {
		if ($size > 0) {
			$base = log($size, 1024);
			$suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
			return round(pow(1024, ($base - floor($base))), $precision) . ' ' . $suffixes[floor($base)];
		}
		return "0 B";
	}

	public function hideEmailWithStarts(string $email): string {
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$explodedEmail = explode("@", $email);
			$name = implode("@", array_slice($explodedEmail, 0, count($explodedEmail) - 1));
			$len = floor(strlen($name) / 2);
			return substr($name, 0, $len) . str_repeat("*", $len) . "@" . end($explodedEmail);
		}
		return $email;
	}

	public function randomizeString(string $string): string {
		if (empty($string)) {$string = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';}
		for ($i = 1; $i <= 10; $i++) {$string = str_shuffle(strrev($string));}
		return $string;
	}

	public function downloadFile(string $realPath): bool {
		if ($this->isFile($realPath) && $this->headersSent() !== true) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . basename($realPath));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . $this->getFIlesizeInBytes($realPath));
			flush();
			readfile($a);
			return true;
		}
		return false;
	}

	public function generateUniqueId(): int {
		$uId = str_shuffle(mt_rand(100000, 999999) . $this->unixTimestamp());
		return substr($uId, 0, 10);
	}

	public function createStreamContext(string $context = "http", string $method = "HEAD"): mixed {
		$options = array();
		if ($context == "http" OR $context == "curl") {
			if ($context == "http") {$options['http'] = array('user_agent ' => self::userAgent, 'method' => $method, 'max_redirects' => 0, 'ignore_errors' => true, 'timeout' => 3, 'follow_location' => 0);} else { $options['curl'] = array('user_agent ' => self::userAgent, 'method' => $method, 'max_redirects' => 0, 'curl_verify_ssl_host' => false, "curl_verify_ssl_peer" => false);}
			$options['ssl'] = array('verify_peer' => false, 'verify_peer_name' => false, 'disable_compression' => true);
		}
		return stream_context_create($options);
	}

	public function remoteFileExist(string $remoteFilename): bool {
		try {
			$getFile = @file_get_contents($remoteFilename, false, $this->createStreamContext(), 0, 5);
			if ($this->isNotFalse($getFile)) {return true;}
		} catch (\Throwable $e) {}
		return false;
	}

	public function getDomain(string $host): string {
		$domain = strtolower(trim($host));
		$count = substr_count($domain, '.');
		if ($count === 2) {
			if (strlen(explode('.', $domain)[1]) > 3) {$domain = explode('.', $domain, 2)[1];}
		} else if ($count > 2) {$domain = $this->getDomain(explode('.', $domain, 2)[1]);}
		return $domain;
	}

	public function isEmptyString(string $string): bool {return $this->isString($string) && $this->isEmpty($string);}
	public function isNotEmptyString(string $string): bool {return $this->isString($string) && $this->isNotEmpty($string);}
	public function isString(mixed $arg): bool {return is_string($arg);}
	public function isNotString(mixed $arg): bool {return !is_string($arg);}
	public function isEmpty(mixed $arg): bool {return @empty($arg);}
	public function isNotEmpty(mixed $arg): bool {return !$this->isEmpty($arg);}
	public function inArray(mixed $needle, array $haystack): bool {return @in_array($needle, $haystack);}
	public function isNotInArray(mixed $needle, array $haystack): bool {return !$this->inArray($needle, $haystack);}
	public function isArray(mixed $arg): bool {return is_array($arg);}
	public function isNotArray(mixed $arg): bool {return !$this->isArray($arg);}
	public function isEmptyArray(array $array): bool {return $this->isArray($array) && $this->isEmpty($array);}
	public function isNotEmptyArray(array $array): bool {return !$this->isEmptyArray($array);}
	public function isBool(mixed $arg): bool {return @is_bool($arg);}
	public function isNotBool(mixed $arg): bool {return !$this->isBool($arg);}
	public function isInt(mixed $arg): bool {return @is_int($arg);}
	public function isNotInt(mixed $arg): bool {return !$this->isInt($arg);}
	public function isNull(mixed $arg): bool {return @is_null($arg);}
	public function isNonNull(mixed $arg): bool {return !$this->isNull($arg);}
	public function isTrue(mixed $arg): bool {return $arg === true;}
	public function isNotTrue(mixed $arg): bool {return !$this->isTrue($arg);}
	public function isFalse(mixed $arg): bool {return $arg === false;}
	public function isNotFalse(mixed $arg): bool {return !$this->isFalse($arg);}
	public function isFloat(mixed $arg): bool {return @is_float($arg);}
	public function isNotFloat(mixed $arg): bool {return !$this->isFloat($arg);}
	public function isNumeric(mixed $arg): bool {return @is_numeric($arg);}
	public function isNotNumeric(mixed $arg): bool {return !$this->isNumeric($arg);}
	public function isResource(mixed $arg): bool {return @is_resource($arg);}
	public function isNotResource(mixed $arg): bool {return !$this->isResource($arg);}
	public function getSize(string $realPath): int | false {return @filesize($realPath);}
	public function getMtime(string $realPath): int | false {return @filemtime($realPath);}
	public function getMime(string $realPath): string | false {return @mime_content_type($realPath);}
	public function isExists(string $realPath): bool {return @file_exists($realPath);}
	public function isReadable(string $realPath): bool {return @is_readable($realPath);}
	public function isExecutable(string $realPath): bool {return @is_executable($realPath);}
	public function isWritable(string $realPath): bool {return @is_writable($realPath);}
	public function isFile(string $realPath): bool {return @is_file($realPath);}
	public function isNotFile(string $realPath): bool {return !$this->isFile($realPath);}
	public function isDir(string $dirname): bool {return @is_dir($dirname);}
	public function isNotDir(string $dirname): bool {return !$this->isDir($dirname);}
	public function isLink(string $realPath): bool {return @is_link($realPath);}
	public function isNotLink(string $realPath): bool {return !$this->isLink($realPath);}
	public function isEmptyDir(string $dirname): bool {return ($this->isDir($dirname)) ? !(new \FilesystemIterator($dirname))->valid() : false;}
	public function isNotEmptyDir(string $dirname): bool {return !$this->isEmptyDir($dirname);}

	public function encFileIntoParts(string $sourceFile, string $toPath, string $key, string $iv, string $method = "aes-128-cbc"): bool {
		if (in_array($method, openssl_get_cipher_methods())) {
			try {
				if ($this->isFile($sourceFile)) {
					if ($this->isNotDir($toPath)) {$this->makeDir($toPath);}
					$chunkSize = self::EncFileIntoPartsChunckSize;
					$index = 1;
					$startBytes = 0;
					$totalBytes = $this->getFIlesizeInBytes($sourceFile);
					while ($startBytes < $totalBytes) {
						$remainingBytes = $totalBytes - $startBytes;
						$chunkBytes = min($chunkSize, $remainingBytes);
						$plainText = @file_get_contents($sourceFile, false, null, $startBytes, $chunkBytes);
						if ($plainText !== false) {
							$realPath = parent::INSERT_DIR_SEPARATOR($toPath) . '' . $index . '.part';
							$index += 1;
							$startBytes += $chunkBytes;
							$encryptedText = @openssl_encrypt($plainText, $method, $key, $option = OPENSSL_RAW_DATA, $iv);
							if ($encryptedText !== false) {$this->saveContentToFile($realPath, $encryptedText);}
						}
					}
					return true;
				}
			} catch (Throwable $e) {}
		}
		return false;
	}

	public function decPartsIntoFile(string $sourcePath, string $toFilename, string $key, string $iv, string $method = "aes-128-cbc"): bool {
		if (in_array($method, openssl_get_cipher_methods())) {
			try {
				if ($this->isDir($sourcePath)) {
					if ($this->isFile($toFilename)) {$this->deleteFile($toFilename);}
					$dirFiles = @scandir($sourcePath, $sortingOrder = SCANDIR_SORT_NONE);
					$numOfParts = 0;
					if ($dirFiles != false) {foreach ($dirFiles as $currentFile) {if (preg_match('/^\d+\.part$/', $currentFile)) {$numOfParts++;}}}
					if ($numOfParts >= 1) {
						for ($index = 1; $index <= $numOfParts; $index++) {
							$realPath = parent::INSERT_DIR_SEPARATOR($sourcePath) . '' . $index . '.part';
							if ($this->isFile($realPath)) {
								$cipherText = @file_get_contents($realPath, false, null, 0, null);
								if ($this->isNotFalse($cipherText)) {
									$this->deleteFile($realPath);
									$decryptedText = @openssl_decrypt($cipherText, $method, $key, $option = OPENSSL_RAW_DATA, $iv);
									if ($decryptedText !== false) {$this->saveContentToFile($toFilename, $decryptedText, true);}
								}
							}
						}
						return true;
					}
				}
			} catch (Throwable $e) {}
		}
		return false;
	}

	public function loadExtension(string $extension): bool {
		if (extension_loaded($extension)) {return true;}
		if (function_exists('dl') === false || ini_get('enable_dl') != 1) {return false;}
		if (strtolower(substr(PHP_OS, 0, 3)) === "win") {$suffix = ".dll";} else if (PHP_OS == 'HP-UX') {$suffix = ".sl";} else if (PHP_OS == 'AIX') {$suffix = ".a";} else if (PHP_OS == 'OSX') {$suffix = ".bundle";} else { $suffix = '.so';}
		return @dl('php_' . $extension . '' . $suffix) || @dl($extension . '' . $suffix);
	}

	public function clearCache(): bool {
		try {
			@header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
			@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			@header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
			@header("Cache-Control: post-check=0, pre-check=0", false);
			@header("Pragma: no-cache");
			return true;
		} catch (\Throwable $e) {}
		return false;
	}

	public function formatInt(float $num): float | string {
		if ($num > 0 && $this->inArray("NumberFormatter", get_declared_classes())) {
			$formater = new \NumberFormatter('en_US', \NumberFormatter::PADDING_POSITION);
			return $formater->format($num);
		}
		return $num;
	}

	public function renameFile(string $source, string $destination): bool {
		if ($this->isFile($source) && $this->isNotFile($destination)) {return @rename($source, $destination);}
		return false;
	}

	public function replaceUrlParamValue(string $param, mixed $value): string {
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

	public function reverseString(string $string): string {return strrev($string);}

	public function mb_reverseString(string $string, ?string $encoding = null): string {$chars = mb_str_split($string, 1, $encoding ?: mb_internal_encoding());return implode('', array_reverse($chars));}

	public function onlyDigits(mixed $argument): int {
		if ($this->isString($argument) || $this->isInt($argument)) {return @preg_replace('/[^0-9]/', '', $argument);}
		return $argument === null ? 0 : (int) $argument;
	}

	public function onlyString(mixed $argument): string {
		if ($this->isString($argument) || $this->isInt($argument)) {return @preg_replace('/[0-9]/', '', $argument);}
		return $argument === null ? "" : (string) $argument;
	}

	public function currentPathURL(): string {
		$ccUrl = $this->completeCurrentUrl();
		$parse = parse_url($ccUrl);
		$scheme = $parse['scheme'];
		$host = $parse['host'];
		$path = parent::ARRANGE_DIR_SEPARATOR($parse['path'], true);
		return $scheme . '://' . $host . '' . $path;
	}

	public function xssafe(string $data, string $encoding = 'UTF-8'): string {return htmlspecialchars($data, ENT_QUOTES | ENT_HTML401, $encoding);}

	public function createTemporaryFilename(string $extension, string $prepend = "", string $append = ""): string | false {
		$extension = $this->isNotEmptyString($extension) ? $extension : 'tmp';
		$prepend = $this->isNotEmptyString($prepend) ? $prepend . '_' : '';
		$append = $this->isNotEmptyString($append) ? '_' . $append : '';
		$path = parent::INSERT_DIR_SEPARATOR(sys_get_temp_dir());
		$realPath = $path . '' . $prepend . '' . substr($this->randUnique("key"), 0, 16) . '' . $append . '.' . $extension;
		return $this->createFile($realPath) ? $realPath : false;
	}

	public function generateRandomFilename(string $extension, string $prepend = "", string $append = ""): string | false {
		$extension = $this->isNotEmptyString($extension) ? $extension : 'tmp';
		$prepend = $this->isNotEmptyString($prepend) ? $prepend . '_' : '';
		$append = $this->isNotEmptyString($append) ? '_' . $append : '';
		return $prepend . '' . substr($this->randUnique("key"), 0, 16) . '' . $append . '.' . $extension;
	}

	public function safeFeof($handle, &$start = NULL) {
		$start = microtime(true);
		return feof($handle);
	}

	public function executeCommand(string $command): array | string {
		$output = "";
		if ($this->isNotEmptyString($command)) {
			$command = escapeshellcmd($command);
			$output = $this->executeCommandUsingExec($command);
		}
		return $output;
	}

	public function executeCommandUsingPopen(string $command): string {
		$output = "";
		if ($this->isNotEmptyString($command) && function_exists('popen')) {
			$handle = @popen($command, 'r');
			if ($this->isResource($handle)) {
				$content = @stream_get_contents($handle);
				if ($this->isString($content)) {$output = $content;}
				@pclose($handle);
			}
		}
		return $output;
	}

	public function executeCommandUsingProcopen(string $command): string {
		$output = "";
		if ($this->isNotEmptyString($command) && function_exists('proc_open')) {
			$errorFilename = $this->createTemporaryFilename("proc", "execute_command_error_output");
			$descriptorspec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("file", $errorFilename, "a"));
			$process = @proc_open($command, $descriptorspec, $pipes);
			if (is_resource($process)) {
				// Writeable handle connected to child stdin
				if (isset($pipes[0])) {@fclose($pipes[0]);}
				// Readable handle connected to child stdout
				if (isset($pipes[1])) {
					$content = @stream_get_contents($pipes[1]);
					fclose($pipes[1]);
					if ($this->isString($content)) {$output = $content;}
				}
				@proc_close($process);
			}
		}
		return $output;
	}

	public function executeCommandUsingExec(string $command): array {
		$output = array();
		if ($this->isNotEmptyString($command) && function_exists('exec')) {
			$content = array();
			$resultcode = 0;
			@exec($command, $content, $resultcode);
			if ($this->isArray($content)) {$output = array_values($content);}
		}
		return $output;
	}

	public function executeCommandUsingShellexec(string $command): string {
		$output = "";
		if ($this->isNotEmptyString($command) && function_exists('shell_exec')) {
			$content = shell_exec($command);
			if ($this->isString($content)) {$output = $content;}
		}
		return $output;
	}

	public function executeCommandUsingSystem(string $command): string {
		$output = "";
		if ($this->isNotEmptyString($command) && function_exists('system')) {
			$resultcode = 0;
			$content = system($command, $resultcode);
			if ($this->isString($content)) {$output = $content;}
		}
		return $output;
	}

	public function executeCommandUsingPassthru(string $command): string {
		$output = "";
		if ($this->isNotEmptyString($command) && function_exists('passthru')) {
			$resultcode = 0;
			ob_start();
			passthru($command, $resultcode);
			$content = ob_get_contents();
			if ($this->isString($content)) {$output = $content;}
			// Use this instead of ob_flush()
			ob_end_clean();
		}
		return $output;
	}

	// Get the current directory
	public function getCwd(): string {return @getcwd();}

	// Change the current directory
	public function chDir(string $dirname): bool {
		if ($this->isDir($dirname)) {@chdir($dirname);}
		return $this->getCwd() === $dirname;
	}

	public function loadPlugin(string $plugin): void {
		$dirname = parent::INSERT_DIR_SEPARATOR(parent::ARRANGE_DIR_SEPARATOR(PHPWebFuse['directories']['plugins']));
		$plugin = parent::ARRANGE_DIR_SEPARATOR($plugin);
		$extension = $this->getExtension($plugin);
		$name = $this->isNotEmptyString($extension) && strtolower($extension) == "php" ? $plugin : $plugin . '.php';
		$plugin = $dirname . '' . $name;
		if ($this->isFile($plugin)) {require_once $plugin;} else {throw new \Exception("The plugin \"" . $plugin . "\" doesn't exist.");}
	}

	public function loadLib(string $lib): void {
		$dirname = parent::INSERT_DIR_SEPARATOR(parent::ARRANGE_DIR_SEPARATOR(PHPWebFuse['directories']['libraries']));
		$lib = parent::ARRANGE_DIR_SEPARATOR($lib);
		$extension = $this->getExtension($lib);
		$name = $this->isNotEmptyString($extension) && strtolower($extension) == "php" ? $lib : $lib . '.php';
		$lib = $dirname . '' . $name;
		if ($this->isFile($lib)) {require_once $lib;} else {throw new \Exception("The lib \"" . $lib . "\" doesn't exist.");}
	}

	public function directTo(string $url): void {
		if (!headers_sent()) {
			@header("location: " . $url);
			exit;
		} else {throw new \Exception("Can't direct to \"" . $url . "\", headers has already been sent.");}
	}

	public function registerErrorHandler() {
		@set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
			if (!(error_reporting() & $errno)) {return false;} // This error code is not included in error_reporting, so let it fall through to the standard PHP error handler
			$errstr = htmlspecialchars($errstr);
			$errorFilename = parent::INSERT_DIR_SEPARATOR(PHPWebFuse['directories']['root']) . "\$error-messages.log";
			$this->saveContentToFile($errorFilename, strip_tags($errno . " ::Filename >> " . $errfile . " ::Line >> " . $errline . " ::Message >> " . $errstr . " ::Date >> " . date("F jS, Y", time()) . " @ " . date("h:i A", time())), true, true);
			echo "<div style='" . self::ErrorsCss['error'] . "'>" . $errno . " :: " . ($this->isTrue($this->isLocalhost()) ? "<b>Filename >></b> " . $errfile . " <b>Line >></b> " . $errline . " <b>Message >></b> " : "") . "" . $errstr . "</div>";
		});
	}

	public function registerExceptionHandler() {
		@set_exception_handler(function (\Throwable $ex) {
			$exceptionFilename = parent::INSERT_DIR_SEPARATOR(PHPWebFuse['directories']['root']) . "\$exception-messages.log";
			$this->saveContentToFile($exceptionFilename, strip_tags("Filename >> " . $ex->getFile() . " ::Line >> " . $ex->getLIne() . " ::Message >> " . $ex->getMessage() . " ::Date >> " . date("F jS, Y", time()) . " @ " . date("h:i A", time())), true, true);
			echo "<div style='" . self::ErrorsCss['exception'] . "'>" . ($this->isTrue($this->isLocalhost()) ? "<b>Filename >></b> " . $ex->getFile() . " <b>Line >></b> " . $ex->getLIne() . " <b>Message >></b> " : "") . "" . $ex->getMessage() . "</div>";
		});
	}

	private static function errorHandler(int $errno, string $errstr, string $errfile, string $errline, array $errcontext) {
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

	public function isLocalhost(): bool {return $this->inArray($this->getIPAddress(), (array) self::localhostAddresses);}

	public function validateIPAddress(string $ip): bool {
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			if ($this->isTrue(self::checkIPRange)) {foreach (self::privateIPRanges as $range) {if ($this->isIPInPrivateRange($ip, $range)) {return false;}}}
			return true;
		}
		return false;
	}

	public function getIPAddress(): string {
		$headersToCheck = array('HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_X_FORWARDED_HOST', 'REMOTE_ADDR');
		foreach ($headersToCheck as $header) {
			$determinedHeader = getenv($header);
			if ($this->isNotEmpty($determinedHeader)) {
				if ($header == "HTTP_X_FORWARDED_FOR") {
					$ipAddresses = explode(',', $determinedHeader);
					foreach ($ipAddresses as $realIp) {if ($this->validateIPAddress((string) $realIp)) {return $realIp;}}
				} else if ($this->validateIPAddress((string) $determinedHeader)) {return $determinedHeader;}
			}
		}
		return "";
	}

	public function isIPInPrivateRange(string $ip, string $range): bool {
		if ($this->isFalse(strpos($range, '/'))) {$range .= '/32';}
		list($subnet, $mask) = explode('/', $range);
		$subnet = ip2long($subnet);
		$ip = ip2long($ip);
		$mask = -1 << (32 - $mask);
		$subnet &= $mask; // Calculate the base address of the subnet
		return ($ip & $mask) == $subnet;
	}

	// Get the temporary directory
	public function getTmpDir(): string {return @sys_get_temp_dir();}

	// Get the upload directory
	public function getUploadDir(): string {return @ini_get('upload_tmp_dir');}

	// Get the default directory
	public function getCurrentFileDir(): string {return @dirname(__FILE__);}

	// Gets last access time of file
	public function accessTime(string $realPath): int {if ($this->isFile($realPath)) {return @fileatime($realPath);}return 0;}

	// Gets file modification time
	public function modificationTime(string $realPath): int {if ($this->isFile($realPath)) {return @filemtime($realPath);}return 0;}

	// Gets inode change time of file
	public function changeTime(string $realPath): int {if ($this->isFile($realPath)) {return @filectime($realPath);}return 0;}

	// A readable unix time
	public function readableUnix(string | int $unix): string {
		if ($this->isNumeric($unix)) {return @date("l, F jS, Y g:i:s A", $unix);}
		return "";
	}

	// Create a hard link
	public function createHardLink(string $target, string $link): bool {
		if ($this->isExists($target)) {return @link($target, $link);}
		return false;
	}

	// Creates a symbolic link
	public function createSymLink(string $target, string $link): bool {
		if ($this->isExists($target)) {return @symlink($target, $link);}
		return false;
	}

	public function calculateRemainingDaysFromUnix(int $unix) {
		$days = 0;
		if ($unix > time()) {
			$devided = (($unix - time()) / 86400);
			return round($devided, 0);
		}
		return $days;
	}

	public function calculateElapsedDaysFromUnix(int $unix) {
		$days = 0;
		if (time() > $unix) {
			$devided = ((time() - $unix) / 86400);
			return round($devided, 0);
		}
		return $days;
	}

	public function convertImageToBase64(string $realPath): string {
		$base64Image = "";
		// Supported image extensions
		$extensions = array('gif', 'jpg', 'jpeg', 'png');
		// Check if the file exists and is readable
		if ($this->isFile($realPath) && $this->isReadable($realPath)) {
			// Get the file extension
			$extension = strtolower($this->getExtension($realPath));
			// Check if the file extension is supported
			if ($this->isNotEmptyString($extension) && $this->inArray($extension, $extensions)) {
				// Get the image content and encode the image content to base64
				$base64Encode = base64_encode($this->getFileContent($realPath));
				// Add the appropriate data URI prefix
				$base64Image = 'data:' . mime_content_type($realPath) . ';base64,' . $base64Encode;
			}
		}
		return $base64Image;
	}

	// Return true on success, otherwise false or string representing error message
	public function validateMobileNumber(int | string $number, string $shortcode = "ng"): bool | string {
		$this->loadPlugin("CountriesList");
		$classesExists = class_exists("\libphonenumber\PhoneNumberUtil") && class_exists("\libphonenumber\PhoneNumberFormat") && class_exists("\libphonenumber\NumberParseException");
		if (class_exists("\CountriesList") && $classesExists && $this->isNumeric($number)) {
			$countriesList = new \CountriesList();
			$shortcodes = $countriesList->getShortCodes();
			$longnames = $countriesList->getLongNames();
			if (isset($shortcodes[strtoupper($shortcode)])) {
				try {
					$shortcode = strtoupper($shortcode);
					$util = \libphonenumber\PhoneNumberUtil::getInstance();
					$parse = $util->parseAndKeepRawInput($number, $shortcode);
					$isValid = $util->isValidNumber($parse);
					if ($this->isTrue($isValid)) {return trim($util->format($parse, \libphonenumber\PhoneNumberFormat::E164));}
				} catch (\libphonenumber\NumberParseException $e) {return $e->getMessage();}
			}
		}
		return false;
	}

	// Returns false on failure or an instance of \SleekDB\Store
	public function sleekDatabase(string $database, string $pathname, array $options = array()): mixed {
		if ($this->isNotEmptyString($database) && $this->makeDir($pathname) && class_exists("\SleekDB\Store")) {
			$options = $this->isEmptyArray($options) ? array('auto_cache' => false, 'timeout' => false, 'primary_key' => 'id', 'folder_permissions' => 0777) : $options;
			return new \SleekDB\Store($database, $this->resolvePath($pathname), $options);
		}
		return false;
	}

	// Returns 00:00:00 on default event if it's not an audio file or doesn't exist
	public function getAudioDuration(string $realPath): string {
		$duration = "00:00:00";
		if ($this->isFile($realPath)) {
			try {
				$rand = rand(0, 1);
				if ($rand == 0) {
					if (class_exists("\JamesHeinrich\GetID3\GetID3")) {
						$getID3 = new \JamesHeinrich\GetID3\GetID3;
						$analyze = @$getID3->analyze($this->resolvePath($realPath));
						if (isset($analyze['playtime_seconds'])) {$duration = gmdate("H:i:s", (int) $analyze['playtime_seconds']);}
					}
				} else {
					if (!in_array("\Mp3Info", get_declared_classes())) {$this->loadLib("Mp3Info" . DIRECTORY_SEPARATOR . "Mp3Info");}
					$info = new \Mp3Info($this->resolvePath($realPath));
					$duration = gmdate("H:i:s", (int) $info->duration);
				}
			} catch (\Throwable $e) {}
		}
		return $duration;
	}

	public function setAudioMetaTags(string $audioname, string $covername, array $options = array()): bool {
		if ($this->isFile($audioname) && $this->isFile($covername)) {
			$audioname = $this->resolvePath($audioname);
			$covername = $this->resolvePath($covername);
			$this->unlimitedWorkflow();
			$classesExists = class_exists("\JamesHeinrich\GetID3\GetID3") && class_exists("\JamesHeinrich\GetID3\WriteTags");
			if ($this->isTrue($classesExists) && $this->isNotEmptyArray($options)) {
				$getID3 = new \JamesHeinrich\GetID3\GetID3;
				$writer = new \JamesHeinrich\GetID3\WriteTags;
				$encoding = 'UTF-8';
				$getID3->setOption(array('encoding' => $encoding));
				$writer->filename = $audioname;
				$writer->tagformats = array('id3v1', 'id3v2.3');
				$writer->overwrite_tags = true;
				$writer->tag_encoding = $encoding;
				$writer->remove_other_tags = true;
				$data = array();
				if (isset($options['title'])) {$data['title'] = array($options['title']);}
				if (isset($options['artist'])) {$data['artist'] = array($options['artist']);}
				if (isset($options['album'])) {$data['album'] = array($options['album']);}
				if (isset($options['year'])) {$data['year'] = array($options['year']);}
				if (isset($options['genre'])) {$data['genre'] = array($options['genre']);}
				if (isset($options['comment'])) {$data['comment'] = array($options['comment']);}
				if (isset($options['track_number'])) {$data['track_number'] = array($options['track_number']);}
				if (isset($options['popularimeter'])) {$data['popularimeter'] = array('email' => "email", 'rating' => 128, 'data' => 0);}
				if (isset($options['unique_file_identifier'])) {$data['unique_file_identifier'] = array('ownerid' => "email", 'data' => md5(time()));}
				$tempPathname = parent::INSERT_DIR_SEPARATOR(parent::ARRANGE_DIR_SEPARATOR(PHPWebFuse['directories']['data'] . DIRECTORY_SEPARATOR . 'getid3' . DIRECTORY_SEPARATOR . 'temp'));
				if ($this->makeDir($tempPathname)) {
					$random = $this->generateRandomFilename("png");
					$_covername = $tempPathname . '' . $random;
					if ($this->convertImage($covername, "png", false, true, $_covername)) {$covername = $_covername;}
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

	public function cleanPHPWebFuseTempDirs(): void {
		$pathnames = $this->searchDir(PHPWebFuse['directories']['root'], array("temp"));
		foreach ($pathnames as $index => $pathname) {
			if ($this->isDir($pathname)) {
				$this->emptyDirectory($pathname);
			}
		}
	}

	public function intMobileDetect(): false | object {
		if (!class_exists("\MobileDetect")) {$this->loadPlugin("MobileDetect");}
		return class_exists("\Detection\MobileDetect") ? new \MobileDetect(new \Detection\MobileDetect) : false;
	}

	public function getBrowser(): string {
		$browser = "";
		$md = $this->intMobileDetect();
		if ($this->isNotFalse($md)) {$browser = $md->getBrowser();}
		return $browser;
	}

	public function getDevice(): string {
		$devices = "";
		$md = $this->intMobileDetect();
		if ($this->isNotFalse($md)) {$devices = $md->getDevice();}
		return $devices;
	}

	public function getDeviceOsName(): string {
		$os = "";
		$md = $this->intMobileDetect();
		if ($this->isNotFalse($md)) {$os = $md->getDeviceOsName();}
		return $os;
	}

	public function getDeviceBrand(): string {
		$brand = "";
		$md = $this->intMobileDetect();
		if ($this->isNotFalse($md)) {$brand = $md->getDeviceBrand();}
		return $brand;
	}

	public function getIPInfo(): array {
		$info = array();
		$md = $this->intMobileDetect();
		if ($this->isNotFalse($md)) {$info = $md->getIPInfo();}
		return $info;
	}

	public function isConnectedToInternet() {
		$socket = false;
		try { $socket = @fsockopen("www.google.com", 443, $errno, $errstr, 30);} catch (\Throwable $e) {}
		if ($socket !== false) {@fclose($socket);return true;}
		return false;
	}

	public function resolvePath(string $pathname): string {
		$realPath = realpath($pathname);
		return $this->isBool($realPath) ? $pathname : $realPath;
	}

	public function debugTrace(string $message) {$trace = array_shift(debug_backtrace());return die($trace["file"] . ": Line " . $trace["line"] . ": " . $message);}

	public function validatedGRecaptcha(string $serverkey, string $token): bool | array {
		if ($this->isNotEmptyString($serverkey) && $this->isNotEmptyString($token) && $this->isNumeric($token)) {
			try {
				$data = array("secret" => $serverkey, 'response' => $token, 'remoteip' => $this->getIPAddress());
				$options = array('http' => array('header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'method' => "POST", 'content' => http_build_query($data)));
				$serverresponse = @file_get_contents("https://google.com/recaptcha/api/siteverify", false, stream_context_create($options));
				if ($this->isNotFalse($serverresponse)) {return $this->jsonToArray($serverresponse);}
			} catch (\Throwable $e) {}
		}
		return false;
	}

	public function generateQrCode(string $content, string $filename): string | bool {
		$result = false;
		if (!defined('QR_MODE_NUL')) {$this->loadLib("phpqrcode" . DIRECTORY_SEPARATOR . "qrlib");}
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

	// Get the current script file
	public function getScriptFile(): string {return @getenv('SCRIPT_FILENAME');}

	// Get the current script name
	public function getScriptName(): string {return @getenv('SCRIPT_NAME');}

	// Get the current script path
	public function getScriptPath(): string {return @dirname($this->getScriptFile());}

	// Get the current script URL
	public function getScriptUrl(): string {return $this->protocol() . '://' . @getenv('HTTP_HOST') . $this->getScriptName();}

	// Get the current request URI
	public function getRequestUri(): string {return @getenv('REQUEST_URI');}

	// Get the current request method
	public function getRequestMethod(): string {return @getenv('REQUEST_METHOD');}

	// Get the current request time
	public function getRequestTime(): int {return @getenv('REQUEST_TIME');}

	// Get the current request time in seconds
	public function getRequestTimeFloat(): float {return @getenv('REQUEST_TIME_FLOAT');}

	// Get the current query string
	public function getQueryString(): string {return @getenv('QUERY_STRING');}

	// Get the current HTTP accept
	public function getHttpAccept(): string {return @getenv('HTTP_ACCEPT');}

	// Get the current HTTP accept charset
	public function getHttpAcceptCharset(): string {return @getenv('HTTP_ACCEPT_CHARSET');}

	// Get the current HTTP accept encoding
	public function getHttpAcceptEncoding(): string {return @getenv('HTTP_ACCEPT_ENCODING');}

	// Get the current HTTP accept language
	public function getHttpAcceptLanguage(): string {return @getenv('HTTP_ACCEPT_LANGUAGE');}

	// Get the current HTTP connection
	public function getHttpConnection(): string {return @getenv('HTTP_CONNECTION');}

	// Get the current HTTP host
	public function getHttpHost(): string {return @getenv('HTTP_HOST');}

	// Get the current HTTP referer
	public function getHttpReferer(): string {return @getenv('HTTP_REFERER');}

	// Get the current HTTP user agent
	public function getHttpUserAgent(): string {return @getenv('HTTP_USER_AGENT');}

	// Get the current HTTP X-Requested-With
	public function getHttpXRequestedWith(): string {return @getenv('HTTP_X_REQUESTED_WITH');}

	// Get the current HTTP X-Forwarded-For
	public function getHttpXForwardedFor(): string {return @getenv('HTTP_X_FORWARDED_FOR');}

	// Get the current HTTP X-Forwarded-Host
	public function getHttpXForwardedHost(): string {return @getenv('HTTP_X_FORWARDED_HOST');}

	// Get the current HTTP X-Forwarded-Proto
	public function getHttpXForwardedProto(): string {return @getenv('HTTP_X_FORWARDED_PROTO');}

	// Get the current HTTP X-Forwarded-Port
	public function getHttpXForwardedPort(): string {return @getenv('HTTP_X_FORWARDED_PORT');}

	// Get the current HTTP X-Forwarded-Server
	public function getHttpXForwardedServer(): string {return @getenv('HTTP_X_FORWARDED_SERVER');}

	// Get the current HTTP X-Forwarded-For-IP
	public function getHttpXForwardedForIp(): string {return @getenv('HTTP_X_FORWARDED_FOR_IP');}

	// Get the current HTTP X-Forwarded-Proto-IP
	public function getHttpXForwardedProtoIp(): string {return @getenv('HTTP_X_FORWARDED_PROTO_IP');}

	// Get the current HTTP X-Forwarded-Host-IP
	public function getHttpXForwardedHostIp(): string {return @getenv('HTTP_X_FORWARDED_HOST_IP');}

	// Get the current HTTP X-Forwarded-Port-IP
	public function getHttpXForwardedPortIp(): string {return @getenv('HTTP_X_FORWARDED_PORT_IP');}

	// Get the current HTTP X-Forwarded-Server-IP
	public function getHttpXForwardedServerIp(): string {return @getenv('HTTP_X_FORWARDED_SERVER_IP');}

	// Get the current HTTP X-Forwarded-For-Client-IP
	public function getHttpXForwardedForClientIp(): string {return @getenv('HTTP_X_FORWARDED_FOR_CLIENT_IP');}

	// Get the current HTTP X-Forwarded-Proto-Client-IP
	public function getHttpXForwardedProtoClientIp(): string {return @getenv('HTTP_X_FORWARDED_PROTO_CLIENT_IP');}

	// Get the current HTTP X-Forwarded-Host-Client-IP
	public function getHttpXForwardedHostClientIp(): string {return @getenv('HTTP_X_FORWARDED_HOST_CLIENT_IP');}

	// Get the current HTTP X-Forwarded-Port-Client-IP
	public function getHttpXForwardedPortClientIp(): string {return @getenv('HTTP_X_FORWARDED_PORT_CLIENT_IP');}

	// Get the current HTTP X-Forwarded-Server-Client-IP
	public function getHttpXForwardedServerClientIp(): string {return @getenv('HTTP_X_FORWARDED_SERVER_CLIENT_IP');}

	// Get the current HTTP X-Forwarded-For-Client
	public function getHttpXForwardedForClient(): string {return @getenv('HTTP_X_FORWARDED_FOR_CLIENT');}

	// Get the current HTTP X-Forwarded-Proto-Client
	public function getHttpXForwardedProtoClient(): string {return @getenv('HTTP_X_FORWARDED_PROTO_CLIENT');}

	// Get the current HTTP X-Forwarded-Host-Client
	public function getHttpXForwardedHostClient(): string {return @getenv('HTTP_X_FORWARDED_HOST_CLIENT');}

	// Get the current HTTP X-Forwarded-Port-Client
	public function getHttpXForwardedPortClient(): string {return @getenv('HTTP_X_FORWARDED_PORT_CLIENT');}

	// Get the current HTTP X-Forwarded-Server-Client
	public function getHttpXForwardedServerClient(): string {return @getenv('HTTP_X_FORWARDED_SERVER_CLIENT');}

	// Get the current HTTP X-Forwarded-For-Client-IP-Client
	public function getHttpXForwardedForClientIpClient(): string {return @getenv('HTTP_X_FORWARDED_FOR_CLIENT_IP_CLIENT');}

	// Get the current HTTP X-Forwarded-Proto-Client-IP-Client
	public function getHttpXForwardedProtoClientIpClient(): string {return @getenv('HTTP_X_FORWARDED_PROTO_CLIENT_IP_CLIENT');}

	// Get the current HTTP X-Forwarded-Host-Client-IP-Client
	public function getHttpXForwardedHostClientIpClient(): string {return @getenv('HTTP_X_FORWARDED_HOST_CLIENT_IP_CLIENT');}

	// PRIVATE METHODS
}
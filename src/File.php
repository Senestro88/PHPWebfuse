<?php

namespace PHPWebfuse;

use \PHPWebfuse\Utils;

/**
 * @author Senestro
 */
class File {
    // PRIVATE VARIABLES

    /**
     * @var const The default read and write chunk size (2MB)
     */
    private const CHUNK_SIZE = 2097152;

    /**
     * @var const The default read and write chunk size when encrypting or decrypting a file (5MB)
     */
    private const ENC_FILE_INTO_PARTS_CHUNK_SIZE = 5242880;

    // PUBLIC VARIABLES

    /**
     * Prevent the constructor from being initialized
     */
    private function __construct() {
        
    }

    // PUBLIC METHODS

    /**
     * Get file size in bytes
     * @param string $file The filename
     * @return int
     */
    public static function getFIlesizeInBytes(string $file): int {
        $bytes = 0;
        if(\is_file($file)) {
            $file = \realpath($file);
            clearstatcache(false, $file);
            $size = @filesize($file);
            if(\is_int($size)) {
                $bytes = $size;
            } else {
                $handle = @fopen($file, 'rb');
                if(\is_resource($handle)) {
                    while(($buffer = fgets($handle, self::CHUNK_SIZE)) !== false) {
                        $bytes += strlen($buffer);
                    }
                }
                fclose($handle);
            }
        }
        return $bytes;
    }

    /**
     * Get file size in kilo bytes
     * @param string $file The filename
     * @return int
     */
    public static function getFilesizeInKB(string $file): int {
        $bytes = self::getFIlesizeInBytes($file);
        return $bytes >= 1 ? \round($bytes / 1024) : 0;
    }

    /**
     * Get file size in mega bytes
     * @param string $file The filename
     * @return int
     */
    public static function getFilesizeInMB(string $file): int {
        $kb = self::getFilesizeInKB($file);
        return $kb >= 1 ? \round($kb / 1024) : 0;
    }

    /**
     * Get file size in giga bytes
     * @param string $file The filename
     * @return int
     */
    public static function getFilesizeInGB(string $file): int {
        $mb = self::getFilesizeInMB($file);
        return $mb >= 1 ? \round($mb / 1024) : 0;
    }

    /**
     * Create a file
     * @param string $file
     * @return bool
     */
    public static function createFile(string $file): bool {
        $isFile = \is_file($file);
        if(Utils::isFalse($isFile)) {
            $handle = @fopen($file, "w");
            if(\is_resource($handle)) {
                fclose($handle);
                Utils::setPermissions($file);
                $isFile = true;
            }
        }
        return Utils::isTrue($isFile);
    }

    /**
     * Save content to file
     * @param string $file The file path
     * @param string $content The file content
     * @param bool $append Wether to append the content to the file , default to 'false'.
     * @param bool $newline Wether to append the content on a new line if $append is true, default to 'false'
     * @return bool
     */
    public static function saveContentToFile(string $file, string $content, bool $append = false, bool $newline = false): bool {
        $saved = false;
        if(self::createFile($file)) {
            $handle = @fopen($file, $append ? "a" : "w");
            if(\is_resource($handle) && flock($handle, LOCK_EX | LOCK_SH)) {
                if(Utils::isTrue($append) && Utils::isTrue($newline) && self::getFIlesizeInBytes($file) >= 1) {
                    $content = "\n" . $content;
                }
                $saved = self::writeContentToHandle($handle, $content);
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
     * @param string $file The filename
     * @return string
     */
    public static function getFileContent(string $file): string {
        $content = '';
        if(\is_file($file)) {
            $handle = @fopen($file, 'rb');
            if(\is_resource($handle) && flock($handle, LOCK_EX | LOCK_SH)) {
                $start = null;
                $timeout = @ini_get('default_socket_timeout');
                while(!self::safeFeof($handle, $start) && (microtime(true) - $start) < $timeout) {
                    $read = fread($handle, self::CHUNK_SIZE);
                    if(\is_string($read)) {
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
     * Write content to file handle
     * @param mixed $handle The file handle
     * @param string $content The file content
     * @return bool
     */
    public static function writeContentToHandle(mixed $handle, string $content): bool {
        $offset = 0;
        if(\is_resource($handle)) {
            while($offset < strlen($content)) {
                $chunk = substr($content, $offset, self::CHUNK_SIZE);
                if((@fwrite($handle, $chunk)) === false) {
                    break;
                }
                $offset += self::CHUNK_SIZE;
            }
        }
        return $offset >= 1;
    }

    /**
     * Gets file type
     * @param string $file The file path
     * @return string | bool
     */
    public static function getType(string $file): string|bool {
        if(\is_file($file)) {
            return @filetype(self::resolvePath($file));
        }
        return "unknown";
    }

    /**
     * Gives information about a file or symbolic link
     * @param string $file The file path
     * @return array
     */
    public static function getStats(string $file): array {
        return \is_file($file) && \is_array($stat = @lstat($file)) ? $stat : array();
    }

    /**
     * Gets file info
     * @param string $file The file path
     * @return array
     */
    public static function getInfo(string $file): array {
        if(\is_file($file)) {
            $array = array();
            $i = new \SplFileInfo($file);
            $array['realpath'] = $i->getRealPath();
            $array['dirname'] = $i->getPath();
            $array['basename'] = $i->getBasename();
            $array['extension'] = $i->getExtension();
            $array['filename'] = $i->getBasename("." . $i->getExtension());
            $array['size'] = array('raw' => $i->getSize(), 'readable' => self::formatSize(self::getFIlesizeInBytes($file)));
            $array['atime'] = array('raw' => $i->getATime(), 'readable' => self::readableUnix($i->getATime()));
            $array['mtime'] = array('raw' => $i->getMTime(), 'readable' => self::readableUnix($i->getMTime()));
            $array['ctime'] = array('raw' => $i->getCTime(), 'readable' => self::readableUnix($i->getCTime()));
            $array['mime'] = self::getMime($file);
            $array['type'] = $i->getType();
            $array['permission'] = array('raw' => self::getPermission($file), 'readable' => self::getReadablePermission($file));
            $array['owner'] = array('raw' => $i->getOwner(), 'readable' => (function_exists("posix_getpwuid") ? posix_getpwuid($i->getOwner()) : ""));
            $array['group'] = array('raw' => $i->getGroup(), 'readable' => (function_exists("posix_getgrgid") ? posix_getgrgid($i->getGroup()) : ""));
            if($i->isLink()) {
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
     * @param string $file The file path
     * @param int $mtime Modifiied time, defaults to 'null'
     * @param int $atime Access time, defaults to 'null'
     * @return bool
     */
    public static function touchFile(string $file, ?int $mtime = null, ?int $atime = null): bool {
        return @touch($file, $mtime, $atime);
    }

    /**
     * Get file extension
     * @param string $file The file path
     * @return string
     */
    public static function getExtension(string $file): string {
        return isset(pathinfo($file)['extension']) ? pathinfo($file)['extension'] : "";
    }
    
    /**
     * Get directory name of a file or directory
     *
     * @param string $file
     * @return string
     */
    public static function getDirname(string $file): string
    {
        return isset(pathinfo($file)['dirname']) ? pathinfo($file)['dirname'] : $file;
    }

    /**
     * Remove extension from a filename
     * @param string $file he file path
     * @return string
     */
    public static function removeExtension(string $file): string {
        $extension = self::getExtension($file);
        if(self::isNotEmptyString($extension)) {
            return substr($file, 0, - (strlen($extension) + 1));
        }
        return $file;
    }

    /**
     * Delete a file
     * @param string $file
     * @return bool
     */
    public static function deleteFile(string $file): bool {
        if(self::isFile($file)) {
            return @unlink($file);
        }
        return false;
    }

    /**
     * Tells whether the filename is a regular file
     * @param string $file
     * @return bool
     */
    public static function isFile(string $file): bool {
        return @is_file($file);
    }

    /**
     * Tells whether the filename is not a regular file
     * @param string $file
     * @return bool
     */
    public static function isNotFile(string $file): bool {
        return !self::isFile($file);
    }

    /**
     * Tells whether the filename is a symbolic link
     * @param string $file
     * @return bool
     */
    public static function isLink(string $file): bool {
        return @is_link($file);
    }

    /**
     * Tells whether the filename is not a symbolic link
     * @param string $file
     * @return bool
     */
    public static function isNotLink(string $file): bool {
        return !self::isLink($file);
    }

    /**
     * Rename a file or directory
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public static function renameFile(string $source, string $destination): bool {
        if(self::isFile($source) && self::isNotFile($destination)) {
            return @rename($source, $destination);
        }
        return false;
    }

    /**
     * Copy a file to destination
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public static function copyFile(string $source, string $destination): bool {
        if(self::isFile($destination)) {
            return true;
        } elseif(self::isFile($source)) {
            clearstatcache(false, $source);
            if(@copy($source, $destination)) {
                Utils::setPermissions($destination);
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
    public static function copyFileToDir(string $source, string $dir): bool {
        if(self::isFile($source)) {
            $dir = self::insert_dir_separator($dir);
            if(self::isNotFile($dir)) {
                self::createFile($dir);
            }
            $destination = $dir . basename($source);
            if(self::isFile($destination)) {
                return true;
            } elseif(@copy($source, $destination)) {
                Utils::setPermissions($destination);
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
    public static function moveFile(string $source, string $destination): bool {
        if(self::isFile($destination)) {
            return true;
        } elseif(self::isFile($source)) {
            clearstatcache(false, $source);
            if(@rename($source, $destination)) {
                if($source !== $destination) {
                    self::deleteFile($source);
                }
                Utils::setPermissions($destination);
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
    public static function moveFileToFile(string $absolutePath, string $dir): bool {
        if(self::isFile($absolutePath)) {
            $dir = self::insert_dir_separator($dir);
            if(self::isNotFile($dir)) {
                self::createFile($dir);
            }
            $destination = $dir . basename($absolutePath);
            if(self::isFile($destination)) {
                return true;
            } elseif(@rename($absolutePath, $destination)) {
                if($absolutePath !== $destination) {
                    self::deleteFile($absolutePath);
                }
                Utils::setPermissions($destination);
                return true;
            }
        }
        return false;
    }

    /**
     * Convert a directory separator to the PHP OS directory separator
     * @param string $dir
     * @return string
     * */
    public static function convert_dir_separators(string $dir): string {
        return str_ireplace(array("\\", "//"), DIRECTORY_SEPARATOR, $dir);
    }

    /**
     * Delete directory separator from the right after converting
     * @param string $dir
     * @return string
     * */
    public static function right_delete_dir_separator(string $dir): string {
        return rtrim(self::convert_dir_separators($dir), DIRECTORY_SEPARATOR);
    }

    /**
     * Delete directory separator from the left after converting
     * @param string $dir
     * @return string
     * */
    public static function left_delete_dir_separator(string $dir): string {
        return ltrim(self::convert_dir_separators($dir), DIRECTORY_SEPARATOR);
    }

    /**
     * Arrange directory separator from multiple separators like "//" or "\\" to PHP OS directory separator
     * @param string $dir
     * @param bool $closeEdges - Defaults to false
     * @return string
     * */
    public static function arrange_dir_separators(string $dir, bool $closeEdges = false): string {
        $separator = DIRECTORY_SEPARATOR;
        $explodedPath = array_filter(explode($separator, self::convert_dir_separators($dir)));
        return ($closeEdges ? $separator : "") . implode($separator, $explodedPath) . ($closeEdges ? $separator : "");
    }

    /**
     * Insert directory separator to the beginning or end
     * @param string $dir
     * @param bool $toEnd - Defaults to true
     * @return string
     * */
    public static function insert_dir_separator(string $dir, bool $toEnd = true): string {
        $separator = DIRECTORY_SEPARATOR;
        return ($toEnd === false ? $separator : "") . self::convert_dir_separators($dir) . ($toEnd === true ? $separator : "");
    }

    /**
     * Open a directory recursively and list out the files
     * @param string $dir The directory path
     * @return array
     */
    public static function scanDirRecursively(string $dir): array {
        $lists = array();
        if(!empty($dir) && \is_dir($dir) && \is_readable($dir)) {
            $dir = \realpath($dir);
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::CURRENT_AS_FILEINFO), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach($iterator as $list) {
                $lists[] = \realpath($list->getRealPath());
            }
            $i = null;
            unset($i);
        }
        return $lists;
    }

    /**
     * Scan a directory and return files list
     * @param string $dir The directory path
     * @return array
     */
    public static function scanDir(string $dir): array {
        $lists = array();
        if(\is_dir($dir) && \is_readable($dir)) {
            $dir = \realpath($dir);
            $iterator = new \IteratorIterator(new \DirectoryIterator($dir));
            foreach($iterator as $list) {
                if(!$list->isDot()) {
                    $lists[] = \realpath($list->getRealPath());
                }
            }
        }
        return $lists;
    }

    /**
     * Scan a directory recursively for patterns
     * @param string $dir The directory path
     * @param string $pattern The pattern
     * @param bool $recursive
     * @return array
     */
    public static function scanDirForPattern(string $dir, string $pattern = "", bool $recursive = false): array {
        $lists = array();
        if(!empty($dir) && \is_dir($dir) && \is_readable($dir)) {
            $dir = \realpath($dir);
            $iterator = $recursive ? new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::CURRENT_AS_SELF) : new \DirectoryIterator($dir);
            if(!empty($pattern)) {
                $CallbackFilterIterator = $recursive ? "\RecursiveCallbackFilterIterator" : "\CallbackFilterIterator";
                $iterator = new $CallbackFilterIterator($iterator, function($current) use($pattern) {
                            // TRUE to accept the current item to the iterator, FALSE otherwise
                            if($current->isDir() && !$current->isDot()) {
                                return true;
                            } else {
                                return Utils::matchFilename($current->getRealPath(), $pattern);
                            }
                        });
            }
            $iterator = $recursive ? new \RecursiveIteratorIterator($iterator) : new \IteratorIterator($iterator);
            foreach($iterator as $key => $list) {
                $lists[] = \realpath($list->getRealPath());
            }
        }
        return $lists;
    }

    /**
     * Gets the size of a directory
     * @param string $dir The directory path
     * @param bool $recursive
     * @return int
     */
    public static function getDirSize(string $dir, bool $recursive = true): int {
        $size = 0;
        $files = Utils::isTrue($recursive) ? self::scanDirRecursively($dir) : self::scanDir($dir);
        foreach($files as $index => $value) {
            if(\is_file($value) && \is_readable($value)) {
                $size += self::getFIlesizeInBytes($value);
                clearstatcache(false, $value);
            }
        }
        $files = null;
        unset($files);
        return $size;
    }

    /**
     * Gets the information of files in a directory
     * @param string $dir The directory path
     * @param bool $recursive
     * @return array
     */
    public static function getDirFilesInfo(string $dir, bool $recursive = true): array {
        $array = array();
        $files = Utils::isTrue($recursive) ? self::scanDirRecursively($dir) : self::scanDir($dir);
        foreach($files as $index => $file) {
            if(\is_file($file)) {
                $array[] = self::getFileInfo($file);
            } elseif(\is_dir($file)) {
                
            }
        }
        $files = null;
        unset($files);
        return $array;
    }

    /**
     * Search a directory
     * @param string $dir The directory path
     * @param array $matches
     * @param bool $asExtensions If true, it assume $matches are extensions to match against else match filenames containing the matches
     * @param bool $recursive
     * @return array
     */
    public static function searchDir(string $dir, array $matches = array(), bool $asExtensions = false, bool $recursive = true): array {
        $results = array();
        // Get files list, either recursively or non-recursively
        $files = $recursive ? self::scanDirRecursively($dir) : self::scanDir($dir);
        // Iterate through each file in the directory
        foreach($files as $file) {
            $info = self::getFileInfo($file);
            // Check each match pattern
            foreach($matches as $match) {
                if($asExtensions) {
                    // If searching by extension, check the file extension
                    $extension = $info['extension'] ?? "";
                    if(Utils::endsWith(strtolower($extension), strtolower($match))) {
                        $results[] = $file;
                    }
                } else {
                    // Otherwise, check if the file name contains the match string
                    if(Utils::containText(strtolower($match), strtolower($file))) {
                        $results[] = $file;
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Delete a file based on extensions
     * @param string $dir
     * @param array $extensions
     * @param bool $recursive: Default to 'true'
     * @return void
     */
    public static function deleteFilesBaseOnExtension(string $dir, array $extensions = array(), bool $recursive = true): void {
        if(\is_dir($dir) && \is_readable($dir)) {
            $dir = Dir::insert_dir_separator(\realpath($dir));
            foreach($extensions as $extension) {
                if(Utils::isTrue($recursive)) {
                    $i = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
                    foreach($i as $list) {
                        $list = \realpath($list->getRealPath());
                        $info = Utils::getFileInfo($list);
                        $si = (isset($info['extension']) ? $info['extension'] : "");
                        if(strtolower($si) == strtolower($extension)) {
                            self::deleteFile($list);
                        }
                    }
                } else {
                    $glob = glob($dir . '*.' . $extension);
                    if(Utils::isNotFalse($glob) && \is_array($glob)) {
                        foreach($glob as $list) {
                            self::deleteFile($list);
                        }
                    }
                }
            }
        }
    }

    /**
     * Make a directory. This function will return true if the directory already exist
     * @param string $dir
     * @return bool
     */
    public static function makeDir(string $dir): bool {
        if(\is_dir($dir)) {
            Utils::setPermissions($dir);
            return true;
        } else {
            try {
                if(Utils::isTrue(@mkdir($dir, Utils::DIRECTORY_PERMISSION, true))) {
                    Utils::setPermissions($dir);
                    return true;
                }
            } catch(\Throwable $e) {
                
            }
        }
        return false;
    }

    /**
     * Delete a directory
     * @param string $dir
     * @return bool
     */
    public static function deleteDir(string $dir): bool {
        return Utils::emptyDirectory($dir, true);
    }

    /**
     * Empty a directory
     * @param string $dir
     * @param bool $delete Wether to delete the directory is self after deleting the directory contents
     * @return bool
     */
    public static function emptyDirectory(string $dir, bool $delete = false): bool {
        if(\is_dir($dir)) {
            $i = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach($i as $list) {
                $list = \realpath($list->getRealPath());
                if(\is_file($list)) {
                    self::deleteFile($list);
                } elseif(\is_dir($list)) {
                    @rmdir($list);
                }
            }
            if(Utils::isTrue($delete)) {
                return @rmdir($dir);
            }
            return true;
        }
        return false;
    }

    /**
     * Tells whether the filename is a directory
     * @param string $dirname
     * @return bool
     */
    public static function isDir(string $dirname): bool {
        return @is_dir($dirname);
    }

    /**
     * Tells whether the filename is not a directory
     * @param string $dirname
     * @return bool
     */
    public static function isNotDir(string $dirname): bool {
        return !self::isDir($dirname);
    }

    /**
     * Check if directory is empty
     * @param string $dirname
     * @return bool
     */
    public static function isEmptyDir(string $dirname): bool {
        return (self::isDir($dirname)) ? !(new \FilesystemIterator($dirname))->valid() : false;
    }

    /**
     * Check if directory is not empty
     * @param string $dirname
     * @return bool
     */
    public static function isNotEmptyDir(string $dirname): bool {
        return !self::isEmptyDir($dirname);
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
    public static function encFileIntoParts(string $sourceFile, string $toPath, string $key, string $iv, string $method = "aes-128-cbc"): bool
    {
        if (in_array($method, openssl_get_cipher_methods())) {
            try {
                if (self::isFile($sourceFile)) {
                    if (self::isNotFile($toPath)) {
                        self::createFile($toPath);
                    }
                    $chunkSize = self::ENC_FILE_INTO_PARTS_CHUNK_SIZE;
                    $index = 1;
                    $startBytes = 0;
                    $totalBytes = self::getFIlesizeInBytes($sourceFile);
                    while ($startBytes < $totalBytes) {
                        $remainingBytes = $totalBytes - $startBytes;
                        $chunkBytes = min($chunkSize, $remainingBytes);
                        $plainText = @file_get_contents($sourceFile, false, null, $startBytes, $chunkBytes);
                        if ($plainText !== false) {
                            $file = self::insert_dir_separator($toPath) . '' . $index . '.part';
                            $index += 1;
                            $startBytes += $chunkBytes;
                            $encryptedText = @openssl_encrypt($plainText, $method, $key, $option = OPENSSL_RAW_DATA, $iv);
                            if ($encryptedText !== false) {
                                self::saveContentToFile($file, $encryptedText);
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
    public static function decPartsIntoFile(string $sourcePath, string $toFilename, string $key, string $iv, string $method = "aes-128-cbc"): bool
    {
        if (in_array($method, openssl_get_cipher_methods())) {
            try {
                if (self::isFile($sourcePath)) {
                    if (self::isFile($toFilename)) {
                        self::deleteFile($toFilename);
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
                            $file = self::insert_dir_separator($sourcePath) . '' . $index . '.part';
                            if (self::isFile($file)) {
                                $cipherText = @file_get_contents($file, false, null, 0, null);
                                if (self::isNotFalse($cipherText)) {
                                    self::deleteFile($file);
                                    $decryptedText = @openssl_decrypt($cipherText, $method, $key, $option = OPENSSL_RAW_DATA, $iv);
                                    if ($decryptedText !== false) {
                                        self::saveContentToFile($toFilename, $decryptedText, true);
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
     * Safe file end of file
     *
     * $timeout = @ini_get('default_socket_timeout');
     * while (!self::safeFeof($handle, $start) && (microtime(true) - $start) < $timeout) {}
     *
     * @param type $handle
     * @param type $start
     * @return bool
     */
    public static function safeFeof($handle, &$start = null): bool
    {
        $start = microtime(true);
        return feof($handle);
    }
}

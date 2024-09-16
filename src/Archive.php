<?php

namespace PHPWebfuse;

use PHPWebfuse\Utils;
use PHPWebfuse\File;
use PHPWebfuse\Path;

/**
 * @author Senestro
 */
class Archive
{
    // PRIVATE VARIABLE
    // PUBLIC VARIABLES
    // PUBLIC METHODS

    /**
     * Prevent the constructor from being initialized
     */
    private function __construct()
    {

    }

    /**
     * Creates a .zip archive from PclZip library
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @return array|string Return string on failure which contains error message else file info
     */
    public static function createPclzip(string $name, array $items, string $dirname): array|string
    {
        $result = "";
        // Load the PclZip
        Utils::loadPlugin("PclZip");
        // Check if the PclZip class exist
        if(class_exists("\PclZip")) {
            // Resolve dirname path
            $dirname = Utils::resolvePath($dirname);
            // Make sure dirname exists or created
            if(Utils::isString($dirname) && File::createDir($dirname)) {
                // Filter the archive name
                $name = self::setName($name, "zip");
                // Arrange dirname
                $dirname = Path::arrange_dir_separators($dirname);
                // The archive absolute path
                $archivename = $dirname . DIRECTORY_SEPARATOR . $name;
                // Delete file if exist
                File::deleteFile($archivename);
                // Init PclZip
                $archive = new \PclZip($archivename);
                // Loop items
                foreach($items as $index => $item) {
                    // Arrange the item path
                    $item = Path::arrange_dir_separators($item);
                    if(File::isFile($item) || File::isDir($item)) {
                        $remmovePath = Path::arrange_dir_separators(File::getDirname($item));
                        $archive->add(Utils::resolvePath($item), PCLZIP_OPT_REMOVE_PATH, $remmovePath);
                    }
                }
                // Check if archive is created
                if(File::isFile($archivename)) {
                    clearstatcache(false, $archivename);
                    $result = File::getInfo($archivename);
                } else {
                    $result = "Failed to create the zip archive: " . $archivename;
                }
            } else {
                $result = "Failed to create the zip archive, invalid dirname: " . $dirname;
            }
        } else {
            $result = "The PclZip plugin isn't loaded";
        }
        return $result;
    }

    /**
     * Creates a .tgz archive
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @return array|string Return string on failure which contains error message else file info
     */
    public static function createGz(string $name, array $items, string $dirname): array|string
    {
        return self::createTarArchive($name, $items, $dirname, true);
    }

    /**
     * Creates a .tar archive
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @return array|string Return string on failure which contains error message else file info
     */
    public static function createTar(string $name, array $items, string $dirname): array|string
    {
        return self::createTarArchive($name, $items, $dirname, false);
    }

    /**
     * Creates a .zip archive from the standard ZipArchive
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @param ?string $password The archive password, default to null
     * @param ?string $comment The  archive comment, default to null
     * @return array|string Return string on failure which contains error message else file info
     */
    public static function createZip(string $name, array $items, string $dirname, ?string $password = null, ?string $comment = null): array|string
    {
        $result = "";
        // Resolve dirname path
        $dirname = Utils::resolvePath($dirname);
        // Check if the ZipArchive class exist
        if(class_exists("\ZipArchive")) {
            // Make sure dirname exists or created
            if(Utils::isString($dirname) && File::createDir($dirname)) {
                // Filter the archive name
                $name = self::setName($name, "zip");
                // Arrange dirname
                $dirname = Path::arrange_dir_separators($dirname);
                // The archive absolute path
                $archivename = $dirname . DIRECTORY_SEPARATOR . $name;
                // Delete file if exist
                File::deleteFile($archivename);
                // Init \ZipArchive
                $archive = new \ZipArchive();
                // Check if the archive is opened
                if(Utils::isTrue($archive->open($archivename, \ZipArchive::OVERWRITE | \ZipArchive::CREATE))) {
                    // Set the comment
                    if(Utils::isString($comment) && Utils::isNotEmptyString($comment)) {
                        $archive->setArchiveComment((string) $comment);
                    }
                    // Set the password
                    $usingPassword = false;
                    if(Utils::isString($password) && Utils::isNotEmptyString($password)) {
                        $archive->setPassword((string) $password);
                        $usingPassword = true;
                    }
                    // The entries
                    $entries = array();
                    // Loop the items
                    foreach($items as $item) {
                        // Arrange the item path
                        $item = Path::arrange_dir_separators($item);
                        // Add the file to zip
                        if(File::isFile($item)) {
                            $entry = basename($item);
                            if($archive->addFile($item, $entry)) {
                                $entries[] = $entry;
                            }
                        } elseif(File::isDir($item)) {
                            // Recursively iterate the directory item
                            $files = File::scanDirRecursively($item);
                            foreach($files as $file) {
                                $entry = Path::arrange_dir_separators(\str_replace(File::getDirname($item), "", $file), false, "/");
                                // When it's an empty directory or file
                                if(File::isDir($file) && $archive->addEmptyDir($entry)) {
                                    $entries[] = $entry;
                                } elseif(File::isFile($file) && $archive->addFile($file, $entry)) {
                                    $entries[] = $entry;
                                }
                            }
                        }
                    }
                    // Loop added entries
                    foreach($entries as $entry) {
                        // Set the compression
                        if($archive->isCompressionMethodSupported(\ZipArchive::CM_BZIP2)) {
                            $archive->setCompressionName($entry, \ZipArchive::CM_BZIP2);
                        }
                        // If password has been set on the archive, set the password for all entries
                        if(Utils::isTrue($usingPassword) && $archive->isEncryptionMethodSupported(\ZipArchive::EM_AES_256)) {
                            $archive->setEncryptionName($entry, \ZipArchive::EM_AES_256);
                        }
                    }
                    // Finish and close
                    $status = $archive->getStatusString();
                    $close = $archive->close();
                    // Check if the archive was unable to close
                    if(Utils::isFalse($close)) {
                        $result = "Failed to create the zip archive, unable to close the zip archive: " . $archivename . " [" . $status . "]";
                    } else {
                        clearstatcache(false, $archivename);
                        $result = File::getInfo($archivename);
                    }
                } else {
                    $result = 'Unable to open the zip archive: ' . $archivename;
                }
            } else {
                $result = "Failed to create the zip archive, invalid dirname: " . $dirname;
            }
        } else {
            $result = "The ZipArchive isn't loaded";
        }
        return $result;
    }

    // PRIVATE METHODS

    /**
     * Set and return the archive name
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param string $extension Add the extension at the end of the name
     * @return string Returns the new name
     */
    private static function setName(string $name, string $extension = "zip"): string
    {
        $name = Utils::isEmptyString($name) ? Utils::randUnique('key') : $name;
        $ext = File::getExtension($name);
        $name = Utils::isNotEmptyString($ext) ? (strtolower($ext) == $extension ? $name : $name . '.' . $extension) : $name . '.' . $extension;
        return str_replace(array('\\', '/', ':', '*', '?', '<', '>', '|'), '_', $name);
    }

    /**
     * Creates a .tar archive or .gz when compress is set to true
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @param bool $compress
     * @return array|string Return string on failure which contains error message else file info
     * @throws \PHPWebfuse\Instance\Exceptions\Exception
     */
    private static function createTarArchive(string $name, array $items, string $dirname, bool $compress = true): array|string
    {
        $result = "";
        // Resolve dirname path
        $dirname = Utils::resolvePath($dirname);
        // Check if the PharData class exist
        if(class_exists("\PharData")) {
            // Make sure dirname exists or created
            if(Utils::isString($dirname) && File::createDir($dirname)) {
                // Filter the archive name
                $name = self::setName($name, "tar");
                // Arrange dirname
                $dirname = Path::arrange_dir_separators($dirname);
                // The archive absolute path
                $archivename = $dirname . DIRECTORY_SEPARATOR . $name;
                // Delete file if exist
                File::deleteFile($archivename);
                // Init PharData
                try {
                    $phar = new \PharData($archivename);
                    // Loop the items
                    foreach($items as $item) {
                        // Arrange the item path
                        $item = Path::arrange_dir_separators($item);
                        // Add the file to zip
                        if(File::isFile($item)) {
                            $entry = basename($item);
                            $phar->addFile($item, $entry);
                        } elseif(File::isDir($item)) {
                            // Recursively iterate the directory item
                            $files = File::scanDirRecursively($item);
                            foreach($files as $file) {
                                $entry = Path::arrange_dir_separators(\str_replace(File::getDirname($item), "", $file), false, "/");
                                // When it's an empty directory or file
                                if(File::isDir($file)) {
                                    $phar->addEmptyDir($entry);
                                } elseif(File::isFile($file)) {
                                    $phar->addFile($file, $entry);
                                }
                            }
                        }
                    }
                    if($compress) {
                        $compressedname = File::removeExtension($archivename) . ".gz";
                        // Delete compressed file if exist
                        File::deleteFile($compressedname);
                        $phar->compress(\Phar::GZ, "gz");
                        if(File::isFile($compressedname)) {
                            // Delete file if exist
                            File::deleteFile($archivename);
                            $archivename = $compressedname;
                        } else {
                            throw new \PHPWebfuse\Instance\Exceptions\Exception("Failed to create the compress .gz archive from the .tar archive [".$compressedname."] - [".$archivename."]");
                        }
                    }
                    // Check if archive is created
                    if(File::isFile($archivename)) {
                        clearstatcache(false, $archivename);
                        $result = File::getInfo($archivename);
                    } else {
                        $result = "Failed to create the " . $extension . " archive: " . $archivename;
                    }
                } catch(\Exception $ex) {
                    // Delete file if exist
                    File::deleteFile($archivename);
                    $result = "Failed to create the " . $extension . " archive, an error has occurred (" . $ex->getMessage() . ")";
                }
            } else {
                $result = "Failed to create the " . $extension . " archive, invalid dirname: " . $dirname;
            }
        } else {
            $result = "The PharData isn't loaded";
        }
        return $result;
    }
}

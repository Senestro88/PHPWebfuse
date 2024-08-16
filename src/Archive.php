<?php

namespace PHPWebfuse;

use \PHPWebfuse\Utils;
use \PHPWebfuse\File;
use \PHPWebfuse\FileInfo;

/**
 * @author Senestro
 */
class Archive {
    // PRIVATE VARIABLE
    // PUBLIC VARIABLES
    // PUBLIC METHODS

    /**
     * Creates a .zip archive from PclZip library
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @return \PHPWebfuse\FileInfo | string Return string on failure which contains error message else \PHPWebfuse\FileInfo
     */
    public static function createPclzip(string $name, array $items, string $dirname): array|string {
        $result = "";
        // Load the PclZip
        Utils::loadPlugin("PclZip");
        // Check if the PclZip class exist
        if(class_exists("\PclZip")) {
            // Resolve dirname path
            $dirname = \realpath($dirname);
            // Make sure dirname exists or created
            if(Utils::isString($dirname) && File::makeDir($dirname)) {
                Utils::unlimitedWorkflow();
                // Filter the archive name
                $name = self::setName($name);
                // Arrange dirname
                $dirname = self::arrangePath($dirname);
                // The archive absolute path
                $archivename = $dirname . DIRECTORY_SEPARATOR . $name;
                // Delete file if exist
                if(File::isFile($archivename)) {
                    File::deleteFile($archivename);
                }
                // Init PclZip
                $archive = new \PclZip($archivename);
                // Loop items
                foreach($items as $index => $item) {
                    // Arrange the item path
                    $item = self::arrangePath($item);
                    if(File::isFile($item) or File::isDir($item)) {
                        $rmPath = self::arrangePath(File::getDirname($item));
                        $archive->add(\realpath($item), PCLZIP_OPT_REMOVE_PATH, $rmPath);
                    }
                }
                // Check if archive is created
                if(File::isFile($archivename)) {
                    clearstatcache(false, $archivename);
                    $result = FileInfo::getInfo($archivename);
                } else {
                    $result = "Failed to create the zip archive [" . $archivename . "]";
                }
            } else {
                $result = "Invalid dirname [" . $dirname . "]";
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
     * @return \PHPWebfuse\FileInfo | string Return string on failure which contains error message else \PHPWebfuse\FileInfo
     */
    public static function createTgz(string $name, array $items, string $dirname): array|string {
        return self::createTgzOrTar($name, $items, $dirname, true);
    }

    /**
     * Creates a .tar archive
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @return \PHPWebfuse\FileInfo | string Return string on failure which contains error message else \PHPWebfuse\FileInfo
     */
    public static function createTar(string $name, array $items, string $dirname): array|string {
        return self::createTgzOrTar($name, $items, $dirname, false);
    }

    /**
     * Creates a .zip archive from the standard ZipArchive
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @param ?string $password The archive password, default to null
     * @param ?string $comment The  archive comment, default to null
     * @return \PHPWebfuse\FileInfo | string Return string on failure which contains error message else \PHPWebfuse\FileInfo
     */
    public static function createZip(string $name, array $items, string $dirname, ?string $password = null, ?string $comment = null): array|string {
        $result = "";
        // Resolve dirname path
        $dirname = \realpath($dirname);
        // Check if the \PhpZip\ZipFile class exist
        if(class_exists("\PhpZip\ZipFile")) {
            // Make sure dirname exists or created
            if(Utils::isString($dirname) && File::makeDir($dirname)) {
                Utils::unlimitedWorkflow();
                // Filter the archive name
                $name = self::setName($name, "zip");
                // Arrange dirname
                $dirname = self::arrangePath($dirname);
                // The archive absolute path
                $archivename = $dirname . DIRECTORY_SEPARATOR . $name;
                // Delete file if exist
                if(File::isFile($archivename)) {
                    File::deleteFile($archivename);
                }
                // Init \ZipArchive
                $archive = new \ZipArchive();
                // Check if the archive is opened
                if(Utils::isTrue($archive->open($archivename, \ZipArchive::OVERWRITE | \ZipArchive::CREATE))) {
                    // Set the comment
                    if(Utils::isString($comment) && Utils::isNotEmptyString($comment)) {
                        $archive->setArchiveComment((string) $comment);
                    }
                    // Set the password
                    $withPassword = false;
                    if(Utils::isString($password) && Utils::isNotEmptyString($password)) {
                        $archive->setPassword((string) $password);
                        $withPassword = true;
                    }
                    // The entries
                    $entries = array();
                    // Loop the items
                    foreach($items as $index => $item) {
                        // Arrange the item path
                        $item = self::arrangePath($item);
                        // Add the file to zip
                        if(File::isFile($item)) {
                            $entry = basename($item);
                            if($archive->addFile($item, $entry)) {
                                $entries[] = $entry;
                            }
                        } elseif(File::isDir($item)) {
                            // Recursively iterate the directory item
                            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($item, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
                            foreach($iterator as $list) {
                                // Arrange iterated list path
                                $listPath = self::arrangePath($list->getPathname());
                                // Set entry for each iterated list
                                $entry = \str_replace("\\", "/", self::arrangePath(str_replace($item, basename($item) . \DIRECTORY_SEPARATOR, $listPath)));
                                // When it's an empty directory or file
                                if($list->isDir() && $archive->addEmptyDir($entry)) {
                                    $entries[] = $entry;
                                } elseif($list->isFile() && $archive->addFile($listPath, $entry)) {
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
                        if(Utils::isTrue($withPassword) && $archive->isEncryptionMethodSupported(\ZipArchive::EM_AES_256)) {
                            $archive->setEncryptionName($entry, \ZipArchive::EM_AES_256);
                        }
                    }
                    // Finish and close
                    $status = $archive->getStatusString();
                    $close = $archive->close();
                    // Check if the archive was unable to close
                    if(Utils::isFalse($close)) {
                        $result = "Unable to close the zip archive: " . $archivename . " [" . $status . "]";
                    } else {
                        clearstatcache(false, $archivename);
                        $result = FileInfo::getInfo($archivename);
                    }
                } else {
                    $result = 'Unable to open the zip archive: ' . $archivename . '';
                }
            } else {
                $result = "Invalid save directory name [" . $dirname . "]";
            }
        } else {
            $result = "The PhpZip\ZipFile isn't loaded";
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
    private static function setName(string $name, string $extension = "zip"): string {
        $name = Utils::isEmptyString($name) ? Utils::randUnique('key') : $name;
        $ext = File::getExtension($name);
        $name = Utils::isNotEmptyString($ext) ? (strtolower($ext) == $extension ? $name : $name . '.' . $extension) : $name . '.' . $extension;
        return str_replace(array('\\', '/', ':', '*', '?', '<', '>', '|'), '_', $name);
    }

    /**
     * Arrange path
     * @param string $path
     * @return string
     */
    private static function arrangePath(string $path): string {
        return File::arrange_dir_separators($path);
    }

    /**
     * Normalize path
     * @param string $path
     * @return string
     */
    private static function normalizePath(string $path): string {
        return File::convert_dir_separators($path);
    }

    /**
     * Creates a .tgz or .tar archive
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @return \PHPWebfuse\FileInfo | string Return string on failure which contains error message else \PHPWebfuse\FileInfo
     */
    private static function createTgzOrTar(string $name, array $items, string $dirname, bool $compress = true): array|string {
        $result = "";
        // Load the ArchiveTar
        Utils::loadPlugin("ArchiveTar");
        // Resolve dirname path
        $dirname = \realpath($dirname);
        // Check if the ArchiveTar class exist
        if(class_exists("\ArchiveTar")) {
            // Make sure dirname exists or created
            if(Utils::isString($dirname) && File::makeDir($dirname)) {
                Utils::unlimitedWorkflow();
                // Filter the archive name
                $name = self::setName($name, $compress ? "tgz" : "tar");
                // Arrange dirname
                $dirname = self::arrangePath($dirname);
                // The archive absolute path
                $archivename = $dirname . DIRECTORY_SEPARATOR . $name;
                // Delete file if exist
                if(File::isFile($archivename)) {
                    File::deleteFile($archivename);
                }
                // Init ArchiveTar
                $archive = new \ArchiveTar($archivename, $compress ? true : null);
                $archive->_separator = ",";
                // Loop items
                foreach($items as $index => $item) {
                    $item = self::arrangePath($item);
                    if(File::isFile($item) || File::isDir($item)) {
                        $adddir = "";
                        $rmdir = self::arrangePath(File::getDirname($item));
                        $archive->addModify($item, $adddir, $rmdir);
                    }
                }
                // Check if archive is created
                if(File::isFile($archivename)) {
                    clearstatcache(false, $archivename);
                    $result = FileInfo::getInfo($archivename);
                } else {
                    $result = "Failed to create the empty archive [" . $archivename . "]";
                }
            } else {
                $result = "Invalid dirname [" . $dirname . "]";
            }
        } else {
            $result = "The ArchiveTar plugin isn't loaded";
        }
        return $result;
    }
}

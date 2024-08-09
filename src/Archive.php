<?php

namespace PHPWebfuse;

/**
 */
class Archive
{
    // PRIVATE VARIABLES

    /**
     * @var \PHPWebfuse\Methods
     */
    private \PHPWebfuse\Methods $methods;

    /**
     * @var \PHPWebfuse\Path
     */
    private \PHPWebfuse\Path $path;

    // PUBLIC METHODS

    /**
     * Construct new Archive instance
     */
    public function __construct()
    {
        $this->methods = new \PHPWebfuse\Methods();
        $this->path = new \PHPWebfuse\Path();
    }

    /**
     * Create a .zip archive from PclZip library
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @return \PHPWebfuse\FileInfo | string Return string on failure which contains error message else \PHPWebfuse\FileInfo
     */
    public function createPclzip(string $name, array $items, string $dirname): \PHPWebfuse\FileInfo|string
    {
        $result = "";
        // Load the PclZip
        $this->methods->loadPlugin("PclZip");
        // Check if the PclZip class exist
        if (class_exists("\PclZip")) {
            // Resolve dirname path
            $dirname = $this->methods->resolvePath($dirname);
            // Make sure dirname exists or created
            if ($this->methods->isString($dirname) && $this->methods->makeDir($dirname)) {
                $this->methods->unlimitedWorkflow();
                // Filter the archive name
                $name = $this->setName($name);
                // Arrange dirname
                $dirname = $this->arrangePath($dirname);
                // The archive absolute path
                $archivename = $dirname . DIRECTORY_SEPARATOR . $name;
                // Delete file if exist
                if ($this->methods->isFile($archivename)) {
                    $this->methods->deleteFile($archivename);
                }
                // Init PclZip
                $archive = new \PclZip($archivename);
                // Loop items
                foreach ($items as $index => $item) {
                    // Arrange the item path
                    $item = $this->arrangePath($item);
                    if ($this->methods->isFile($item) or $this->methods->isDir($item)) {
                        $rmPath = $this->arrangePath($this->methods->getDirname($item));
                        $archive->add($this->methods->resolvePath($item), PCLZIP_OPT_REMOVE_PATH, $rmPath);
                    }
                }
                // Check if archive is created
                if ($this->methods->isFile($archivename)) {
                    clearstatcache(false, $archivename);
                    $result = new \PHPWebfuse\FileInfo($archivename);
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
     * Create a .tgz archive
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @return \PHPWebfuse\FileInfo | string Return string on failure which contains error message else \PHPWebfuse\FileInfo
     */
    public function createTgz(string $name, array $items, string $dirname): \PHPWebfuse\FileInfo|string
    {
        return $this->createTgzOrTar($name, $items, $dirname, true);
    }

    /**
     * Create a .tar archive
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @return \PHPWebfuse\FileInfo | string Return string on failure which contains error message else \PHPWebfuse\FileInfo
     */
    public function createTar(string $name, array $items, string $dirname): \PHPWebfuse\FileInfo|string
    {
        return $this->createTgzOrTar($name, $items, $dirname, false);
    }

    /**
     * Create a .zip archive from the standard ZipArchive
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @param ?string $password The archive password, default to null
     * @param ?string $comment The  archive comment, default to null
     * @return \PHPWebfuse\FileInfo | string Return string on failure which contains error message else \PHPWebfuse\FileInfo
     */
    public function createZip(string $name, array $items, string $dirname, ?string $password = null, ?string $comment = null): \PHPWebfuse\FileInfo|string
    {
        $result = "";
        // Resolve dirname path
        $dirname = $this->methods->resolvePath($dirname);
        // Check if the ZipArchive class exist
        if (class_exists("\ZipArchive")) {
            // Make sure dirname exists or created
            if ($this->methods->isString($dirname) && $this->methods->makeDir($dirname)) {
                $this->methods->unlimitedWorkflow();
                // Filter the archive name
                $name = $this->setName($name, "zip");
                // Arrange dirname
                $dirname = $this->arrangePath($dirname);
                // The archive absolute path
                $archivename = $dirname . DIRECTORY_SEPARATOR . $name;
                // Delete file if exist
                if ($this->methods->isFile($archivename)) {
                    $this->methods->deleteFile($archivename);
                }
                // Init ZipArchive
                $archive = new \ZipArchive();
                // Check if the archive is opened
                if ($this->methods->isTrue($archive->open($archivename, \ZipArchive::OVERWRITE | \ZipArchive::CREATE))) {
                    // Set the comment
                    if ($this->methods->isString($comment) && $this->methods->isNotEmptyString($comment)) {
                        $archive->setArchiveComment((string) $comment);
                    }
                    // Set the password
                    $withPassword = false;
                    if ($this->methods->isString($password) && $this->methods->isNotEmptyString($password)) {
                        $archive->setPassword((string) $password);
                        $withPassword = true;
                    }
                    // The entries
                    $entries = array();
                    // Loop the items
                    foreach ($items as $index => $item) {
                        // Arrange the item path
                        $item = $this->arrangePath($item);
                        // Add the file to zip
                        if ($this->methods->isFile($item)) {
                            $entry = basename($item);
                            if ($archive->addFile($item, $entry)) {
                                $entries[] = $entry;
                            }
                        } elseif ($this->methods->isDir($item)) {
                            // Recursively iterate the directory item
                            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($item, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
                            foreach ($iterator as $list) {
                                // Arrange iterated list path
                                $listPath = $this->arrangePath($list->getPathname());
                                // Set entry for each iterated list
                                $entry = $this->normalizePath(str_replace($item, basename($item) . DIRECTORY_SEPARATOR, $listPath));
                                // When it's an empty directory or file
                                if ($list->isDir() && $archive->addEmptyDir($entry)) {
                                    $entries[] = $entry;
                                } elseif ($list->isFile() && $archive->addFile($listPath, $entry)) {
                                    $entries[] = $entry;
                                }
                            }
                        }
                    }
                    // Loop added entries
                    foreach ($entries as $entry) {
                        // Set the compression
                        if ($archive->isCompressionMethodSupported(\ZipArchive::CM_BZIP2)) {
                            $archive->setCompressionName($entry, \ZipArchive::CM_BZIP2);
                        }
                        // If password has been set on the archive, set the password for all entries
                        if ($this->methods->isTrue($withPassword) && $archive->isEncryptionMethodSupported(\ZipArchive::EM_AES_256)) {
                            $archive->setEncryptionName($entry, \ZipArchive::EM_AES_256);
                        }
                    }
                    // Finish and close
                    $status = $archive->getStatusString();
                    $close = $archive->close();
                    // Check if the archive was unable to close
                    if ($this->methods->isFalse($close)) {
                        $result = "Unable to close the zip archive: " . $archivename . " [" . $status . "]";
                    } else {
                        clearstatcache(false, $archivename);
                        $result = new \PHPWebfuse\FileInfo($archivename);
                    }
                } else {
                    $result = 'Unable to open the zip archive: ' . $archivename . '';
                }
            } else {
                $result = "Invalid save directory name [" . $dirname . "]";
            }
        } else {
            $result = "The ZipArchive plugin isn't loaded";
        }
        return $result;
    }

    // PRIVATE METHODS

    /**
     * Set the archive name
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param string $extension Add the extension at the end of the name
     * @return string Returns the new name
     */
    private function setName(string $name, string $extension = "zip"): string
    {
        if ($this->methods->isEmptyString($name)) {
            $name = $this->methods->randUnique('key');
        }
        $ext = $this->methods->getExtension($name);
        $name = $this->methods->isNotEmptyString($ext) ? (strtolower($ext) == $extension ? $name : $name . '.' . $extension) : $name . '.' . $extension;
        return str_replace(array('\\', '/', ':', '*', '?', '<', '>', '|'), '_', $name);
    }

    /**
     * Arrange path
     * @param string $path
     * @return string
     */
    private function arrangePath(string $path): string
    {
        return $this->path->arrange_dir_separators($path);
    }

    /**
     * Normalize path
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        return $this->path->convert_dir_separators($path);
    }

    /**
     * Create a .tgz or .tar archive
     * @param string $name The name of the archive. It generate a random name when it's an empty string provided
     * @param array $items The items can be a combination of files and directories
     * @param string $dirname The directory to save the archive
     * @return \PHPWebfuse\FileInfo | string Return string on failure which contains error message else \PHPWebfuse\FileInfo
     */
    private function createTgzOrTar(string $name, array $items, string $dirname, bool $compress = true): \PHPWebfuse\FileInfo|string
    {
        $result = "";
        // Load the ArchiveTar
        $this->methods->loadPlugin("ArchiveTar");
        // Resolve dirname path
        $dirname = $this->methods->resolvePath($dirname);
        // Check if the ArchiveTar class exist
        if (class_exists("\ArchiveTar")) {
            // Make sure dirname exists or created
            if ($this->methods->isString($dirname) && $this->methods->makeDir($dirname)) {
                $this->methods->unlimitedWorkflow();
                // Filter the archive name
                $name = $this->setName($name, $compress ? "tgz" : "tar");
                // Arrange dirname
                $dirname = $this->arrangePath($dirname);
                // The archive absolute path
                $archivename = $dirname . DIRECTORY_SEPARATOR . $name;
                // Delete file if exist
                if ($this->methods->isFile($archivename)) {
                    $this->methods->deleteFile($archivename);
                }
                // Init ArchiveTar
                $archive = new \ArchiveTar($archivename, $compress ? true : null);
                $archive->_separator = ",";
                // Loop items
                foreach ($items as $index => $item) {
                    $item = $this->arrangePath($item);
                    if ($this->methods->isFile($item) or $this->methods->isDir($item)) {
                        $adddir = "";
                        $rmdir = $this->arrangePath($this->methods->getDirname($item));
                        $archive->addModify($item, $adddir, $rmdir);
                    }
                }
                // Check if archive is created
                if ($this->methods->isFile($archivename)) {
                    clearstatcache(false, $archivename);
                    $result = new \PHPWebfuse\FileInfo($archivename);
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

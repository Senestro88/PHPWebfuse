<?php
namespace PHPWebFuse;
/**
 * The PHPWebFuse 'Archive' Class
 */
class Archive extends \PHPWebFuse\Methods {

// PUBLIC METHODS

	public function __construct() {}

	/**
	 * Create a .zip archive
	 *
	 * @param string $name - The name of the archive. It generate a random name when it's an empty string provided
	 * @param array $items - The items can be a combination of files and directories
	 * @param string $dirname - The directory to save the archive
	 * @return \PHPWebFuse\FileInfo | string - Return string on failure which contains error message else \PHPWebFuse\FileInfo
	 */
	public function createPclzip(string $name, array $items, string $dirname): \PHPWebFuse\FileInfo  | string {
		$result = "";
		// Load the PclZip
		parent::loadPlugin("PclZip");
		// Check if the PclZip class exist
		if (class_exists("\PclZip")) {
			// Resolve dirname path
			$dirname = parent::resolvePath($dirname);
			// Make sure dirname exists or created
			if (parent::isString($dirname) && parent::makeDir($dirname)) {
				parent::unlimitedWorkflow();
				// Filter the archive name
				$name = $this->setName($name);
				// Arrange dirname
				$dirname = $this->arrangePath($dirname);
				// The archive absolute path
				$archivename = $dirname . '' . $name;
				// Delete file if exist
				if (parent::isFile($archivename)) {parent::deleteFile($archivename);}
				// Init PclZip
				$archive = new \PclZip($archivename);
				// Loop items
				foreach ($items as $index => $item) {
					// Arrange the item path
					$item = $this->arrangePath($item);
					if (parent::isFile($item) OR parent::isDir($item)) {
						$rmPath = $this->arrangePath(parent::getDirname($item));
						$archive->add(parent::resolvePath($item), PCLZIP_OPT_REMOVE_PATH, $rmPath);
					}
				}
				// Check if archive is created
				if (parent::isFile($archivename)) {
					clearstatcache(false, $archivename);
					$result = new \PHPWebFuse\FileInfo($archivename);
				} else { $result = "Failed to create the zip archive [" . $archivename . "]";}
			} else { $result = "Invalid dirname [" . $dirname . "]";}
		} else { $result = "The PclZip plugin isn't loaded";}
		return $result;
	}

	/**
	 * Create a .tgz archive
	 *
	 * @param string $name - The name of the archive. It generate a random name when it's an empty string provided
	 * @param array $items - The items can be a combination of files and directories
	 * @param string $dirname - The directory to save the archive
	 * @return \PHPWebFuse\FileInfo | string - Return string on failure which contains error message else \PHPWebFuse\FileInfo
	 */
	public function createTgz(string $name, array $items, string $dirname): \PHPWebFuse\FileInfo  | string {
		return $this->createTgzOrTar($name, $items, $dirname, true);
	}

	/**
	 * Create a .tar archive
	 *
	 * @param string $name - The name of the archive. It generate a random name when it's an empty string provided
	 * @param array $items - The items can be a combination of files and directories
	 * @param string $dirname - The directory to save the archive
	 * @return \PHPWebFuse\FileInfo | string - Return string on failure which contains error message else \PHPWebFuse\FileInfo
	 */
	public function createTar(string $name, array $items, string $dirname): \PHPWebFuse\FileInfo  | string {
		return $this->createTgzOrTar($name, $items, $dirname, false);
	}

	/**
	 * Create a .zip archive
	 *
	 * @param string $name - The name of the archive. It generate a random name when it's an empty string provided
	 * @param array $items - The items can be a combination of files and directories
	 * @param string $dirname - The directory to save the archive
	 * @param ?string $password - The archive password
	 * @param ?string $comment - The  archive comment
	 * @return \PHPWebFuse\FileInfo | string - Return string on failure which contains error message else \PHPWebFuse\FileInfo
	 */
	public function createZip(string $name, array $items, string $dirname, ?string $password = null, ?string $comment = null): \PHPWebFuse\FileInfo  | string {
		$result = "";
		// Resolve dirname path
		$dirname = parent::resolvePath($dirname);
		// Check if the ZipArchive class exist
		if (class_exists("\ZipArchive")) {
			// Make sure dirname exists or created
			if (parent::isString($dirname) && parent::makeDir($dirname)) {
				parent::unlimitedWorkflow();
				// Filter the archive name
				$name = $this->setName($name, "zip");
				// Arrange dirname
				$dirname = $this->arrangePath($dirname);
				// The archive absolute path
				$archivename = $dirname . '' . $name;
				// Delete file if exist
				if (parent::isFile($archivename)) {parent::deleteFile($archivename);}
				// Init ZipArchive
				$archive = new \ZipArchive();
				// Check if the archive is opened
				if (parent::isTrue($archive->open($archivename, \ZipArchive::OVERWRITE | \ZipArchive::CREATE))) {
					// Set the comment
					if (parent::isString($comment) && parent::isNotEmptyString($comment)) {$archive->setArchiveComment((string) $comment);}
					// Set the password
					$withPassword = false;
					if (parent::isString($password) && parent::isNotEmptyString($password)) {
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
						if (parent::isFile($item)) {
							$entry = basename($item);
							if ($archive->addFile($item, $entry)) {
								$entries[] = $entry;
							}
						} else if (parent::isDir($item)) {
							// Recursively iterate the directory item
							$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($item, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
							foreach ($iterator as $list) {
								// Arrange iterated list pathname
								$listPath = $this->arrangePath($list->getPathname());
								// Set entry for each iterated list
								$entry = $this->normalizePath(str_replace($item, basename($item) . DIRECTORY_SEPARATOR, $listPath));
								// When it's an empty directory or file
								if ($list->isDir() && $archive->addEmptyDir($entry)) {
									$entries[] = $entry;
								} else if ($list->isFile() && $archive->addFile($listPath, $entry)) {
									$entries[] = $entry;
								}
							}
						}
					}
					// Loop added entries
					foreach ($entries as $entry) {
						// Set the compression
						if ($archive->isCompressionMethodSupported(\ZipArchive::CM_BZIP2)) {$archive->setCompressionName($entry, \ZipArchive::CM_BZIP2);}
						// If password has been set on the archive, set the password for all entries
						if (parent::isTrue($withPassword) && $archive->isEncryptionMethodSupported(\ZipArchive::EM_AES_256)) {$archive->setEncryptionName($entry, \ZipArchive::EM_AES_256);}
					}
					// Finish and close
					$status = $archive->getStatusString();
					$close = $archive->close();
					// Check if the archive was unable to close
					if (parent::isFalse($close)) {$result = "Unable to close the zip archive: " . $archivename . " [" . $status . "]";} else {
						clearstatcache(false, $archivename);
						$result = new \PHPWebFuse\FileInfo($archivename);
					}
				} else { $result = 'Unable to open the zip archive: ' . $archivename . '';}
			} else { $result = "Invalid save directory name [" . $dirname . "]";}
		} else { $result = "The ZipArchive plugin isn't loaded";}
		return $result;
	}

	// PRIVATE METHODS

	/**
	 * Set the archive name
	 *
	 * @param string $name - The name of the archive. It generate a random name when it's an empty string provided
	 * @param string $extension - Add the extension at the end of the name
	 * @return string - Returns the new name
	 */
	private function setName(string $name, string $extension = "zip"): string {
		if (parent::isEmptyString($name)) {$name = parent::randUnique('key');}
		$ext = parent::getExtension($name);
		$name = $this->isNotEmptyString($ext) ? (strtolower($ext) == $extension ? $name : $name . '.' . $extension) : $name . '.' . $extension;
		return str_replace(array('\\', '/', ':', '*', '?', '<', '>', '|'), '_', $name);
	}

	private function arrangePath(string $pathname): string {
		$resolved = parent::resolvePath($pathname);
		if (parent::isString($resolved)) {
			$arranged = parent::ARRANGE_DIR_SEPARATOR($resolved);
			return parent::isDir($resolved) ? parent::INSERT_DIR_SEPARATOR($arranged) : $arranged;
		}
		return $pathname;
	}

	private function normalizePath(string $pathname): string {
		return str_replace(DIRECTORY_SEPARATOR, "/", $pathname);
	}

	/**
	 * Create a .tgz or .tar archive
	 *
	 * @param string $name - The name of the archive. It generate a random name when it's an empty string provided
	 * @param array $items - The items can be a combination of files and directories
	 * @param string $dirname - The directory to save the archive
	 * @return \PHPWebFuse\FileInfo | string - Return string on failure which contains error message else \PHPWebFuse\FileInfo
	 */
	private function createTgzOrTar(string $name, array $items, string $dirname, bool $compress = true): \PHPWebFuse\FileInfo  | string {
		$result = "";
		// Load the ArchiveTar
		parent::loadPlugin("ArchiveTar");
		// Resolve dirname path
		$dirname = parent::resolvePath($dirname);
		// Check if the ArchiveTar class exist
		if (class_exists("\ArchiveTar")) {
			// Make sure dirname exists or created
			if (parent::isString($dirname) && parent::makeDir($dirname)) {
				parent::unlimitedWorkflow();
				// Filter the archive name
				$name = $this->setName($name, $compress ? "tgz" : "tar");
				// Arrange dirname
				$dirname = $this->arrangePath($dirname);
				// The archive absolute path
				$archivename = $dirname . '' . $name;
				// Delete file if exist
				if (parent::isFile($archivename)) {parent::deleteFile($archivename);}
				// Init ArchiveTar
				$archive = new \ArchiveTar($archivename, $compress ? true : null);
				$archive->_separator = ",";
				// Loop items
				foreach ($items as $index => $item) {
					$item = $this->arrangePath($item);
					if (parent::isFile($item) OR parent::isDir($item)) {
						$adddir = "";
						$rmdir = $this->arrangePath(parent::getDirname($item));
						$archive->addModify($item, $adddir, $rmdir);
					}
				}
				// Check if archive is created
				if (parent::isFile($archivename)) {
					clearstatcache(false, $archivename);
					$result = new \PHPWebFuse\FileInfo($archivename);
				} else { $result = "Failed to create the empty archive [" . $archivename . "]";}
			} else { $result = "Invalid dirname [" . $dirname . "]";}
		} else { $result = "The ArchiveTar plugin isn't loaded";}
		return $result;
	}
}
<?php
namespace PHPWebFuse;
/**
 *
 */
class FileInfo extends \PHPWebFuse\Methods {
	public $realPath;
	public $basename;
	public $dirname;
	public $sizes;

	/**
	 * File Info
	 * 
	 * @param string $realPath 
	 */
	public function __construct(string $realPath) {
		clearstatcache(false, $realPath);
		$this->realPath = $realPath;
		$this->basename = basename($realPath);
		$this->dirname = parent::getDirname($realPath);
		$size = parent::getFIlesizeInBytes($realPath);
		$this->sizes = array(
			'bytes' => $size,
			'kilobytes' => round($size / 1024, 1),
			'megabytes' => round(($size / 1024) / 1024, 1),
			'gigabytes' => round((($size / 1024) / 1024) / 1024, 1),
		);
	}
}
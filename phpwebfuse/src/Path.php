<?php
namespace PHPWebFuse;
/**
 *
 */
class Path {
	// PUBLIC METHODS
	
	public function __construct() {}

	/**
	 * Convert a directory separator to the PHP OS Directory separator
	 * 
	 * @param string $pathname
	 * @return string
	 * */
	public function CONVERT_DIR_SEPARATOR(string $pathname): string {return str_ireplace(array("\\", "//"), DIRECTORY_SEPARATOR, $pathname);}

	/**
	 * Delete directory separator from the right after converting
	 * 
	 * @param string $pathname
	 * @return string
	 * */
	public function RIGHT_DEL_DIR_SEPARATOR(string $pathname): string {return rtrim(self::CONVERT_DIR_SEPARATOR($pathname), DIRECTORY_SEPARATOR);}

	/**
	 * Delete directory separator from the left after converting
	 * 
	 * @param string $pathname
	 * @return string
	 * */
	public function LEFT_DEL_DIR_SEPARATOR(string $pathname): string {return ltrim(self::CONVERT_DIR_SEPARATOR($pathname), DIRECTORY_SEPARATOR);}

	/**
	 * Arrange directory separator from multiple separators like "//" or "\\"
	 * 
	 * @param string $pathname
	 * @param bool $closeEdges - Defaults to false
	 * @return string
	 * */
	public function ARRANGE_DIR_SEPARATOR(string $pathname, bool $closeEdges = false): string {
		$pathname = self::CONVERT_DIR_SEPARATOR($pathname);
		$separator = DIRECTORY_SEPARATOR;
		$explodedPath = array_filter(explode($separator, $pathname));
		return ($closeEdges ? $separator : "") . implode($separator, $explodedPath) . ($closeEdges ? $separator : "");
	}
	
	/**
	 * Insert directory separator to the begining or end
	 * 
	 * @param string $pathname
	 * @param bool $toEnd - Defaults to true
	 * @return string
	 * */
	public function INSERT_DIR_SEPARATOR(string $pathname, bool $toEnd = true): string {
		$pathname = self::CONVERT_DIR_SEPARATOR($pathname);
		$separator = DIRECTORY_SEPARATOR;
		return ($toEnd === false ? $separator : "") . $pathname . ($toEnd === true ? $separator : "");
	}
}
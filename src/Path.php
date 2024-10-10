<?php

namespace PHPWebfuse;

/**
 * @author Senestro
 */
class Path {
    // PRIVATE VARIABLE
    // PUBLIC VARIABLES
    // PUBLIC METHODS

    /**
     * Prevent the constructor from being initialized
     */
    private function __construct() {
    }

    /**
     * Convert a directory separator to the PHP OS directory separator
     * @param string $path
     * @param string|null $separator Use this to overwrite the default build in DIRECTORY_SEPARATOR constant
     * @return string
     */
    public static function convert_dir_separators(string $path, ?string $separator = null): string {
        $separator = \is_string($separator) && !empty($separator) ? $separator : DIRECTORY_SEPARATOR;
        return str_ireplace(array("\\", "/"), $separator, $path);
    }

    /**
     * Delete directory separator from the right side after converting it
     * @param string $path
     * @param string|null $separator Use this to overwrite the default build in DIRECTORY_SEPARATOR constant
     * @return string
     * */
    public static function right_delete_dir_separator(string $path, ?string $separator = null): string {
        $separator = \is_string($separator) && !empty($separator) ? $separator : DIRECTORY_SEPARATOR;
        return rtrim(self::convert_dir_separators($path, $separator), $separator);
    }

    /**
     * Delete directory separator from the left side after converting it
     * @param string $path
     * @param string|null $separator Use this to overwrite the default build in DIRECTORY_SEPARATOR constant
     * @return string
     * */
    public static function left_delete_dir_separator(string $path, ?string $separator = null): string {
        $separator = \is_string($separator) && !empty($separator) ? $separator : DIRECTORY_SEPARATOR;
        return ltrim(self::convert_dir_separators($path, $separator), $separator);
    }


    /**
     * Normalizes the directory separators in the given path.
     *
     * This function ensures that the directory separators are consistent throughout the given path.
     * It can optionally add a separator at the start and end of the path, depending on the 
     * value of the `$closeEdges` parameter.
     *
     * @param string $path The directory path to normalize.
     * @param bool $closeEdges If true, adds directory separators at the start and end of the path.
     *                         Default is false.
     * @param string|null $separator The separator to use for the path (defaults to DIRECTORY_SEPARATOR).
     *                               If null or empty, the system's DIRECTORY_SEPARATOR is used.
     * @return string The normalized path with consistent directory separators.
     */
    public static function arrange_dir_separators(string $path, bool $closeEdges = false, ?string $separator = null): string {
        // Use the provided separator, or default to the system's DIRECTORY_SEPARATOR if not provided.
        $separator = \is_string($separator) && !empty($separator) ? $separator : DIRECTORY_SEPARATOR;
        // Convert the path to use consistent separators and split the path into components.
        $explodedPath = array_filter(explode($separator, self::convert_dir_separators($path, $separator)));
        // Return the path, optionally closing the edges with the separator.
        return ($closeEdges ? $separator : "") . implode($separator, $explodedPath) . ($closeEdges ? $separator : "");
    }

    /**
     * Normalizes the directory separators in the given path, with special handling for Windows paths.
     *
     * This function normalizes the directory separators in a path and can optionally add a separator
     * at the start and end of the path. If the operating system is Windows and the path starts with a 
     * drive letter (e.g., "C:\"), the edges are not closed.
     *
     * @param string $path The directory path to normalize.
     * @param bool $closeEdges If true, adds directory separators at the start and end of the path, unless
     *                         it's a Windows path with a drive letter.
     *                         Default is false.
     * @param string|null $separator The separator to use for the path (defaults to DIRECTORY_SEPARATOR).
     *                               If null or empty, the system's DIRECTORY_SEPARATOR is used.
     * @return string The normalized path with consistent directory separators.
     */
    public static function arrange_dir_separators_v2(string $path, bool $closeEdges = false, ?string $separator = null): string {
        // Use the provided separator, or default to the system's DIRECTORY_SEPARATOR if not provided.
        $separator = \is_string($separator) && !empty($separator) ? $separator : DIRECTORY_SEPARATOR;
        // Convert the path to use consistent separators and split the path into components.
        $explodedPath = array_filter(explode($separator, self::convert_dir_separators($path, $separator)));
        // Check if the current operating system is Windows.
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        // Check if the path starts with a drive letter (e.g., "C:\").
        $startWithDriveLetter = preg_match('/^[A-Za-z]:[\/\\\]/', $path);
        // Return the path, conditionally closing the edges unless it's a Windows path with a drive letter.
        return ($closeEdges ? (!$isWindows || !$startWithDriveLetter ? $separator : "") : "") . implode($separator, $explodedPath) . ($closeEdges ? $separator : "");
    }


    /**
     * Insert directory separator to the beginning or end of the directory path
     * @param string $path
     * @param bool $toEnd - Defaults to true
     * @param string|null $separator Use this to overwrite the default build in DIRECTORY_SEPARATOR constant
     * @return string
     * */
    public static function insert_dir_separator(string $path, bool $toEnd = true, ?string $separator = null): string {
        $separator = \is_string($separator) && !empty($separator) ? $separator : DIRECTORY_SEPARATOR;
        return ($toEnd === false ? $separator : "") . self::convert_dir_separators($path, $separator) . ($toEnd === true ? $separator : "");
    }

    /**
     * Merge paths together
     * @param string $a
     * @param string $b
     * @param string|null $separator Use this to overwrite the default build in DIRECTORY_SEPARATOR constant
     * @return string
     */
    public static function merge(string $a, string $b, ?string $separator = null): string {
        $separator = \is_string($separator) && !empty($separator) ? $separator : DIRECTORY_SEPARATOR;
        $a = self::right_delete_dir_separator($a, $separator);
        $b = self::left_delete_dir_separator($b, $separator);
        return $a . $separator . $b;
    }

    // PRIVATE METHODS
}

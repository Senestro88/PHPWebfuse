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
     * Arrange directory separator by replacing multiple separators joined together (\\ or //) to single separator
     * @param string $path
     * @param bool $closeEdges Close the edged with a separator. Defaults to false
     * @param string|null $separator Use this to overwrite the default build in DIRECTORY_SEPARATOR constant
     * @return string
     * */
    public static function arrange_dir_separators(string $path, bool $closeEdges = false, ?string $separator = null): string {
        $separator = \is_string($separator) && !empty($separator) ? $separator : DIRECTORY_SEPARATOR;
        $explodedPath = array_filter(explode($separator, self::convert_dir_separators($path, $separator)));
        return ($closeEdges ? $separator : "") . implode($separator, $explodedPath) . ($closeEdges ? $separator : "");
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

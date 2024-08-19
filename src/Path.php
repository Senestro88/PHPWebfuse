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
     * @param string $dir
     * @return string
     * */
    public static function convert_dir_separators(string $dir): string {
        return str_ireplace(array("\\", "/"), DIRECTORY_SEPARATOR, $dir);
    }

    /**
     * Delete directory separator from the right side after converting it
     * @param string $dir
     * @return string
     * */
    public static function right_delete_dir_separator(string $dir): string {
        return rtrim(self::convert_dir_separators($dir), DIRECTORY_SEPARATOR);
    }

    /**
     * Delete directory separator from the left side after converting it
     * @param string $dir
     * @return string
     * */
    public static function left_delete_dir_separator(string $dir): string {
        return ltrim(self::convert_dir_separators($dir), DIRECTORY_SEPARATOR);
    }

    /**
     * Arrange directory separator by replacing multiple separators joined together (\\ or //) to single separator
     * @param string $dir
     * @param bool $closeEdges Close the edged with a separator. Defaults to false
     * @return string
     * */
    public static function arrange_dir_separators(string $dir, bool $closeEdges = false): string {
        $separator = DIRECTORY_SEPARATOR;
        $explodedPath = array_filter(explode($separator, self::convert_dir_separators($dir)));
        return ($closeEdges ? $separator : "") . implode($separator, $explodedPath) . ($closeEdges ? $separator : "");
    }

    /**
     * Insert directory separator to the beginning or end of the directory path
     * @param string $dir
     * @param bool $toEnd - Defaults to true
     * @return string
     * */
    public static function insert_dir_separator(string $dir, bool $toEnd = true): string {
        $separator = DIRECTORY_SEPARATOR;
        return ($toEnd === false ? $separator : "") . self::convert_dir_separators($dir) . ($toEnd === true ? $separator : "");
    }

    /**
     * Merge paths together
     * @param string $a
     * @param string $b
     * @return string
     */
    public static function mergePath(string $a, string $b): string {
        $a = self::right_delete_dir_separator($a);
        $b = self::left_delete_dir_separator($b);
        return $a . DIRECTORY_SEPARATOR . $b;
    }

    // PRIVATE METHODS
}

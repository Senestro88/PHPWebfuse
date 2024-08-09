<?php

namespace PHPWebfuse;

/**
 */
class Path
{
    // PUBLIC METHODS

    /**
     * Construct new Path instance
     */
    public function __construct()
    {

    }

    /**
     * Convert a directory separator to the PHP OS Directory separator
     * @param string $path
     * @return string
     * */
    public function convert_dir_separators(string $path): string
    {
        return str_ireplace(array("\\", "//"), DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Delete directory separator from the right after converting
     * @param string $path
     * @return string
     * */
    public function right_delete_dir_separator(string $path): string
    {
        return rtrim($this->convert_dir_separators($path), DIRECTORY_SEPARATOR);
    }

    /**
     * Delete directory separator from the left after converting
     * @param string $path
     * @return string
     * */
    public function left_delete_dir_separator(string $path): string
    {
        return ltrim($this->convert_dir_separators($path), DIRECTORY_SEPARATOR);
    }

    /**
     * Arrange directory separator from multiple separators like "//" or "\\"
     * @param string $path
     * @param bool $closeEdges - Defaults to false
     * @return string
     * */
    public function arrange_dir_separators(string $path, bool $closeEdges = false): string
    {
        $separator = DIRECTORY_SEPARATOR;
        $explodedPath = array_filter(explode($separator, $this->convert_dir_separators($path)));
        return ($closeEdges ? $separator : "") . implode($separator, $explodedPath) . ($closeEdges ? $separator : "");
    }

    /**
     * Insert directory separator to the beginning or end
     *
     * @param string $path
     * @param bool $toEnd - Defaults to true
     * @return string
     * */
    public function insert_dir_separator(string $path, bool $toEnd = true): string
    {
        $separator = DIRECTORY_SEPARATOR;
        return ($toEnd === false ? $separator : "") . $this->convert_dir_separators($path) . ($toEnd === true ? $separator : "");
    }
}

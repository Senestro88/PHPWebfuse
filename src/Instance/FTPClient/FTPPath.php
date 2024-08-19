<?php
namespace PHPWebfuse\Instance\FTPClient;

/**
 * @author Senestro
 */
class FTPPath {
    // PRIVATE VARIABLE
    
    /**
     * The remote path separator
     * @var string
     */
    private string $RPS = "/";

    // PUBLIC VARIABLES
    
    // PUBLIC FUNCTIONS

    /**
     * Construct new FTPPath instance
     */
    public function __construct(string $RPS = "/")
    {
        $this->RPS = $RPS;
    }    
    
     /**
     * Convert a directory separator to the remote PHP OS directory separator
     * @param string $path
     * @return string
     * */
    public function convert_dir_separators(string $path): string
    {
        return str_ireplace(array("\\", "/"), $this->RPS, $path);
    }

    /**
     * Delete directory separator from the right side after converting it
     * @param string $path
     * @return string
     * */
    public function right_delete_dir_separator(string $path): string
    {
        return rtrim($this->convert_dir_separators($path), $this->RPS);
    }

    
    /**
     * Delete directory separator from the left side after converting it
     * @param string $path
     * @return string
     * */
    public function left_delete_dir_separator(string $path): string
    {
        return ltrim($this->convert_dir_separators($path), $this->RPS);
    }

    /**
     * Arrange directory separator by replacing multiple separators joined together (\\ or //) to single separator
     * @param string $path
     * @param bool $closeEdges Close the edged with a separator. Defaults to false
     * @return string
     * */
    public function arrange_dir_separators(string $path, bool $closeEdges = false): string
    {
        $separator = $this->RPS;
        $explodedPath = array_filter(explode($separator, $this->convert_dir_separators($path)));
        return ($closeEdges ? $separator : "") . implode($separator, $explodedPath) . ($closeEdges ? $separator : "");
    }

    /**
     * Insert directory separator to the beginning or end of the directory path
     * @param string $path
     * @param bool $toEnd - Defaults to true
     * @return string
     * */
    public function insert_dir_separator(string $path, bool $toEnd = true): string
    {
        $separator = $this->RPS;
        return ($toEnd === false ? $separator : "") . $this->convert_dir_separators($path) . ($toEnd === true ? $separator : "");
    }
    
    // PRIVATE FUNCTIONS
}

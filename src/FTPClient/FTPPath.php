<?php
namespace PHPWebfuse\FTPClient;

class FTPPath {
    // PRIVATE VARIABLE
    
    /**
     * The remote path separator
     * @var string
     */
    private string $RPS = "/";

    // PUBLIC VARIABLES
    
    // PUBLIC FUNCTIONS

    public function __construct(string $RPS = "/")
    {
        $this->RPS = $RPS;
    }    
    
    public function convert_dir_separators(string $path): string
    {
        return str_ireplace(array("\\", "//"), $this->RPS, $path);
    }

    public function right_delete_dir_separator(string $path): string
    {
        return rtrim($this->convert_dir_separators($path), $this->RPS);
    }

    public function left_delete_dir_separator(string $path): string
    {
        return ltrim($this->convert_dir_separators($path), $this->RPS);
    }

    public function arrange_dir_separators(string $path, bool $closeEdges = false): string
    {
        $separator = $this->RPS;
        $explodedPath = array_filter(explode($separator, $this->convert_dir_separators($path)));
        return ($closeEdges ? $separator : "") . implode($separator, $explodedPath) . ($closeEdges ? $separator : "");
    }

    public function insert_dir_separator(string $path, bool $toEnd = true): string
    {
        $separator = $this->RPS;
        return ($toEnd === false ? $separator : "") . $this->convert_dir_separators($path) . ($toEnd === true ? $separator : "");
    }
    
    // PRIVATE FUNCTIONS
}

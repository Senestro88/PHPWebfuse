<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace PHPWebfuse;

/**
 */
class PharExtractor
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

    // PUBLIC VARIABLES

    /**
     * @var string The Phar archive filename
     */
    public string $filename = "";

    /**
     * @var string Where to extract the Phar archive
     */
    public string $directory = "";

    /**
     * The error message
     * @var array
     */
    public array $errorMessages = array();

    /**
     * Construct new PharExtractor instance
     * @throws \Exception If phar.readonly is set to true in php.ini
     */
    public function __construct()
    {
        $this->methods = new \PHPWebfuse\Methods();
        $this->path = new \PHPWebfuse\Path();
        $pharReadonly = ini_get('phar.readonly');
        if (is_string($pharReadonly) && (strtolower($pharReadonly) == "on" || $pharReadonly == "1" || $pharReadonly == 1)) {
            throw new \Exception('Extracting of Phar archives is disabled in php.ini. Please make sure that "phar.readonly" is set to "off".');
        }
    }

    /**
     *
     * @param string $filename The Phar archive filename
     * @param string $directory Where to extract the Phar archive
     * @return bool
     */
    public function extract(string $filename, string $directory): bool
    {
        $this->filename = $filename;
        $this->directory = $directory;
        if(is_file($this->filename) && is_readable($this->filename)) {
            $this->filename = $this->realPath($this->filename);
            if($this->isValidPharExtension($this->filename)) {
                try {
                    $phar = new \Phar($this->filename, 0);
                    if($this->methods->makeDir($this->directory)) {
                        $this->directory = $this->path->insert_dir_separator($this->path->arrange_dir_separators($this->realPath($this->directory)));
                        $this->decompressPhar($phar);
                        if($phar->extractTo($this->directory, null, true)) {
                            return true;
                        } else {
                            $this->errorMessages[] = "Failed to extract the phar achive ".$this->filename." to ".$this->directory."";
                        }
                    } else {
                        $this->errorMessages[] = "Unable to create the extraction directory or check if it exists.";
                    }
                } catch (\Throwable $e) {
                    $this->errorMessages[] = $e->getMessage();
                }
            } else {
                $this->errorMessages[] = "The filename must be a valid phar archive.";
            }
        } else {
            $this->errorMessages[] = "The filename must be a valid and readable.";
        }
        return false;
    }

    // PRIVATE METHODS

    /**
     * Resolve a path
     * @param string $path
     * @return string|false
     */
    private function realPath(string $path): string|false
    {
        return @realpath($path);
    }

    /**
     * Check wether if the phar archive ends with .phar
     * @param string $filename The Phar archive filename
     * @return bool
     */
    private function isValidPharExtension(string $filename): bool
    {
        return $this->methods->endsWith(".phar", strtolower($filename));
    }


    /**
     * Decompress the Phar
     * @param \Phar $phar
     * @return void
     */
    private function decompressPhar(\Phar $phar): void
    {
        if($phar->isCompressed()) {
            $phar->decompressFiles();
        }
    }
}

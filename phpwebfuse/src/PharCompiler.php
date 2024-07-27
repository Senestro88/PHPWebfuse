<?php

namespace PHPWebfuse;

/**
  The PHPWebfuse 'PharCompiler' class
 */
class PharCompiler
{
    // PRIVATE VARIABLES

    /**
     * @var \PHPWebfuse\Path The default PHPWebfuse path class
     */
    private $path = null;

    /**
     * @var string The project root path
     */
    private $rootPath = "";

    /**
     *
     * @var array The phar files relative to the project root path
     */
    private $files = array();

    /**
     *
     * @var array The list of index files
     */
    private $index = array();

    /**
     * @var string The output filename to save the Phar Archive
     */
    private $outputPhar = "";

    // PUBLIC METHODS

    /**
     * @param string $rootPath The project root path
     * @throws \Exception If phar.readonly is to true in php.ini or project directory doesn't exists
     */
    public function __construct(string $rootPath)
    {
        $this->path = new \PHPWebfuse\Path();
        $pharReadonly = ini_get('phar.readonly');
        if (is_string($pharReadonly) && (strtolower($pharReadonly) == "on" || $pharReadonly == "1" || $pharReadonly == 1)) {
            throw new \Exception('Creation of Phar archives is disabled in php.ini. Please make sure that "phar.readonly" is set to "off".');
        } else {
            $realPath = $this->realPath($rootPath);
            if (!is_string($realPath)) {
                throw new \Exception('Before the creation of Phar archive, the project root path must exists.');
            } else {
                $this->rootPath = $this->path->arrange_dir_separators($realPath);
            }
        }
    }

    /**
     *
     * @param string $file The name of the file relative to the project root path
     * @return void
     */
    public function addFile(string $file): void
    {
        $file = $this->path->arrange_dir_separators($file);
        $realPath = $this->realPath($this->rootPath . DIRECTORY_SEPARATOR . $file);
        if (is_string($realPath)) {
            $this->files[$file] = $realPath;
        }
    }

    /**
     * Gets list of all added files.
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Get the project root path
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     *
     * @param string $directory The name of the directory relative to the project root path
     * @param array $exclude List of file name patterns to exclude (optional)
     * @return void
     */
    public function addDirectory(string $directory, array $exclude = array()): void
    {
        $realPath = $this->realPath($this->rootPath . DIRECTORY_SEPARATOR . $this->path->arrange_dir_separators($directory));
        if (is_string($realPath) && is_dir($realPath)) {
            $iterator = new \RecursiveDirectoryIterator($realPath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::CURRENT_AS_SELF);
            if (!empty($exclude)) {
                $iterator = new \RecursiveCallbackFilterIterator($iterator, function (\RecursiveDirectoryIterator $current) use ($exclude) {
                    if ($current->isDir()) {
                        return true;
                    } return $this->filter($current->getSubPathname(), $exclude);
                });
            }
            $iterator = new \RecursiveIteratorIterator($iterator);
            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                $this->addFile(substr($file->getPathName(), strlen($this->rootPath) + 1));
            }
        }
    }

    /**
     * Gets list of defined index files.
     * @return array
     */
    public function getIndexes(): array
    {
        return $this->index;
    }

    /**
     * Adds an index file
     * @param string $file The name of the file relative to the project root
     * @param string $env The SAPI type, default is 'cli''
     * @return void
     */
    public function setIndex(string $file, string $env = 'cli'): void
    {
        $env = strtolower($env);
        if (!in_array($env, array('cli', 'web'))) {
            throw new \InvalidArgumentException(sprintf('Index file type "%s" is invalid, must be one of: cli, web', $env));
        } else {
            $file = $this->path->arrange_dir_separators($file);
            $realPath = $this->realPath($this->rootPath . DIRECTORY_SEPARATOR . $file);
            if (is_string($realPath)) {
                $this->index[$env] = array($file, $realPath);
            }
        }
    }

    /**
     * Add index file from a relative path
     * @param string $file The name of the file relative to the $directory
     * @param string $directory The name of the directory relative to the project root
     * @param string $env The SAPI type, default is 'cli''
     * @return void
     */
    public function setIndexFromDir(string $file, string $directory, string $env = 'cli'): void
    {
        $this->setIndex($directory . DIRECTORY_SEPARATOR . $file, $env);
    }

    /**
     * Gets list of all SAPIs in the index files.
     * @return array
     */
    public function getSapis(): array
    {
        return array_keys($this->index);
    }

    /**
     * Returns whether the compiled program will support the given SAPI type.
     * @param string $sapi The SAPI type
     * @return bool
     */
    public function supportsSapi(string $sapi): bool
    {
        return in_array($sapi, $this->getSapis());
    }

    /**
     * Compiles all files into a single PHAR file.
     * @param string $outputPhar The full name of the file to create
     * @return void
     * @throws \Exception if no index files are defined
     */
    public function compile(string $outputPhar): void
    {
        $this->outputPhar = $outputPhar;
        if (empty($this->index) || (!isset($this->index['cli']) || !isset($this->index['web']))) {
            throw new \Exception('Cannot compile when no index files of \'cli\' and \'web\' version defined.');
        } else {
            @unlink($this->outputPhar);
            $phar = new \Phar($this->outputPhar, 0, basename($this->outputPhar));
            $phar->setSignatureAlgorithm(\Phar::SHA1);
            $phar->startBuffering();
            // Add files
            foreach ($this->files as $relativePath => $realPath) {
                @$phar->addFile($realPath, $relativePath);
            }
            // Set indexes
            foreach ($this->index as $env => $data) {
                list($relativePath, $realPath) = $data;
                @$phar->addFile($realPath, $relativePath);
            }
            // $this->setPharStub($phar);
            $this->setDefaultPharStub($phar);
            $this->compressPhar($phar);
            $phar->stopBuffering();
            unset($phar);
        }
    }

    // PRIVATE METHODS

    private function realPath(string $path): string|false
    {
        return @realpath($path);
    }

    /**
     * Filters the given path.
     * @param string $path
     * @param array $patterns
     * @return bool
     */
    private function filter(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->match($path, $pattern)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Matches the given path
     * @param string $path
     * @param string $pattern
     * @return bool
     */
    private function match(string $path, string $pattern): bool
    {
        $inverted = false;
        if ($pattern[0] == '!') {
            $pattern = substr($pattern, 1);
            $inverted = true;
        }
        return fnmatch($pattern, $path) == ($inverted ? false : true);
    }

    /**
     * Set the default stub
     * @return string
     */
    private function setDefaultPharStub(\Phar $phar): void
    {
        $name = basename($this->outputPhar);
        // Customize the stub to add the shebang and avoid displaying it in the Phar's content list
        // $stub = array('#!/usr/bin/env php', '<?php');
        $stub = array('<?php');
        $stub[] = "Phar::mapPhar('$name');";
        $stub[] = "if (PHP_SAPI == 'cli') {";
        if (isset($this->index['cli'])) {
            $stub[] = " require 'phar://" . $name . "/" . $this->index['cli'][0] . "';";
        } else {
            $stub[] = " exit('This program can not be invoked via the CLI version of PHP, use the Web interface instead.'.PHP_EOL);";
        }
        $stub[] = '} else {';
        if (isset($this->index['web'])) {
            $stub[] = " require 'phar://" . $name . "/" . $this->index['web'][0] . "';";
        } else {
            $stub[] = " exit('This program can not be invoked via the Web interface, use the CLI version of PHP instead.'.PHP_EOL);";
        }
        $stub[] = '}';
        $stub[] = '__HALT_COMPILER();';
        $phar->setStub(implode("\n", $stub));
    }
    
    /**
     * Set the Phar stub
     * @param \Phar $phar
     * @return void
     */
    private function setPharStub(\Phar $phar): void
    {
        // Customize the stub to add the shebang and avoid displaying it in the Phar's content list
        // $stub = array('#!/usr/bin/env php');
        $stub = array();
        $stub[] = $phar->createDefaultStub(isset($this->index['cli']) ? $this->index['cli'][0] : null, isset($this->index['web']) ? $this->index['web'][0] : null);
        $phar->setStub(implode("\n", $stub));
    }

    /**
     * Compress the Phar
     * @param \Phar $phar
     * @return void
     */
    private function compressPhar(\Phar $phar): void
    {
        if ($phar->canCompress(\Phar::GZ)) {
            $phar->compressFiles(\Phar::GZ);
        }
    }

}

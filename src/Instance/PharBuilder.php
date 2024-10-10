<?php

namespace PHPWebfuse\Instance;

use \PHPWebfuse\Utils;
use \PHPWebfuse\File;
use \PHPWebfuse\Path;

/**
 * @author Senestro
 */
class PharBuilder {
    // PRIVATE VARIABLES

    /**
     * @var string The root path
     */
    private string $rootPath = "";

    /**
     * @var array The phar files relative to the root path
     */
    private array $files = array();

    /**
     * @var array The list of stub interface files relative to the root path
     */
    private array $interfaces = array();

    /**
     * @var string The index file to override stub interfaces files
     */
    private string $index = "";

    /**
     * @var string The output filename to save the Phar Archive
     */
    private string $output = "";

    /**
     * @var string The default shebang
     */
    private string $shebang = "#!/usr/bin/env php";

    // PUBLIC METHODS

    /**
     * Construct new PharBuilder instance
     * @param string $rootPath The root path
     * @throws \Exception If phar.readonly is set to true in php.ini or project directory doesn't exists
     */
    public function __construct(string $rootPath) {
        $pharReadonly = ini_get('phar.readonly');
        if (is_string($pharReadonly) && (strtolower($pharReadonly) == "on" || $pharReadonly == "1" || $pharReadonly == 1)) {
            throw new \Exception('Creation of Phar archives is disabled in php.ini. Please make sure that "phar.readonly" is set to "off".');
        } else {
            $realPath = $this->realPath($rootPath);
            if (is_string($realPath) && is_dir($rootPath) && is_readable($rootPath)) {
                $this->rootPath = Path::arrange_dir_separators_v2($realPath);
            } else {
                throw new \Exception('To creation a Phar archive, the root path must exists and readable.');
            }
        }
    }

    /**
     * Add a file to the phar archive
     * @param string $file The name of the file relative to the root path
     * @return void
     */
    public function addFile(string $file): void {
        $entry = Path::arrange_dir_separators_v2($file);
        $realPath = $this->realPath($this->rootPath . DIRECTORY_SEPARATOR . $entry);
        if (is_string($realPath) && is_file($realPath) && is_readable($realPath)) {
            $this->files[$entry] = $realPath;
        }
    }

    /**
     * Gets list of all added files.
     * @return array
     */
    public function getFiles(): array {
        return $this->files;
    }

    /**
     * Get the root path
     * @return string
     */
    public function getRootPath(): string {
        return $this->rootPath;
    }

    /**
     * Add a directory
     * @param string $directory The name of the directory relative to the root path
     * @param array $exclude List of file name patterns to exclude (optional)
     * @return void
     */
    public function addDirectory(string $directory, string $excludePattern = ""): void {
        $realPath = $this->realPath($this->rootPath . DIRECTORY_SEPARATOR . Path::arrange_dir_separators_v2($directory));
        if (is_string($realPath) && is_dir($realPath)) {
            $iterator = new \RecursiveDirectoryIterator($realPath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::CURRENT_AS_SELF);
            if (!empty($excludePattern)) {
                $iterator = new \RecursiveCallbackFilterIterator($iterator, function (\RecursiveDirectoryIterator $current) use ($excludePattern) {
                    // TRUE to accept the current item to the iterator, FALSE otherwise
                    if ($current->isDir() && !$current->isDot()) {
                        return true;
                    } else {
                        return !$this->matchFilename($current->getRealPath(), $excludePattern);
                    }
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
     * Gets list of defined interface files.
     * @return array
     */
    public function getInterfaces(): array {
        return $this->interfaces;
    }

    /**
     * Set the interface
     * @param string $file The name of the file relative to the root path
     * @param string $sapi The SAPI type, default is 'cli''
     * @return void
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function setInterface(string $file, string $sapi = 'cli'): void {
        $entry = Path::arrange_dir_separators_v2($file);
        $realPath = $this->realPath($this->rootPath . DIRECTORY_SEPARATOR . $entry);
        if (is_string($realPath) && is_file($realPath) && is_readable($realPath)) {
            $sapi = strtolower($sapi);
            if (!in_array($sapi, array('cli', 'web'))) {
                throw new \InvalidArgumentException(sprintf('The interface file specified by sapi "%s" is invalid, must be either cli or web', $sapi));
            } else {
                if (in_array($entry, array_keys($this->files))) {
                    $this->interfaces[$sapi] = $entry;
                } else {
                    throw  new \Exception('To set the Phar interface specified by file "' . $file . '" is invalid, the file file must be added using addFile()');
                }
            }
        }
    }

    /**
     * Override the interfaces and set this as the default stub
     * @param string $file The name of the file relative to the root path
     * @return void
     */
    public function overrideInterfaces(string $file): void {
        $entry = Path::arrange_dir_separators_v2($file);
        $realPath = $this->realPath($this->rootPath . DIRECTORY_SEPARATOR . $entry);
        if (is_string($realPath) && is_file($realPath) && is_readable($realPath)) {
            if (in_array($entry, array_keys($this->files))) {
                $this->index = $realPath;
                $this->interfaces = array();
                unset($this->files[$entry]);
            } else {
                throw  new \Exception('To override the Phar interfaces specified by file "' . $file . '" is invalid, the file file must be added using addFile()');
            }
        }
    }

    /**
     * Gets list of all SAPIs in the interfaces
     * @return array
     */
    public function getSapisForInterface(): array {
        return array_keys($this->interfaces);
    }

    /**
     * Returns whether the compiled program will support the given SAPI type.
     * @param string $sapi The SAPI type
     * @return bool
     */
    public function supportsSapiInInterface(string $sapi): bool {
        return in_array($sapi, $this->getSapisForInterface());
    }

    /**
     * Compiles all files into a single PHAR file.
     * @param string $output The full name of the file to create
     * @param bool $compress If to GZ compress the Phar files
     * @return \PHPWebfuse\FileInfo | false
     * @throws \Exception if no interface is defined
     */
    public function build(string $output, bool $compress = false, bool $addshebang = false): array | false {
        $this->output = $output;
        if (!is_file($this->index) && empty($this->interfaces)) {
            throw new \Exception('Cannot compile when no interface is defined.');
        } else {
            @unlink($this->output);
            // Create phar
            $alias = basename($this->output);
            $phar = new \Phar($this->output, 0, $alias);
            // Set the signature algorithm for a phar and apply it
            $phar->setSignatureAlgorithm(\Phar::SHA1);
            // Start buffering Phar write operations, do not modify the Phar object on disk
            $phar->startBuffering();
            // Add files
            foreach ($this->files as $relativePath => $realPath) {
                // Add a file from the filesystem to the phar archive
                @$phar->addFile($realPath, $relativePath);
            }
            // Used to set the PHP loader or bootstrap stub of a Phar archive
            if (is_file($this->index)) {
                $this->setPharStubFromIndex($phar, $addshebang);
            } else {
                $this->setPharStubFromInterfaces($phar, $addshebang);
            }
            // Stop buffering write requests to the Phar archive, and save changes to disk
            $phar->stopBuffering();
            // Compresses all files in the current Phar archive
            if ($compress) {
                $this->compressPhar($phar);
            }
            @chmod($this->output, 0770);
            unset($phar);
            if (is_file($this->output)) {
                return File::getInfo($this->output);
            }
        }
        return false;
    }

    // PRIVATE METHODS

    /**
     * Resolve a path
     * @param string $path
     * @return string|false
     */
    private function realPath(string $path): string|false {
        return @realpath($path);
    }

    /**
     * Matches the given path
     * @param string $path
     * @param string $excludePattern
     * @return bool
     */
    private function matchFilename(string $path, string $excludePattern): bool {
        $inverted = false;
        if ($excludePattern[0] == '!') {
            $excludePattern = substr($excludePattern, 1);
            $inverted = true;
        }
        return fnmatch($excludePattern, $path) == ($inverted ? false : true);
    }

    /**
     * Set the stub index from interfaces
     * @param \Phar $phar
     * @param bool $addshebang
     * @param bool $useExit
     * @return void
     */
    private function setPharStubFromInterfaces(\Phar $phar, bool $addshebang = false, bool $useExit = true): void {
        $alias = basename($this->output);
        $stub = $addshebang ? array($this->shebang, '<?php') : array('<?php');
        $stub[] = "[space]Phar::mapPhar('$alias');";
        $stub[] = "[space]Phar::interceptFileFuncs();";
        $stub[] = "[space]if (PHP_SAPI == 'cli') {";
        if (isset($this->interfaces['cli'])) {
            $stub[] = "[space][space]require_once 'phar://" . $alias . "/" . $this->interfaces['cli'] . "';";
        } else {
            $stub[] = "[space][space]" . ($useExit ? "exit" : "throw new \RuntimeException") . "('This program can not be invoked via the CLI version of PHP, use the Web interface instead.');";
        }
        $stub[] = '[space]} else {';
        if (isset($this->interfaces['web'])) {
            $stub[] = "[space][space]set_include_path('phar://' . __FILE__ . PATH_SEPARATOR . get_include_path());";
            $stub[] = "[space][space]require_once 'phar://" . $alias . "/" . $this->interfaces['web'] . "';";
        } else {
            $stub[] = "[space][space]" . ($useExit ? "exit" : "throw new \RuntimeException") . "('This program can not be invoked via the Web interface, use the CLI version of PHP instead.');";
        }
        $stub[] = '[space]}';
        $stub[] = '[space]__HALT_COMPILER();';
        $stub[] = '?>';
        $phar->setStub($this->contentFormatter(implode("\n", $stub)));
    }

    /**
     * Set the stub interface from index
     * @param \Phar $phar
     * @param bool $addshebang
     * @return void
     */
    private function setPharStubFromIndex(\Phar $phar, bool $addshebang = false): void {
        // Get content and trim white spaces
        $content = trim(File::getFileContent($this->index));
        // Add the shebang if $addshebang is set to true
        $content = $addshebang && !Utils::startsWith($this->shebang, $content) ? $this->shebang . "[newline]" . $content : $content;
        // Set the stub by formatting and adding the __HALT_COMPILER()
        $phar->setStub($this->contentFormatter($this->addHalt($content)));
    }

    /**
     * Compress the Phar
     * @param \Phar $phar
     * @return void
     */
    private function compressPhar(\Phar $phar): void {
        if ($phar->canCompress(\Phar::GZ)) {
            $phar->compressFiles(\Phar::GZ);
        }
    }

    /**
     * Format content
     * @param string $content
     * @return string
     */
    private function contentFormatter(string $content): string {
        return str_replace(array("[newline]", "[space]", "[tab]"), array("\n", " ", "\t"), $content);
    }

    /**
     * Check if __HALT_COMPILER() is found at the end of the string
     * @param string $content
     * @return bool
     */
    private function isHaltFound(string $content): bool {
        $halts = array("__HALT_COMPILER()", "__HALT_COMPILER();", "__HALT_COMPILER(); ?>", "__HALT_COMPILER();?>", "__HALT_COMPILER();\n?>");
        foreach ($halts as $value) {
            if (Utils::endsWith($value, $content)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add halt to content
     * @param string $content
     * @return string
     */
    private function addHalt(string $content): string {
        if (!$this->isHaltFound($content)) {
            $content = substr($content, -2) == "?>" ? substr($content, 0, -2) : $content;
            $content .= "\n__HALT_COMPILER();\n?>";
        }
        return $content;
    }
}

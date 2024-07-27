<?php

namespace PHPWebfuse;

/**
 * The PHPWebfuse 'FileInfo' class
 */
class FileInfo
{
    // PRIVATE VARIABLES

    /**
     * @var \PHPWebfuse\Methods The default PHPWebfuse methods class
     */
    private $methods = null;

    // PUBLIC VARIABLES

    /**
     * @var string The file absolute path
     */
    public $absolutePath;

    /**
     * @var string The file basename
     */
    public $basename;

    /**
     * @var string The file directory name
     */
    public $dirname;

    /**
     * @var array An array of the file size
     */
    public $sizes;

    // PUBLIC METHODS

    /**
     * Construct new File information  instance
     * @param string $absolutePath
     */
    public function __construct(string $absolutePath)
    {
        $this->methods = new \PHPWebfuse\Methods();
        if ($this->methods->isFile($absolutePath)) {
            clearstatcache(false, $absolutePath);
            $this->absolutePath = $absolutePath;
            $this->basename = basename($absolutePath);
            $this->dirname = $this->methods->getDirname($absolutePath);
            $size = $this->methods->getFIlesizeInBytes($absolutePath);
            $this->sizes = array(
                'bytes' => $size,
                'kilobytes' => round($size / 1024, 1),
                'megabytes' => round(($size / 1024) / 1024, 1),
                'gigabytes' => round((($size / 1024) / 1024) / 1024, 1),
            );
        }
    }
}

<?php

namespace PHPWebfuse;

/**
 */
class FileInfo
{
    // PRIVATE VARIABLES

    /**
     * @var \PHPWebfuse\Methods
     */
    private \PHPWebfuse\Methods $methods;

    // PUBLIC VARIABLES

    /**
     * @var string The file absolute path
     */
    public string $absolutePath = "";

    /**
     * @var string The file basename
     */
    public string $basename = "";

    /**
     * @var string The file directory name
     */
    public string $dirname = "";

    /**
     * @var array An array of the file size
     */
    public array $sizes = array();

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

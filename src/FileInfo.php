<?php

namespace PHPWebfuse;

use \PHPWebfuse\Utils;
use \PHPWebfuse\File;

/**
 * @author Senestro
 */
class FileInfo {

    // PRIVATE VARIABLE
    // PUBLIC VARIABLES
    // PUBLIC METHODS
    
    /**
     * Prevent the constructor from being initialized
     */
    private function __construct() {
        
    }


    public static function getInfo(string $file): array {
        $info = array();
        if(File::isFile($file)) {
            $file = \realpath($file);
            clearstatcache(false, $file);
            $info['realPath'] = $file;
            $info['basename'] = \basename($file);
            $info['dirname'] = File::getDirname($file);
            $size = File::getFIlesizeInBytes($file);
            $info['sizes'] = array(
                'bytes' => $size,
                'kilobytes' => round($size / 1024, 1),
                'megabytes' => round(($size / 1024) / 1024, 1),
                'gigabytes' => round((($size / 1024) / 1024) / 1024, 1),
            );
        }
        return $info;
    }

    // PRIVATE METHODS
}

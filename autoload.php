<?php

// THE ROOT DIRECTORY
$rootDir = str_replace("\\", DIRECTORY_SEPARATOR, dirname('' . __FILE__ . '') . DIRECTORY_SEPARATOR);

$os = strtolower(PHP_OS);
if(substr($os, 0, 3) === "win") {
    $os = "Windows";
} elseif(substr($os, 0, 4) == "unix") {
    $os = "Unix";
} elseif(substr($os, 0, 5) == "linux") {
    $os = "Linux";
} else {
    $os = "Unknown";
}

// THE 'SRC' DIRECTORY
$srcDir = $rootDir . 'src' . DIRECTORY_SEPARATOR;

// THE MAIN CONSTANT
if(!defined("PHPWebfuse")) {
    define('PHPWebfuse', array(
        "os" => $os,
        "directories" => array(
            "root" => $rootDir,
            "src" => $srcDir,
            "data" => $srcDir . "data" . DIRECTORY_SEPARATOR,
            "images" => $srcDir . "images" . DIRECTORY_SEPARATOR,
            "libraries" => $srcDir . "libraries" . DIRECTORY_SEPARATOR,
            "plugins" => $srcDir . "plugins" . DIRECTORY_SEPARATOR,
        ),
    ));
}

// LOAD THE COMPOSER IF THE DIRECTORY IS FOUND
$vendorDir = $rootDir . 'vendor' . DIRECTORY_SEPARATOR;
if(is_dir($vendorDir) && is_readable($vendorDir)) {
    require_once $vendorDir . 'autoload.php';
}

// The PHPWebfuse class error constant
if(!\defined("THROW_PHPWEBFUSE_CLASS_ERROR")) {
    \define("THROW_PHPWEBFUSE_CLASS_ERROR", \true);
}

// The PHPWebfuse exception class
if(!\class_exists("\PHPWEBFUSE_EXCEPTION")) {

    class PHPWEBFUSE_EXCEPTION extends \Exception {
        
    }

}

// THE AUTOLOADER CALLBACK
if(!function_exists("PHPWebfuseAutoloader")) {

    function PHPWebfuseAutoloader(string $classname) {
        $throwException = false;
        $srcDir = PHPWebfuse['directories']['src'];
        $os = strtolower(PHPWebfuse['os']);
        if(is_int(strpos($classname, "PHPWebfuse", 0)) && !class_exists($classname)) {
            $classname = implode(DIRECTORY_SEPARATOR, array_filter(explode(DIRECTORY_SEPARATOR, str_replace("PHPWebfuse", "", $classname))));
            $absolutePath = $srcDir . $classname . ".php";
            if($os == "unix" || $os == "linux" || $os == "unknown") {
                $exploded = array_filter(explode(DIRECTORY_SEPARATOR, $absolutePath));
                $absolutePath = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $exploded);
            }
            if(is_file($absolutePath) && is_readable($absolutePath)) {
                require_once $absolutePath;
            } else {
                if(\defined("THROW_PHPWEBFUSE_CLASS_ERROR") && THROW_PHPWEBFUSE_CLASS_ERROR === true) {
                    throw new \PHPWEBFUSE_EXCEPTION("The PHPWebfuse class file isn't found [" . $absolutePath . "]");
                } else {
                    return;
                }
            }
        } else {
            return;
        }
    }

}
// UNREGISTER THE AUTOLOADER IF REGISTERED
spl_autoload_unregister("PHPWebfuseAutoloader");
// REGISTER THE AUTOLOADER IF NOT REGISTERED
spl_autoload_register("PHPWebfuseAutoloader", true, true);

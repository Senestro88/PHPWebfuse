<?php

// DEFINE DIRECTORY AND PATH SEPARATOR
if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}
if (!defined("PS")) {
    define("PS", PATH_SEPARATOR);
}

// THE ROOT DIRECTORY
$rootDir = str_replace("\\", DIRECTORY_SEPARATOR, dirname('' . __FILE__ . '') . DIRECTORY_SEPARATOR);

$os = strtolower(PHP_OS);
if (substr($os, 0, 3) === "win") {
    $os = "Windows";
} elseif (substr($os, 0, 4) == "unix") {
    $os = "Unix";
} elseif (substr($os, 0, 5) == "linux") {
    $os = "Linux";
} else {
    $os = "Unknown";
}

// THE 'SRC' DIRECTORY
$srcDir = $rootDir . 'src' . DIRECTORY_SEPARATOR;

// The main constants
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

/**
 * The 'PHPWebfuse' Autoloader class
 */
class PHPWebfuseAutoloader
{
    // Privent calling as instance
    private function __construct()
    {

    }

    /**
     *
     * @param string $classname
     * @return void
     */
    public static function load(string $classname)
    {
        $srcDir = PHPWebfuse['directories']['src'];
        $os = strtolower(PHPWebfuse['os']);
        if (is_int(strpos($classname, "PHPWebfuse", 0)) && !class_exists($classname)) {
            $classname = str_replace(array("\\", DIRECTORY_SEPARATOR), "", substr($classname, strlen("PHPWebfuse")));
            $absolutePath = $srcDir . $classname . ".php";
            if (($os == "unix" or $os == "linux") or $os == "unknown") {
                $exploded = array_filter(explode(DIRECTORY_SEPARATOR, $absolutePath));
                $absolutePath = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $exploded);
            }
            if (is_file($absolutePath) && is_readable($absolutePath)) {
                require_once $absolutePath;
            } else {
                return;
            }
        } else {
            return;
        }
    }
}

// Register the 'PHPWebfuseAutoloader' class
spl_autoload_register("\PHPWebfuseAutoloader::load", true, true);

// Load the classes in 'src'
$srcIterator = new \IteratorIterator(new \DirectoryIterator($srcDir));
foreach ($srcIterator as $index => $item) {
    if (!$item->isDot() && $item->isFile()) {
        require_once $item->getRealPath();
    } else {
        continue;
    }
}

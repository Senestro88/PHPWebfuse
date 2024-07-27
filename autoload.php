<?php

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

// THE 'SRC' AND 'VENDOR' DIRECTORY
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

// THE AUTOLOADER CALLBACK
if(!function_exists("PHPWebfuseAutoloader")) {
    function PHPWebfuseAutoloader(string $classname)
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
// UNREGISTER THE AUTOLOADER IF REGISTERED
spl_autoload_unregister("PHPWebfuseAutoloader");
// REGISTER THE AUTOLOADER
spl_autoload_register("PHPWebfuseAutoloader", true, true);
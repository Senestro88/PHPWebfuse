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

// THE AUTOLOADER CALLBACK
if(!function_exists("PHPWebfuseAutoloader")) {

    function PHPWebfuseAutoloader(string $classname) {
        $namespace = 'PHPWebfuse';
        // Check if the class belongs to the PHPWebfuse namespace
        if(strpos($classname, $namespace, 0) === 0) {
            // Get the relative class path by removing the namespace
            $relativeClass = str_replace("$namespace\\", '', $classname);
            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
            // Get the "src" directory and the OS type
            $srcDir = PHPWebfuse['directories']['src'];
            $os = strtolower(PHPWebfuse['os']);
            // Build the absolute class path
            $classPath = $srcDir . DIRECTORY_SEPARATOR . $relativePath;
            // If the OS is Unix, Linux, or unknown, ensure the path starts with a slash
            if(in_array($os, ['unix', 'linux', 'unknown'])) {
                $classPath = DIRECTORY_SEPARATOR . ltrim($classPath, DIRECTORY_SEPARATOR);
            }
            // Check if the file exists and is readable, then require it
            if(is_readable($classPath)) {
                require_once $classPath;
            }
        }
    }

}
// UNREGISTER THE AUTOLOADER IF REGISTERED
spl_autoload_unregister("PHPWebfuseAutoloader");
// REGISTER THE AUTOLOADER IF NOT REGISTERED
spl_autoload_register("PHPWebfuseAutoloader", true, true);

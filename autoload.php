<?php

// THE ROOT DIRECTORY
$PHPWEBFUSE_ROOT_DIR = str_replace("\\", DIRECTORY_SEPARATOR, dirname('' . __FILE__ . '') . DIRECTORY_SEPARATOR);

$PHPWEBFUSE_OS = strtolower(PHP_OS);
if(substr($PHPWEBFUSE_OS, 0, 3) === "win") {
    $PHPWEBFUSE_OS = "Windows";
} elseif(substr($PHPWEBFUSE_OS, 0, 4) == "unix") {
    $PHPWEBFUSE_OS = "Unix";
} elseif(substr($PHPWEBFUSE_OS, 0, 5) == "linux") {
    $PHPWEBFUSE_OS = "Linux";
} else {
    $PHPWEBFUSE_OS = "Unknown";
}

// THE 'SRC' DIRECTORY
$PHPWEBFUSE_SRC_FIR = $PHPWEBFUSE_ROOT_DIR . 'src' . DIRECTORY_SEPARATOR;

// THE MAIN CONSTANT
if(!defined("PHPWEBFUSE")) {
    define('PHPWEBFUSE', array(
        "os" => $PHPWEBFUSE_OS,
        "directories" => array(
            "root" => $PHPWEBFUSE_ROOT_DIR,
            "src" => $PHPWEBFUSE_SRC_FIR,
            "data" => $PHPWEBFUSE_SRC_FIR . "data" . DIRECTORY_SEPARATOR,
            "images" => $PHPWEBFUSE_SRC_FIR . "images" . DIRECTORY_SEPARATOR,
            "libraries" => $PHPWEBFUSE_SRC_FIR . "libraries" . DIRECTORY_SEPARATOR,
            "plugins" => $PHPWEBFUSE_SRC_FIR . "plugins" . DIRECTORY_SEPARATOR,
        ),
    ));
}

// LOAD THE COMPOSER IF THE DIRECTORY IS FOUND
$PHPWEBFUSE_VENDOR_DIR = $PHPWEBFUSE_ROOT_DIR . 'vendor' . DIRECTORY_SEPARATOR;
if(is_dir($PHPWEBFUSE_VENDOR_DIR) && is_readable($PHPWEBFUSE_VENDOR_DIR)) {
    require_once $PHPWEBFUSE_VENDOR_DIR . 'autoload.php';
}

// THE AUTOLOADER CALLBACK
if(!function_exists("PHPWEBFUSE_AUTOLOADER")) {

    function PHPWEBFUSE_AUTOLOADER(string $classname)
    {
        $namespace = 'PHPWebfuse';
        // Check if the class belongs to the PHPWebfuse namespace
        if(strpos($classname, $namespace, 0) === 0) {
            // Get the relative class path by removing the namespace
            $RELATIVE_CLASS = str_replace("$namespace\\", '', $classname);
            $RELATIVE_PATH = str_replace('\\', DIRECTORY_SEPARATOR, $RELATIVE_CLASS) . '.php';
            // Get the "src" directory and the OS type
            $PHPWEBFUSE_SRC_FIR = PHPWEBFUSE['directories']['src'];
            $PHPWEBFUSE_OS = strtolower(PHPWEBFUSE['os']);
            // Build the absolute class path
            $CLASS_PATH = $PHPWEBFUSE_SRC_FIR . DIRECTORY_SEPARATOR . $RELATIVE_PATH;
            // If the OS is Unix, Linux, or unknown, ensure the path starts with a slash
            if(in_array($PHPWEBFUSE_OS, ['unix', 'linux', 'unknown'])) {
                $CLASS_PATH = DIRECTORY_SEPARATOR . ltrim($CLASS_PATH, DIRECTORY_SEPARATOR);
            }
            // Check if the file exists and is readable, then require it
            if(is_readable($CLASS_PATH)) {
                require_once $CLASS_PATH;
            }
        }
    }

}
// UNREGISTER THE AUTOLOADER IF REGISTERED
spl_autoload_unregister("PHPWEBFUSE_AUTOLOADER");
// REGISTER THE AUTOLOADER IF NOT REGISTERED
spl_autoload_register("PHPWEBFUSE_AUTOLOADER", true, true);

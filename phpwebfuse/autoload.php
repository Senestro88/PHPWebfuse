<?php
// DEFINE DIRECTORY AND PATH SEPARATOR
if (!defined("DS")) {define("DS", DIRECTORY_SEPARATOR);}
if (!defined("PS")) {define("PS", PATH_SEPARATOR);}

// THE ROOT DIRECTORY
$rootDir = str_replace("\\", DIRECTORY_SEPARATOR, dirname('' . __FILE__ . '') . DIRECTORY_SEPARATOR);

$os = strtolower(PHP_OS);
if (substr($os, 0, 3) === "win") {$os = "Windows";} else if (substr($os, 0, 4) == "unix") {$os = "Unix";} else if (substr($os, 0, 5) == "linux") {$os = "Linux";} else { $os = "Unknown";}

// THE 'SRC' DIRECTORY
$srcDir = $rootDir . 'src' . DIRECTORY_SEPARATOR;

// The main constants
define('PHPWebFuse', array(
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
 * The 'PHPWebFuse' Autoloader class
 */
class PHPWebFuseAutoloader {
	// Privent calling as instance
	private function __construct() {}

	public static function load(string $classname) {
		$srcDir = PHPWebFuse['directories']['src'];
		$os = strtolower(PHPWebFuse['os']);
		if (is_int(strpos($classname, "PHPWebFuse", 0)) && !class_exists($classname)) {
			$classname = str_replace(array("\\", DIRECTORY_SEPARATOR), "", substr($classname, strlen("PHPWebFuse")));
			$absolutePath = $srcDir . $classname . ".php";
			if (($os == "unix" OR $os == "linux") OR $os == "unknown") {
				$exploded = array_filter(explode(DIRECTORY_SEPARATOR, $absolutePath));
				$absolutePath = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $exploded);
			}
			if (is_file($absolutePath) && is_readable($absolutePath)) {require_once $absolutePath;} else {return;}
		} else {return;}
	}
}

// Register the 'PHPWebFuseAutoloader' class
spl_autoload_register("\PHPWebFuseAutoloader::load", true, true);

// Load the classes in 'src'
$srcIterator = new \IteratorIterator(new \DirectoryIterator($srcDir));
foreach ($srcIterator as $index => $item) {
	if (!$item->isDot() && $item->isFile()) {
		require_once $item->getRealPath();
	}
}
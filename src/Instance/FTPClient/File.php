<?php

namespace PHPWebfuse\Instance\FTPClient;

use \PHPWebfuse\Utils;
use \PHPWebfuse\Path;

/**
 * @author Senestro
 */
class File {
    /* PUBLIC VARIABLES */

    private string $dir;
    private string $line;
    private string $type;
    private string $realpath;
    private string $key;
    private int $id;
    private string $basename;
    private array $permission;
    private string $owner;
    private string $group;
    private array $size = array();
    private string $month;
    private string $day;
    private string $time;
    private string $target;
    
    /**
     * The remote directory separator
     * @var string
     */
    private string $rds= "/"; 

    // PUBLIC METHODS

    /**
     * The constructor
     * @param \PHPWebfuse\Instance\FTPClient $client
     * @param \PHPWebfuse\Instance\FTPClient\Bridge $bridge
     * @param string $dir
     * @param string $line
     * @param string $RPS
     */
    public function __construct(\PHPWebfuse\Instance\FTPClient $client, string $dir, string $line) {
        $this->dir = $dir;
        $this->line = $line;
        $this->validate();
    }

    /**
     * Get the file type (file, directory, link or unknown)
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Get the real path to the file
     * @return string
     */
    public function getRealPath(): string {
        return $this->realpath;
    }

    /**
     * Get the unique key
     * @return string
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * Get the unique Id
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * Get the basename of the file
     * @return string
     */
    public function getBasename(): string {
        return $this->basename;
    }

    /**
     * Unlike getBasename, it removes the extension from the basename
     * @return string
     */
    public function getName(): string {
        return $this->methods->removeExtension($this->getBasename());
    }

    /**
     * Ge the file extension
     * @return string
     */
    public function getEtension(): string {
        return $this->methods->getExtension($this->getBasename());
    }

    /**
     * Removes extension from the real path
     * @return string
     */
    public function removeExtension(): string {
        return $this->methods->removeExtension($this->getRealPath());
    }

    /**
     * Get the raw permission, like -drwx and octal permssion, like 0775
     * @return string
     */
    public function getPermission(): array {
        return $this->permission;
    }

    /**
     * Get the owner
     * @return string
     */
    public function getOwner(): string {
        return $this->owner;
    }

    /**
     * Get the group
     * @return string
     */
    public function getGroup(): string {
        return $this->group;
    }

    /**
     * Get the size and in human readable
     * @return int
     */
    public function getSize(): array {
        return $this->size;
    }

    /**
     * Get the month
     * @return string
     */
    public function getMonth(): string {
        return $this->month;
    }

    /**
     * Get the day
     * @return string
     */
    public function getDay(): string {
        return $this->day;
    }

    /**
     * Ge the time
     * @return string
     */
    public function getTime(): string {
        return $this->time;
    }

    /**
     * Get the target if it's a link
     * @return string
     */
    public function getTarget(): string {
        return $this->target;
    }

    /**
     * Get the directory
     * @return string
     */
    public function getDir(): string {
        return $this->insertrds($this->arrangerds($this->dir), true);
    }

    /**
     * Determine if it's a directory
     * @return bool
     */
    public function isDir(): bool {
        return $this->getType() === "directory";
    }

    // PUBLIC STATIC METHODS
    // PRIVATE FUNCTIONS

    /**
     * Validate the line
     * @return void
     */
    private function validate(): void {
        // Make sure the line is not empty
        if(Utils::isNotEmptyString($this->line)) {
            // Split the raw list entry by space
            $parts = preg_split("/\s+/", $this->line);
            // Making sure that the line parts are greater than or equal to 9
            if(count($parts) >= 9) {
                // Extract the relevant parts
                $permission = $parts[0]; // Raw permission
                $number = $parts[1]; // The number
                $owner = $parts[2]; // The owner
                $group = $parts[3]; // The group
                $size = $parts[4]; // The size
                $month = $parts[5]; // The month
                $day = $parts[6]; // The day
                $time = $parts[7]; // The time
                $basename = $parts[8]; // The file basename
                // Make sure the basename is set and it's a string
                if($basename && is_string($basename)) {
                    // Set a variable to check if the basename is empty or the basename is parent directory or current directory
                    $notValid = empty($basename) || $basename == "." || $basename == "..";
                    // Include when basename is not empty or basename is not the current directory or parent directory
                    if(!$notValid) {
                        // Get the file type (file, directory, link or unknown)
                        $this->type = $this->convertRawPermissionToType($permission);
                        // Get the full path of the item
                        $this->realpath = Path::arrange_dir_separators(Path::merge($this->dir, $basename, $this->rds), false, $this->rds);
                        // Handle filenames with spaces
                        if(isset($parts[9])) {
                            for($i = 9; $i < count($parts); $i++) {
                                $this->realpath .= ' ' . $parts[$i];
                            }
                        }
                        // Create a unique index for the item
                        $this->key = md5($this->type . '#' . $this->realpath);
                        // Set items
                        $this->id = $number;
                        $this->basename = basename($this->realpath);
                        $this->permission = array("number" => $this->convertRawPermissionToOctal($permission), "string" => $permission);
                        $this->owner = $owner;
                        $this->group = $group;
                        $this->size = array("number" => $size, "string" => Utils::formatSize($size));
                        $this->month = $month;
                        $this->day = $day;
                        $this->time = $time;
                        // Add the target of a symbolic link if present
                        $this->target = $this->type == "link" && isset($parts[10]) ? $parts[10] : "";
                    }
                }
            }
        }
    }

    /**
     * Convert raw permission (drwx---r-x ...) to type (file, directory, link or unknown).
     * @param string $permission
     * @return string
     */
    private function convertRawPermissionToType(string $permission): string {

        if(!is_numeric($permission)) {
            if(empty($permission[0])) {
                return 'unknown';
            }
            switch($permission[0]) {
                case '-': return 'file';
                case 'd': return 'directory';
                case 'l': return 'link';
                default: return 'unknown';
            }
        }
        return "unknown";
    }

    /**
     * Convert raw info permission (drwxr-xr-x)  to 0755
     * @param string $permission
     * @return string | null
     */
    private function convertRawPermissionToOctal(string $permission): ?string {
        // Check if the permission string is not numeric (i.e., it's a symbolic permission like "rwxr-xr--")
        if(!is_numeric($permission)) {
            // Remove the first character (usually a file type indicator like '-', 'd', etc.)
            $permission = substr($permission, 1);
            // Split the remaining permission string into parts of 3 characters each (owner, group, world)
            $permissionparts = str_split($permission, 3);
            // Initialize an associative array to store octal values for owner, group, and world
            $octalPermission = array(
                'owner' => $permissionparts[0] ?: "",
                'group' => $permissionparts[1] ?: "",
                'world' => $permissionparts[2] ?: ""
            );
            // Define an associative array to map permission characters to their octal values
            $permsValues = array("r" => 4, "w" => 2, "x" => 1);
            // Loop through each part (owner, group, world) to calculate the octal value
            foreach($octalPermission as $index => $value) {
                $permInt = 0;
                // Check if the permission part is not empty
                if(Utils::isNotEmptyString($value)) {
                    // Loop through each character in the permission part
                    foreach(str_split($value, 1) as $splitedValue) {
                        // Add the corresponding octal value if the character exists in the permsValues array
                        if(isset($permsValues[$splitedValue])) {
                            $permInt += $permsValues[$splitedValue];
                        }
                    }
                }
                // Assign the calculated octal value to the respective index in the octalPermission array
                $octalPermission[$index] = $permInt;
            }
            // Combine the octal values of owner, group, and world into a single string and prepend with '0'
            return '0' . $octalPermission['owner'] . $octalPermission['group'] . $octalPermission['world'];
        }
        // Return null if the permission string was numeric (not a symbolic permission)
        return null;
    }
    
    /**
     * Arrange remote  separator by replacing multiple separators joined together (\\ or //) to single separator
     * @param string $path
     * @return string
     */
    private function arrangerds(string $path): string {
        return Path::arrange_dir_separators($path, false, $this->rds);
    }
    
    /**
     * Insert directory separator to the beginning or end of the directory path
     * @param string $path
     * @param bool $toEnd
     * @return string
     */
    private function insertrds(string $path, bool $toEnd = true): string {
        return Path::insert_dir_separator($path, $toEnd, $this->rds);
    }
}

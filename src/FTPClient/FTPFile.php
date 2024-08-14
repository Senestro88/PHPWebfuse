<?php

namespace PHPWebfuse\FTPClient;

/**
 */
class FTPFile
{
    /* PUBLIC VARIABLES */

    /**
     * @var \PHPWebfuse\Methods
     */
    private \PHPWebfuse\Methods $methods;
    
    /**
     * @var \PHPWebfuse\FTPClient\FTPPath
     */
    private \PHPWebfuse\FTPClient\FTPPath $FtpPath;

    private string $remotedir;
    private string $line;
    private string $type;
    private string $realPath;
    private string $key;
    private int $id;
    private string $basename;
    private string $rawPermission;
    private ?int $octalPermission;
    private string $owner;
    private string $group;
    private int $size = -1;
    private string $readableSize;
    private string $month;
    private string $day;
    private string $time;
    private string $target;
    
    /**
     * The remote path separator
     * @var string
     */
    private $RPS = "/";

    // PUBLIC METHODS

    /**
     * The constructor
     * @param \PHPWebfuse\FTPClient $client
     * @param \PHPWebfuse\FTPClient\FTPAdapter $adapter
     * @param string $remotedir
     * @param string $line
     * @param string $RPS
     */
    public function __construct(\PHPWebfuse\FTPClient $client, \PHPWebfuse\FTPClient\FTPAdapter $adapter, string $remotedir, string $line, string $RPS = "/")
    {
        $this->methods = new \PHPWebfuse\Methods();
        $this->remotedir = $remotedir;
        $this->line = $line;
        $this->RPS = $RPS;
        $this->FtpPath = new \PHPWebfuse\FTPClient\FTPPath($RPS);
        $this->validateLine();
    }

    /**
     * Get the file type (file, directory, link or unknown)
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the real path to the file
     * @return string
     */
    public function getRealPath(): string
    {
        return $this->realPath;
    }
    
    /**
     * Get the unique key
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /** 
     * Get the unique Id
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the basename of the file
     * @return string
     */
    public function getBasename(): string
    {
        return $this->basename;
    }

    /**
     * Unlike getBasename, it removes the extension from the basename
     * @return string
     */
    public function getName(): string
    {
        return $this->methods->removeExtension($this->getBasename());
    }

    /**
     * Ge the file extension
     * @return string
     */
    public function getEtension(): string
    {
        return $this->methods->getExtension($this->getBasename());
    }

    /**
     * Removes extension from the real path
     * @return string
     */
    public function removeExtension(): string
    {
        return $this->methods->removeExtension($this->getRealPath());
    }
    
    /**
     * Get the raw permission, like -drwx
     * @return string
     */
    public function getRawPermission(): string
    {
        return $this->rawPermission;
    }

    /**
     * Get the octal permssion, like 0775
     * @return int
     */
    public function getOctalPermission(): int
    {
        return $this->octalPermission;
    }

    /**
     * Get the owner
     * @return string
     */
    public function getOwner(): string
    {
        return $this->owner;
    }

    /**
     * Get the group
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * Get the size
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get the size to human readable
     * @return string
     */
    public function getReadableSize(): string
    {
        return $this->readableSize;
    }

    /**
     * Get the month
     * @return string
     */
    public function getMonth(): string
    {
        return $this->month;
    }
    
    /**
     * Get the day
     * @return string
     */
    public function getDay(): string
    {
        return $this->day;
    }

    /**
     * Ge the time
     * @return string
     */
    public function getTime(): string
    {
        return $this->time;
    }

    /**
     * Get the target if it's a link
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Get the directory
     * @return string
     */
    public function getDir(): string
    {
        return $this->FtpPath->insert_dir_separator($this->arrangeRPath($this->remotedir), true);
    }
    
    /**
     * Determine if it's a directory
     * @return bool
     */
    public function isDir(): bool {
        return $this->getType() === "directory";
    }
    
    // PRIVATE FUNCTIONS

    /**
     * Validate the line
     * @return void
     */
    private function validateLine(): void
    {
        // Make sure the line is not empty
        if($this->methods->isNotEmptyString($this->line)) {
            // Split the raw list entry by space
            $lineparts = preg_split("/\s+/", $this->line);
            // Making sure that the line parts are greater than or equal to 9
            if (count($lineparts) >= 9) {
                // Extract the relevant parts
                $permission = $lineparts[0]; // Raw permission
                $number = $lineparts[1]; // The number
                $owner = $lineparts[2]; // The owner
                $group = $lineparts[3]; // The group
                $size = $lineparts[4]; // The size
                $month = $lineparts[5]; // The month
                $day = $lineparts[6]; // The day
                $time = $lineparts[7]; // The time
                $basename = $lineparts[8]; // The file basename
                // Make sure the basename is set and it's a string
                if($basename && is_string($basename)) {
                    // Set a variable to check if the basename is empty or the basename is parent directory or current directory
                    $notValid = empty($basename) || $basename == "." || $basename == "..";
                    // Include when basename is not empty or basename is not the current directory or parent directory
                    if(!$notValid) {
                        // Get the file type (file, directory, link or unknown)
                        $this->type = $this->convertRawPermissionToType($permission);
                        // Get the full path of the item
                        $this->realPath = $this->arrangeRPath($this->remotedir . $this->RPS . $basename);
                        // Handle filenames with spaces
                        if (isset($lineparts[9])) {
                            for ($i = 9; $i < count($lineparts); $i++) {
                                $this->realPath .= ' ' . $lineparts[$i];
                            }
                        }
                        // Create a unique index for the item
                        $this->key = md5($this->type . '#' . $this->realPath);
                        // Set items
                        $this->id = $number;
                        $this->basename = basename($this->realPath);
                        $this->rawPermission = $permission;
                        $this->octalPermission = $this->convertRawPermissionToOctal($permission);
                        $this->owner = $owner;
                        $this->group = $group;
                        $this->size = $size;
                        $this->readableSize = $this->methods->formatSize($size);
                        $this->month = $month;
                        $this->day = $day;
                        $this->time = $time;
                        // Add the target of a symbolic link if present
                        $this->target = $this->type == "link" && isset($lineparts[10]) ? $lineparts[10] : "";
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
    private function convertRawPermissionToType(string $permission): string
    {

        if(!is_numeric($permission)) {
            if (empty($permission[0])) {
                return 'unknown';
            }
            switch ($permission[0]) {
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
    private function convertRawPermissionToOctal(string $permission): ?string
    {
        // Check if the permission string is not numeric (i.e., it's a symbolic permission like "rwxr-xr--")
        if (!is_numeric($permission)) {
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
            foreach ($octalPermission as $index => $value) {
                $permInt = 0;
                // Check if the permission part is not empty
                if ($this->methods->isNotEmptyString($value)) {
                    // Loop through each character in the permission part
                    foreach (str_split($value, 1) as $splitedValue) {
                        // Add the corresponding octal value if the character exists in the permsValues array
                        if (isset($permsValues[$splitedValue])) {
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
     * Get or determine a path separator from path
     * @param string $path
     * @return string
     */
    private function getSepFromPath(string $path): string
    {
        $sep = "/";
        if ($this->methods->containText("\\", $path)) {
            $sep = '\\';
        } else {
            $sep = '/';
        }
        return $sep;
    }
    
    /**
     * Arrange remote path separators
     * @param string $path
     * @return string
     */
    private function arrangeRPath(string $path, bool $closeEdges = false): string
    {
        return $this->FtpPath->arrange_dir_separators($path, $closeEdges);
    }
}

<?php

namespace PHPWebfuse;

/**
 * The PHPWebfuse 'Database' class
 */
class Database
{
    // PRIVATE VARIABLES

    /**
     * @var \PHPWebfuse\Methods The default PHPWebfuse methods class
     */
    private $methods = null;

    /**
     * @var \mysqli The database connection instance
     */
    private $connection = null;

    /**
     * @var string The database connection host
     */
    private $host = null;

    /**
     * @var string The database connection username
     */
    private $user = null;

    /**
     * @var string The database connection password
     */
    private $password = null;

    // PUBLIC VARIABLES

    /**
     * @var string Messages are stored here
     */
    public $message = "";

    // PUBLIC METHODS

    /**
     * Construct new Database instance and initialize a new database connection
     * @param string|null $host
     * @param string|null $user
     * @param string|null $password
     * @param bool $reset Force reset the connection
     */
    public function __construct(?string $host = null, ?string $user = null, ?string $password = null, bool $reset = false)
    {
        $this->methods = new \PHPWebfuse\Methods();
        if (!in_array('mysqli', get_declared_classes())) {
            $this->message = "The mysqli class doesn't exist or wasn't found.";
        } elseif ($this->methods->isNotEmptyString($host) && $this->methods->isNotEmptyString($user) && $this->methods->isNotEmptyString($password)) {
            $this->init($host, $user, $password, $reset);
        }
    }

    /**
     * Close the database connection and reset the connection variable to default (null)
     */
    public function __destruct()
    {
        if ($this->methods->isNonNull($this->connection)) {
            @$this->connection->close();
            $this->connection = null;
        }
    }

    /**
     * Make a new connection
     * @param string $host
     * @param string $user
     * @param string $password
     * @param bool $reset Force reset the connection
     * @return bool
     */
    public function connect(string $host, string $user, string $password, bool $reset = false): bool
    {
        return $this->init($host, $user, $password, $reset);
    }

    /**
     * Get the connection instance
     * @return \mysqli|null
     */
    public function connection(): \mysqli | null
    {
        return $this->connection;
    }


    public function lastInsertID(): int|string
    {
        if ($this->methods->isNonNull($this->connection)) {
            return $this->connection->insert_id;
        }return "";
    }

    /**
     *  Select a database
     * @param string $database
     * @return bool
     */
    public function selectDb(string $database): bool
    {
        if ($this->methods->isNonNull($this->connection)) {
            return @$this->connection->select_db($database);
        }return false;
    }

    /**
     * Get the last error
     * @return string
     */
    public function lastError(): string
    {
        if ($this->methods->isNonNull($this->connection)) {
            return $this->connection->error;
        }return "";
    }

    /**
     * Get the connection host info
     * @return string
     */
    public function hostInfo(): string
    {
        if ($this->methods->isNonNull($this->connection)) {
            return $this->connection->host_info;
        }return "";
    }

    /**
     * Get the connection server info
     * @return string
     */
    public function serverInfo(): string
    {
        if ($this->methods->isNonNull($this->connection)) {
            return $this->connection->server_info;
        }return "";
    }

    /**
     * Get the connection server info
     * @return string
     */
    public function serverVersion(): string
    {
        if ($this->methods->isNonNull($this->connection)) {
            return $this->connection->server_version;
        }return "";
    }

    /**
     * Get the last query info
     * @return string
     */
    public function lastQueryInfo(): string
    {
        if ($this->methods->isNonNull($this->connection)) {
            return $this->connection->info;
        }return "";
    }

    /**
     * Get the connection protocol version
     * @return int|string
     */
    public function protocolVersion(): int|string
    {
        if ($this->methods->isNonNull($this->connection)) {
            return $this->connection->protocol_version;
        }return "";
    }

    /**
     * Escape characters
     * @param string $string
     * @return string
     */
    public function escape(string $string): string
    {
        if ($this->methods->isNonNull($this->connection)) {
            return $this->connection->real_escape_string($string);
        }return $string;
    }

    /**
     * Create database
     * @param string $name
     * @param string $character Defaults to 'latin1'
     * @param string $collate Defaults to 'latin1_general_ci'
     * @return bool
     */
    public function createDatabase(string $name, string $character = "latin1", string $collate = " latin1_general_ci"): bool
    {
        if ($this->methods->isNonNull($this->connection)) {
            return $this->connection->query("CREATE DATABASE IF NOT EXISTS `" . strtolower($name) . "` DEFAULT CHARACTER SET " . $character . " COLLATE " . $collate . ";");
        }
        return false;
    }

    /**
     * Delete database
     * @param string $name
     * @return bool
     */
    public function deleteDatabase(string $name): bool
    {
        if ($this->methods->isNonNull($this->connection)) {
            return $this->connection->query("DROP DATABASE IF EXISTS `" . strtolower($name) . "`");
        }
        return false;
    }

    /**
     * Optimize database
     * @param array $databases
     * @return array
     */
    public function optimiseDatabases(array $databases): array
    {
        $result = array();
        if ($this->methods->isNonNull($this->connection)) {
            foreach ($databases as $database) {
                $database = $this->sanitizeIdentifier($database);
                $status = $this->connection->query("SHOW TABLE STATUS FROM " . $database . ";");
                if ($status) {
                    while ($row = $status->fetch_assoc()) {
                        $dataFree = $row['Data_free'];
                        $table = $row['Name'];
                        $result[$database][$table] = "";
                        if ($dataFree > 0) {
                            if (@$this->connection->query("OPTIMIZE TABLE `" . $database . "." . $table . "`")) {
                                $result[$database][$table] = true;
                            } else {
                                $result[$database][$table] = false;
                            }
                        } else {
                            $result[$database][$table] = true;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Delete a database table
     * @param string $database
     * @param string $table
     * @return bool
     */
    public function deleteDatabaseTable(string $database, string $table): bool
    {
        if ($this->methods->isNonNull($this->connection) && $this->doesDatabaseTableExist($database, $table)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            return $this->connection->query('DROP TABLE IF EXISTS ' . $database . '.' . $table . ';');
        }
        return false;
    }

    /**
     * Check if a database table exist
     *
     * @param string $databases
     * @param string $table
     * @return bool
     */
    public function doesDatabaseTableExist(string $database, string $table): bool
    {
        if ($this->methods->isNonNull($this->connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $tables = $this->connection->query('SHOW TABLES FROM ' . $database . ';');
            if ($tables) {
                while ($ft = $tables->fetch_array()) {
                    if ($ft[0] == $table) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Truncate or empty a database table
     *
     * @param string $databases
     * @param string $table
     * @return bool
     */
    public function truncateDatabaseTable(string $database, string $table): bool
    {
        if ($this->methods->isNonNull($this->connection) && $this->doesDatabaseTableExist($database, $name)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            return $this->connection->query('TRUNCATE ' . $database . '.' . $table . ';');
        }
        return false;
    }

    /**
     * Insert into database table
     * @param string $database
     * @param string $table
     * @param array $data
     * @param bool $preprare Wether to prepare or directly execute the query
     * @return bool
     */
    public function insertToDatabaseTable(string $database, string $table, array $data = array(), bool $preprare = true): bool
    {
        if ($this->methods->isNonNull($this->connection) && $this->doesDatabaseTableExist($database, $table)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $fields = $values = array();
            foreach ($data as $index => $value) {
                if (!$this->methods->isEmptyString($index) && !$this->methods->isEmptyString($value)) {
                    $fields[] = $index;
                    $values[] = $value;
                }
            }
            $statement = "INSERT IGNORE INTO " . $database . "." . $table . " (`" . implode("`, `", $fields) . "`) VALUES (";
            $rows = "";
            foreach ($values as $value) {
                if ($this->methods->isInt($value)) {
                    $rows .= $this->escape($value) . ', ';
                } elseif ($this->methods->isString($value)) {
                    $rows .= '"' . $this->escape($value) . '", ';
                } else {
                    $rows .= '"' . $this->escape((string) $value) . '", ';
                }
            }
            $statement .= substr(trim($rows), -1) == "," ? substr(trim($rows), 0, -1) : $rows;
            $statement .= ");";
            $result = $preprare === true ? $this->connection->prepare($statement) : $this->connection->query($statement);
            if (!$result || (isset($result->affected_rows) && $result->affected_rows < 1)) {
                $this->message = $this->lastError();
            }
            return $result;
        }
        return false;
    }

    /**
     * Create a database table
     * @param string $database
     * @param string $table
     * @param array $columns Defaults to 'array()'
     * @param string $comment Defaults to ''
     * @param string $engine Defaults to 'MyISAM'
     * @param string $character Defaults to 'latin1'
     * @param string $collate Defaults to 'latin1_general_ci'
     * @param bool $autoincrement Default to 'true'. Wether to set it as auto increment
     * @return bool
     */
    public function createDatabaseTable(string $database, string $table, array $columns = array(), string $comment = '', string $engine = "MyISAM", string $character = "latin1", string $collate = " atin1_general_ci", bool $autoincrement = true): bool
    {
        if ($this->methods->isNonNull($this->connection) && $this->methods->isFalse($this->doesDatabaseTableExist($database, $table))) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $statement = "CREATE TABLE IF NOT EXISTS " . $database . "." . $name . " (";
            foreach ($columns as $name => $value) {
                $statement .= "" . $name . " " . $value . ", ";
            }
            $statement = rtrim($statement, ", ");
            $statement .= ")";
            $autoincrementValue = $autoincrement ? ' AUTO_INCREMENT=1' : '';
            $statement .= ' ENGINE=' . $engine . ' DEFAULT CHARSET=' . $character . ' COLLATE=' . $collate . '' . $autoincrementValue . '';
            $statement .= strlen($comment) > 0 ? ' COMMENT "' . $this->escape($comment) . '";' : ';';
            $created = $this->connection->query($statement);
            if ($this->methods->isNotTrue($created)) {
                $this->message = $this->lastError();
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Arrange database table
     * @param array $databases
     * @return array
     */
    public function arrangeDatabaseTables(array $databases): array
    {
        $messages = array();
        if ($this->methods->isNonNull($this->connection)) {
            foreach ($databases as $database) {
                $database = $this->sanitizeIdentifier($database);
                $messages[$database] = array();
                $tables = $this->connection->query('SHOW TABLES FROM ' . $database . ';');
                if ($tables && $this->connection->select_db($database)) {
                    while ($ft = $tables->fetch_array()) {
                        $table = $ft[0];
                        $messages[$database][$table] = "";
                        $tablestatements = array();
                        $columnsdata = array();
                        $columns = $this->connection->query('SHOW FULL COLUMNS FROM ' . $table . ';');
                        if ($columns) {
                            try {
                                while ($ft = $columns->fetch_assoc()) {
                                    $columnsdata[] = $ft;
                                }
                            } catch (\Thowable $e) {

                            }
                            foreach ($columnsdata as $index => $data) {
                                $field = $data['Field'];
                                $type = $data['Type'];
                                $collation = $data['Collation'];
                                $null = $data['Null'];
                                $key = $data['Key'];
                                $default = $data['Default'];
                                $extra = $data['Extra'];
                                $comment = $data['Comment'];
                                if ($field == "id" && $key == "PRI") {
                                    $tablestatements[$table] = "ALTER TABLE `" . $table . "` CHANGE `" . $field . "` `" . $field . "` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;";
                                } elseif (substr($type, 0, 3) == 'int' || substr($type, 0, 6) == 'bigint') {
                                    $tablestatements[$table] = "ALTER TABLE `" . $table . "` CHANGE `" . $field . "` `" . $field . "` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '" . $comment . "';";
                                } elseif ((substr($type, 0, 7) == 'varchar' || substr($type, 0, 4) == 'char') && $collation !== "NULL") {
                                    $tablestatements[$table] = "ALTER TABLE `" . $table . "` CHANGE `" . $field . "` `" . $field . "` " . strtoupper($type) . " CHARACTER SET latin1 COLLATE " . $collation . " NOT NULL DEFAULT '' COMMENT '" . $comment . "';";
                                } elseif (substr($type, 0, 4) == 'text' && $collation !== "NULL") {
                                    $tablestatements[$table] = "ALTER TABLE `" . $table . "` CHANGE `" . $field . "` `" . $field . "` " . strtoupper($type) . " CHARACTER SET latin1 COLLATE " . $collation . " NOT NULL COMMENT '" . $comment . "';";
                                }
                            }
                            foreach ($tablestatements as $statement) {
                                if ($this->connection->query($statement)) {
                                    $messages[$database][$table] = true;
                                } else {
                                    $messages[$database][$table] = "Failed to arrange table [" . $this->lastError() . "]";
                                }
                            }
                        }
                    }
                    // Reset the connection
                    $this->init($this->host, $this->user, $this->password, true);
                }
            }
        }
        return $messages;
    }

    /**
     * Get all table columns
     * @param string $database
     * @param string $table
     * @return array
     */
    public function getTableColumns(string $database, string $table): array
    {
        $result = array();
        if ($this->methods->isNonNull($this->connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $columns = $this->connection->query('SHOW FULL COLUMNS FROM ' . $database . '.' . $table . ';');
            if ($columns) {
                try {
                    while ($ft = $columns->fetch_assoc()) {
                        $result[] = $ft;
                    }
                } catch (\Thowable $e) {

                }
            }
        }
        return $result;
    }

    /**
     * Get all table rows from a 'where' statement
     * @param string $database
     * @param string $table
     * @param string $column
     * @param mixed $columnvalue
     * @param string $order
     * @return array
     */
    public function getTableRowsWhereClause(string $database, string $table, string $column, mixed $columnvalue, string $order = "id"): array
    {
        $result = array();
        if ($this->methods->isNonNull($this->connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $columnvalue = $this->escape($columnvalue);
            $statement = "SELECT * FROM " . $database . "." . $table . " WHERE `" . $column . "`='" . $this->escape($columnvalue) . "' ORDER BY " . $order . ";";
            $result = $this->executeAndFetchAssociationFromSelectStatement($statement);
        }
        return $result;
    }

    /**
     * Get all table rows
     * @param string $database
     * @param string $table
     * @param string $order
     * @return array
     */
    public function getTableRows(string $database, string $table, string $order = "id"): array
    {
        $result = array();
        if ($this->methods->isNonNull($this->connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $statement = "SELECT * FROM " . $database . "." . $table . " ORDER BY " . $order . ";";
            $result = $this->executeAndFetchAssociationFromSelectStatement($statement);
        }
        return $result;
    }

    /**
     * Execute a database where 'where' statement (where id=1) and return a single index value if found
     * @param string $database
     * @param string $table
     * @param string $column
     * @param mixed $columnvalue
     * @param mixed $index
     * @return string
     */
    public function getTableRowsIndexValue(string $database, string $table, string $column, mixed $columnvalue, mixed $index): string
    {
        $value = "";
        if ($this->methods->isNonNull($this->connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $columnvalue = $this->escape($columnvalue);
            $assoc = $this->getTableRowsWhereClause($database, $table, $column, $columnvalue);
            foreach ($assoc as $i => $data) {
                if (isset($data[$index])) {
                    $value = $data[$index];
                    break;
                }
            }
        }
        return $value;
    }

    /**
     * Execute and fetch association from a 'select' statement
     * @param string $statement
     * @return array
     */
    public function executeAndFetchAssociationFromSelectStatement(string $statement): array
    {
        $result = array();
        if ($this->methods->isNonNull($this->connection)) {
            if ($this->methods->startsWith("SELECT", $statement)) {
                $select = $this->connection->query($statement);
                if ($select && $select->num_rows > 0) {
                    while ($row = $select->fetch_assoc()) {
                        $result[] = $row;
                    }
                } else {
                    $this->message = "Failed to execute the where clause statement. " . $this->lastError();
                }
            } else {
                $this->message = "To execute a where clause and fetch the association, the statement must start with \"SELECT\"";
            }
        }
        return $result;
    }

    /**
     * Get all database tables
     * @param string $database
     * @return array
     */
    public function getDatabaseTables(string $database): array
    {
        $result = array();
        if ($this->methods->isNonNull($this->connection)) {
            $database = $this->sanitizeIdentifier($database);
            $tables = $this->connection->query("SHOW TABLES FROM " . $database . ";");
            if ($tables) {
                while ($row = $tables->fetch_array()) {
                    $result[] = $row[0];
                }
            }
        }
        return $result;
    }

    /**
     * Get table export structure data
     * @param string $database
     * @param string $table
     * @return string
     */
    public function getTableExportStructureData(string $database, string $table): string
    {
        $structure = '';
        if ($this->methods->isNonNull($this->connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $result = $this->connection->query("SHOW CREATE TABLE " . $database . "." . $table . ";");
            if ($result) {
                $row = $result->fetch_row();
                $structure .= "\n-- ---------------------------------------------------------\n";
                $structure .= "-- Table structure for table `$table`\n";
                $structure .= "-- ---------------------------------------------------------\n";
                $structure .= $row[1] . ";\n";
            }
        }
        return $structure;
    }

    /**
     * Get table export insert data
     * @param string $database
     * @param string $table
     * @return string
     */
    public function getTableExportInsertData(string $database, string $table): string
    {
        $data = '';
        if ($this->methods->isNonNull($this->connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $result = $this->connection->query("SELECT * FROM " . $database . "." . $table . ";");
            if ($result) {
                $fieldCount = $result->field_count;
                $fields = [];
                while ($field = $result->fetch_field()) {
                    $fields[] = "`" . $field->name . "`";
                }
                $fieldNames = implode(', ', $fields);
                $dd = $dv = "";
                $dd .= "\n-- Dumping data for table `$table`\n";
                $dd .= "INSERT INTO `$table` ($fieldNames) VALUES\n";
                while ($row = $result->fetch_row()) {
                    $dv .= "(" . $this->escapeExportRowData($row, $fieldCount) . "),\n";
                }
                if (!empty($dv)) {
                    $dd .= $dv;
                    $data = rtrim($dd, ",\n") . ";\n";
                }
            }
        }
        return $data;
    }

    /**
     * Export database's
     * @param array $databases
     * @param string|null $savePathname
     * @return array
     */
    public function exportDatabases(array $databases, ?string $savePathname = null): array
    {
        $messages = [];
        $savePathname = self::isNull($savePathname) ? dirname(__DIR__) : $savePathname;
        if ($this->methods->isNotDir($savePathname)) {
            $this->methods->makeDir($savePathname);
        }
        $savePathname = $this->methods->resolvePath($savePathname);
        $savePathname = $this->methods->INSERT_DIR_SEPARATOR($savePathname);
        // Iterate through each database and back it up
        foreach ($databases as $database) {
            $database = $this->sanitizeIdentifier($database);
            $backupContent = $this->createDatabaseExportContent($database);
            // If backup content is generated, save it to file
            if ($this->methods->isString($backupContent)) {
                $this->methods->makeDir($savePathname);
                $absolutePath = $this->methods->CONVERT_DIR_SEPARATOR($savePathname) . "database-backup-[" . $database . "].sql";
                // Save the backup content to a file
                if ($this->methods->saveContentToFile($absolutePath, $backupContent)) {
                    $messages[$database] = $absolutePath;
                } else {
                    $messages[$database] = false;
                }
            } else {
                $messages[$database] = false;
            }
        }
        return $messages;
    }

    // PRIVATE METHODS

    /**
     * Initialize a new MySQLi connection
     * @param string $host
     * @param string $user
     * @param string $password
     * @param bool $reset Force reset the connection
     * @return bool
     */
    private function init(string $host, string $user, string $password, bool $reset = false): bool
    {
        if ($this->methods->isNull($this->connection) || $this->methods->isTrue($reset)) {
            try {
                mysqli_report(MYSQLI_REPORT_STRICT);
                $this->connection = new \mysqli($host, $user, $password);
                if ($this->connection instanceof \mysqli) {
                    $this->host = $host;
                    $this->user = $user;
                    $this->password = $password;
                    /* Set the desired charset, time_zone, and sql_mode after establishing a connection */
                    @$this->connection->set_charset('utf8mb4');
                    @$this->connection->query("SET time_zone='" . $this->methods->GMT . "'");
                    @$this->connection->query("SET GLOBAL sql_mode = '';");
                    return true;
                }
            } catch (\Throwbale $e) {
                $this->message = "Database connection was not established. " . $e->getMessage();
            }
        }
        $this->connection = null;
        return false;
    }

    /**
     * Sanitize identifiers
     * @param string $identifier
     * @return string
     */
    private function sanitizeIdentifier(string $identifier): string
    {
        return $this->escape(str_replace([' ', '/', '\\', '-'], '', $identifier));
    }

    /**
     * Escape a database row data from fields count
     * @param array $row
     * @param int $fieldCount
     * @return string
     */
    private function escapeExportRowData(array $row, int $fieldCount): string
    {
        for ($i = 0; $i < $fieldCount; $i++) {
            $row[$i] = str_replace("'", "''", preg_replace("/\n/", "\\n", $row[$i]));
            $row[$i] = isset($row[$i]) ? (is_numeric($row[$i]) ? $row[$i] : "'$row[$i]'") : "''";
        }
        return implode(', ', $row);
    }

    /**
     * Create a database export file content
     * @param string $database
     * @return string|null
     */
    private function createDatabaseExportContent(string $database): ?string
    {
        $tables = $this->getDatabaseTables($database);
        if (empty($tables)) {
            return null;
        }
        $backupContent = "\n\n--\n-- Database: `$database`\n--";
        foreach ($tables as $table) {
            $backupContent .= $this->getTableExportStructureData($database, $table);
            $backupContent .= $this->getTableExportInsertData($database, $table);
        }
        return $this->wrapDatabaseExportContent($backupContent);
    }

    /**
     * Wrap a database export file content
     * @param string $content
     * @return string
     */
    private function wrapDatabaseExportContent(string $content): string
    {
        $header = "-- ---------------------------------------------------------\n";
        $header .= "-- SQL Dump\n";
        $header .= "-- Host Connection Info: " . $this->hostInfo() . "\n";
        $header .= "-- Generation Time: " . date('F d, Y \a\t H:i A ( e )') . "\n";
        $header .= "-- Server version: " . $this->serverInfo() . "\n";
        $header .= "-- PHP Version: " . PHP_VERSION . "\n";
        $header .= "-- ---------------------------------------------------------\n\n";
        $header .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $header .= "SET time_zone = \"+00:00\";\n";
        $header .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
        $header .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
        $header .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
        $header .= "/*!40101 SET NAMES utf8 */;\n\n";
        $footer = "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
        $footer .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
        $footer .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
        return $header . $content . "\n" . $footer;
    }
}
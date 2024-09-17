<?php

namespace PHPWebfuse\Instance;

use PHPWebfuse\File;
use \PHPWebfuse\Utils;
use \PHPWebfuse\Path;

/**
 * @author Senestro
 */
class Database {
    // PRIVATE VARIABLES

    /**
     * @var \mysqli The database connection instance
     */
    private static ?\mysqli $connection = null;

    /**
     * @var string The database connection host
     */
    private ?string $host = null;

    /**
     * @var string The database connection username
     */
    private ?string $user = null;

    /**
     * @var string The database connection password
     */
    private ?string $password = null;

    // PUBLIC VARIABLES

    /**
     * @var string Messages are stored here
     */
    public string $message = "";

    // PUBLIC METHODS

    /**
     * Construct new Database instance and initialize a new database connection
     * @param string|null $host
     * @param string|null $user
     * @param string|null $password
     * @param bool $reset True to force reset the connection
     */
    public function __construct(?string $host = null, ?string $user = null, ?string $password = null, bool $reset = false) {
        if (!in_array('mysqli', get_declared_classes())) {
            $this->message = "The mysqli class doesn't exist or wasn't found.";
        } else if (Utils::isNotEmptyString($host) && Utils::isNotEmptyString($user) && Utils::isNotEmptyString($password)) {
            if (Utils::isNull(self::$connection) || Utils::isTrue($reset)) {
                $this->init($host, $user, $password, $reset);
            }
        }
    }

    /**
     * Close the database connection and reset the connection variable to default (null)
     */
    public function __destruct() {
        if (Utils::isNonNull(self::$connection)) {
            @self::$connection->close();
            self::$connection = null;
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
    public function connect(string $host, string $user, string $password, bool $reset = false): bool {
        return $this->init($host, $user, $password, $reset);
    }

    /**
     * Get the connection instance
     * @return \mysqli|null
     */
    public function connection(): \mysqli | null {
        return self::$connection;
    }

    public function lastInsertID(): int|string {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->insert_id;
        }
        return "";
    }

    /**
     *  Select a database
     * @param string $database
     * @return bool
     */
    public function selectDb(string $database): bool {
        if (Utils::isNonNull(self::$connection)) {
            return @self::$connection->select_db($database);
        }
        return false;
    }

    /**
     * Get the last error
     * @return string
     */
    public function lastError(): string {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->error;
        }
        return "";
    }

    /**
     * Get the connection host info
     * @return string
     */
    public function hostInfo(): string {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->host_info;
        }
        return "";
    }

    /**
     * Get the connection server info
     * @return string
     */
    public function serverInfo(): string {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->server_info;
        }
        return "";
    }

    /**
     * Get the connection server info
     * @return string
     */
    public function serverVersion(): string {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->server_version;
        }
        return "";
    }

    /**
     * Get the last query info
     * @return string
     */
    public function lastQueryInfo(): string {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->info;
        }
        return "";
    }

    /**
     * Get the connection protocol version
     * @return int|string
     */
    public function protocolVersion(): int|string {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->protocol_version;
        }
        return "";
    }

    /**
     * Escape characters
     * @param string $string
     * @return string
     */
    public function escape(string $string): string {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->real_escape_string($string);
        }
        return $string;
    }

    /**
     * Run a query
     * @param string $query The query string
     * @return mixed
     */
    public function query(string $query): mixed {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->query($query);
        }
        return \null;
    }

    /**
     * Prepare a query
     * @param string $query The query string
     * @return mixed
     */
    public function prepareQuery(string $query): mixed {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->prepare($query);
        }
        return \null;
    }

    /**
     * Multi query
     * @param string $query The query string
     * @return mixed
     */
    public function multiQuery(string $query): mixed {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->multi_query($query);
        }
        return \null;
    }

    /**
     * Create database
     * @param string $name The database name
     * @param string $character Default to 'latin1'
     * @param string $collate Default to 'latin1_general_ci'
     * @return bool
     */
    public function createDatabase(string $name, string $character = "latin1", string $collate = " latin1_general_ci"): bool {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->query("CREATE DATABASE IF NOT EXISTS `" . strtolower($name) . "` DEFAULT CHARACTER SET " . $character . " COLLATE " . $collate . ";");
        }
        return false;
    }

    /**
     * Delete database
     * @param string $name The database name
     * @return bool
     */
    public function deleteDatabase(string $name): bool {
        if (Utils::isNonNull(self::$connection)) {
            return self::$connection->query("DROP DATABASE IF EXISTS `" . strtolower($name) . "`");
        }
        return false;
    }

    /**
     * Check if a database exist
     * @param string $database The database name
     * @return bool
     */
    public function doesDatabaseExist(string $database): bool {
        if (Utils::isNonNull(self::$connection)) {
            $result = self::$connection->query("SHOW DATABASES LIKE `" . strtolower($database) . "`");
            return $result && $result->num_rows > 0;
        }
        return false;
    }

    /**
     * Optimize database's
     * @param array $databases The database's
     * @return array
     */
    public function optimiseDatabases(array $databases): array {
        $result = array();
        if (Utils::isNonNull(self::$connection)) {
            foreach ($databases as $database) {
                $database = $this->sanitizeIdentifier($database);
                $status = self::$connection->query("SHOW TABLE STATUS FROM " . $database . ";");
                if ($status) {
                    while ($row = $status->fetch_assoc()) {
                        $dataFree = $row['Data_free'];
                        $table = $row['Name'];
                        $result[$database][$table] = "";
                        if ($dataFree > 0) {
                            if (@self::$connection->query("OPTIMIZE TABLE `" . $database . "." . $table . "`")) {
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
     * @param string $database The database name
     * @param string $table The database table name
     * @return bool
     */
    public function deleteDatabaseTable(string $database, string $table): bool {
        if (Utils::isNonNull(self::$connection) && $this->doesDatabaseTableExist($database, $table)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            return self::$connection->query('DROP TABLE IF EXISTS ' . $database . '.' . $table . ';');
        }
        return false;
    }

    /**
     * Check if a database table exist
     * @param string $database The database name
     * @param string $table The database table name
     * @return bool
     */
    public function doesDatabaseTableExist(string $database, string $table): bool {
        if (Utils::isNonNull(self::$connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $tables = self::$connection->query('SHOW TABLES FROM ' . $database . ';');
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
     * @param string $database The database name
     * @param string $table The database table name
     * @return bool
     */
    public function truncateDatabaseTable(string $database, string $table): bool {
        if (Utils::isNonNull(self::$connection) && $this->doesDatabaseTableExist($database, $table)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            return self::$connection->query('TRUNCATE ' . $database . '.' . $table . ';');
        }
        return false;
    }

    /**
     * Insert into database table
     * @param string $database The database name
     * @param string $table The database table name
     * @param array $data The database table array data
     * @param bool $preprare If to prepare or directly execute the query
     * @return bool
     *
     * Example: insertToDatabaseTable("main_db", "users_table", array('id'=>1, 'timestamp'=>123456789));
     */
    public function insertToDatabaseTable(string $database, string $table, array $data = array(), bool $preprare = true): bool {
        if (Utils::isNonNull(self::$connection) && $this->doesDatabaseTableExist($database, $table)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $fields = $values = array();
            foreach ($data as $index => $value) {
                if (!Utils::isEmptyString($index) && !Utils::isEmptyString($value)) {
                    $fields[] = $index;
                    $values[] = $value;
                }
            }
            $statement = "INSERT IGNORE INTO " . $database . "." . $table . " (`" . implode("`, `", $fields) . "`) VALUES (";
            $rows = "";
            foreach ($values as $value) {
                if (Utils::isInt($value)) {
                    $rows .= $this->escape($value) . ', ';
                } elseif (Utils::isString($value)) {
                    $rows .= '"' . $this->escape($value) . '", ';
                } else {
                    $rows .= '"' . $this->escape((string) $value) . '", ';
                }
            }
            $statement .= substr(trim($rows), -1) == "," ? substr(trim($rows), 0, -1) : $rows;
            $statement .= ");";
            $result = $preprare === true ? self::$connection->prepare($statement) : self::$connection->query($statement);
            if (!$result || (isset($result->affected_rows) && $result->affected_rows < 1)) {
                $this->message = $this->lastError();
            }
            return $result;
        }
        return false;
    }

    /**
     * Create a database table
     * @param string $database The database name
     * @param string $table The database table name
     * @param array $columns Defaults to 'array()'
     * @param string $comment Defaults to ''
     * @param string $engine Defaults to 'MyISAM'
     * @param string $character Defaults to 'latin1'
     * @param string $collate Defaults to 'latin1_general_ci'
     * @param bool $autoincrement If to set it as auto increment, default to 'true'.
     *
     * Example: createDatabaseTable("main_db", "users_table", array("id" => "bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT ''", "timestamp" => "bigint(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT ''", "PRIMARY KEY" => "(id)"), "users_table_comment");
     * @return bool
     */
    public function createDatabaseTable(string $database, string $table, array $columns = array(), string $comment = '', string $engine = "MyISAM", string $character = "latin1", string $collate = "latin1_general_ci", bool $autoincrement = true): bool {
        if (Utils::isNonNull(self::$connection) && Utils::isFalse($this->doesDatabaseTableExist($database, $table))) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $statement = "CREATE TABLE IF NOT EXISTS " . $database . "." . $table . " (";
            foreach ($columns as $name => $value) {
                $statement .= "" . $name . " " . $value . ", ";
            }
            $statement = rtrim($statement, ", ");
            $statement .= ")";
            $autoincrementValue = $autoincrement ? ' AUTO_INCREMENT=1' : '';
            $statement .= ' ENGINE=' . $engine . ' DEFAULT CHARSET=' . $character . ' COLLATE=' . $collate . '' . $autoincrementValue . '';
            $statement .= strlen($comment) > 0 ? ' COMMENT "' . $this->escape($comment) . '";' : ';';
            $created = self::$connection->query($statement);
            if (Utils::isNotTrue($created)) {
                $this->message = $this->lastError();
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Arrange database's tables
     * @param array $databases The database's
     * @return array
     */
    public function arrangeDatabaseTables(array $databases): array {
        $messages = array();
        if (Utils::isNonNull(self::$connection)) {
            foreach ($databases as $database) {
                $database = $this->sanitizeIdentifier($database);
                $messages[$database] = array();
                $tables = self::$connection->query('SHOW TABLES FROM ' . $database . ';');
                if ($tables && self::$connection->select_db($database)) {
                    while ($ft = $tables->fetch_array()) {
                        $table = $ft[0];
                        $messages[$database][$table] = "";
                        $tablestatements = array();
                        $columnsdata = array();
                        $columns = self::$connection->query('SHOW FULL COLUMNS FROM ' . $table . ';');
                        if ($columns) {
                            try {
                                while ($ft = $columns->fetch_assoc()) {
                                    $columnsdata[] = $ft;
                                }
                            } catch (\Exception $e) {
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
                                if (self::$connection->query($statement)) {
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
     * @param string $database The database name
     * @param string $table The database table name
     * @return array
     */
    public function getTableColumns(string $database, string $table): array {
        $result = array();
        if (Utils::isNonNull(self::$connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $columns = self::$connection->query('SHOW FULL COLUMNS FROM ' . $database . '.' . $table . ';');
            if ($columns) {
                try {
                    while ($ft = $columns->fetch_assoc()) {
                        $result[] = $ft;
                    }
                } catch (\Exception $e) {
                }
            }
        }
        return $result;
    }

    /**
     * Get all table rows from a 'where' statement
     * @param string $database The database name
     * @param string $table The database table name
     * @param string $column The database table column name
     * @param mixed $columnvalue The database column value name
     * @param string $orderBy The order by
     * @return array
     *
     * Example: getTableRowsWhereClause("main_db", "users_table", "country" "Nigeria", "state");
     */
    public function getTableRowsWhereClause(string $database, string $table, string $column, mixed $columnvalue, string $orderBy = "id"): array {
        $result = array();
        if (Utils::isNonNull(self::$connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $columnvalue = $this->escape($columnvalue);
            $statement = "SELECT * FROM " . $database . "." . $table . " WHERE `" . $column . "`='" . $this->escape($columnvalue) . "' ORDER BY " . $orderBy . ";";
            $result = $this->executeAndFetchAssociationFromSelectStatement($statement);
        }
        return $result;
    }

    /**
     * Get all table rows
     * @param string $database The database name
     * @param string $table The database table name
     * @param string $orderBy The order by
     * @return array
     */
    public function getTableRows(string $database, string $table, string $orderBy = "id"): array {
        $result = array();
        if (Utils::isNonNull(self::$connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $statement = "SELECT * FROM " . $database . "." . $table . " ORDER BY " . $orderBy . ";";
            $result = $this->executeAndFetchAssociationFromSelectStatement($statement);
        }
        return $result;
    }

    /**
     * Execute a database where 'where' statement (where id=1) and return a single index value if found
     * @param string $database The database name
     * @param string $table The database table name
     * @param string $column The database table column name
     * @param mixed $columnvalue The database column value name
     * @param mixed $index The index to match from rows
     * @return string
     *
     * Example: getTableRowsIndexValue("main_db", "users_table", "country", "Nigeria", "id");
     */
    public function getTableRowsIndexValue(string $database, string $table, string $column, mixed $columnvalue, mixed $index): string {
        $value = "";
        if (Utils::isNonNull(self::$connection)) {
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
    public function executeAndFetchAssociationFromSelectStatement(string $statement): array {
        $result = array();
        if (Utils::isNonNull(self::$connection)) {
            if (Utils::startsWith("SELECT", strtoupper($statement))) {
                $select = self::$connection->query($statement);
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
     * @param string $database The database name
     * @return array
     */
    public function getDatabaseTables(string $database): array {
        $result = array();
        if (Utils::isNonNull(self::$connection)) {
            $database = $this->sanitizeIdentifier($database);
            $tables = self::$connection->query("SHOW TABLES FROM " . $database . ";");
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
     * @param string $database The database name
     * @param string $table The database table name
     * @return string
     */
    public function getTableExportStructureData(string $database, string $table): string {
        $structure = '';
        if (Utils::isNonNull(self::$connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $result = self::$connection->query("SHOW CREATE TABLE " . $database . "." . $table . ";");
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
     * @param string $database The database name
     * @param string $table The database table name
     * @return string
     */
    public function getTableExportInsertData(string $database, string $table): string {
        $data = '';
        if (Utils::isNonNull(self::$connection)) {
            $database = $this->sanitizeIdentifier($database);
            $table = $this->sanitizeIdentifier($table);
            $result = self::$connection->query("SELECT * FROM " . $database . "." . $table . ";");
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
     * @param array $databases The database's name
     * @param string|null $savePathname Where to save the exported database's
     * @return array
     */
    public function exportDatabases(array $databases, ?string $savePathname = null): array {
        $messages = [];
        $savePathname = Utils::isNull($savePathname) ? dirname(__DIR__) : $savePathname;
        if (File::isNotDir($savePathname)) {
            File::createDir($savePathname);
        }
        $savePathname = File::createDir($savePathname);
        $savePathname = Path::insert_dir_separator($savePathname);
        // Iterate through each database and back it up
        foreach ($databases as $database) {
            $database = $this->sanitizeIdentifier($database);
            $backupContent = $this->createDatabaseExportContent($database);
            // If backup content is generated, save it to file
            if (Utils::isString($backupContent)) {
                File::createDir($savePathname);
                $absolutePath = Path::convert_dir_separators($savePathname) . "database-backup-[" . $database . "].sql";
                // Save the backup content to a file
                if (File::saveContentToFile($absolutePath, $backupContent)) {
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
     * @param bool $reset If to force reset the connection
     * @return bool
     */
    private function init(string $host, string $user, string $password, bool $reset = false): bool {
        if (Utils::isNull(self::$connection) || Utils::isTrue($reset)) {
            try {
                mysqli_report(MYSQLI_REPORT_STRICT);
                self::$connection = new \mysqli($host, $user, $password);
                if (self::$connection instanceof \mysqli) {
                    $this->host = $host;
                    $this->user = $user;
                    $this->password = $password;
                    /* Set the desired charset, time_zone, and sql_mode after establishing a connection */
                    @self::$connection->set_charset('utf8mb4');
                    @self::$connection->query("SET time_zone='" . Utils::GMT . "'");
                    @self::$connection->query("SET GLOBAL sql_mode = '';");
                    return true;
                }
            } catch (\Exception $e) {
                $this->message = "Database connection was not established. " . $e->getMessage();
            }
        }
        self::$connection = null;
        return false;
    }

    /**
     * Sanitize identifiers
     * @param string $identifier
     * @return string
     */
    private function sanitizeIdentifier(string $identifier): string {
        return $this->escape(str_replace([' ', '/', '\\', '-'], '', $identifier));
    }

    /**
     * Escape a database row data from fields count
     * @param array $row
     * @param int $fieldCount
     * @return string
     */
    private function escapeExportRowData(array $row, int $fieldCount): string {
        for ($i = 0; $i < $fieldCount; $i++) {
            $row[$i] = str_replace("'", "''", preg_replace("/\n/", "\\n", $row[$i]));
            $row[$i] = isset($row[$i]) ? (is_numeric($row[$i]) ? $row[$i] : "'$row[$i]'") : "''";
        }
        return implode(', ', $row);
    }

    /**
     * Create a database export file content
     * @param string $database The database name
     * @return string|null
     */
    private function createDatabaseExportContent(string $database): ?string {
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
    private function wrapDatabaseExportContent(string $content): string {
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

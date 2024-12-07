<?php

namespace PHPWebfuse\Instance;

use PHPWebfuse\File;
use \PHPWebfuse\Utils;
use \PHPWebfuse\Path;

/**
 * @author Senestro
 */
class Database extends Utils {
    // PRIVATE VARIABLES
    // PUBLIC VARIABLES
    public static ?\Throwable $lastThrowable = null;

    /**
     * @var \mysqli The database connection instance
     */
    private ?\mysqli $connection = null;

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
     * @var string Last messages are stored here
     */
    public string $lastMessage = "";

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
            $this->lastMessage = "The mysqli class doesn't exist or wasn't found.";
        } else if (Utils::isNotEmptyString($host) && Utils::isNotEmptyString($user) && Utils::isNotEmptyString($password)) {
            $this->init($host, $user, $password, $reset);
        }
    }

    /**
     * Close the database connection and reset the connection variable to default (null)
     */
    public function __destruct() {
        if (Utils::isNonNull($this->connection)) {
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
    public function connect(string $host, string $user, string $password, bool $reset = false): bool {
        $this->init($host, $user, $password, $reset);
        return Utils::isNonNull($this->connection);
    }

    /**
     * Get the connection instance
     * @return \mysqli|null
     */
    public function connection(): \mysqli | null {
        return $this->connection;
    }

    /**
     * Get the last insert ID
     * @return int|string
     */
    public function lastInsertID(): int|string {
        return Utils::isNonNull($this->connection) ?  $this->connection->insert_id : -1;
    }

    /**
     *  Select a database
     * @param string $database
     * @return bool
     */
    public function selectDb(string $database): bool {
        return Utils::isNonNull($this->connection) ? @$this->connection->select_db($database) : false;
    }

    /**
     * Get the last error
     * @return string
     */
    public function lastError(): string {
        return Utils::isNonNull($this->connection) ? $this->connection->error : "";
    }

    /**
     * Get the connection host info
     * @return string
     */
    public function hostInfo(): string {
        return Utils::isNonNull($this->connection) ?  $this->connection->host_info : "";
    }

    /**
     * Get the connection server info
     * @return string
     */
    public function serverInfo(): string {
        return Utils::isNonNull($this->connection) ? $this->connection->server_info : "";
    }

    /**
     * Get the connection server version or -1
     * @return string
     */
    public function serverVersion(): int {
        return Utils::isNonNull($this->connection) ? $this->connection->server_version : -1;
    }

    /**
     * Get the last query info
     * @return ?string
     */
    public function lastQueryInfo(): ?string {
        return Utils::isNonNull($this->connection) ? $this->connection->info : \null;
    }

    /**
     * Get the connection protocol version or -1
     * @return int
     */
    public function protocolVersion(): int {
        return Utils::isNonNull($this->connection) ? $this->connection->protocol_version : -1;
    }

    /**
     * Escape characters
     * @param string $string
     * @return string
     */
    public function escape(string $string): string {
        return Utils::isNonNull($this->connection) ? $this->connection->real_escape_string($string) : $string;
    }

    /**
     * Run a query
     * @param string $query The query string
     * @return \mysqli_result|bool
     */
    public function query(string $query): \mysqli_result|bool {
        return Utils::isNonNull($this->connection) ? $this->connection->query($query) : false;
    }

    /**
     * Prepare a query
     * @param string $query The query string
     * @return \mysqli_stmt|bool
     */
    public function prepare(string $query): \mysqli_stmt|false {
        return Utils::isNonNull($this->connection) ? $this->connection->prepare($query) : false;
    }

    /**
     * Multi query
     * @param string $query The query string
     * @return bool
     */
    public function multiQuery(string $query): bool {
        return Utils::isNonNull($this->connection) ? $this->connection->multi_query($query) : false;
    }

    /**
     * Create database
     * @param string $name The database name
     * @param string $character Default to 'latin1'
     * @param string $collate Default to 'latin1_general_ci'
     * @return bool
     */
    public function createDatabase(string $name, string $character = "latin1", string $collate = " latin1_general_ci"): bool {
        if (Utils::isNonNull($this->connection)) {
            return $this->connection->query("CREATE DATABASE IF NOT EXISTS `" . strtolower($name) . "` DEFAULT CHARACTER SET " . $character . " COLLATE " . $collate . ";");
        }
        return false;
    }

    /**
     * Delete database
     * @param string $name The database name
     * @return bool
     */
    public function deleteDatabase(string $name): bool {
        return Utils::isNonNull($this->connection) ?  $this->connection->query("DROP DATABASE IF EXISTS `" . strtolower($name) . "`") : false;
    }

    /**
     * Check if a database exist
     * @param string $database The database name
     * @return bool
     */
    public function doesDatabaseExist(string $database): bool {
        if (Utils::isNonNull($this->connection)) {
            $result = $this->connection->query("SHOW DATABASES LIKE `" . strtolower($database) . "`");
            return $result instanceof \mysqli_result && $result->num_rows > 0;
        }
        return false;
    }

    /**
     * Optimize databases
     * @param array $databases The databases
     * @return array
     */
    public function optimizeDatabases(array $databases): array {
        $result = [];
        if (Utils::isNonNull($this->connection)) {
            foreach ($databases as $database) {
                $database = $this->escapeReplace($database);
                $status = $this->connection->query("SHOW TABLE STATUS FROM " . $database . ";");
                if ($status instanceof \mysqli_result) {
                    while ($row = $status->fetch_assoc()) {
                        $table = $row['Name'];
                        $dataFree = $row['Data_free'];
                        $result[$database][$table] = $dataFree > 0 ?  Utils::isNotFalse($this->connection->query("OPTIMIZE TABLE `" . $database . "." . $table . "`")) : true;
                    }
                    $status->free();
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
        if (Utils::isNonNull($this->connection)) {
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            return $this->connection->query('DROP TABLE IF EXISTS ' . $database . '.' . $table . ';');
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
        if (Utils::isNonNull($this->connection)) {
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            $tables = $this->connection->query('SHOW TABLES FROM ' . $database . ';');
            if ($tables instanceof \mysqli_result && $tables->num_rows > 0) {
                while ($row = $tables->fetch_array()) {
                    // First column contains the table name
                    if ($row[0] == $table) {
                        return true;
                    }
                }
                $tables->free();
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
        if (Utils::isNonNull($this->connection)) {
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            $this->connection->query('TRUNCATE ' . $database . '.' . $table . ';');
            return  $this->connection->affected_rows > 0;
        }
        return false;
    }

    /**
     * Insert into database table
     *
     * @param string $database The database name
     * @param string $table The database table name
     * @param array $data The database table array data
     * @param bool $prepare If to prepare or directly execute the query
     * @param array $onDuplicate The ON DUPLICATE KEY UPDATE clause
     * @return bool|\mysqli_stmt
     */
    public function insertToDatabaseTable(string $database, string $table, array $data = [], bool $prepare = true, array $onDuplicate = []): bool|\mysqli_stmt {
        if (Utils::isNonNull($this->connection) && Utils::isNotEmptyArray($data)) {
            // Sanitize identifiers
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            // Prepare fields and values
            $fields = $values = [];
            foreach ($data as $key => $value) {
                if (!Utils::isEmptyString($key)) {
                    $fields[] = $key;
                    $values[] = Utils::isInt($value) ? $this->escape($value) : '"' . $this->escape((string) $value) . '"';
                }
            }
            // Build the SQL statement
            $statement = sprintf("INSERT IGNORE INTO %s.%s (`%s`) VALUES (%s)", $database, $table, implode("`, `", $fields), implode(", ", $values));
            // Add ON DUPLICATE KEY UPDATE clause if provided
            if (Utils::isNotEmptyArray($onDuplicate)) {
                $updateFields = [];
                foreach ($onDuplicate as $key => $value) {
                    $updateFields[] = sprintf("%s = %s", $key, $this->escape($value));
                }
                $statement .= " ON DUPLICATE KEY UPDATE " . implode(", ", $updateFields);
            }
            $statement .= ";";
            // Execute the statement
            $result = $prepare ? $this->connection->prepare($statement) : $this->connection->query($statement);
            if ($result instanceof \mysqli_stmt || Utils::isTrue($result)) {
                return $result instanceof \mysqli_stmt ? $result : $this->connection->affected_rows > 0;
            } else {
                $this->lastMessage = $this->lastError();
            }
        }
        return false;
    }

    /**
     * Updates rows in a specified database table based on provided data and conditions.
     *
     * @param string $database  The name of the database.
     * @param string $table     The name of the table to update.
     * @param array $data       An associative array of column-value pairs to set in the update.
     * @param array $whereKeys  An associative array of column-value pairs for the WHERE clause.
     * @param bool $prepare     If to prepare or directly execute the query.
     * @return bool|\mysqli_stmt
     */
    public function updateDatabaseTable(string $database, string $table, array $data = [], array $whereKeys = [], bool $prepare = true): bool|\mysqli_stmt {
        if (Utils::isNonNull($this->connection) && Utils::isNotEmptyArray($data) && Utils::isNotEmptyArray($whereKeys)) {
            // Sanitize identifiers
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            // Prepare fields and values for the UPDATE statement
            $updateFields = [];
            foreach ($data as $key => $value) {
                if (!Utils::isEmptyString($key)) {
                    $escaped = $this->escape($value);
                    $updateFields[] = sprintf("`%s` = %s", $key, Utils::isString($value) ? "\"$escaped\"" : $escaped);
                }
            }
            // Prepare the WHERE clause
            $whereFields = [];
            foreach ($whereKeys as $key => $value) {
                if (!Utils::isEmptyString($key)) {
                    $escaped = $this->escape($value);
                    $whereFields[] = sprintf("`%s` = %s", $key, Utils::isString($value) ? "\"$escaped\"" : $escaped);
                }
            }
            // Build the UPDATE statement
            $statement = sprintf("UPDATE %s.%s SET %s WHERE %s;", $database, $table, implode(", ", $updateFields), implode(" AND ", $whereFields));
            // Execute the statement
            $result = $prepare ? $this->connection->prepare($statement) : $this->connection->query($statement);
            if ($result instanceof \mysqli_stmt || Utils::isTrue($result)) {
                return $result instanceof \mysqli_stmt ? $result : $this->connection->affected_rows > 0;
            } else {
                $this->lastMessage = $this->lastError();
            }
        }
        return false;
    }


    /**
     * Updates rows in a specified database table based on provided data.
     *
     * @param string $database  The name of the database.
     * @param string $table     The name of the table to update.
     * @param array $data       An associative array of column-value pairs to set in the update.
     * @param bool $prepare     If to prepare or directly execute the query.
     * @return bool|\mysqli_stmt
     */
    public function updateDatabaseTableRows(string $database, string $table, array $data = [], bool $prepare = true): bool|\mysqli_stmt {
        if (Utils::isNonNull($this->connection) && Utils::isNotEmptyArray($data)) {
            // Sanitize database and table names
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            // Prepare fields and values for the UPDATE statement
            $updateFields = [];
            foreach ($data as $key => $value) {
                if (!Utils::isEmptyString($key)) {
                    $escaped = $this->escape($value);
                    $updateFields[] = sprintf("`%s` = %s", $key, Utils::isString($value) ? "\"$escaped\"" : $escaped);
                }
            }
            // Build the UPDATE statement
            $statement = sprintf("UPDATE %s.%s SET %s;", $database, $table, implode(", ", $updateFields));
            // Execute the statement
            $result = $prepare ? $this->connection->prepare($statement) : $this->connection->query($statement);
            if ($result instanceof \mysqli_stmt || Utils::isTrue($result)) {
                return $result instanceof \mysqli_stmt ? $result : $this->connection->affected_rows > 0;
            } else {
                $this->lastMessage = $this->lastError();
            }
        }
        return false;
    }



    /**
     * Deletes a specific row from a specified database table based on provided conditions.
     *
     * @param string $database  The name of the database.
     * @param string $table     The name of the table to delete from.
     * @param array $whereKeys  An associative array of column-value pairs for the WHERE clause.
     * @param bool $prepare     If to prepare or directly execute the query.
     * @return bool|\mysqli_stmt
     */
    public function deleteDatabaseTableRow(string $database, string $table, array $whereKeys = [], bool $prepare = true): bool|\mysqli_stmt {
        if (Utils::isNonNull($this->connection) && Utils::isNotEmptyArray($whereKeys)) {
            // Sanitize database and table names
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            // Prepare the WHERE clause
            $whereFields = [];
            foreach ($whereKeys as $key => $value) {
                if (!Utils::isEmptyString($key)) {
                    $escaped = $this->escape($value);
                    $whereFields[] = sprintf("`%s` = %s", $key, Utils::isString($value) ? "\"$escaped\"" : $escaped);
                }
            }
            // Build the DELETE statement
            $statement = sprintf("DELETE FROM %s.%s WHERE %s;", $database, $table, implode(" AND ", $whereFields));
            // Execute the statement
            $result = $prepare ? $this->connection->prepare($statement) : $this->connection->query($statement);
            if ($result instanceof \mysqli_stmt || Utils::isTrue($result)) {
                return $result instanceof \mysqli_stmt ? $result : $this->connection->affected_rows > 0;
            } else {
                $this->lastMessage = $this->lastError();
            }
        }
        return false;
    }

    /**
     * Deletes all rows from a specified database table.
     *
     * @param string $database  The name of the database.
     * @param string $table     The name of the table to delete from.
     * @param bool $prepare     If to prepare or directly execute the query.
     * @return bool|\mysqli_stmt
     */
    public function deleteDatabaseTableRows(string $database, string $table, bool $prepare = true): bool|\mysqli_stmt {
        if (Utils::isNonNull($this->connection)) {
            // Sanitize database and table names
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            // Build the DELETE statement
            $statement = sprintf("DELETE FROM %s.%s;", $database, $table);
            // Execute the statement
            $result = $prepare ? $this->connection->prepare($statement) : $this->connection->query($statement);
            if ($result instanceof \mysqli_stmt || Utils::isTrue($result)) {
                return $result instanceof \mysqli_stmt ? $result : $this->connection->affected_rows > 0;
            } else {
                $this->lastMessage = $this->lastError();
            }
        }
        return false;
    }

    /**
     * Create a database table
     * 
     * @param string $database The database name
     * @param string $table The database table name
     * @param array $columns Defaults to 'array()' - an associative array of column names and their definitions
     * @param string $comment Defaults to '' - the comment for the table
     * @param string $engine Defaults to 'MyISAM' - the database engine to use
     * @param string $character Defaults to 'latin1' - the character set to use
     * @param string $collate Defaults to 'latin1_general_ci' - the collation to use
     * @param bool $autoIncrement If to set the primary key as auto increment, default to 'true'
     * 
     * @return bool - true if the table was created successfully, false otherwise
     */
    public function createDatabaseTable(string $database, string $table, array $columns = [], string $comment = '', string $engine = "MyISAM", string $character = "latin1", string $collate = "latin1_general_ci", bool $autoIncrement = true): bool {
        if (Utils::isNonNull($this->connection)) {
            // Sanitize the database and table names
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            // Build the CREATE TABLE statement
            $columnsDefinition = [];
            foreach ($columns as $name => $definition) {
                $columnsDefinition[] = "$name $definition";
            }
            $statement = sprintf("CREATE TABLE IF NOT EXISTS %s.%s (%s) ENGINE=%s DEFAULT CHARSET=%s COLLATE=%s%s", $database, $table, implode(", ", $columnsDefinition), $engine, $character, $collate, $autoIncrement ? " AUTO_INCREMENT=1" : "");
            // Add the table comment if specified
            if (Utils::isNotEmptyString($comment)) {
                $statement .= " COMMENT \"" . $this->escape($comment) . "\"";
            }
            // Execute the statement
            return $this->connection->query($statement);
        }
        return false;
    }


    /**
     * Arrange database's tables
     * 
     * @param array $databases The databases
     * @return array
     */
    public function arrangeDatabaseTables(array $databases): array {
        $messages = [];
        if (Utils::isNonNull($this->connection)) {
            foreach ($databases as $database) {
                $database = $this->escapeReplace($database);
                $messages[$database] = [];
                // Get list of tables in the database
                $tables = $this->connection->query('SHOW TABLES FROM ' . $database . ';');
                if ($tables instanceof \mysqli_result && $this->connection->select_db($database)) {
                    // Process each table
                    while ($table = $tables->fetch_array()) {
                        $tableName = $table[0];
                        $messages[$database][$tableName] = "";
                        $statements = [];
                        $columns = [];
                        // Get column details for the table
                        $columns = $this->connection->query('SHOW FULL COLUMNS FROM ' . $tableName . ';');
                        if ($columns instanceof \mysqli_result) {
                            while ($column = $columns->fetch_assoc()) {
                                $columns[] = $column;
                            }
                            // Build table alteration statements based on column data
                            foreach ($columns as $data) {
                                $field = $data['Field'];
                                $type = $data['Type'];
                                $collation = $data['Collation'];
                                $null = $data['Null'];
                                $key = $data['Key'];
                                $default = $data['Default'];
                                $extra = $data['Extra'];
                                $comment = $data['Comment'];
                                // Primary key alteration
                                if ($field == "id" && $key == "PRI") {
                                    $statements[$tableName] = "ALTER TABLE `" . $tableName . "` CHANGE `" . $field . "` `" . $field . "` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;";
                                }
                                // Integer or BigInt columns
                                elseif (substr($type, 0, 3) == 'int' || substr($type, 0, 6) == 'bigint') {
                                    $statements[$tableName] = "ALTER TABLE `" . $tableName . "` CHANGE `" . $field . "` `" . $field . "` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '" . $comment . "';";
                                }
                                // Varchar or Char columns with collation
                                elseif ((substr($type, 0, 7) == 'varchar' || substr($type, 0, 4) == 'char') && $collation !== "NULL") {
                                    $statements[$tableName] = "ALTER TABLE `" . $tableName . "` CHANGE `" . $field . "` `" . $field . "` " . strtoupper($type) . " CHARACTER SET latin1 COLLATE " . $collation . " NOT NULL DEFAULT '' COMMENT '" . $comment . "';";
                                }
                                // Text columns with collation
                                elseif (substr($type, 0, 4) == 'text' && $collation !== "NULL") {
                                    $statements[$tableName] = "ALTER TABLE `" . $tableName . "` CHANGE `" . $field . "` `" . $field . "` " . strtoupper($type) . " CHARACTER SET latin1 COLLATE " . $collation . " NOT NULL COMMENT '" . $comment . "';";
                                }
                            }
                            // Execute each statement and track success
                            foreach ($statements as $statement) {
                                if ($this->connection->query($statement)) {
                                    $this->connection->commit();
                                    $messages[$database][$tableName] = true;
                                } else {
                                    $messages[$database][$tableName] = "Failed to arrange table: " . $this->lastError();
                                }
                            }
                        }
                    }
                    // Reset the connection for the next database
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
        if (Utils::isNonNull($this->connection)) {
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            $columns = $this->connection->query('SHOW FULL COLUMNS FROM ' . $database . '.' . $table . ';');
            if ($columns instanceof \mysqli_result) {
                try {
                    while ($ft = $columns->fetch_assoc()) {
                        $result[] = $ft;
                    }
                } catch (\Exception $e) {
                    self::setLastThrowable($e);
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
     * @param mixed $columnValue The database column value name
     * @param string $orderBy The order by
     * @return array
     *
     * Example: getTableRowsWhereClause("main_db", "users_table", "country" "Nigeria", "state");
     */
    public function getTableRowsWhereClause(string $database, string $table, string $column, mixed $columnValue, string $orderBy = "id"): array {
        $result = array();
        if (Utils::isNonNull($this->connection)) {
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            $columnValue = $this->escape($columnValue);
            $statement = "SELECT * FROM " . $database . "." . $table . " WHERE `" . $column . "`='" . $this->escape($columnValue) . "' ORDER BY " . $orderBy . ";";
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
        if (Utils::isNonNull($this->connection)) {
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
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
     * @param mixed $columnValue The database column value name
     * @param mixed $index The index to match from rows
     * @return string
     *
     * Example: getTableRowsIndexValue("main_db", "users_table", "country", "Nigeria", "id");
     */
    public function getTableRowsIndexValue(string $database, string $table, string $column, mixed $columnValue, mixed $index): string {
        $value = "";
        if (Utils::isNonNull($this->connection)) {
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            $columnValue = $this->escape($columnValue);
            $assoc = $this->getTableRowsWhereClause($database, $table, $column, $columnValue);
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
        if (Utils::isNonNull($this->connection)) {
            if (Utils::startsWith("SELECT", strtoupper($statement))) {
                $select = $this->connection->query($statement);
                if ($select instanceof \mysqli_result && $select->num_rows > 0) {
                    while ($row = $select->fetch_assoc()) {
                        $result[] = $row;
                    }
                } else {
                    $this->lastMessage = "Failed to execute the where clause statement. " . $this->lastError();
                }
            } else {
                $this->lastMessage = "To execute a where clause and fetch the association, the statement must start with \"SELECT\"";
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
        if (Utils::isNonNull($this->connection)) {
            $database = $this->escapeReplace($database);
            $tables = $this->connection->query("SHOW TABLES FROM " . $database . ";");
            if ($tables instanceof \mysqli_result) {
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
        if (Utils::isNonNull($this->connection)) {
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            $result = $this->connection->query("SHOW CREATE TABLE " . $database . "." . $table . ";");
            if ($result instanceof \mysqli_result) {
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
        if (Utils::isNonNull($this->connection)) {
            $database = $this->escapeReplace($database);
            $table = $this->escapeReplace($table);
            $result = $this->connection->query("SELECT * FROM " . $database . "." . $table . ";");
            if ($result instanceof \mysqli_result) {
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
                if (Utils::isNotEmptyString($dv)) {
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
            $database = $this->escapeReplace($database);
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

    /**
     * Retrieves the last throwable instance.
     *
     * This method returns the most recent throwable instance that was stored
     * using the `setLastThrowable` method. If no throwable has been set, it returns null.
     *
     * @return \Throwable|null The last throwable instance or null if none is set.
     */
    public static function getLastThrowable(): ?\Throwable {
        return self::$lastThrowable;
    }

    // PRIVATE METHODS

    /**
     * Sets the last throwable instance.
     *
     * This method allows storing the most recent exception or error object 
     * that implements the Throwable interface. It is useful for tracking
     * or logging errors globally.
     *
     * @param \Throwable $e The throwable instance to be stored.
     * @return void
     */
    private static function setLastThrowable(\Throwable $e): void {
        self::$lastThrowable = $e;
    }

    /**
     * Initialize a new MySQLi connection
     * @param string $host
     * @param string $user
     * @param string $password
     * @param bool $reset If to force reset the connection
     */
    private function init(string $host, string $user, string $password, bool $reset = false) {
        if (Utils::isNull($this->connection) || Utils::isTrue($reset)) {
            try {
                mysqli_report(MYSQLI_REPORT_STRICT);
                $this->connection = new \mysqli($host, $user, $password);
                if ($this->connection instanceof \mysqli) {
                    $this->host = $host;
                    $this->user = $user;
                    $this->password = $password;
                    /* Set the desired charset, time_zone, and sql_mode after establishing a connection */
                    $this->connection->autocommit(true);
                    $this->connection->set_charset('utf8mb4');
                    $this->connection->query("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
                    $this->connection->query("SET time_zone='" . Utils::GMT . "'");
                    $this->connection->query("SET GLOBAL sql_mode = '';");
                }
            } catch (\Exception $e) {
                $this->connection = null;
                self::setLastThrowable($e);
                $this->lastMessage = "Database connection was not established. " . $e->getMessage();
            }
        }
    }

    /**
     * Sanitize values
     * @param string $identifier
     * @return string
     */
    private function escapeReplace(string $value): string {
        return $this->escape(str_replace([' ', '/', '\\', '-'], '', $value));
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
        if (Utils::isNotEmptyArray($tables)) {
            $backupContent = "\n\n--\n-- Database: `$database`\n--";
            foreach ($tables as $table) {
                $backupContent .= $this->getTableExportStructureData($database, $table);
                $backupContent .= $this->getTableExportInsertData($database, $table);
            }
            return $this->wrapDatabaseExportContent($backupContent);
        }
        return null;
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

<?php
namespace PHPWebFuse;
/**
 *
 */
class Database extends \PHPWebFuse\Methods {
	// PUBLIC VARIABLES

	public $message = "";

	// PRIVATE VARIABLES

	private $connection = null;
	private $host = null;
	private $user = null;
	private $password = null;

	// PUBLIC METHODS

	public function __construct(?string $host = null, ?string $user = null, ?string $password = null, bool $reset = false) {
		if (!in_array('mysqli', get_declared_classes())) {
			$this->message = "The mysqli class doesn't exist or wasn't found.";
		} else if (parent::isNotEmptyString($host) && parent::isNotEmptyString($user) && parent::isNotEmptyString($password)) {$this->init($host, $user, $password, $reset);}
	}

	public function __destruct() {
		if (parent::isNonNull($this->connection)) {
			@$this->connection->close();
			$this->connection = null;
		}
	}

	public function connect(string $host, string $user, string $password, bool $reset = false): bool {return $this->init($host, $user, $password, $reset);}
	public function connection(): mixed {return $this->connection;}
	public function lastInsertID(): int | string {if (parent::isNonNull($this->connection)) {return $this->connection->insert_id;}return "";}
	public function selectDb(string $database): bool {if (parent::isNonNull($this->connection)) {return @$this->connection->select_db($database);}return false;}
	public function lastError(): string {if (parent::isNonNull($this->connection)) {return $this->connection->error;}return "";}
	public function hostInfo(): string {if (parent::isNonNull($this->connection)) {return $this->connection->host_info;}return "";}
	public function serverInfo(): string {if (parent::isNonNull($this->connection)) {return $this->connection->server_info;}return "";}
	public function serverVersion(): string {if (parent::isNonNull($this->connection)) {return $this->connection->server_version;}return "";}
	public function lastQueryInfo(): string {if (parent::isNonNull($this->connection)) {return $this->connection->info;}return "";}
	public function protocolVersion(): int | string {if (parent::isNonNull($this->connection)) {return $this->connection->protocol_version;}return "";}
	public function escape(string $string): string {if (parent::isNonNull($this->connection)) {return $this->connection->real_escape_string($string);}return $string;}

	/**
	 * Create a database
	 *
	 * @param string $name
	 * @param string $character: Defaults to 'latin1'
	 * @param string $collate: Defaults to 'latin1_general_ci'
	 * @return bool
	 */
	public function createDatabase(string $name, string $character = "latin1", string $collate = " latin1_general_ci"): bool {
		if (parent::isNonNull($this->connection)) {return $this->connection->query("CREATE DATABASE IF NOT EXISTS `" . strtolower($name) . "` DEFAULT CHARACTER SET " . $character . " COLLATE " . $collate . ";");}
		return false;
	}

	/**
	 * Delete a database
	 *
	 * @param string $name
	 * @return bool
	 */
	public function deleteDatabase(string $name): bool {
		if (parent::isNonNull($this->connection)) {return $this->connection->query("DROP DATABASE IF EXISTS `" . strtolower($name) . "`");}
		return false;
	}

	/**
	 * Optimise a database
	 *
	 * @param array $databases
	 * @return array
	 */
	public function optimiseDatabases(array $databases): array {
		$result = array();
		if (parent::isNonNull($this->connection)) {
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
							} else { $result[$database][$table] = false;}
						} else { $result[$database][$table] = true;}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Delete a database table
	 *
	 * @param string $databases
	 * @param string $table
	 * @return bool
	 */
	public function deleteDatabaseTable(string $database, string $table): bool {
		if (parent::isNonNull($this->connection) && $this->doesDatabaseTableExist($database, $table)) {
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
	public function doesDatabaseTableExist(string $database, string $table): bool {
		if (parent::isNonNull($this->connection)) {
			$database = $this->sanitizeIdentifier($database);
			$table = $this->sanitizeIdentifier($table);
			$tables = $this->connection->query('SHOW TABLES FROM ' . $database . ';');
			if ($tables) {while ($ft = $tables->fetch_array()) {if ($ft[0] == $table) {return true;}}}
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
	public function truncateDatabaseTable(string $database, string $table): bool {
		if (parent::isNonNull($this->connection) && $this->doesDatabaseTableExist($database, $name)) {
			$database = $this->sanitizeIdentifier($database);
			$table = $this->sanitizeIdentifier($table);
			return $this->connection->query('TRUNCATE ' . $database . '.' . $table . ';');
		}
		return false;
	}

	/**
	 * Insert into database table
	 *
	 * @param string $databases
	 * @param string $table
	 * @param array $data
	 * @param bool $preprare: Wether to prepare or directly execute the query
	 * @return bool
	 */
	public function insertToDatabaseTable(string $database, string $table, array $data = array(), bool $preprare = true): bool {
		if (parent::isNonNull($this->connection) && $this->doesDatabaseTableExist($database, $table)) {
			$database = $this->sanitizeIdentifier($database);
			$table = $this->sanitizeIdentifier($table);
			$fields = $values = array();
			foreach ($data as $index => $value) {
				if (!parent::isEmptyString($index) && !parent::isEmptyString($value)) {
					$fields[] = $index;
					$values[] = $value;
				}
			}
			$statement = "INSERT IGNORE INTO " . $database . "." . $table . " (`" . implode("`, `", $fields) . "`) VALUES (";
			$rows = "";
			foreach ($values as $value) {if (parent::isInt($value)) {$rows .= $this->escape($value) . ', ';} else if (parent::isString($value)) {$rows .= '"' . $this->escape($value) . '", ';} else { $rows .= '"' . $this->escape((string) $value) . '", ';}}
			$statement .= substr(trim($rows), -1) == "," ? substr(trim($rows), 0, -1) : $rows;
			$statement .= ");";
			$result = $preprare === true ? $this->connection->prepare($statement) : $this->connection->query($statement);
			if (!$result || (isset($result->affected_rows) && $result->affected_rows < 1)) {$this->message = $this->lastError();}
			return $result;
		}
		return false;
	}

	/**
	 * Create a database table
	 *
	 * @param string $databases
	 * @param string $table
	 * @param array $columns: Defaults to 'array()'
	 * @param string $comment: Defaults to ''
	 * @param string $engine: Defaults to 'MyISAM'
	 * @param string $character: Defaults to 'latin1'
	 * @param string $collate: Defaults to 'latin1_general_ci'
	 * @param bool $autoincrement: Default to 'true'. Wether to set it as auto increment
	 * @return bool
	 */
	public function createDatabaseTable(string $database, string $table, array $columns = array(), string $comment = '', string $engine = "MyISAM", string $character = "latin1", string $collate = " atin1_general_ci", bool $autoincrement = true): bool {
		if (parent::isNonNull($this->connection) && parent::isFalse($this->doesDatabaseTableExist($database, $table))) {
			$database = $this->sanitizeIdentifier($database);
			$table = $this->sanitizeIdentifier($table);
			$statement = "CREATE TABLE IF NOT EXISTS " . $database . "." . $name . " (";
			foreach ($columns as $name => $value) {$statement .= "" . $name . " " . $value . ", ";}
			$statement = rtrim($statement, ", ");
			$statement .= ")";
			$autoincrementValue = $autoincrement ? ' AUTO_INCREMENT=1' : '';
			$statement .= ' ENGINE=' . $engine . ' DEFAULT CHARSET=' . $character . ' COLLATE=' . $collate . '' . $autoincrementValue . '';
			$statement .= strlen($comment) > 0 ? ' COMMENT "' . $this->escape($comment) . '";' : ';';
			$created = $this->connection->query($statement);
			if (parent::isNotTrue($created)) {$this->message = $this->lastError();return false;}
			return true;
		}
		return false;
	}

	/**
	 * Arrange a database table
	 *
	 * @param array $databases
	 * @return array
	 */
	public function arrangeDatabaseTables(array $databases): array {
		$messages = array();
		if (parent::isNonNull($this->connection)) {
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
							try {while ($ft = $columns->fetch_assoc()) {$columnsdata[] = $ft;}} catch (\Thowable $e) {}
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
								} else if (substr($type, 0, 3) == 'int' || substr($type, 0, 6) == 'bigint') {
									$tablestatements[$table] = "ALTER TABLE `" . $table . "` CHANGE `" . $field . "` `" . $field . "` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '" . $comment . "';";
								} else if ((substr($type, 0, 7) == 'varchar' || substr($type, 0, 4) == 'char') && $collation !== "NULL") {
									$tablestatements[$table] = "ALTER TABLE `" . $table . "` CHANGE `" . $field . "` `" . $field . "` " . strtoupper($type) . " CHARACTER SET latin1 COLLATE " . $collation . " NOT NULL DEFAULT '' COMMENT '" . $comment . "';";
								} else if (substr($type, 0, 4) == 'text' && $collation !== "NULL") {
									$tablestatements[$table] = "ALTER TABLE `" . $table . "` CHANGE `" . $field . "` `" . $field . "` " . strtoupper($type) . " CHARACTER SET latin1 COLLATE " . $collation . " NOT NULL COMMENT '" . $comment . "';";
								}
							}
							foreach ($tablestatements as $statement) {
								if ($this->connection->query($statement)) {
									$messages[$database][$table] = true;
								} else { $messages[$database][$table] = "Failed to arrange table [" . $this->lastError() . "]";}
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
	 * Get a table columns
	 *
	 * @param string $databases
	 * @param string $table
	 * @return array
	 */
	public function getTableColumns(string $database, string $table): array {
		$result = array();
		if (parent::isNonNull($this->connection)) {
			$database = $this->sanitizeIdentifier($database);
			$table = $this->sanitizeIdentifier($table);
			$columns = $this->connection->query('SHOW FULL COLUMNS FROM ' . $database . '.' . $table . ';');
			if ($columns) {try {while ($ft = $columns->fetch_assoc()) {$result[] = $ft;}} catch (\Thowable $e) {}}
		}
		return $result;
	}

	/**
	 * Get table rows in a where clause manner
	 *
	 * @param string $databases
	 * @param string $table
	 * @param string $column
	 * @param string $columnvalue
	 * @param string $order: Defaults to 'id'
	 * @return array
	 */
	public function getTableRowsWhereClause(string $database, string $table, string $column, mixed $columnvalue, string $order = "id"): array {
		$result = array();
		if (parent::isNonNull($this->connection)) {
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
	 *
	 * @param string $databases
	 * @param string $table
	 * @param string $order: Defaults to 'id'
	 * @return array
	 */
	public function getTableRows(string $database, string $table, string $order = "id"): array {
		$result = array();
		if (parent::isNonNull($this->connection)) {
			$database = $this->sanitizeIdentifier($database);
			$table = $this->sanitizeIdentifier($table);
			$statement = "SELECT * FROM " . $database . "." . $table . " ORDER BY " . $order . ";";
			$result = $this->executeAndFetchAssociationFromSelectStatement($statement);
		}
		return $result;
	}

	/**
	 * Get table rows and return an index value
	 *
	 * @param string $databases
	 * @param string $table
	 * @param string $column
	 * @param string $columnvalue
	 * @param string $index
	 * @return string
	 */
	public function getTableRowsIndexValue(string $database, string $table, string $column, mixed $columnvalue, mixed $index): string {
		$value = "";
		if (parent::isNonNull($this->connection)) {
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
	 * Execute and fetch association from a select statement
	 *
	 * @param string $statement
	 * @return array
	 */
	public function executeAndFetchAssociationFromSelectStatement(string $statement): array {
		$result = array();
		if (parent::isNonNull($this->connection)) {
			if (parent::startsWith("SELECT", $statement)) {
				$select = $this->connection->query($statement);
				if ($select && $select->num_rows > 0) {
					while ($row = $select->fetch_assoc()) {$result[] = $row;}
				} else { $this->message = "Failed to execute the where clause statement. " . $this->lastError();}
			} else { $this->message = "To execute a where clause and fetch the association, the statement must start with \"SELECT\"";}
		}
		return $result;
	}

	/**
	 * Get all database tables
	 *
	 * @param string $database
	 * @return array
	 */
	public function getDatabaseTables(string $database): array {
		$result = array();
		if (parent::isNonNull($this->connection)) {
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
	 *
	 * @param string $database
	 * @param string $table
	 * @return string
	 */
	public function getTableExportStructureData(string $database, string $table): string {
		$structure = '';
		if (parent::isNonNull($this->connection)) {
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
	 *
	 * @param string $database
	 * @param string $table
	 * @return string
	 */
	public function getTableExportInsertData(string $database, string $table): string {
		$data = '';
		if (parent::isNonNull($this->connection)) {
			$database = $this->sanitizeIdentifier($database);
			$table = $this->sanitizeIdentifier($table);
			$result = $this->connection->query("SELECT * FROM " . $database . "." . $table . ";");
			if ($result) {
				$fieldCount = $result->field_count;
				$fields = [];
				while ($field = $result->fetch_field()) {$fields[] = "`" . $field->name . "`";}
				$fieldNames = implode(', ', $fields);
				$dd = $dv = "";
				$dd .= "\n-- Dumping data for table `$table`\n";
				$dd .= "INSERT INTO `$table` ($fieldNames) VALUES\n";
				while ($row = $result->fetch_row()) {$dv .= "(" . $this->escapeExportRowData($row, $fieldCount) . "),\n";}
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
	 *
	 * @param array $databases
	 * @param string $savePathname: Defaults to 'null'
	 * @return array
	 */
	public function exportDatabases(array $databases, ?string $savePathname = null): array {
		$messages = [];
		$savePathname = self::isNull($savePathname) ? dirname(__DIR__) : $savePathname;
		if (parent::isNotDir($savePathname)) {parent::makeDir($savePathname);}
		$savePathname = parent::resolvePath($savePathname);
		$savePathname = parent::INSERT_DIR_SEPARATOR($savePathname);
		// Iterate through each database and back it up
		foreach ($databases as $database) {
			$database = $this->sanitizeIdentifier($database);
			$backupContent = $this->createDatabaseExportContent($database);
			// If backup content is generated, save it to file
			if (parent::isString($backupContent)) {
				parent::makeDir($savePathname);
				$absolutePath = parent::CONVERT_DIR_SEPARATOR($savePathname) . "database-backup-[" . $database . "].sql";
				// Save the backup content to a file
				if (parent::saveContentToFile($absolutePath, $backupContent)) {
					$messages[$database] = $absolutePath;
				} else { $messages[$database] = false;}
			} else { $messages[$database] = false;}
		}
		return $messages;
	}

	// PRIVATE METHODS
	
	private function init(string $host, string $user, string $password, bool $reset = false): bool {
		if (parent::isNull($this->connection) || parent::isTrue($reset)) {
			try {
				mysqli_report(MYSQLI_REPORT_STRICT);
				$this->connection = new \mysqli($host, $user, $password);
				$this->host = $host;
				$this->user = $user;
				$this->password = $password;
				/* Set the desired charset, time_zone, and sql_mode after establishing a connection */
				@$this->connection->set_charset('utf8mb4');
				@$this->connection->query("SET time_zone='" . parent::GMT . "'");
				@$this->connection->query("SET GLOBAL sql_mode = '';");
				return true;
			} catch (\mysqli_sql_exception $e) {
				$this->connection = null;
				$this->message = "Database connection was not established. " . $e->getMessage();
			}
		}
		return false;
	}

	private function sanitizeIdentifier(string $identifier): string {
		return $this->escape(str_replace([' ', '/', '\\', '-'], '', $identifier));
	}

	private function escapeExportRowData(array $row, int $fieldCount): string {
		for ($i = 0; $i < $fieldCount; $i++) {
			$row[$i] = str_replace("'", "''", preg_replace("/\n/", "\\n", $row[$i]));
			$row[$i] = isset($row[$i]) ? (is_numeric($row[$i]) ? $row[$i] : "'$row[$i]'") : "''";
		}
		return implode(', ', $row);
	}

	private function createDatabaseExportContent(string $database): ?string {
		$tables = $this->getDatabaseTables($database);
		if (empty($tables)) {return null;}
		$backupContent = "\n\n--\n-- Database: `$database`\n--";
		foreach ($tables as $table) {
			$backupContent .= $this->getTableExportStructureData($database, $table);
			$backupContent .= $this->getTableExportInsertData($database, $table);
		}
		return $this->wrapDatabaseExportContent($backupContent);
	}

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
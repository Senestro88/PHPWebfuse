<?php
namespace PHPWebFuse;
/**
 *
 */
class Tasker extends \PHPWebFuse\Methods {
	// PRIVATE VARIABLES

	private $executable = "";

	/**
	 * The tasks is an array containing arrays of background tasks to create
	 * 1: Each array must contain
	 * 		"path" - The absolute filename
	 * 2: On Widnows environment, each array must contain:
	 * 		"winTime" - 00:00
	 * 		"winExecution"- daily
	 * 		"winTaskname"
	 * 2: On Linux/Unix, each array must contain:
	 * 		"unixTime" - 0 0,12 * * * ((0:00) and noon (12:00) every day)
	 */
	private $tasks = array();

	// PUBLIC METHODS

	/**
	 * @param array $tasks 
	 */
	public function __construct(array $tasks = array()) {
		$this->tasks = $tasks;
		$this->setExecutable();
	}

	/**
	 * Create tasks
	 * 
	 * @param array $tasks
	 * @return array
	 */
	public function createTasks(array $tasks = array()): array {
		$this->tasks = $tasks;
		$this->setExecutable();
		$result = array();
		if (parent::isNotEmptyString($this->executable)) {
			$commands = $this->FormatTasksCommands();
			foreach ($commands as $path => $command) {
				$result[$path] = parent::executeCommandUsingPopen($command);
			}
		}
		return $result;
	}

	// PRIVATE METHODS

	private function getExecutable(): string {
		parent::loadComposer();
		$executable = "";
		if (class_exists("\Symfony\Component\Process\PhpExecutableFinder")) {
			$finder = new \Symfony\Component\Process\PhpExecutableFinder;
			$_executable = $finder->find();
			if (parent::isString($_executable)) {$executable = parent::convertExtension($_executable);}
		}
		return $executable;
	}

	private function setExecutable(): void {
		$executable = $this->getExecutable();
		if (parent::isString($executable) && parent::isNotEmptyString($executable)) {
			$this->executable = $executable;
		}
	}

	private function FormatTasks() {
		$os = strtolower(parent::getOS());
		$results = array();
		foreach ($this->tasks as $data) {
			$path = isset($data['path']) ? (string) $data['path'] : "";
			if (parent::isFile($path)) {
				if ($os == "windows") {
					$winTaskname = isset($data['winTaskname']) ? (string) $data['winTaskname'] : basename($path);
					$winTime =isset( $data['winTime']) ? (string)  $data['winTime'] : "00:00";
					$winExecution = isset($data['winExecution']) ? (string) $data['winExecution'] :  "daily";
					$results[] = array("path" => $path, "time" => $winTime, "schedule" => $winExecution, "taskName" => $winTaskname);
				} else {
					$unixTime = $data['unixTime'] ?? "0 0,12 * * *";
					$results[] = array('path' => $path, 'time' => $unixTime);
				}
			}
		}
		return $results;
	}

	private function FormatTasksCommands($redirect = false) {
		$os = strtolower(parent::getOS());
		$commands = array();
		if (parent::isNotEmptyString($this->executable) && function_exists("exec")) {
			$crons = $this->FormatTasks();
			foreach ($crons as $index => $data) {
				$path = $data['path'];
				$time = $data['time'];
				if ($os == "windows") {
					$schedule = $data['schedule'];
					$taskName = $data['taskName'];
					$result = array();
					@exec("schtasks /query /tn " . $taskName . "", $result, $resultcode);
					if (isset($result) && empty($result)) {
						$commands[$path] = "schtasks /create /tn " . $taskName . " /tr \"" . $this->executable . " " . $path . "\" /sc " . $schedule . " /st " . $time . "";
					}
				} else {
					$result = array();
					@exec('crontab -l', $result, $resultcode);
					if (isset($result) && empty($result)) {
						$result = array_flip($result);
						if (!isset($output[$time . " " . $this->executable . " " . $absolutePath])) {
							$arg = $redirect === true ? " > /dev/null 2>&1" : "";
							$commands[$path] = "echo -e  \"`crontab -l`\n" . $time . " " . $this->executable . " " . $path . "" . $arg . "\" | crontab -";
						}
					}
				}
			}
		}
		return $commands;
	}
}
<?php

namespace PHPWebfuse;

/**
 */
class Tasker
{
    // PRIVATE VARIABLES

    /**
     * @var \PHPWebfuse\Methods
     */
    private \PHPWebfuse\Methods $methods;

    /**
     * @var array The tasks is an array containing arrays of background tasks to create
     *      1: Each array must contain
     *          "path" - The absolute filename
     *      2: On Windows environment, each array must contain:
     *          "winTime" - 00:00
     *          "winExecution"- daily
     *          "winTaskname"
     *      3: On Linux/Unix, each array must contain:
     *          "unixTime" - 0 0,12 * * * ((0:00) and noon (12:00) every day)
     */
    private array $tasks = array();

    /**
     * @var array The created tasks are stored here
     */
    private array $createdTasks = array();

    /**
     * @var string The php executable absolute path
     */
    private string $php = "";

    // PUBLIC METHODS

    /**
     * Construct new Tasker instance
     * @param array $tasks
     */
    public function __construct(array $tasks = array())
    {
        $this->methods = new \PHPWebfuse\Methods();
        $this->tasks = $tasks;
        $this->setExecutable();
    }

    /**
     * Create tasks
     * @param array $tasks
     * @return array
     */
    public function createTasks(array $tasks = array()): array
    {
        $this->tasks = $tasks;
        $this->setExecutable();
        if ($this->methods->isNotEmptyString($this->php)) {
            $commands = $this->formatTasksCommands();
            foreach ($commands as $path => $command) {
                $this->createdTasks[$path] = $this->methods->executeCommandUsingPopen($command);
            }
        }
        return $this->createdTasks;
    }

    // PRIVATE METHODS

    /**
     * Get the php executable
     * @return string
     */
    private function getExecutable(): string
    {
        $executable = "";
        if (class_exists("\Symfony\Component\Process\PhpExecutableFinder")) {
            $finder = new \Symfony\Component\Process\PhpExecutableFinder();
            $_executable = $finder->find();
            if ($this->methods->isString($_executable)) {
                $executable = $this->methods->convertExtension($_executable);
            }
        }
        return $executable;
    }

    /**
     * Set the php executable
     * @return void
     */
    private function setExecutable(): void
    {
        $executable = $this->getExecutable();
        if ($this->methods->isString($executable) && $this->methods->isNotEmptyString($executable)) {
            $this->php = $executable;
        }
    }

    /**
     * Format tasks
     * @return array
     */
    private function formatTasks(): array
    {
        $os = strtolower($this->methods->getOS());
        $results = array();
        foreach ($this->tasks as $data) {
            $path = isset($data['path']) ? (string) $data['path'] : "";
            if ($this->methods->isFile($path)) {
                if ($os == "windows") {
                    $winTaskname = isset($data['winTaskname']) ? (string) $data['winTaskname'] : basename($path);
                    $winTime = isset($data['winTime']) ? (string) $data['winTime'] : "00:00";
                    $winExecution = isset($data['winExecution']) ? (string) $data['winExecution'] : "daily";
                    $results[] = array("path" => $path, "time" => $winTime, "schedule" => $winExecution, "taskName" => $winTaskname);
                } else {
                    $unixTime = $data['unixTime'] ?? "0 0,12 * * *";
                    $results[] = array('path' => $path, 'time' => $unixTime);
                }
            }
        }
        return $results;
    }

    /**
     * Format the task with and option to redirect the task command to "> /dev/null 2>&1"
     * @param type $redirect
     * @return array
     */
    private function formatTasksCommands($redirect = false): array
    {
        $os = strtolower($this->methods->getOS());
        $commands = array();
        if ($this->methods->isNotEmptyString($this->php) && function_exists("exec")) {
            $crons = $this->formatTasks();
            foreach ($crons as $index => $data) {
                $path = $data['path'];
                $time = $data['time'];
                if ($os == "windows") {
                    $schedule = $data['schedule'];
                    $taskName = $data['taskName'];
                    $result = array();
                    $resultcode = 0;
                    @exec("schtasks /query /tn " . $taskName . "", $result, $resultcode);
                    if (isset($result) && empty($result)) {
                        $commands[$path] = "schtasks /create /tn " . $taskName . " /tr \"" . $this->php . " " . $path . "\" /sc " . $schedule . " /st " . $time . "";
                    }
                } else {
                    $result = array();
                    $resultcode = 0;
                    @exec('crontab -l', $result, $resultcode);
                    if (isset($result) && empty($result)) {
                        $result = array_flip($result);
                        if (!isset($result[$time . " " . $this->php . " " . $path])) {
                            $arg = $redirect === true ? " > /dev/null 2>&1" : "";
                            $commands[$path] = "echo -e  \"`crontab -l`\n" . $time . " " . $this->php . " " . $path . "" . $arg . "\" | crontab -";
                        }
                    }
                }
            }
        }
        return $commands;
    }
}

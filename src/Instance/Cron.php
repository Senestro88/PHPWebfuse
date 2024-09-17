<?php

namespace PHPWebfuse\Instance;

use PHPWebfuse\File;
use \PHPWebfuse\Utils;

/**
 * @author Senestro
 */
class Cron
{
    // PRIVATE VARIABLES

    /**
     * @var string The php executable absolute path
     */
    private static string $executable = "";

    // PUBLIC METHODS

    /**
     * Construct new Cron instance
     * @param string|null $executable
     */
    public function __construct(?string $executable = null)
    {
        if(Utils::isNonNull($executable) && \is_executable($executable)) {
            self::$executable = \realpath($executable);
        } else {
            self::$executable = $this->getPhpExecutable();
        }
    }

    /**
     * Create tasks
     * @param array $crons
     * @return array
     */
    public function create(array $crons = array()): array
    {
        if (Utils::isNotEmptyString(self::$executable) && !empty($crons)) {
            $created = array();
            foreach ($this->commands($crons) as $path => $command) {
                $created[$path] = Utils::executeCommandUsingPopen($command);
            }
            return $created;
        }
        return array();
    }

    // PRIVATE METHODS

    /**
     * Get the php executable
     * @return string
     */
    private function getPhpExecutable(): string
    {
        $executable = "";
        if (class_exists("\Symfony\Component\Process\PhpExecutableFinder")) {
            $finder = new \Symfony\Component\Process\PhpExecutableFinder();
            $_executable = $finder->find();
            if (Utils::isString($_executable)) {
                $executable = Utils::convertExtension($_executable);
            }
        }
        return $executable;
    }

    /**
     * Format the crons
     * @param array $crons
     * @return array
     */
    private function format(array $crons = array()): array
    {
        $os = strtolower(Utils::getOS());
        $results = array();
        foreach ($crons as $data) {
            $path = isset($data['path']) ? (string) $data['path'] : "";
            if (File::isFile($path)) {
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
     * Format the crons with and option to redirect the crons command to "> /dev/null 2>&1" (Silent the cron ouput)
     * @param array $crons The Crons
     * @param type $silent Wether to silent the crons output
     * @return array
     */
    private function commands(array $crons = array(), $silent = true): array
    {
        $os = strtolower(Utils::getOS());
        $commands = array();
        if (Utils::isNotEmptyString(self::$executable) && function_exists("exec")) {
            $crons = $this->format($crons);
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
                        $commands[$path] = "schtasks /create /tn " . $taskName . " /tr \"" . self::$executable . " " . $path . "\" /sc " . $schedule . " /st " . $time . "";
                    }
                } else {
                    $result = array();
                    $resultcode = 0;
                    @exec('crontab -l', $result, $resultcode);
                    if (isset($result) && empty($result)) {
                        $result = array_flip($result);
                        if (!isset($result[$time . " " . self::$executable . " " . $path])) {
                            $arg = $silent === true ? " > /dev/null 2>&1" : "";
                            $commands[$path] = "echo -e  \"`crontab -l`\n" . $time . " " . self::$executable . " " . $path . "" . $arg . "\" | crontab -";
                        }
                    }
                }
            }
        }
        return $commands;
    }
}

<?php

namespace PHPWebfuse;

/**
 * @author Senestro
 */
class Time {
    /**
     * Prevent the constructor from being initialized
     */
    private function __construct() {
    }

    /**
     * Returns an associative array containing the date information of the timestamp, or the current local time if timestamp is omitted or null.
     * @param int|null $timestamp
     * @return array
     */
    public static function getDate(?int $timestamp = null): array {
        return getdate($timestamp);
    }

    /**
     * Organize a time unix timestamp to human readable
     * @param ?int  $timestamp
     * @return string
     */
    public static function organizeTime(?int $timestamp = null): string {
        $currentDate = self::getDate();
        $targetDate = self::getDate($timestamp);
        if ($currentDate['mday'] === $targetDate['mday'] && $currentDate['mon'] === $targetDate['mon'] && $currentDate['year'] === $targetDate['year']) {
            return date("h:i A", $timestamp);
        } elseif (($currentDate['mday'] - 1) === $targetDate['mday'] && $currentDate['mon'] === $targetDate['mon'] && $currentDate['year'] === $targetDate['year']) {
            return "Yesterday";
        } else {
            $isYear = ($currentDate['year'] !== $targetDate['year']) ? ", Y" : '';
            return ucfirst(date("F j" . $isYear, $timestamp));
        }
    }

    /**
     * Format a time unix timestamp into time ago to human readable
     * @param int $timestamp:
     * @return string
     */
    public static function FormatTimeAgoVersion1(int $timestamp): string {
        $difference = time() - $timestamp;
        if ($difference < 5) {
            return 'less than 5 seconds ago';
        }
        $conditions = [31104000 => 'year', 2592000 => 'month', 86400 => 'day', 3600 => 'hour', 60 => 'minute', 1 => 'second'];
        foreach ($conditions as $seconds => $unit) {
            $differenceUnit = floor($difference / $seconds);
            if ($differenceUnit >= 1) {
                $plural = $differenceUnit > 1 ? 's' : '';
                return $differenceUnit . ' ' . $unit . $plural . ' ago';
            }
        }
        return '..';
    }

    /**
     * Format a time unix timestamp into time ago to human readable
     * @param int $timestamp
     * @param int $level
     * @return string
     */
    public static function FormatTimeAgoVersion2(int $timestamp, int $level = 2): string {
        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        $dateDiff = $date->diff(new \DateTime());
        $since = ['year' => $dateDiff->y, 'month' => $dateDiff->m, 'day' => $dateDiff->d, 'hour' => $dateDiff->h, 'minute' => $dateDiff->i, 'second' => $dateDiff->s];
        $since = array_filter($since); // Remove empty date values
        $since = array_slice($since, 0, $level); // Output only the first x date values
        $lastValue = key(array_slice($since, -1, 1, true)); // Build string
        $string = '';
        foreach ($since as $unit => $value) {
            if ($string) {
                $string .= $unit !== $lastValue ? ', ' : ' and ';
            }
            $plural = $value > 1 ? 's' : ''; // Set plural
            $string .= $value . ' ' . $unit . $plural; // Add date value
        }
        return $string . ' ago';
    }
}

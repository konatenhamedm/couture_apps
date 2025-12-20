<?php

namespace App\Service;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Utility class for building date ranges compatible with Doctrine DQL
 * Replaces the need for DATE() function calls in DQL queries
 */
class DateRangeBuilder
{
    /**
     * Convert a single date to a full day range (00:00:00 to 23:59:59)
     * 
     * @param DateTime $date The date to convert to a day range
     * @return array Array with [startDate, endDate] covering the full day
     */
    public static function dayRange(DateTime $date): array
    {
        if (!$date instanceof DateTime) {
            throw new InvalidArgumentException('Parameter must be a DateTime object');
        }
        
        $start = clone $date;
        $start->setTime(0, 0, 0);
        
        $end = clone $date;
        $end->setTime(23, 59, 59);
        
        return [$start, $end];
    }
    
    /**
     * Format a DateTime object for DQL compatibility
     * 
     * @param DateTime $date The date to format
     * @return string Formatted date string in 'Y-m-d H:i:s' format
     */
    public static function formatForDQL(DateTime $date): string
    {
        if (!$date instanceof DateTime) {
            throw new InvalidArgumentException('Parameter must be a DateTime object');
        }
        
        return $date->format('Y-m-d H:i:s');
    }
    
    /**
     * Create a date range from start and end dates with proper time boundaries
     * 
     * @param DateTime $startDate Start date
     * @param DateTime $endDate End date
     * @return array Array with [startDate, endDate] with proper time boundaries
     */
    public static function periodRange(DateTime $startDate, DateTime $endDate): array
    {
        if (!$startDate instanceof DateTime || !$endDate instanceof DateTime) {
            throw new InvalidArgumentException('Both parameters must be DateTime objects');
        }
        
        if ($startDate > $endDate) {
            throw new InvalidArgumentException('Start date cannot be after end date');
        }
        
        $start = clone $startDate;
        $start->setTime(0, 0, 0);
        
        $end = clone $endDate;
        $end->setTime(23, 59, 59);
        
        return [$start, $end];
    }
    
    /**
     * Validate that a DateTime object is not null and is valid
     * 
     * @param DateTime|null $date The date to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidDate(?DateTime $date): bool
    {
        return $date instanceof DateTime;
    }
    
    /**
     * Get the current date with proper timezone handling
     * 
     * @param string|null $timezone Optional timezone string
     * @return DateTime Current date with specified timezone
     */
    public static function now(?string $timezone = null): DateTime
    {
        if ($timezone) {
            try {
                $tz = new DateTimeZone($timezone);
                return new DateTime('now', $tz);
            } catch (\Exception $e) {
                // Fall back to default timezone if invalid timezone provided
                return new DateTime('now');
            }
        }
        
        return new DateTime('now');
    }
    
    /**
     * Create a DateTime object from a date string with error handling
     * 
     * @param string $dateString Date string to parse
     * @param string|null $timezone Optional timezone
     * @return DateTime|null DateTime object or null if parsing fails
     */
    public static function createFromString(string $dateString, ?string $timezone = null): ?DateTime
    {
        try {
            if ($timezone) {
                $tz = new DateTimeZone($timezone);
                return new DateTime($dateString, $tz);
            }
            
            return new DateTime($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Ensure date precision is preserved during conversions
     * 
     * @param DateTime $date Original date
     * @return DateTime Date with preserved precision
     */
    public static function preservePrecision(DateTime $date): DateTime
    {
        // Clone to avoid modifying original
        $preserved = clone $date;
        
        // Ensure microseconds are preserved if they exist
        $format = $date->format('Y-m-d H:i:s.u');
        
        try {
            return DateTime::createFromFormat('Y-m-d H:i:s.u', $format) ?: $preserved;
        } catch (\Exception $e) {
            return $preserved;
        }
    }
}
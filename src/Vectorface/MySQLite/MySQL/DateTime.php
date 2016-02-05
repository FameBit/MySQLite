<?php

namespace Vectorface\MySQLite\MySQL;

/**
 * Provides Date and Time MySQL compatibility functions for SQLite.
 *
 * http://dev.mysql.com/doc/refman/5.7/en/date-and-time-functions.html
 */
trait DateTime
{
    /**
     * NOW - Return the current date and time
     *
     * @return string The current timestamp, in MySQL's date format.
     */
    public static function mysql_now()
    {
        return date("Y-m-d H:i:s");
    }

    /**
     * TO_DAYS - Return the date argument converted to days
     *
     * @param string $date A date to be converted to days.
     * @return int The date converted to a number of days since year 0.
     */
    public static function mysql_to_days($date)
    {
        // Why does this work on my Debian machine with PHP 5.6, and not on Travis?
        // - strtotime("0000-12-31") yields -62135665200
        // - 60 * 60 * 24 is 86400 (seconds)
        // - 1413108827 / 86400 = -719162.79166667, python similarly says -719162.7916666666
        // - ceil bumps it up 1 to -719162
        // - 719527 + (-719162), python agrees
        // - So why is Travis giving me 364?!? Their PHP is configured for a different timezone (I think)!
        return intval(719528 + ((strtotime($date) - date("Z")) / (60 * 60 * 24)));
    }

    /**
     * UNIX_TIMESTAMP - Return a UNIX timestamp
     *
     * @param string $date The date to be converted to a unix timestamp. Defaults to the current date/time.
     * @return int The number of seconds since the unix epoch.
     */
    public static function mysql_unix_timestamp($date = null)
    {
        if (!isset($date)) {
            return time();
        }

        return strtotime($date);
    }
    
    /**
     * FROM_UNIXTIME - returns a date /datetime from a version of unix_timestamp
     *
     * @param string $date The unit timestamp.
     * @param string $format Optional format string
     * @return datetime Date Time from timestamp.
     */
    public static function mysql_from_unixtime($date, $format = null)
    {
        if (!isset($format)) {
            $format = \DateTime::ISO8601;
        }

        return (new \DateTime())->setTimestamp($date)->format($format);

    }

    private static function dateWithFormat($date, $format)
    {
        if (!isset($date)) {
            return 0;
        }

        return (new \DateTime($date))->format($format);
    }

    /**
     * YEAR - returns the year from a date
     *
     * @param datetime $date date to get year from
     * @return int year from $date
     */
    public static function mysql_year($date)
    {
        return static::dateWithFormat($date,"Y");
    }

    /**
     * MONTH - returns the month from a date
     *
     * @param datetime $date date to get year from
     * @return int month from $date
     */
    public static function mysql_month($date)
    {
        return static::dateWithFormat($date,"m");

    }
}

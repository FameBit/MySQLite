<?php

namespace Vectorface\MySQLite\MySQL;

use InvalidArgumentException;

/**
 * Provides Comparison MySQL compatibility functions for SQLite.
 *
 * http://dev.mysql.com/doc/refman/5.7/en/comparison-operators.html
 */
trait Comparison
{
    /**
     * ISNULL - Returns 1 if the expression is null, 0 otherwise.
     *
     * @param $expr
     * @return int
     */
    public static function mysql_isnull($expr)
    {
        if (is_null($expr)) {
            return 1;
        }
        return 0;
    }

    /**
     * LEAST - Return the smallest argument
     *
     * @param mixed ... One or more numeric arguments.
     * @return mixed The argument whose value is considered lowest.
     */
    public static function mysql_least()
    {
        $args = func_get_args();
        if (!count($args)) {
            throw new InvalidArgumentException('No arguments provided to SQLite LEAST');
        }

        return min($args);
    }
}

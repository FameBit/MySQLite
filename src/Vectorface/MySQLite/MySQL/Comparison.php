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

    private static function checkArgs($args)
    {
       if (!count($args)) {
            throw new InvalidArgumentException('No arguments provided to function');
        } 
    }

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
        static::checkArgs($args);

        return min($args);
    }
    
    /**
    * GREATEST - Return greatest argument
    * @param mixed ... One or more arguments
    * @return mixed Greatest argument
    */
    public static function mysql_greatest()
    {
        $args = func_get_args();
        static::checkArgs($args);

        return max($args);
    }
}

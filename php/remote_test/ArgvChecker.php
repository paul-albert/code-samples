<?php
/**
 * @author Albert Paul
 * @package test
 *
 */

/**
 *  Class for check of $argv array (command-line arguments).
 */
class ArgvChecker
{
    /**
     * Checks for command-line arguments.
     * (Simply checking for command-line arguments array items count)
     * 
     * @param array $argv - array of command-line arguments
     * @return boolean - true/false
     */
    public static function checkArgvCount($argv)
    {
        return (count($argv) < Constants::MIN_ARGV_COUNT) ? false : true;
    }
}

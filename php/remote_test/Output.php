<?php
/**
 * @author Albert Paul
 * @package test
 *
 */

/**
 *  Class for work with message outputs.
 */
class Output
{
    /**
     * Print message about error and exits.
     * 
     * @param string $message - error message
     * @return void
     */
    public static function printAndExit($message)
    {
        exit($message . Constants::EOL);
    }
    
    /**
     * Prints information about aggregated data.
     * 
     * @param array $data - aggregated data array
     * @return void
     */
    public static function printAggregatedData($data)
    {
        if (!empty($data)) {
            print Constants::EOL . Constants::TOTALS_TITLE . Constants::EOL;
            foreach ($data as $key => $value) {
                print $key . ' ' . number_format($value, Constants::DECIMAL_PRECISION, Constants::DECIMAL_POINT, Constants::THOUSANDS_SEPARATOR) . Constants::EOL;
            }
        }
        print Constants::EOL;
    }
}
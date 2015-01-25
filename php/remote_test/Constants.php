<?php
/**
 * @author Albert Paul
 * @package test
 *
 */

/**
 * Class with constants.
 */
class Constants
{
    const EOL                     = PHP_EOL;                   // line ending character
    const MIN_ARGV_COUNT          = 2;                         // minimal count of command-line arguments
    const PAYMENT_REG_EXP_PATTERN = '/^(pay)\d{6}[a-z]{2}$/i'; // reg-exp pattern string for payment parsing
    const CURRENCY_COLUMN_TITLE   = 'Currency';                // currency column title in data
    const DEBIT_COLUMN_TITLE      = 'Debit';                   // debit column title in data
    const DECIMAL_PRECISION       = 2;                         // precision for decimals format
    const TOTALS_TITLE            = 'Totals';                  // title for print before aggregated data
    const DECIMAL_POINT           = '.';                       // character before decimals
    const THOUSANDS_SEPARATOR     = ',';                       // character between every groups of thousands
}
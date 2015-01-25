<?php
/**
 * @author Albert Paul
 * @package test
 *
 */

/**
 * Class for data aggregation.
 */
class CsvData
{
    /**
     * Aggregates parsed data.
     * 
     * @param array $data - data for aggregating
     * @return array - aggregated data array
     */
    public static function aggregateData($data)
    {
        $aggregated = array();
        foreach ($data as $item) {
            if (isset($aggregated[$item[Constants::CURRENCY_COLUMN_TITLE]])) {
                $aggregated[$item[Constants::CURRENCY_COLUMN_TITLE]] += $item[Constants::DEBIT_COLUMN_TITLE];
            } else {
                $aggregated[$item[Constants::CURRENCY_COLUMN_TITLE]] = $item[Constants::DEBIT_COLUMN_TITLE];
            }
        }
        return $aggregated;
    }
}
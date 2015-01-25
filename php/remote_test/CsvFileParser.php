<?php
/**
 * @author Albert Paul
 * @package test
 *
 */

/**
 *  Class for parsing of CSV file.
 */
class CsvFileParser
{
    /**
     * $this->filename - CSV file name
     * @var string $filename
     */
    private $filename;
    
    /**
     * Class constructor.
     * 
     * @param string $filename
     * @return void
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
    }
    
    /**
     * Checks for array item existence by regular expression.
     * 
     * @param array $row - checkable array
     * @return boolean - true if found, else false
     */
    private function checkRowForRegExp($row)
    {
        foreach ($row as $item) {
            if (preg_match(Constants::PAYMENT_REG_EXP_PATTERN, $item)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Parses CSV file into data as array.
     * 
     * @param string $delimiter - field delimiter for data inside CSV file
     * @return boolean | array - data array or false, if file not exists
     */
    public function parseCsvFileToArray($delimiter = ',')
    {
        if (!file_exists($this->filename) || !is_readable($this->filename)) {
            return false;
        }
        $header = null;
        $data = array();
        if (($handle = fopen($this->filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1024, $delimiter)) !== false) {
                if (is_null($header)) {
                    $header = $row;
                } else {
                    if ($this->checkRowForRegExp($row)) {
                        array_push($data, array_combine($header, $row));
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }
}
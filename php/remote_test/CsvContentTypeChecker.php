<?php
/**
 * @author Albert Paul
 * @package test
 *
 */

/**
 *  Class for check of CSV file MIME-content type.
 */
class CsvContentTypeChecker
{
    /**
     * $this->allowedCSVMimeContentTypes - array of allowed CSV MIME-content types
     * @var array $allowedCSVMimeContentTypes 
     */
    private $allowedCSVMimeContentTypes;
    
    /**
     * Class constructor.
     * 
     * @param array $allowedCSVMimeContentTypes
     * @return void
     */
    public function __construct($allowedCSVMimeContentTypes)
    {
        $this->allowedCSVMimeContentTypes = $allowedCSVMimeContentTypes;
    }
    
    /**
     * Gets MIME-content type of CSV file by its filename.
     * 
     * @param string $filename - CSV file name
     * @return string/boolean - MIME-content type/false
     */
    protected function getMimeType($filename)
    {
        // mime_content_type is deprecated function for PHP
        if (is_resource($finfo = finfo_open(FILEINFO_MIME_TYPE))) {
            $result = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $result; // returns MIME-type of file
        }
        return false;
    }
    
    /**
     * Checks for MIME-content type of CSV file, that is passed through command-line arguments earlier.
     * 
     * @param array $argv - array of command-line arguments
     * @return boolean - true/false
     */
    public function checkMimeContentType($argv)
    {
        $csvContentTypeIterator = new CsvContentTypeIterator($this->allowedCSVMimeContentTypes);
        return (!$csvContentTypeIterator->hasItem($this->getMimeType($argv[1]))) ? false : true;
    }
}

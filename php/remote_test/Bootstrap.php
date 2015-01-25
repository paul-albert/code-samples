<?php
/**
 * @author Albert Paul
 * @package test
 *
 */

// classes loading - for PHP >= 5.3.0
spl_autoload_register(function ($class) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . $class . '.php';
});

class Bootstrap
{
    /**
     * Main method of class.
     * 
     * @param array $argv - array with command-line arguments
     * @return void
     */
    public static function main($argv)
    {
        // first we need to check command-line arguments
        if (!ArgvChecker::checkArgvCount($argv)) {
            Output::printAndExit('USAGE:' . Constants::EOL . 'php ' . $argv[0] . ' FileName.csv');
        }
        
        // array of allowed CSV MIME-content types (short notation for array, PHP >= 5.4)
        $allowedCSVMimeContentTypes = [
            'text/csv',
            'text/plain',
            'application/csv',
            'text/comma-separated-values',
            'application/excel',
            'application/vnd.ms-excel',
            'application/vnd.msexcel',
            'text/anytext',
            'application/octet-stream',
            'application/txt',
            'application/download',
        ];
        // then we need to check MIME-content type of CSV-file that was passed through $argv
        $csvContentTypeChecker = new CsvContentTypeChecker($allowedCSVMimeContentTypes);
        if (!$csvContentTypeChecker->checkMimeContentType($argv)) {
            Output::printAndExit('ERROR:' . Constants::EOL . 'File "' . $argv[1] . '" is not allowable MIME content type.');
        }
        
        // now we can parse data (inside CSV file) into data array
        $csvFileParser = new CsvFileParser($argv[1]);
        $data = $csvFileParser->parseCsvFileToArray();
        
        // and we want to aggregate data
        $data = CsvData::aggregateData($data);
        
        // last - show info about aggregated data
        Output::printAggregatedData($data);
    }
}

Bootstrap::main($argv);

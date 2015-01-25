<?php

/**
 * Parses and imports games
 *
 * Command to make imports:
 * 
 * 1. /path/to/public_html/protected/yiic games parse
 * 
 * (Please do sure that file yiic is allowable to run (chmod +x file))
 * (for first command may be passed additional parameter for horoscope type,
 * for example:
 *     /path/to/public_html/protected/yiic games parse --categoryId=123 --categoryPath=/path/to/project/category123
 * )
 * 
 */

class GamesCommand extends ConsoleCommand
{
    const FILE_TYPE_IMAGE = 'image';
    const FILE_TYPE_SWF = 'swf';
    const FILE_TYPE_TXT = 'txt';
    const FILE_TYPE_UNKNOWN = 'unknown';
    
    /**
     * Main action for parse and import of games.
     * 
     * @param integer $categoryId - category id for parsing
     * @param string $categoryPath - full path to directory with games files for parsing
     */
    public function actionParse($categoryId, $categoryPath)
    {
        $date = date('Y-m-d');
        
        $categorySubDirectories = $this->getCategorySubDirectories($categoryPath);
        foreach ($categorySubDirectories as $categorySubDirectory) {
            
            $files = $this->getSubDirectoryFiles($categorySubDirectory);
            
            $gameObject = new Game();
            $gameObject->name = trim(basename($categorySubDirectory));
            $gameObject->description = $this->getInfoFromFiles($files, 'txt');
            $gameObject->category_id = (int) $categoryId;
            $gameObject->swf = $this->getInfoFromFiles($files, 'swf');
            $gameObject->image = $this->getInfoFromFiles($files, 'image');
            $gameObject->mark = rand(4, 5);
            $gameObject->status = 1;
            $gameObject->date_publish = $date;
            $gameObject->insert();
            
            // handle of games files (SWF and images)
            if (!empty($gameObject->swf)) {
                copy($this->getInfoFromFiles($files, 'swfPath'), $gameObject->getSwfPath());
            }
            if (!empty($gameObject->image)) {
                copy($this->getInfoFromFiles($files, 'imagePath'), $gameObject->getPreviewPath());
            }
        }
    }
    
    /**
     * Gets category's subdirectories.
     * 
     * @param string $categoryPath - path to category
     * @return array - category subdirectories array
     */
    private function getCategorySubDirectories($categoryPath)
    {
        $subDirectories = array();
        if (!empty($categoryPath)) {
            $directories = new \DirectoryIterator($categoryPath); 
            foreach ($directories as $fileInfo) {
                if (!$fileInfo->isDot() && $fileInfo->isDir()) {
                    array_push($subDirectories, $fileInfo->getPathname());
                }
            }
            sort($subDirectories);
        }
        return $subDirectories;
    }
    
    /**
     * Gets subdirectory's files.
     * 
     * @param string $categorySubDirectory - path to subdirectory
     * @return array - subdirectory files array
     */
    private function getSubDirectoryFiles($categorySubDirectory)
    {
        $subDirectoryFiles = array();
        $files = new \FilesystemIterator($categorySubDirectory);
        foreach ($files as $fileInfo) {
            $file = array(
                'pathname' => $fileInfo->getPathname(),
                'extension' => strtolower($fileInfo->getExtension()),
                'type' => '',
            );
            switch ($file['extension']) {
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                    $file['type'] = self::FILE_TYPE_IMAGE;
                    break;
                case 'swf':
                    $file['type'] = self::FILE_TYPE_SWF;
                    break;
                case 'txt':
                    $file['type'] = self::FILE_TYPE_TXT;
                    break;
                default:
                    $file['type'] = self::FILE_TYPE_UNKNOWN;
                    break;
            }
            array_push($subDirectoryFiles, $file);
        }
        return $subDirectoryFiles;
    }
    
    /**
     * Gets info from files array by attribute.
     * 
     * @param array $files - files array
     * @param string $attribute - attribute for search info
     * @return string - info by attribute
     */
    private function getInfoFromFiles($files, $attribute)
    {
        $info = '';
        switch ($attribute) {
            case 'image':
                foreach ($files as $file) {
                    if ($file['type'] === self::FILE_TYPE_IMAGE) {
                        $info = $file['extension'];
                        break;
                    }
                }
                break;
            case 'imagePath':
                foreach ($files as $file) {
                    if ($file['type'] === self::FILE_TYPE_IMAGE) {
                        $info = $file['pathname'];
                        break;
                    }
                }
                break;
            case 'swf':
                foreach ($files as $file) {
                    if ($file['type'] === self::FILE_TYPE_SWF) {
                        $info = 'swf';
                        break;
                    }
                }
                break;
            case 'swfPath':
                foreach ($files as $file) {
                    if ($file['type'] === self::FILE_TYPE_SWF) {
                        $info = $file['pathname'];
                        break;
                    }
                }
                break;
            case 'txt':
                foreach ($files as $file) {
                    if ($file['type'] === self::FILE_TYPE_TXT) {
                        $info = trim($this->encoding(file_get_contents($file['pathname'])));
                        break;
                    }
                }
                break;
            default:
                break;
        }
        return $info;
    }
    
    /**
     * Checks string for is it of UTF-8 encoding.
     * 
     * @param string $string - string for check
     * @return boolean - true / false
     */
    private function detectUTF8($string)
    {
        return preg_match('%(?:
            [\xC2-\xDF][\x80-\xBF]              # non-overlong 2-byte
            |\xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
            |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            |\xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
            |\xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
            |[\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            |\xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )+%xs',
        $string);
    }
    
    /**
     * Converts string from cp1251 to utf8 encoding.
     * 
     * @param string $stringInput - input string
     * @return string - converted string
     */
    private function cp1251TOutf8($stringInput)
    {
        $stringOutput = '';
        $length = strlen($stringInput);
        for ($i = 0; $i < $length; $i++) {
            $iAscii = ord($stringInput[$i]);
            if ($iAscii >= 192 && $iAscii <= 255) {
                $stringOutput .=  "&#" . (1040 + ($iAscii - 192)) . ";";
            } else if ($iAscii == 168) {
                $stringOutput .= "&#" . (1025) . ";";
            } else if ($iAscii == 184) {
                $stringOutput .= "&#" . (1105) . ";";
            } else {
                $stringOutput .= $stringInput[$i];
            }
        }
        return $stringOutput;
    }
    
    /**
     * Encodes string to UTF-8.
     * 
     * @param string $string - string for encode
     * @return string - encoded string
     */
    private function encoding($string)
    {
        if (function_exists('iconv')) {
            if (@!iconv('utf-8', 'cp1251', $string)) { // ugly hack :(
                $string = iconv('cp1251', 'utf-8', $string);
            }
            return $string;
        } else {
            return ($this->detectUTF8($string)) ? $string : $this->cp1251TOutf8($string);
        }
    }

}

?>

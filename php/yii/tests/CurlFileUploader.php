<?php

class CurlFileUploader
{
    
    public $uploadURL;
    public $formFileVariableName;
    public $postParams = array();
    public $curlOptions = array();
    public $curlHandle;

    /**
     * Class constructor.
     * 
     * @param string $filePath - absolute path of file
     * @param string $uploadURL - url for upload file
     * @param string $formFileVariableName - form field name to upload file
     * @param array $otherParams - assosiative array of other params which you want to send as post (by default empty array)
     */
    public function __construct($filePath, $uploadURL, $formFileVariableName, $otherParams = array())
    {
        if (!file_exists($filePath)) {
            print "\n" . 'Unable to find file.';
            exit();
        }
        $this->uploadURL = $uploadURL;
        if (!empty($otherParams) && is_array($otherParams)) {
            foreach ($otherParams as $fieldKey => $fieldValue) {
                $this->postParams[$fieldKey] = $fieldValue;
            }
        }
        $this->postParams[$formFileVariableName] = '@' . $filePath . ';filename=' . basename($filePath) . ';type=' . mime_content_type($filePath);
        $this->curlHandle = curl_init();
        $this->curlOptions = array(
            //CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:24.0) Gecko/20100101 Firefox/24.0',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 30,
        );
    }

    /**
     * Makes authorization through login/password pair.
     * 
     * @param string $loginURL - url for authorization
     * @param string $login - login for authorization
     * @param string $password - password for authorization
     */
    public function login($loginURL, $login, $password)
    {
        $cookieFile = tempnam('/tmp', 'CURLCOOKIE_'); // very important for store of cookies / sessions to upload in future
        $curlOptions = $this->curlOptions;
        $curlOptions[CURLOPT_URL] = $loginURL;
        $curlOptions[CURLOPT_COOKIEFILE] = $cookieFile;
        $curlOptions[CURLOPT_COOKIEJAR] = $cookieFile;
        $curlOptions[CURLOPT_POST] = true;
        $curlOptions[CURLOPT_POSTFIELDS] = array('AdminLoginForm[login]' => $login, 'AdminLoginForm[password]' => $password);
        curl_setopt_array($this->curlHandle, $curlOptions);
        $postResult = curl_exec($this->curlHandle);
        if (curl_errno($this->curlHandle)) {
            print curl_error($this->curlHandle);
            print "\n" . 'Unable to login.';
            curl_close($this->curlHandle);
            exit();
        }
    }
    
    /**
     * File upload function (through CURL-extension in PHP)
     */
    public function uploadFile()
    {
        $curlOptions = $this->curlOptions;
        $curlOptions[CURLOPT_URL] = $this->uploadURL;
        $curlOptions[CURLOPT_POST] = true;
        $curlOptions[CURLOPT_POSTFIELDS] = $this->postParams;
        curl_setopt_array($this->curlHandle, $curlOptions);
        $postResult = curl_exec($this->curlHandle);
        if (curl_errno($this->curlHandle)) {
            print curl_error($this->curlHandle);
            print "\n" . 'Unable to upload file.';
            exit();
        }
        curl_close($this->curlHandle);
        return $postResult;
    }
}

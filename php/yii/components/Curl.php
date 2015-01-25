<?php

/**
 * Class to work with curl with single or miltiple requests
 */
class Curl
{

    /**
     * Single request to given url
     * @param mixed $data string if url and array if url with post value
     * @param array $options curl options
     * 
     * @return mixed false on error and string with result on success
     */
    public static function request($data, $options = array())
    {
        $curl = self::prepareCurl($data, $options);

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }

    /**
     * Multi request of given urls
     * @param array $data Array of mixed - strings if urls and arrays if urls with post values
     * @param array $options curl options
     * @return array of mixed - false on error and string with result on success
     */
    public static function requestMulti($data, $options = array())
    {
        $curls = array();
        $result = array();
        $mh = curl_multi_init();

        foreach ($data as $id => $d) {
            $curls[$id] = self::prepareCurl($d, $options);
            curl_multi_add_handle($mh, $curls[$id]);
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        foreach ($curls as $id => $c) {
            $result[$id] = curl_multi_getcontent($c);
            curl_multi_remove_handle($mh, $c);
            curl_close($c);
        }

        curl_multi_close($mh);

        return $result;
    }
    
    /**
     * Makes parrallel requests by getting $parrallelsCount requests from $data array and passing results to $processData callback
     * @param int $parrallelsCount
     * @param array $data Array of mixed - strings if urls and arrays if urls with post values
     * @param Callable $processData
     * @param array $options
     * @example 
     * $links = array(
            'http://forbes.ua/',
            'http://obozrevatel.com/',
            'http://www.1plus1.ua/',
            'http://www.microsoft.com',
        );
     * Curl::requestParrallel(2, $links, function($id, $res) {
            echo "\n", $id, "\n", substr($res, 0, 100);
        });
     */
    public static function requestParrallel($parrallelsCount, $data, Callable $processData, $options = array())
    {
        $mh = curl_multi_init();

        $count = count($data) < $parrallelsCount ? count($data) : $parrallelsCount;
        for ($i = 0; $i < $count; $i++) {
            list($id, $d) = each($data);
            $curls[$id] = self::prepareCurl($d, $options);
            curl_multi_add_handle($mh, $curls[$id]);
        }
        
        $active = null;
        do {
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            
            if ($mrc != CURLM_OK) {
                break;
            }
            
            // a request was just completed -- find out which one
            while ($done = curl_multi_info_read($mh)) {
                $doneId = array_search($done['handle'], $curls);
                // request successful.  process output using the callback function.
                $processData($doneId, curl_multi_getcontent($done['handle']));

                // start a new request (it's important to do this before removing the old one) TODO: is this comment correct????
                if ((list($id, $d) = each($data))) {
                    $curls[$id] = self::prepareCurl($d, $options);
                    curl_multi_add_handle($mh, $curls[$id]);
                }

                // remove the curl handle that just completed
                curl_multi_remove_handle($mh, $done['handle']);
                if ($doneId) {
                    unset($curls[$doneId]);
                }
            }
        } while ($active);

        curl_multi_close($mh);
    }

    /**
     * Makes parrallel requests by getting $parrallelsCount requests from $getData callback and passing results to $processData callback
     * @param int $parrallelsCount
     * @param Callable $getData
     * @param Callable $processData
     * @param array $options
     * @example
     * $links = array(
            'http://forbes.ua/',
            'http://obozrevatel.com/',
            'http://www.1plus1.ua/',
            'http://www.microsoft.com',
        );
     * Curl::requestParrallel2(2, function(&$current) use($links) {
            if (is_null($current)) {
                $current = 0;
            } else {
                $current++;
            }
            if ($current < count($links)) {
                return $links[$current];
            } else {
                return null;
            }
        }, function($id, $res) {
            echo "\n", $id, "\n", substr($res, 0, 100);
        });
     */
    public static function requestParrallel2($parrallelsCount, Callable $getData, Callable $processData, $options = array())
    {
        $mh = curl_multi_init();

        $id = null;
        for ($i = 0; $i < $parrallelsCount; $i++) {
            $d = $getData($id);
            if (empty($d)) {
                break;
            }
            $curls[$id] = self::prepareCurl($d, $options);
            curl_multi_add_handle($mh, $curls[$id]);
        }
        
        $active = null;
        do {
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            
            if ($mrc != CURLM_OK) {
                break;
            }
            
            // a request was just completed -- find out which one
            while ($done = curl_multi_info_read($mh)) {
                $doneId = array_search($done['handle'], $curls);
                // request successful.  process output using the callback function.
                $processData($doneId, curl_multi_getcontent($done['handle']));

                // start a new request (it's important to do this before removing the old one) TODO: is this comment correct????
                $d = $getData($id);
                if (!empty($d)) {
                    $curls[$id] = self::prepareCurl($d, $options);
                    curl_multi_add_handle($mh, $curls[$id]);
                }

                // remove the curl handle that just completed
                curl_multi_remove_handle($mh, $done['handle']);
                if ($doneId) {
                    unset($curls[$doneId]);
                }
            }
        } while ($active);

        curl_multi_close($mh);
    }

    /**
     * Prepares curl for exec
     * @param mixed $data string if url and array if url with post value
     * @param array $options curl options
     * @return handler initialized curl 
     */
    private static function prepareCurl($data, $options = array())
    {
        $curl = curl_init();

        // get url from array if $d is array (like when sending post) or whole $d otherwise
        $url = (is_array($data) && !empty($data['url'])) ? $data['url'] : $data;

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        // process post if needed
        if (is_array($data) && !empty($data['post'])) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data['post']);
        }

        // add options if needed
        if (count($options) > 0) {
            curl_setopt_array($curl, $options);
        }

        return $curl;
    }

}

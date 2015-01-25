<?php

/**
 * Class to parse XML content.
 */

class XmlParser
{

    /**
     * Parses XML content as array.
     * 
     * @param string $content XML content as string
     * @param integer $getAttributes 1 or 0 for get attributes too
     * @param string $priority priority for tags parse
     * 
     * @return array parsed array for XML content
     */
    public static function parseXmlAsArray($content, $getAttributes = 1, $priority = 'tag')
    {
        if (!$content) {
            return array();
        }

        if (!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!"; 
            return array();
        }

        // get the XML parser of PHP - PHP must have this module for the parser to work 
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($content), $xmlValues);
        xml_parser_free($parser);

        // exit if xml values are not found
        if (!$xmlValues) {
            return;
        }
        
        // initialization for arrays
        $xmlArray = array();
        $parents = array();
        $openedTags = array();
        $arr = array();

        // reference for array of result
        $current = &$xmlArray;
        // go through the tags.
        // multiple tags with same name will be turned into an array
        $repeatedTagIndex = array();
        foreach ($xmlValues as $data) {
            // remove existing values, or there will be trouble
            unset($attributes, $value);
            // this command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            // we could use the array by itself, but this cooler. 
            extract($data);

            $result = array();
            $attributesData = array();

            if (isset($value)) {
                if ($priority == 'tag') {
                    $result = $value;
                } else {
                    // put the value in a assoc array if we are in the 'Attribute' mode
                    $result['value'] = $value;
                }
            }

            // set the attributes too.
            if (isset($attributes) && $getAttributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == 'tag') {
                        $attributesData[$attr] = $val;
                    } else {
                        // set all the attributes in a array called 'attr'
                        $result['attr'][$attr] = $val;
                    }
                }
            }

            // see tag status and do the needed.
            if ($type == "open") {
                // the starting of the tag '<tag>'
                $parent[$level - 1] = &$current;
                if (!is_array($current) or (!in_array($tag, array_keys($current)))) {
                    // insert new tag
                    $current[$tag] = $result;
                    if ($attributesData) {
                        $current[$tag . '_attr'] = $attributesData;
                    }
                    $repeatedTagIndex[$tag . '_' . $level] = 1;
                    $current = &$current[$tag];
                } else {
                    // there was another element with the same tag name
                    // if there is a 0th element it is already an array
                    if (isset($current[$tag][0])) {
                        $current[$tag][$repeatedTagIndex[$tag . '_' . $level]] = $result;
                        $repeatedTagIndex[$tag . '_' . $level]++;
                    } else {
                        // this section will make the value an array if multiple tags with the same name appear together
                        // this will combine the existing item and the new item together to make an array
                        $current[$tag] = array($current[$tag], $result);
                        $repeatedTagIndex[$tag . '_' . $level] = 2;

                        // the attribute of the last(0th) tag must be moved as well
                        if (isset($current[$tag . '_attr'])) {
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }
                    }
                    $lastItemIndex = $repeatedTagIndex[$tag . '_' . $level] - 1;
                    $current = &$current[$tag][$lastItemIndex];
                }
            } elseif ($type == "complete") {
                // tags that ends in 1 line '<tag />'
                // see if the key is already taken.
                if (!isset($current[$tag])) {
                    // new key
                    $current[$tag] = $result;
                    $repeatedTagIndex[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' && $attributesData) {
                        $current[$tag . '_attr'] = $attributesData;
                    }
                } else {
                    // if taken, put all things inside a list(array)
                    if (isset($current[$tag][0]) && is_array($current[$tag])) {
                        // if it is already an array...
                        // ...push the new element into that array. 
                        $current[$tag][$repeatedTagIndex[$tag . '_' . $level]] = $result;

                        if ($priority == 'tag' && $getAttributes && $attributesData) {
                            $current[$tag][$repeatedTagIndex[$tag . '_' . $level] . '_attr'] = $attributesData;
                        }
                        $repeatedTagIndex[$tag . '_' . $level]++;
                    } else {
                        // if it is not an array...
                        //...make it an array using using the existing value and the new value
                        $current[$tag] = array($current[$tag], $result);
                        $repeatedTagIndex[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' && $getAttributes) {
                            // the attribute of the last(0th) tag must be moved as well
                            if (isset($current[$tag . '_attr'])) {
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset($current[$tag . '_attr']);
                            }

                            if ($attributesData) {
                                $current[$tag][$repeatedTagIndex[$tag . '_' . $level] . '_attr'] = $attributesData;
                            }
                        }
                        // 0 and 1 index is already taken
                        $repeatedTagIndex[$tag . '_' . $level]++;
                    }
                }
            } elseif ($type == 'close') {
                // end of tag '</tag>'
                $current = &$parent[$level - 1];
            }
        }

        return($xmlArray);
    }

}

?>

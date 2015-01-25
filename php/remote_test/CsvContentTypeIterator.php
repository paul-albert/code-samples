<?php
/**
 * @author Albert Paul
 * @package test
 *
 */

/**
 * Class for array iterator.
 */
class CsvContentTypeIterator extends ArrayIterator
{
    /**
     * Searches in array iterator for item by its value.
     * (Behavior as in_array built-in function)
     * 
     * @param string $value
     * @return boolean - true/false
     */
    public function hasItem($value)
    {
        foreach ($this as $v) {
            if ($v == $value) {
                return true;
            }
        }
        return false;
    }
}
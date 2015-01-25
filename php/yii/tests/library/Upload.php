<?php

namespace library;

class Upload
{

    /**
     * Destination directory
     *
     * @var string
     */
    protected $_destination;

    /**
     * Constructor
     *
     * @param string $str
     */
    public function __construct($destination)
    {
        $this->_destination = rtrim($destination, '/');
    }

    public function upload($name)
    {
        if (!isset($_FILES[$name]) || !is_uploaded_file($_FILES[$name]['tmp_name'])) {
            return false;
        }
        return move_uploaded_file($_FILES[$name]['tmp_name'], $this->_destination . '/' . $_FILES[$name]['name']);
    }

}

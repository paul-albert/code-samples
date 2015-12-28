<?php
class DbBase
{
    /**
     * @var PDO connection to DB
     */
    protected $db = null;
    
    /**
     * @var array field => value
     */
    public $params = array();

    /**
     * @var boolean if such record in table
     */
    public $hasRecord = false;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        return '';
    }
    
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        } else {
            $this->params[$name] = $value;
        }
    }
}

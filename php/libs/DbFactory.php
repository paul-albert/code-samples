<?php

/**
 * Description of DbFactory
 */
class DbFactory
{
    /**
     * Creates PDO connection using $config data
     * @param array $config
     * @return \PDO
     */
    public static function createDb($config)
    {
        $db = new PDO($config['connectionString'], $config['username'], $config['password']);
        $db->exec("SET NAMES ".$config['charset']);
        $db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
        
        return $db;
    }
    
    /**
     * Checks if db connnection is alive
     * @param PDO $db
     * @param array $config
     * @return PDO
     */
    public static function ping($db, $config)
    {
        if (!($db instanceof PDO)) {
            return DbFactory::createDb($config);
        }
        
        try {
            $db->query('SELECT 1');
        } catch (Exception $e) {
            $db = DbFactory::createDb($config);
        }
        
        return $db;
    }
}
<?php

/**
 * Class for work with MySQL database.
 */
class MysqlCommand
{
    /**
     *
     * @var connection - PDO object for database connection 
     */
    protected static $connection = null;
    
    /**
     * Enables / disables foreign key checks for database connection.
     * 
     * @param PDO $connection - database connection
     * @param bool $check - true for enable, false for disable
     */
    public static function checkIntegrity($connection, $check = true)
    {
        self::$connection = $connection;
        self::$connection->exec('SET FOREIGN_KEY_CHECKS = ' . ($check ? 1 : 0));
    }
    
    /**
     * Checks for table existence in database
     * 
     * @param PDO $connection - database connection
     * @param string $tableName - cleanable table name
     * @return boolean TRUE if table exists, FALSE if no table found
     */
    public static function tableExists($connection, $tableName)
    {
        // try a select statement against the table
        // run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
        self::$connection = $connection;
        try {
            $result = self::$connection->query('SELECT 1 FROM ' . $tableName . ' LIMIT 1');
        } catch (Exception $e) {
            // if we got an exception, then table not found
            return false;
        }
        // result is either boolean FALSE (no table found) or PDOStatement object (table found)
        return $result !== false;
    }
    
    /**
     * Deletes all rows from table.
     * 
     * @param PDO $connection - database connection
     * @param string $tableName - cleanable table name
     */
    public static function cleanTable($connection, $tableName)
    {
        self::$connection = $connection;
        self::$connection->exec('DELETE FROM ' . $tableName);
    }
    
    /**
     * Gets database table's primary key field name.
     * 
     * @param PDO $connection - database connection
     * @param string $tableName - the table name whose primary key needs to be found
     * @return mixed the string / array value for the primary key field. If this is not found, return null.
     */
    public static function getPrimaryKey($connection, $tableName)
    {
        self::$connection = $connection;
        // try to get information about database table primary keys by table name
        $query = self::$connection->prepare("SHOW INDEX FROM " . $tableName . " WHERE key_name = 'PRIMARY'");
        $query->execute();
        $rows = $query->fetchAll();
        if (!empty($rows) && is_array($rows)) {
            $primaryKeys = array();
            foreach ($rows as $row) {
                // we search column names that are related to PK
                if (!empty($row['Column_name'])) {
                    $primaryKeys[] = $row['Column_name'];
                }
            }
            if (count($primaryKeys) == 1) {
                return $primaryKeys[0]; // return as string
            } elseif (count($primaryKeys) > 1) {
                return $primaryKeys; // return as array
            } else {
                return null; // return empty result
            }
        }
        return null;
    }
    
    /**
     * Resets the sequence value of a table's primary key.
     * The sequence will be reset such that the primary key of the next new row inserted will have the specified value or 1.
     * 
     * @param PDO $connection - database connection
     * @param string $tableName - the table name whose primary key sequence will be reset
     * @param mixed $value the value for the primary key of the next new row inserted. If this is not set, nothing will be set.
     */
    public static function resetSequence($connection, $tableName, $value = null)
    {
        self::$connection = $connection;
        // if primary key exists in table, then we can reset its sequence
        $primaryKey = self::getPrimaryKey(self::$connection, $tableName);
        if (!is_null($primaryKey) && is_string($primaryKey) && !is_null($value)) {
            self::$connection->exec('ALTER TABLE ' . $tableName . ' AUTO_INCREMENT = ' . ((int) $value));
        }
    }
    
    /**
     * Gets records count in database table.
     * 
     * @param PDO $connection - database connection
     * @param string $tableName - the table name whose primary key needs to be found
     * @return integer records count in database table.
     */
    public static function recordsCount($connection, $tableName)
    {
        self::$connection = $connection;
        $res = self::$connection->query('SELECT COUNT(*) FROM ' . $tableName);
        return (int) $res->fetch(PDO::FETCH_NUM)[0];
    }
}
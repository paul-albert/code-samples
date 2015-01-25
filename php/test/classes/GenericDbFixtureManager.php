<?php

/**
 * Generic class that manages database fixtures during tests.
 */
class GenericDbFixtureManager
{
    /**
     * @var string the base path containing all fixtures. Defaults to null.
     */
    protected $basePath;
    /**
     * @var string the alias of the database connection. Defaults to 'db'.
     * Note, data in this database may be deleted or changed during testing.
     */
    protected $dbConnectionAlias = 'db';
    /**
     * @var db - current database connection (through PDO)
     */
    private $_db;
    /**
     *
     * @var dbPool - database connections pool (as array of PDO objects)
     */
    private $_dbPool;
    /**
     * @var rows - array with rows of fixtures data (fixture name, row alias => row)
     */
    private $_rows;
    /**
     * @var records - array with records of fixtures data (fixture name, row alias => record (or class name))
     */
    private $_records;

    /**
     * Class constructor.
     * 
     * @param string $basePath - base path for fixtures files
     */
    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }
    
    /**
     * Sets internal database connections pool.
     * 
     * @param array $dbPool - array of PDO-based databases connections
     */
    public function setDbPool($dbPool)
    {
        $this->_dbPool = $dbPool;
    }
    
    /**
     * Returns the database connection used to load fixtures.
     * 
     * @return DbFactory the database connection
     */
    public function getDbConnection()
    {
        // check for database connection property is set already
        if (is_null($this->_db)) {
            // if no, then get database connection from database connections pool by its alias
            $this->_db = $this->_dbPool[$this->dbConnectionAlias];
            // additional type check for created database connection
            if (!($this->_db instanceof PDO)) {
               throw new PDOException('Database connection error - value of connection alias "' . $this->dbConnectionAlias . '" is invalid. Please check config file.');
            }
        }
        return $this->_db;
    }

    /**
     * Enables or disables current database integrity check.
     * This method may be used to temporarily turn off foreign constraints check.
     * 
     * @param boolean $check - whether to enable database integrity check
     */
    public function checkIntegrity($check = true)
    {
        // turns on/off check integrity for current database
        MysqlCommand::checkIntegrity($this->getDbConnection(), $check);
    }

    /**
     * Resets the table to the state that it contains no fixture data.
     * {@link truncateTable} will be invoked to delete all rows in the table and reset primary key sequence.
     * 
     * @param string $tableName - the table name
     */
    public function resetTable($tableName)
    {
        $this->truncateTable($tableName);
    }

    /**
     * Removes all rows from the specified table and resets its primary key sequence, if any.
     * You may need to call {@link checkIntegrity} to turn off integrity check temporarily before you call this method.
     * 
     * @param string $tableName - the table name
     */
    public function truncateTable($tableName)
    {
        // check for table exists
        if (MysqlCommand::tableExists($this->getDbConnection(), $tableName) !== false) {
            // clean table
            MysqlCommand::cleanTable($this->getDbConnection(), $tableName);
            // reset table sequence
            MysqlCommand::resetSequence($this->getDbConnection(), $tableName, 1);
        } else {
            throw new PDOException('Table "' . $tableName . '" does not exist.');
        }
    }

    /**
     * Loads the fixture for the specified table.
     * This method will insert rows given in the fixture into the corresponding table.
     * The loaded rows will be returned by this method.
     * If the table has auto-incremental primary key, each row will contain updated primary key value.
     * If the fixture does not exist, this method will return false.
     * Note, you may want to call {@link resetTable} before calling this method
     * so that the table is emptied first.
     * 
     * @param string $tableName - table name
     * @return array the loaded fixture rows indexed by row aliases (if any). false is returned if the table does not have a fixture.
     */
    public function loadFixture($tableName)
    {
        // set file name for fixture
        $fileName = $this->basePath . DIRECTORY_SEPARATOR . $tableName . '.php';
        // check that file exists
        if (!is_file($fileName)) {
            return false;
        }
        $rows = array();
        $db = $this->getDbConnection();
        // read file with fixtures data
        $fixtureData = require($fileName);
        // prepare database table object
        $dbTable = new DbTable($db, $tableName);
        $primaryKey = MysqlCommand::getPrimaryKey($db, $tableName);
        foreach ($fixtureData as $alias => $row) {
            // insert current row from fixture data to table (not bulk insert mode!)
            $dbTable->insert($row, true);
            if (!is_null($primaryKey)) {
                if (is_string($primaryKey) && !isset($row[$primaryKey])) {
                    $row[$primaryKey] = $db->lastInsertId();
                } elseif(is_array($primaryKey)) {
                    foreach ($primaryKey as $pk) {
                        if (!isset($row[$pk])) {
                            $row[$pk] = $db->lastInsertId();
                            break;
                        }
                    }
                }
            }
            $rows[$alias] = $row;
        }
        return $rows;
    }

    /**
     * Loads the specified fixtures.
     * For each fixture, the corresponding table will be reset first by calling
     * {@link resetTable} and then be populated with the fixture data.
     * The loaded fixture data may be later retrieved using {@link getRows}.
     * (Note: {@link resetTable} will be called to reset the table in any case.)
     * 
     * @param array $metaFixtures - meta fixtures array to be loaded (with database aliases and table names).
     * The array keys are named database connections names ("aliases"), and the array values are arrays with table names.
     */
    public function load($metaFixtures)
    {
        $this->_rows = array();
        $this->_records = array();
        foreach ($metaFixtures as $dbAlias => $fixtures) {
            // set database connection id
            $this->dbConnectionAlias = $dbAlias;
            // turn off check of database integrity for foreign keys
            $this->checkIntegrity(false);
            if (!empty($fixtures) && is_array($fixtures)) {
                // load current fixture data to database tables
                foreach ($fixtures as $tableName) {
                    // clean current table
                    $this->resetTable($tableName);
                    // load fixture to current table
                    $rows = $this->loadFixture($tableName);
                    if (is_array($rows) && is_string($tableName)) {
                        // and assign fixture data to rows for get fixture data later
                        $this->_rows[$tableName] = $rows;
                        foreach (array_keys($rows) as $fixtureAlias) {
                            $this->_records[$tableName][$fixtureAlias] = $this->dbConnectionAlias . ':' . $tableName;
                        }
                    }
                }
            }
            // turn on check of database integrity for foreign keys
            $this->checkIntegrity(true);
            // we must unset internal database handle for reconnect to next database
            $this->_db = null;
        }
    }

    /**
     * Returns the fixture data rows.
     * The rows will have updated primary key values if the primary key is auto-incremental.
     * 
     * @param string $name - the fixture name
     * @return array the fixture data rows. False is returned if there is no such fixture data.
     */
    public function getRows($name)
    {
        return (isset($this->_rows[$name])) ? $this->_rows[$name] : false;
    }
    
    /**
     * Returns the specified database record instance in the fixture data.
     * 
     * @param string $name the fixture name
     * @param string $alias the alias for the fixture data row
     * @return DbTable the database record instance. False is returned if there is no such fixture row.
     */
    public function getRecord($name, $alias)
    {
        if (strpos($alias, ':') !== false) {
            list($dbAlias, $recordAlias) = explode(':', $alias);
        } else {
            return false;
        }
        if (isset($this->_records[$name][$recordAlias])) {
            if (is_string($this->_records[$name][$recordAlias])) {
                list($dbAlias, $tableName) = explode(':', $this->_records[$name][$recordAlias]);
                $row = $this->_rows[$name][$recordAlias];
                // we must unset internal database connection handle for reconnect to specified database by its alias
                $this->_db = null;
                $this->dbConnectionAlias = $dbAlias;
                $db = $this->getDbConnection();
                $primaryKey = MysqlCommand::getPrimaryKey($db, $tableName);
                if (!is_null($primaryKey)) {
                    // prepare database table object for work
                    $dbTable = new DbTable($db, $tableName);
                    // simple string for primary key in table
                    if (is_string($primaryKey)) {
                        $dbTable->populateById($row[$primaryKey]); // because we want select by id
                    } else {
                        // array for primary key in table ("composite primary key")
                        $pk = array();
                        foreach ($primaryKey as $k) {
                            $pk[$k] = $row[$k];
                        }
                        $dbTable->populateByAttr($pk); // because we want select by attributes
                    }
                    // check if found record in table
                    if ($dbTable->hasRecord) {
                        $this->_records[$name][$recordAlias] = $dbTable->params;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }
            return $this->_records[$name][$recordAlias];
        } else {
            return false;
        }
    }
}
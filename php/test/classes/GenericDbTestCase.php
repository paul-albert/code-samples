<?php

/**
 * Generic class for testing through database.
 */
class GenericDbTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var memcached - instance of connection for Memcache
     */
    protected static $memcached;
    /**
     * @var dbPool - pool of PDO-based connections for testing databases
     */
    protected static $dbPool;
    /**
     * @var fixtures - array with fixtures data
     */
    protected static $fixtures = array();
    /**
     * @var fixtureManager - fixtures manager object
     */
    protected static $fixtureManager;
    /**
     * @var fixtureBasePath - base path for fixtures files
     */
    protected static $fixtureBasePath = '';
    
    /**
     * Method that is called only once per class, and even before object is constructed.
     * (That is reason why it is marked as static public function.)
     */
    public static function setUpBeforeClass()
    {
        // calls related method of parent class
        parent::setUpBeforeClass();
    }
    
    /**
     * Preparing of fixtures.
     * Establishes database and memcache connections for all tests in this test class
     * and loads defined fixtures to databases.
     * (Reason why it is marked as static public function: it is called in static method of child class.)
     * 
     * @param array $fixtures - array with fixtures data, by default is null
     * @throws Exception
     */
    public static function prepareFixtures($fixtures = array())
    {
        // check for fixtures array
        if (is_array($fixtures) && !empty($fixtures)) {
            self::$fixtures = $fixtures;
        } else {
            throw new Exception('Empty array for fixtures.');
        }
        
        // fills databases connections pool
        try {
            // try to connect to databases and Memcache through global config that was set in Registry pattern
            self::$memcached = MemcachedFactory::create(Registry::get('config')['memcached']);
            // here fixtures property must be declared as array
            foreach (array_keys(self::$fixtures) as $dbConnectionAlias) {
                self::$dbPool[$dbConnectionAlias] = DbFactory::createDb(Registry::get('config')[$dbConnectionAlias]);
            }
        } catch (Exception $e) {
            // handle exception in case of error
            echo $e->getMessage() . "\n\n";
            throw $e;
        }
        
        // set base path for fixtures
        self::$fixtureBasePath = __DIR__ . '/../fixtures';
        
        // load fixtures to databases
        self::loadFixtures(self::$fixtures);
    }
    
    /**
     * Generic functional for running before any test methods calls.
     */
    protected function setUp()
    {
        // call parent set up
        parent::setUp();
    }
    
    /**
     * Gets fixtures manager class instance.
     * 
     * @return GenericDbFixtureManager - instance of class for fixtures manager
     * @throws Exception
     */
    public function getFixtureManager()
    {
        // check for fixtures manager is not null
        if (is_null(self::$fixtureManager)) {
            // create fixtures manager by base path for fixtures
            self::$fixtureManager = new GenericDbFixtureManager(self::$fixtureBasePath);
            // additional check for type of created object
            if (!(self::$fixtureManager instanceof GenericDbFixtureManager)) {
               throw new Exception("Couldn't get fixture manager.");
            }
            // set databases connections pool for fixtures manager
            self::$fixtureManager->setDbPool(self::$dbPool);
        }
        return self::$fixtureManager;
    }
    
    /**
     * Loading of fixtures.
     * 
     * @param array $fixtures - array with fixtures data, by default is null
     */
    public function loadFixtures($fixtures = null)
    {
        // if fixtures is not set still, then take them from early set fixtures
        if (is_null($fixtures)) {
            $fixtures = self::$fixtures;
        }
        // load fixture data by fixtures manager
        self::getFixtureManager()->load($fixtures);
    }
    
    /**
     * PHP magic method.
     * This method is overridden so that named fixture data can be accessed like a normal property.
     * 
     * @param string $name the property name
     * @return mixed the property value
     */
    public function __get($name)
    {
        if (is_array(self::$fixtures) && ($rows = self::getFixtureManager()->getRows($name)) !== false) {
            return $rows;
        } else {
            throw new Exception('Unknown property "' . $name . '" for class "' . get_class($this) . '".');
        }
    }

    /**
     * PHP magic method.
     * This method is overridden so that named fixture database record instances can be accessed in terms of a method call.
     * 
     * @param string $name method name
     * @param string $params method parameters
     * @return mixed the property value
     */
    public function __call($name, $params)
    {
        if (is_array(self::$fixtures) && isset($params[0]) && ($record = self::getFixtureManager()->getRecord($name, $params[0])) !== false) {
            return $record;
        } else {
            throw new Exception('Unknown method "' . $name . '" for class "' . get_class($this) . '".');
        }
    }
}

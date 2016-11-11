<?php
/**
 * @package tdcDatabaseConnector
 * @subpackage UnitTest 
 */

require_once 'PHPUnit/Framework/TestCase.php';

use \Topolis\DatabaseConnector\ConnectorFactory;

/**
 * An abstract class that defines all unit tests. These tests MUST NOT BE OVERRIDDEN and define
 * the absolute minimum of methods every compatible connector class has to support.
 * The concrete connector test has to override these three methods:
 * 
 * setUpDatabase()      create database and fill with defined values (@see setUp())
 * tearDownDatabase()   drop database after use
 * getConnectorType()   return identifier of this connector's test
 * 
 * see MysqlConnectorTest for examples
 * 
 * @package ConnectorConnector
 * @subpackage UnitTest
 * @author ToBe
 */
abstract class ConnectorTest extends PHPUnit_Framework_TestCase {
    
    // Unittest Master Configuration
    protected $hostname = "localhost";
    protected $user     = "devdb";
    protected $pass     = "dev123db";

    // Set in Constructor
    protected $config = array();
    protected $dbname = null;
    protected $connection = null;
    protected $testvalues = array();
   
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp() {
        parent::setUp ();

        // Generate Database name
        $this->dbname = "unittest_mysqlconn_".uniqid();
        
        //Setup Configuration object for tested connector
        $this->config["type"]     = $this->getConnectorType();
        $this->config["host"]     = $this->hostname;
        $this->config["user"]     = $this->user;
        $this->config["password"] = $this->pass;
        $this->config["database"] = $this->dbname;        
        
        // Generate Test table Values and keep in memory for comparison        
        $this->testvalues = array();
        $this->testvalues["testtable1"] = array();
        for($i = 1; $i<10; $i++) {
            $this->testvalues["testtable1"][$i] = array();
            $this->testvalues["testtable1"][$i]["id"] = $i;            
            $this->testvalues["testtable1"][$i]["numberCol"] = $i;
            $this->testvalues["testtable1"][$i]["decimalCol"] = round($i*pi(),2);
            $this->testvalues["testtable1"][$i]["datetimeCol"] = date('Y-m-d H:i:s', 123456789+$i*5000);
            $this->testvalues["testtable1"][$i]["stringCol"] = "Some testvalue $i";
        }        
        
        // Setup database with test tables
        $this->setUpDatabase($this->dbname, $this->testvalues);
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown() {
        $this->tearDownDatabase($this->dbname);
        parent::tearDown ();
    }
    
    /**
     * Constructs the test case.
     */
    public function __construct() {
    }

    abstract protected function setUpDatabase($dbname, $tables);
    abstract protected function tearDownDatabase($dbname);
    abstract protected function getConnectorType();
    
    // ------------------------------------------------------------------------
    
    /**
     * Test Constructor / Factory
     */
    public function testConstruct() {
        $this->assertInstanceOf("\\Topolis\\DatabaseConnector\\Common\\IConnector", ConnectorFactory::getConnector($this->config));
    }

    /**
     * Test simple SELECT without binding
     * 1) Sorted by Key field
     * 2) Unsorted
     */
    public function testSelect() {
        $connector = ConnectorFactory::getConnector($this->config);
        $query = "SELECT * FROM testtable1";
        $result = $connector->select($query, null, "id"); // sorted by key field
        
        $this->assertEquals($this->testvalues["testtable1"], $result);
        
        $query = "SELECT * FROM testtable1 ORDER BY id ASC";
        $result = $connector->select($query); // unsorted
        $this->assertEquals(array_values($this->testvalues["testtable1"]), $result);
        
    }    
    
    /**
     * Test SELECT with binding
     */
    public function testSelectBinding() {
        
        $TESTROW = 3;
        
        $connector = ConnectorFactory::getConnector($this->config);
        $query = "SELECT * FROM testtable1 WHERE id = :id AND numberCol = :numberCol AND decimalCol = :decimalCol AND datetimeCol = :datetimeCol AND stringCol LIKE :stringCol";
        $params = array("id"          => $this->testvalues["testtable1"][$TESTROW]["id"],
                        "numberCol"   => $this->testvalues["testtable1"][$TESTROW]["numberCol"],
                        "decimalCol"  => $this->testvalues["testtable1"][$TESTROW]["decimalCol"],
                        "datetimeCol" => $this->testvalues["testtable1"][$TESTROW]["datetimeCol"],
                        "stringCol"   => $this->testvalues["testtable1"][$TESTROW]["stringCol"]."%" );
        $result = $connector->select($query, $params, "id");
        
        $this->assertEquals(array($TESTROW => $this->testvalues["testtable1"][$TESTROW]), $result);
    }

    /**
     * Test Query with SELECT statement and Binding
     */
    public function testQuerySelect() {
        $connector = ConnectorFactory::getConnector($this->config);
        $query = "SELECT * FROM testtable1 WHERE id > :id";
        $params = array("id" => 2 );
        $result = $connector->query($query, $params);
        
        $args = array("handle" => $result, "connector" => $connector);        
        return $args;
    }

    /**
     * Test Count
     * @depends testQuerySelect
     */
    public function testQueryCount($args) {
        $connector = $args["connector"];
        $this->assertNotEquals(false,$args["handle"]);
        $this->assertEquals(7, $connector->count($args["handle"]));
    }
    
    /**
     * Test Fetch
     * @depends testQuerySelect
     */
    public function testQueryFetch($args) {
        $connector = $args["connector"];
        $this->assertNotEquals(false,$args["handle"]);
        for($i=3; $i<10; $i++) {
            $row = $connector->fetch($args["handle"], true);
            $this->assertEquals($this->testvalues["testtable1"][$i], $row);
        }        

        return $args;
    }

    /**
     * Test Reset
     * @depends testQueryFetch
     */
    public function testQueryReset($args) {
        $connector = $args["connector"];
        $connector->reset($args["handle"]);
        for($i=3; $i<10; $i++) {
            $row = $connector->fetch($args["handle"], true);
            $this->assertEquals($this->testvalues["testtable1"][$i], $row);
        }        
    }    
    
    /**
     * Test INSERT with binding
     */
    public function testQueryInsert() {
        $connector = ConnectorFactory::getConnector($this->config);
        $query = "INSERT INTO testtable1 (numberCol, decimalCol, datetimeCol, stringCol) VALUES (:numberCol, :decimalCol, :datetimeCol, :stringCol)";
        $params = array("numberCol"   => "112233",
                        "decimalCol"  => "456.789",
                        "datetimeCol" => "2012-12-21 12:21:12",
                        "stringCol"   => "A newly inserted line" );
        $result = $connector->query($query, $params);
        $this->assertNotEquals(false, $result);
        
        // test last_id
        $lastid = $connector->lastId($result);
        $this->assertGreaterThan(0, $lastid);
        
        // test inserted values
        $expected = $params;
        $expected["id"] = $lastid;
        $query = "SELECT * FROM testtable1 WHERE id = :id";
        $params = array("id" => $lastid );
        $result = $connector->select($query, $params, "id");
        
        $this->assertEquals(array($lastid => $expected), $result);
    }

    /**
     * Test INSERT with numeric string and leading zeroes
     */
    public function testSpecialTypesInsert() {
        $connector = ConnectorFactory::getConnector($this->config);
        $query = "INSERT INTO testtable1 (stringCol) VALUES (:stringCol)";
        $params = array("stringCol"   => "000123" );
        $result = $connector->query($query, $params);
        
        // test inserted values
        $id = $connector->lastId($result);
        $query = "SELECT stringCol FROM testtable1 WHERE id = :id";
        $params = array("id" => $id );
        $result = $connector->select($query, $params);
        
        $this->assertEquals("000123", $result[0]["stringCol"]);
    }

    /**
     * Test Disconnect (without verifying because we simplay cant know)
     */
    public function testDisconnect() {
        $connector = ConnectorFactory::getConnector($this->config);
        $result = $connector->select("SELECT * FROM testtable1");
        $connector->disconnect();
    }

    /**
     * Test exception handling
     * @expectedException \Topolis\DatabaseConnector\Common\ConnectorException
     */
    public function testSQLException() {
        $connector = ConnectorFactory::getConnector($this->config);
        $result = $connector->select("Something invalid for sql");
    }     
}
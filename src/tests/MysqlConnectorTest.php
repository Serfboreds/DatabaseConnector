<?php
/**
 * @package DatabaseConnector
 * @subpackage UnitTest 
 */

require_once dirname(__FILE__).'/ConnectorTest.abstract.php';

use \Topolis\DatabaseConnector\ConnectorFactory;

/**
 * Mysql variant of the specified tests in abstract class ConnectorTest
 * 
 * @package DatabaseConnector
 * @subpackage UnitTest
 * @author tbulla
 */
class MysqlConnectorTest extends ConnectorTest {

    /**
     * create database and fill with tables as defined in setUp()
     * @param string $dbname      name of database to create
     * @param array $tables       tables and their columns and values to create
     */
    protected function setUpDatabase($dbname, $tables){
        //Open Connection
        $this->connection = mysql_connect($this->hostname, $this->user, $this->pass, true);
        
        //Create Database
        mysql_unbuffered_query("CREATE DATABASE ".$this->dbname, $this->connection);
        mysql_select_db($this->dbname, $this->connection);
        
        //Create Table
        mysql_unbuffered_query("CREATE TABLE testtable1 (
                                   id bigint AUTO_INCREMENT NOT NULL,
                                   numberCol int, 
                                   decimalCol decimal(10,5),
                                   datetimeCol datetime,
                                   stringCol varchar(250),
                                   PRIMARY KEY (id)
                                )", $this->connection);        

        //Reset table and insert Values
        mysql_unbuffered_query("TRUNCATE TABLE testtable1", $this->connection);    
        $sql = "INSERT INTO testtable1 (id, numberCol, decimalCol, datetimeCol, stringCol) VALUES ";
        
        $data = array();
        foreach ($tables["testtable1"] as $row)
           $data[] = "('".implode("', '", $row)."')";
        $sql .= implode(", ", $data);
        
        mysql_unbuffered_query($sql, $this->connection);        
    }
    
    /**
     * remove created database
     * @param string $dbname
     */
    protected function tearDownDatabase($dbname){
        if($this->connection) {
            $sql = "DROP DATABASE ".$dbname;
            mysql_query($sql, $this->connection);
            mysql_close($this->connection);
        }           
    }
    
    protected function getConnectorType() {
        return "MySQL";
    }

    public function testSqlInjection() {
        $connector = ConnectorFactory::getConnector($this->config);
        $query = "SELECT * FROM testtable1 WHERE id = :id";
        $param = array("id" => "' OR '1'='1");
        $result = $connector->query($query, $param);
        
        $this->assertEquals(0, $connector->count($result));        
    }
}
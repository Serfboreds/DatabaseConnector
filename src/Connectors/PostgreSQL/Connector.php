<?php
/**
 * MysqlConnector
 * 
 * @package DatabaseConnector
 * @subpackage PostgreSQL 
 */

namespace Topolis\DatabaseConnector\Connectors\PostgreSQL;

use Topolis\DatabaseConnector\Common\ConnectorException;
use Topolis\DatabaseConnector\Common\IConnector;
use Topolis\Logger\Logger;
use Topolis\FunctionLibrary\String;
 
/**
 * Database connector for Mysql databases
 * 
 * @author ToBe
 * @package DatabaseConnector
 * @subpackage Mysql
 */
class Connector implements IConnector
{
    protected $Mapper;
	protected $Binder;
	protected $Connection;
	protected $config;
	protected $profile = array("total" => array("in" => 0, "out" => 0, "time" => 0, "calls" => 0), "calls" => array());
	
    protected static $defaults = array("host" => "localhost", "debug" => false, "profile" => false, "profile-calls" => false);

    /**
     * constructor
     * config needs to have the following elements:
     * - hostname          hostname optionally with port (localhost:3306)
     * - user              username for connection
     * - pass              password for connection
     * - database          database name
     * @param array $config      array of configuration parameters
     */
    public function __construct($config) {
        $this->config = $config + self::$defaults;
        
        if($this->config["profile"])
            Logger::getInstance()->registerFlush(array($this, "profileTotal"));
    }
    
    /**
     * set the binder class to use
     * @param Binder $Binder
     */
    public function setBinder(Binder $Binder) {
        $this->Binder = $Binder;        
    }

    /**
    * set the binder class to use
    * @param Binder $Binder
    */
    public function setMapper(Mapper $Mapper) {
    	$this->Mapper = $Mapper;
    }    
    
    /**
     * execute a table returning sql query and return it's result as two-dimensional array (1 - Rows, 2 - Columns)
     * @param string $sql          sql statement with bind placeholders
     * @param array $params        (Optional) array with values for all placeholders
     * @param string $key_column   (Optional) column to use as first dimension instead of consecutive numbers
     * @return array
     */
    public function select($sql, $params = null, $key_column = false) {
        $table = false;
        $handle = $this->query($sql, $params);
        
        if($handle) {
            $table = array();
            while($row = $this->fetch($handle, true)) {
                if($key_column && isset($row[$key_column]))
                    $table[$row[$key_column]] = $row;
                else
                    $table[] = $row;
            }
        }
        
        return $table;
    }    
    
    /**
     * execute a sql statemenent. Returned handle can be used in methods like count() or fetch()
     * @param string $sql          sql statement with bind placeholders
     * @param array $params        (Optional) array with values for all placeholders
     * @return resource            result handle
     */
    public function query($sql, $params = null, $map = null) {
    	
    	if($map !== null)
    		$sql = $this->Mapper->map($params, $map);    	
    	
        if($params !== null)
            $sql = $this->Binder->bind($sql, $params);

        return $this->execute($sql);
    }

    /**
     * fetch one result row from db result handle
     * @param resource $handle          db result handle
     * @param boolean $associative      use column names as keys or consecutive numbers   
     * @return mixed               result array or false on EOF
     */
    public function fetch($handle, $associative = true) {
        if(!$this->Connection) return false;
        
        return pg_fetch_array($handle, null, $associative ? PGSQL_ASSOC : PGSQL_NUM);
    }
    
    /**
     * return count of results from last sql call
     * @param resource $handle     db result handle from query()
     * @return int                 number of results available
     */
    public function count($handle) {
        if(!$this->Connection) return false;        
        
        return pg_num_rows($handle);
    }    

    /**
     * return count of affected rows from last sql call
     * @param resource $handle     db result handle from query()
     * @return int                 number of results available
     */
    public function affected($handle) {
        if(!$this->Connection) return false;        
        
        return pg_affected_rows($this->Connection);
    }      
    
    /**
     * reset row position to start or set to specified position
     * @param resource $handle     db result handle from query()
     * @param int $row             (Optional) position to set to. Default: 0     
     * @return boolean             result of operation
     */
    public function reset($handle, $row = 0) {
        if(!$this->Connection) return false;
        
        return pg_result_seek($handle,$row);
    }
    
    /**
     * id of last auto-generated id during a sql insert 
     * @return int
     */
    public function lastId($handle) {
        if(!$this->Connection) return false;
        
        $query = "SELECT lastval() AS lastid";
        $result = $this->select($query);
        
        if($result)
            return $result[0]["lastid"];
        
        return false;
    }
    
    /**
     * connect to configured database. This methid is implicitely called by any method that 
     * needs a connection and does not need to be called explicitely.
     * @throws DBConnectorException            exception if connection fails
     */
    public function connect() {
      if($this->Connection) $this->disconnect();
      
      $connString = "dbname=".$this->config["database"]." "
                  . "host=".$this->config["host"]." "
                  . "user=".$this->config["user"]." "
                  . "password=".$this->config["password"];

      $this->Connection = @pg_connect($connString, PGSQL_CONNECT_FORCE_NEW);
      
      if(!$this->Connection)
          throw new ConnectorException(__METHOD__." - Can't connect to server");
          
      return;
        
    }
    
    /**
     * disconnect database connection. Simply returns if database already is disconnected.
     */
    public function disconnect() {
      if(!$this->Connection) return;

      pg_close($this->Connection);
      
      return;
    }
    
    /**
     * execute prepared sql statement
     * @param string $sql
     * @throws tdcConnectorException      exception if mysql server returned an error
     * @return resource                  resource handle if statement returned data
     */
    protected function execute($sql) {
        if(!$this->Connection) $this->connect();

        if($this->config["debug"])
        	Logger::getInstance()->log(__CLASS__.": Executing SQL - ".$sql, Logger::DEBUG);        

        $starttime = microtime(true);
        
        $handle = @pg_query($this->Connection, $sql);

        if($this->config["profile"]){
            $this->profile["total"]["calls"] ++;
            $this->profile["total"]["in"] += strlen($sql);
            $this->profile["total"]["time"] += microtime(true) - $starttime;
        }
        
        if(!$handle)
        {
            $error = pg_last_error($this->Connection);
            throw new ConnectorException(__METHOD__." - Error during SQL call - ".$error);
        }

        return $handle;
    }
    
    // ------------------------------------------------------------------------
    
    public function profileTotal($logger){
        
        $message = __CLASS__." - Profile Summary: Input ".String::BtoStr($this->profile["total"]["in"]).","
                                               ." Calls ".$this->profile["total"]["calls"].","
                                               ." Time ".round($this->profile["total"]["time"],3)."s";
        
        $logger->log($message, Logger::PROFILE);
    }
}

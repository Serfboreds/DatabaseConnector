<?php
/**
 * OracleConnector
 * 
 * @package DatabaseConnector
 * @subpackage Oracle 
 */

namespace Topolis\DatabaseConnector\Connectors\Oracle;

use Topolis\DatabaseConnector\Common\ConnectorException;
use Topolis\DatabaseConnector\Common\IConnector;
use Topolis\Logger\Logger;
use Topolis\FunctionLibrary\String;
 
define("ORACLE_DEFAULT_ENCODING", "UTF-8");

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
	
    protected static $defaults = array("connection"     => "localhost", 
                                       "encoding"       => ORACLE_DEFAULT_ENCODING,
                                       "debug"          => false, 
                                       "profile"        => false, 
                                       "profile-calls"  => false);

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
        if(!$this->Connection) $this->connect();

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
        if(!$this->Connection) $this->connect();

        $starttime = microtime(true);

        $statement = oci_parse($this->Connection, $sql);

    	if($map !== null)
    		$params = $this->Mapper->map($params, $map);

        if($params !== null)
            $this->Binder->bind($statement, $params);

        $result = $this->execute($statement, $sql);

        if($this->config["profile"]){
            $this->profile["total"]["calls"] ++;
            $this->profile["total"]["in"] = false; // we dont know the size of the generates statement
            $this->profile["total"]["time"] += microtime(true) - $starttime;
        }

        return $result;
    }
    
    /**
     * free a statements resources
     * @param resource $handle         statement resource
     */
    public function free($handle){
        return oci_free_statement($statement);
    }

    /**
     * fetch one result row from db result handle
     * @param resource $handle          db result handle
     * @param boolean $associative      use column names as keys or consecutive numbers   
     * @return mixed               result array or false on EOF
     */
    public function fetch($handle, $associative = true) {
        if(!$this->Connection) return false;

        $starttime = microtime(true);
        $startbytes = memory_get_usage();

        $result = oci_fetch_array($handle, ($associative ? OCI_ASSOC : OCI_NUM) + OCI_RETURN_NULLS);

        if($this->config["profile"]){
            $this->profile["total"]["time"] += microtime(true) - $starttime;
            $this->profile["total"]["out"] += memory_get_usage() - $startbytes;
        }

        return $result;
    }

    /**
     * fetch all result rows from a db result handle
     * @param      $handle
     * @param      $rows
     * @param int  $skip
     * @param      $max
     * @param bool $associative
     * @return bool|int
     */
    public function fetchMulti($handle, &$rows, $skip = 0, $max = -1) {
        if(!$this->Connection) return false;

        $starttime = microtime(true);
        $startbytes = memory_get_usage();

        $result = oci_fetch_all($handle, $rows, $skip, $max, OCI_FETCHSTATEMENT_BY_ROW);

        if($this->config["profile"]){
            $this->profile["total"]["time"] += microtime(true) - $starttime;
            $this->profile["total"]["out"] += memory_get_usage() - $startbytes;
        }

        return $result;
    }
    
    /**
     * return count of results from last sql call
     * @param resource $handle     db result handle from query()
     * @return int                 number of results available
     */
    public function count($handle) {
        if(!$this->Connection) return false;        
        
        throw new ConnectorException(__METHOD__." - number of selected rows not supported on Oracle databases");
    }    

    /**
     * return count of affected rows from last sql call
     * @param resource $handle     db result handle from query()
     * @return int                 number of results available
     */
    public function affected($handle) {
        if(!$this->Connection) return false;        
        
        return oci_num_rows($handle);
    }      
    
    /**
     * reset row position to start or set to specified position
     * @param resource $handle     db result handle from query()
     * @param int $row             (Optional) position to set to. Default: 0     
     * @return boolean             result of operation
     */
    public function reset($handle, $row = 0) {
        if(!$this->Connection) return false;
        
        throw new ConnectorException(__METHOD__." - seek of result sets not supported on Oracle databases");
    }
    
    /**
     * id of last auto-generated id during a sql insert 
     * @return int
     */
    public function lastId($handle) {
        if(!$this->Connection) return false;
        
        throw new ConnectorException(__METHOD__." - id of insert not supported on Oracle databases (You can use RETURNING with a bound param in your insert query)");
                
        return false;
    }
    
    /**
     * connect to configured database. This methid is implicitely called by any method that 
     * needs a connection and does not need to be called explicitely.
     * @throws DBConnectorException            exception if connection fails
     */
    public function connect() {
        if($this->Connection)
          $this->disconnect();

        $starttime = microtime(true);

        $this->Connection = @oci_connect($this->config["user"], $this->config["password"], $this->config["connection"], $this->config["encoding"]);

        if($this->config["profile"])
            $this->profile["total"]["time"] += microtime(true) - $starttime;

        if(!$this->Connection)
          throw new ConnectorException(__METHOD__." - Can't connect to server");
          
      return;
        
    }
    
    /**
     * disconnect database connection. Simply returns if database already is disconnected.
     */
    public function disconnect() {
      if(!$this->Connection) return;

      oci_close($this->Connection);
      
      return;
    }
    
    /**
     * execute prepared sql statement
     * @param resource $statement        oci statement resource identifier     
     * @param string $sql                used for debug output
     * @throws tdcConnectorException     exception if mysql server returned an error
     * @return resource                  resource handle if statement returned data
     */
    protected function execute($statement, $sql) {
        if($this->config["debug"])
        	Logger::getInstance()->log(__CLASS__.": Executing SQL - ".$sql, Logger::DEBUG);        

        $result = @oci_execute($statement, OCI_COMMIT_ON_SUCCESS);

        if(!$result)
        {
            $error = oci_error($statement);
            throw new ConnectorException(__METHOD__." - Error during SQL call - ".$error["message"].
                                         ($this->config["debug"] ? " in SQL (Offset ".$error["offset"]."): ".$error["sqltext"] : ""));
        }

        return $statement;
    }
    
    // ------------------------------------------------------------------------
    
    public function profileTotal($logger){
        
        $message = __CLASS__." - Profile Summary: Input ".String::BtoStr($this->profile["total"]["in"]).","
                                               ." Output ".String::BtoStr($this->profile["total"]["out"]).","
                                               ." Calls ".$this->profile["total"]["calls"].","
                                               ." Time ".round($this->profile["total"]["time"],3)."s";
        
        $logger->log($message, Logger::PROFILE);
    }
}

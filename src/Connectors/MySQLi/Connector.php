<?php
/**
 * THIS CONNECTOR NEEDS (AND EXTENDS) THE BASIC MYSQL CONNECTOR!
 * @package DatabaseConnector
 * @subpackage Mysqli
 * @author ToBe
 */

namespace Topolis\DatabaseConnector\Connectors\MySQLi;

use Topolis\DatabaseConnector\Common\IConnector;
use Topolis\DatabaseConnector\Common\ConnectorException;
use Topolis\DatabaseConnector\Connectors\MySQL\Connector as BaseConnector;
use Topolis\Logger\Logger;
use \MySQLi_Result;
use \mysqli;

/**
 * Database connector for Mysql databases
 * 
 * @package DatabaseConnector
 * @subpackage Mysqli
 * @author ToBe
 */
class Connector extends BaseConnector implements IConnector 
{
    /* @var Binder $Binder */
    protected $Binder;
    /* @var mysqli $Connection */
    protected $Connection;

    /**
     * fetch one result row from db result handle
     * @param resource $handle           db result handle
     * @param boolean  $associative      use column names as keys or consecutive numbers
     * @throws ConnectorException
     * @return mixed               result array or false on EOF
     */
    public function fetch($handle, $associative = true) {
        if(!$this->Connection) return false;
        
        if(!$handle instanceof MySQLi_Result)
            throw new ConnectorException(__METHOD__." - Invalid result object");

        $starttime = microtime(true);
        $startbytes = memory_get_usage();

        if($associative)
            $result = $handle->fetch_assoc();
        else
            $result = $handle->fetch_array();

        if($this->config["profile"]){
            $this->profile["total"]["time"] += microtime(true) - $starttime;
            $this->profile["total"]["out"] += memory_get_usage() - $startbytes;
        }

        return $result;
    }

    /**
     * return count of results from last sql call
     * @param resource $handle     db result handle from query()
     * @throws ConnectorException
     * @return int                 number of results available
     */
    public function count($handle) {
        if(!$this->Connection) return false;        

        if(!$handle instanceof MySQLi_Result)
            throw new ConnectorException(__METHOD__." - Invalid result object");
        
        return $handle->num_rows;
    }    

    /**
     * return count of affected rows from last sql call
     * @param resource $handle     db result handle from query()
     * @return int                 number of results available
     */
    public function affected($handle) {
        if(!$this->Connection) return false;    
        
        return $this->Connection->affected_rows;
    }

    /**
     * reset row position to start or set to specified position
     * @param resource $handle          db result handle from query()
     * @param int      $row             (Optional) position to set to. Default: 0
     * @throws ConnectorException
     * @return boolean             result of operation
     */
    public function reset($handle, $row = 0) {
        if(!$this->Connection) return false;

        if(!$handle instanceof MySQLi_Result)
            throw new ConnectorException(__METHOD__." - Invalid result object");
        
        return $handle->data_seek($row);
    }

    /**
     * id of last auto-generated id during a sql insert
     * @param $handle
     * @return int
     */
    public function lastId($handle) {
        if(!$this->Connection) return false;
        
        return $this->Connection->insert_id;
    }
    
    /**
     * connect to configured database. This methid is implicitely called by any method that 
     * needs a connection and does not need to be called explicitely.
     * @throws ConnectorException            exception if connection fails
     */
    public function connect() {
        if($this->Connection) $this->disconnect();

        $this->Connection = new mysqli($this->config["host"],$this->config["user"],$this->config["password"]);
        if($this->Connection->connect_error){
            $this->Connection = false;
            throw new ConnectorException(__METHOD__." - Can't connect to '".$this->config["host"]."'");
        }

        @$this->Connection->set_charset($this->config["encoding"]);

        $result = $this->Connection->select_db($this->config["database"]);
        if(!$result)
            throw new ConnectorException(__METHOD__." - Can't select database '".$this->config["database"]."'");

        $this->Binder->setConnection($this->Connection);
        return;
        
    }
    
    /**
     * disconnect database connection. Simply returns if database already is disconnected.
     */
    public function disconnect() {
      if(!$this->Connection) return;

      $this->Connection->close();
      $this->Connection = false;
      return;
    }

    /**
     * execute prepared sql statement
     * @param string $sql
     * @throws ConnectorException
     * @return resource                  resource handle if statement returned data
     */
    protected function execute($sql) {
        if(!$this->Connection) $this->connect();

        if($this->config["debug"])
            Logger::getInstance()->log(__CLASS__.": Executing SQL - ".$sql, Logger::DEBUG);
        
        $starttime = microtime(true);

        $result = $this->Connection->multi_query($sql);

        if($this->config["profile"]){
            $this->profile["total"]["calls"] ++;
            $this->profile["total"]["in"] += strlen($sql);
            $this->profile["total"]["time"] += microtime(true) - $starttime;
        }

        if(!$result)
        {
            $error = $this->Connection->error;
            throw new ConnectorException(__METHOD__." - Error during SQL call - ".$error);
        }

        // Get last result from multi-query
        do{
            if(isset($handle) && $handle instanceof MySQLi_Result)
                $handle->free();
                
            $handle = $this->Connection->store_result();
        }while($this->Connection->more_results() && $this->Connection->next_result());

        //Return true if no Result but no Error
        if(!$handle && $this->Connection->error == "")
            $handle = true;

        return $handle;
    }
}
?>
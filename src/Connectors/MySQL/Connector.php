<?php
/**
 * MysqlConnector
 * 
 * @package DatabaseConnector
 * @subpackage Mysql 
 */

namespace Topolis\DatabaseConnector\Connectors\MySQL;

use Topolis\DatabaseConnector\Common\ConnectorException;
use Topolis\DatabaseConnector\Common\IConnector;
use Topolis\Logger\Logger;
use Topolis\FunctionLibrary\String;
 
@define("MYSQL_CLIENT_LONG_PASSWORD", 1);         /* new more secure passwords */
@define("MYSQL_CLIENT_FOUND_ROWS", 2);            /* Found instead of affected rows */
@define("MYSQL_CLIENT_LONG_FLAG", 4);             /* Get all column flags */
@define("MYSQL_CLIENT_CONNECT_WITH_DB", 8);       /* One can specify db on connect */
@define("MYSQL_CLIENT_NO_SCHEMA", 16);            /* Don't allow database.table.column */
@define("MYSQL_CLIENT_COMPRESS", 32);             /* Can use compression protocol */
@define("MYSQL_CLIENT_ODBC", 64);                 /* Odbc client */
@define("MYSQL_CLIENT_LOCAL_FILES", 128);         /* Can use LOAD DATA LOCAL */
@define("MYSQL_CLIENT_IGNORE_SPACE", 256);        /* Ignore spaces before '(' */
@define("MYSQL_CLIENT_PROTOCOL_41", 512);         /* New 4.1 protocol */
@define("MYSQL_CLIENT_INTERACTIVE", 1024);        /* This is an interactive client */
@define("MYSQL_CLIENT_SSL", 2048);                /* Switch to SSL after handshake */
@define("MYSQL_CLIENT_IGNORE_SIGPIPE", 4096);     /* IGNORE sigpipes */
@define("MYSQL_CLIENT_TRANSACTIONS", 8192);       /* Client knows about transactions */
@define("MYSQL_CLIENT_RESERVED", 16384);          /* Old flag for 4.1 protocol */
@define("MYSQL_CLIENT_SECURE_CONNECTION", 32768); /* New 4.1 authentication */
@define("MYSQL_CLIENT_MULTI_STATEMENTS", 65536);  /* Enable/disable multi-stmt support */
@define("MYSQL_CLIENT_MULTI_RESULTS", 131072);    /* Enable/disable multi-results */
@define("MYSQL_CLIENT_REMEMBER_OPTIONS", pow(2, 31));

// MYSQL Client Flags used on connect
define("MYSQL_CLIENT_FLAGS", 0);
define("MYSQL_CONNECTION_ENCODING", "utf8");

/**
 * Database connector for Mysql databases
 * 
 * @author ToBe
 * @package DatabaseConnector
 * @subpackage Mysql
 */
class Connector implements IConnector
{
    /* @var Mapper $Mapper */
    protected $Mapper;
    /* @var Binder $Binder */
	protected $Binder;
    /* @var resource $Connection */
	protected $Connection;
    /* @var array $config */
	protected $config;
    /* @var array $profile */
	protected $profile = array("total" => array("in" => 0, "out" => 0, "time" => 0, "calls" => 0), "calls" => array());
	
    protected static $defaults = array("host" => "localhost",
                                       "debug" => false,
                                       "profile" => false,
                                       "profile-calls" => false,
                                       "encoding" => MYSQL_CONNECTION_ENCODING);

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
        
        // $this->connect();
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
     * @param Mapper $Mapper
     */
    public function setMapper(Mapper $Mapper) {
    	$this->Mapper = $Mapper;
    }

    /**
     * execute a table returning sql query and return it's result as two-dimensional array (1 - Rows, 2 - Columns)
     * @param string      $sql           sql statement with bind placeholders
     * @param array       $params        (Optional) array with values for all placeholders
     * @param bool|string $key_column    (Optional) column to use as first dimension instead of consecutive numbers
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
     * @param string $sql           sql statement with bind placeholders
     * @param array  $params        (Optional) array with values for all placeholders
     * @param null   $map
     * @return resource            result handle
     */
    public function query($sql, $params = null, $map = null) {

    	if($map !== null)
    		$params = $this->Mapper->map($params, $map);    	
    	
        if($params !== null){
            if(!$this->Connection) $this->connect(); // Binder uses escape_real_string and needs an open connection
            $sql = $this->Binder->bind($sql, $params);
        }

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

        $starttime = microtime(true);
        $startbytes = memory_get_usage();

        $result = mysql_fetch_array($handle, $associative ? MYSQL_ASSOC : MYSQL_NUM);

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
        
        return mysql_num_rows($handle);
    }    

    /**
     * return count of affected rows from last sql call
     * @param resource $handle     db result handle from query()
     * @return int                 number of results available
     */
    public function affected($handle) {
        if(!$this->Connection) return false;        
        
        return mysql_affected_rows($this->Connection);
    }      
    
    /**
     * reset row position to start or set to specified position
     * @param resource $handle     db result handle from query()
     * @param int $row             (Optional) position to set to. Default: 0     
     * @return boolean             result of operation
     */
    public function reset($handle, $row = 0) {
        if(!$this->Connection) return false;
        
        return mysql_data_seek($handle,$row);
    }

    /**
     * id of last auto-generated id during a sql insert
     * @param $handle
     * @return int
     */
    public function lastId($handle) {
        if(!$this->Connection) return false;
        
        return mysql_insert_id($this->Connection);
    }
    
    /**
     * connect to configured database. This methid is implicitely called by any method that 
     * needs a connection and does not need to be called explicitely.
     * @throws ConnectorException            exception if connection fails
     */
    public function connect() {
        if($this->Connection) $this->disconnect();

        $this->Connection = @mysql_connect($this->config["host"],$this->config["user"],$this->config["password"], true, MYSQL_CLIENT_FLAGS);
        if(!$this->Connection)
            throw new ConnectorException(__METHOD__." - Can't connect to server");

        @mysql_set_charset($this->config["encoding"] ,$this->Connection);
        $result = @mysql_select_db($this->config["database"], $this->Connection);
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

      mysql_close($this->Connection);
      
      return;
    }
    
    /**
     * execute prepared sql statement
     * @param string $sql
     * @throws ConnectorException      exception if mysql server returned an error
     * @return resource                  resource handle if statement returned data
     */
    protected function execute($sql) {
        if(!$this->Connection) $this->connect();

        if($this->config["debug"])
        	Logger::getInstance()->log(__CLASS__.": Executing SQL - ".$sql, Logger::DEBUG);        

        $starttime = microtime(true);

        $handle = @mysql_query ($sql, $this->Connection);

        if($this->config["profile"]){
            $this->profile["total"]["calls"] ++;
            $this->profile["total"]["in"] += strlen($sql);
            $this->profile["total"]["time"] += microtime(true) - $starttime;
        }

        if(!$handle)
        {
            $error = mysql_error ($this->Connection);
            throw new ConnectorException(__METHOD__." - Error during SQL call - ".$error);
        }

        return $handle;
    }
    
    // ------------------------------------------------------------------------
    
    public function profileTotal(Logger $logger){
        $name = isset($this->config["name"]) ? $this->config["name"] : $this->config["host"];
        $message = __CLASS__." - Profile Summary for ".$name."/".$this->config["database"].": Input ".String::BtoStr($this->profile["total"]["in"]).","
                                                                                ." Output ".String::BtoStr($this->profile["total"]["out"]).","
                                                                                ." Calls ".$this->profile["total"]["calls"].","
                                                                                ." Time ".round($this->profile["total"]["time"],3)."s";
        
        $logger->log($message, Logger::PROFILE);
    }
}

<?php 
/**
 * IConnector
 * 
 * @package DatabaseConnector
 * @subpackage Common 
 */

namespace Topolis\DatabaseConnector\Common;
 
/**
 * Interface for Database Connectors
 * 
 * @author ToBe
 * @package DatabaseConnector
 * @subpackage Common
 */
interface IConnector{
    /**
     * constructor
     * config needs to have the following elements:
     * - hostname          hostname optionally with port (localhost:3306)
     * - user              username for connection
     * - pass              password for connection
     * - database          database name
     * @param array $config      array of configuration parameters
     */
    public function __construct($config);
    
    /**
     * execute a table returning sql query and return it's result as two-dimensional array (1 - Rows, 2 - Columns)
     * @param string $sql          sql statement with bind placeholders
     * @param array $params        (Optional) array with values for all placeholders
     * @param string $key_column   (Optional) column to use as first dimension instead of consecutive numbers
     * @return array
     */
    public function select($sql, $params = null, $key_column = false);
        
    /**
     * execute a sql statemenent. Returned handle can be used in methods like count() or fetch()
     * @param string $sql          sql statement with bind placeholders
     * @param array $params        (Optional) array with values for all placeholders
     * @return resource            result handle
     */    
    public function query($sql, $params = null);
    
    /**
     * fetch one result row from db result handle
     * @param resource $handle          db result handle
     * @param boolean $associative      use column names as keys or consecutive numbers   
     * @return mixed               result array or false on EOF
     */    
    public function fetch($handle, $associative = true);
    
    /**
     * reset row position to start or set to specified position
     * @param resource $handle     db result handle from query()
     * @param int $row             (Optional) position to set to. Default: 0     
     * @return boolean             result of operation
     */    
    public function reset($handle);

    /**
     * return count of results from last sql call
     * @param resource $handle     db result handle from query()
     * @return int                 number of results available
     */    
    public function count($handle);
    
    /**
     * id of last auto-generated id during a sql insert 
     * @return int
     */    
    public function lastid($handle);
}

?>
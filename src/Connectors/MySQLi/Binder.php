<?php
/**
 * THIS CONNECTOR NEEDS (AND EXTENDS) THE BASIC MYSQL CONNECTOR!
 * @package DatabaseConnector
 * @subpackage Mysqli
 * @author ToBe
 */

namespace Topolis\DatabaseConnector\Connectors\MySQLi;

use Topolis\DatabaseConnector\Connectors\MySQL\Binder as BaseBinder;
use \mysqli;

/**
 * Exception class for anything thrown by a connector object
 *
 * @package DatabaseConnector
 * @subpackage Mysql
 * @author ToBe
 */
class Binder extends BaseBinder
{
    /**
     * Database connection, needed for special db dependant escapeing methods
     * @var mysqli $connection
     */
    protected $connection = null;
    
    /**
     * Set db connection special db dependant escapeing methods
     * @param mysqli $connection
     */
    public function setConnection($connection){
        $this->connection = $connection;
    }
    
    /**
     * cast a value to string as defined by target database and escape dangerous characters
     * @param mixed $value
     * @return string      
     */    
    protected function castString($value) {
        return self::ENCAP.$this->connection->real_escape_string($value).self::ENCAP;
    }        
}

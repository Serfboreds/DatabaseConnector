<?php
/**
 * THIS CONNECTOR NEEDS (AND EXTENDS) THE BASIC MYSQL CONNECTOR!
 * @package DatabaseConnector
 * @subpackage Mysqli
 */

namespace Topolis\DatabaseConnector\Connectors\MySQLi;

use Topolis\DatabaseConnector\Common\ConnectorException;
use Topolis\DatabaseConnector\Common\IConnectorFactory;
use Topolis\DatabaseConnector\Connectors\MySQL\Mapper;

/**
 * Generate and return a initialized Connector object 
 * 
 * @package DatabaseConnector
 * @subpackage Mysqli
 * @author ToBe
 */
class ConnectorFactory implements IConnectorFactory {
    
    /**
     * generate connector object
     * - hostname          hostname optionally with port (localhost:3306)
     * - user              username for connection
     * - pass              password for connection
     * - database          database name
     * @param array $config      array of configuration parameters
     * @return IConnector
     */
    public static function getConnector($config) {
        $Connector = new Connector($config);
        $Connector->setBinder( new Binder() );
        $Connector->setMapper( new Mapper() );
        return $Connector;
    }
}

?>
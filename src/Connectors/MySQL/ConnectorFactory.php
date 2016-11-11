<?php
/**
 * @package DatabaseConnector
 * @subpackage Mysql
 */

namespace Topolis\DatabaseConnector\Connectors\MySQL;

use Topolis\DatabaseConnector\Common\ConnectorException;
use Topolis\DatabaseConnector\Common\IConnectorFactory;

/**
 * Generate and return a initialized MysqlConnector object 
 * 
 * @package DatabaseConnector
 * @subpackage Mysql
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
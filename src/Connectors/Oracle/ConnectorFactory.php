<?php
/**
 * @package DatabaseConnector
 * @subpackage Oracle
 */

namespace Topolis\DatabaseConnector\Connectors\Oracle;

use Topolis\DatabaseConnector\Common\ConnectorException;
use Topolis\DatabaseConnector\Common\IConnectorFactory;

/**
 * Generate and return a initialized MysqlConnector object 
 * 
 * @package DatabaseConnector
 * @subpackage Oracle
 * @author ToBe
 */
class ConnectorFactory implements IConnectorFactory {
    
    /**
     * generate connector object
     * - user              username for connection
     * - pass              password for connection
     * - connection        EasyConnect connection string
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
<?php
/**
 * ConnectorKit
 * 
 * @package DatabaseConnector
 * @subpackage Common 
 */

namespace Topolis\DatabaseConnector\Common;
 
/**
 * Interface for Database Connector Kits
 * 
 * @author ToBe
 * @package DatabaseConnector
 * @subpackage Common
 */
interface IConnectorFactory {
    
    /**
     * generate connector object
     * - hostname          hostname optionally with port (localhost:3306)
     * - user              username for connection
     * - pass              password for connection
     * - database          database name
     * @param array $config      array of configuration parameters
     * @return IConnector
     */    
    public static function getConnector($config);
}

?>
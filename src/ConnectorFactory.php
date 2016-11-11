<?php
/**
 * @package DatabaseConnector
 * @subpackage Common
 */

namespace Topolis\DatabaseConnector;

use Topolis\DatabaseConnector\Common\IConnector;
use Topolis\DatabaseConnector\Common\IConnectorFactory;
use Topolis\DatabaseConnector\Common\ConnectorException;

/**
 * interface for all database specific factories 
 * 
 * @package DatabaseConnector
 * @subpackage Common
 * @author ToBe
 */
class ConnectorFactory implements IConnectorFactory {

    /**
     * generate connector object depending on give type. This method expects a concrete
     * connector factory of type IConnectorFactory with classname <Type>ConnectorFactory
     * in a file located in ./Connectors/<type>/ConnectorFactory.class.php.
     * The type "MySQL" for example refers to the class ConnectorFactory in the file
     * ./Connectors/MySQL/ConnectorFactory.class.php
     *
     * - type              determines concrete DBConnector to use
     * - hostname          hostname optionally with port (localhost:3306)
     * - user              username for connection
     * - pass              password for connection
     * - database          database name
     * @param array $config array of configuration parameters
     * @throws ConnectorException
     * @return IConnector
     */
    public static function getConnector($config) {
        if(!isset($config["type"]))
            throw new ConnectorException(__METHOD__." - No database type specified");
            
        $class = __NAMESPACE__."\\Connectors\\".$config["type"]."\\ConnectorFactory";
        
        if(!class_exists($class))
            throw new ConnectorException(__METHOD__." - Database connector factory for '".$config["type"]."' not found");

        /* @var IConnectorFactory $class */
        return $class::getConnector($config); 
    }
    
}
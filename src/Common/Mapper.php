<?php
/**
 * TypeMapper
 * 
 * @package DatabaseConnector
 * @subpackage Common 
 */

namespace Topolis\DatabaseConnector\Common;
 
use Topolis\DatabaseConnector\Common\ConnectorException;
 
/**
 * Type validation, obligatory check and default values
 * 
 * format of $map
 *    array( string    "name" => 
 *        array ( string   "type"       - type to validate ( one of boolean, integer, double, string )
 *                bool     "mandatory"  - (Optional) either true or false
 *                mixed    "default"    - (Optional) default value if none given and not mandatory
 *        )
 *    )
 * 
 * @author ToBe
 * @package DatabaseConnector
 * @subpackage Common
 */
abstract class Mapper
{
	public function map($params, $map)
	{
		$result = true;
		
		// Check for parameters not in map
		$surplus = array_diff_key($params, $map);
		if(count($surplus) > 0)
		    throw new ConnectorException(__METHOD__." - Surplus elements in params. (".implode(", ",keys($surplus)).")");
		
		//Check map versus params
		foreach($map as $name => $type)
		    $this->checkType($name, $type, $params);
		    
		return $params;
	}
	
	protected function checkType($name, $type, &$params)
	{
		// Mandatory parameter missing?
		if(!isset($params[$name]) && isset($type["mandatory"]) && $type["mandatory"])
		    throw new ConnectorException (__METHOD__." - Parameter ".$name." is missing but mandatory");
		
		// Not mandatory but no default?
		if(!isset($params[$name]) && !isset($type["default"]))
		    throw new ConnectorException (__METHOD__." - Missing Parameter ".$name." has no default");
		    
		// Set default
		if(!isset($params[$name]) && isset($type["default"]))
		    $params[$name] = $type["default"];
		    
		// Parameter has correct type?
		if($type["type"] != gettype($params[$name]))
		    throw new ConnectorException (__METHOD__." - Parameter ".$name." is not of type ".$type["type"]);
	}
}

?>
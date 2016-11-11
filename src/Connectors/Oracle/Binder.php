<?php 
/**
* @package DatabaseConnector
* @subpackage Oracle
*/

namespace Topolis\DatabaseConnector\Connectors\Oracle;

use Topolis\DatabaseConnector\Common\ConnectorException;

/**
 * Exception class for anything thrown by a connector object
 *
 * @package DatabaseConnector
 * @subpackage Oracle
 * @author ToBe
 */
class Binder
{
    /**
     * Database connection, needed for special db dependant escapeing methods
     * @var resource $connection
     */
    protected $connection = null;
    
    /**
     * encapsulation string for values in sql scripts 
     * @var string
     */
    const ENCAP = "'";
    
    /**
     * bind (cast and insert) values from parameters array safely into sql script
     * @param string $sql        sql statement with bind placeholders
     * @param array $params      array with values for all placeholders
     * @return string            finished sql script
     */
    public function bind($statement, $params) {
        
        $params = $this->cast($params);

        foreach($params as $key => &$value){
            oci_bind_by_name($statement, ":".$key, $value);
        }

        return $params;
    }
    
    /**
     * cast an array of values to proper database specific values and escape dangerous characters
     * @param array $params           array of values to cast
     * @throws ConnectorException  exception if a value has a unknown type
     * @return array                  filtered and cast values
     */
    protected function cast($params) {
        $cast = array();
        foreach($params as $key => $value) {
            try {
                $type = $this->getType($value);
            }
            catch(\Exception $e) {
                throw new ConnectorException(__METHOD__." - Type of parameter $key is unknown");
            }

            switch($type) {
                case "null":     $value = $this->castNull($value);      break;
                case "array":    $value = $this->castArray($value);     break;
                case "boolean":  $value = $this->castBoolean($value);   break;
                case "integer":  $value = $this->castInteger($value);   break;
                case "decimal":  $value = $this->castDecimal($value);   break;
                case "datetime": $value = $this->castDatetime($value);  break;
                case "string":   $value = $this->castString($value);    break;
            }
            $cast[$key] = $value;
        }
        return $cast;
    }
    
    /**
     * get type of given value the available types are defined by interface and include:
     * null, integer, decimal, array, datetime, string
     * @param mixed $value            value to get a type for
     * @throws ConnectorException  exception if a value has a unknown type
     * @return string                 one of the available data types
     */
    protected function getType($value) {
        if(is_bool($value))
            return "boolean";            
        
        if(is_null($value))
            return "null";
            
        if(is_numeric($value) && !preg_match("/[^0-9\-]/", $value) && (string)(int)$value === (string)$value)
            return "integer";
            
        if(is_numeric($value) && (string)(float)$value === (string)$value)
            return "decimal";

        if(is_array($value))
            return "array";
            
        if($value instanceof \DateTime)
            return "datetime";
            
        if(is_string($value))
            return "string";
            
        throw new ConnectorException(__METHOD__." - Type of value unknown");
    }
    
    /**
     * cast a value to null as defined by target database
     * @param mixed $value
     * @return NULL           allways returns NULL, no matter what was passed
     */
    protected function castNull($value) {
        return "NULL";
    }

    /**
     * cast an array of values recursively though the cast($params) method above
     * @param array $value
     * @return array
     */
    protected function castArray($value) {
        return implode(",",$this->cast($value));
    }    

    /**
     * cast a value to boolean as defined by target database
     * @param mixed $value
     * @return boolean
     */    
    protected function castBoolean($value) {
        return $value != 0 ? 1 : 0;
    }

    /**
     * cast a value to integer as defined by target database
     * @param mixed $value
     * @return integer
     */    
    protected function castInteger($value) {
        
        if($value > PHP_INT_MAX)
            return $this->castString($value); 
        else
            return (int)$value;
    }

    /**
     * cast a value to decimal as defined by target database
     * TODO: float might not be a good type because of it's bad precision. Use something else?
     * @param mixed $value
     * @return float
     */    
    protected function castDecimal($value) {
        return (float)$value;
    }
    
    /**
     * cast a value to datetime as defined by target database
     * @param mixed $value
     * @return string      format is internation format as used by mysql "Y-m-d H:i:s"
     */    
    protected function castDatetime($value) {
        return date_format($value, "Y-m-d H:i:s");
    }

    /**
     * cast a value to string as defined by target database and escape dangerous characters
     * @param mixed $value
     * @return string      
     */    
    protected function castString($value) {
        return $value;
    }        
}

?>
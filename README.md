# DatabaseConnector
Connectors for SQL Databases with parameter binding, escaping and mapping. Also provides interfaces for debugging and profiling.

* Support for MySQL, MySQLi, PostgeSQL (more will appear over time)
* Secure and type safe escaping and binding of input parameters
* Optionaly enforcing rules on binding parameters via a type map
* encapsulation of database type specific methods into generic methods
* UTF-8 aware connection encoding
* simplified select method
* easy extensibility for other database types
* Debugging anf profiling info (when used with Topolis/Logger)

This class does **not** feature:
* data abstraction layers
* query language abstraction (a query builder similar to Zend_Db_Select is planned but not yet available)
* caching

This page shows the basic usage and binding mechanics. If you need more information, please consult the code documentation of the `IConnector` interface. All database connectors will implement this interface.

## Configuration
Configuration is done via a array of parameters that is passed to the `ConnectorFactory` when a new connection is requested.

* **type** Type of the database. Must be identical to a folder name inside `Connectors` folder of this library.
* **host** Database host name
* **user** Username to log into database
* **password** Password to log into database
* **database** Name of database to connecto to
* **debug** (Optional) generate notices with executed SQL statements when set to `true`. Never enable on a live server. SQL statements may contain sensible data.
* **profile** (Optional) generate log entries on a Topolis/Logger instance if present containing information on number of calls, timing and memory consumption.
* **encoding** (Optional) Connection encoding for database connection. Defaults to "utf8"

## Instantiation
A database connector is requested via the generic factory class `ConnectorFactory`. This class then requests a type specifig connector from the respectife connector factory.

```
$config = array("type" => "MySQLi", // Case sensitive
                "host" => "localhost"
                "user" => "dbname",
                "password" => "12345678"
                "database" => "mydata");

$Connector = ConnectorFactory->getConnector($config);
```

## SQL Query
To execute a query, you need to provide a string with the SQL statement containing only static values or placeholders for binding, and an array with all values for your placeholders. The connector will then replace all placeholders with properly escaped, cast and quoted values from this array.

Placeholders are written as a leading colon and the name of the placeholder.

Supported Types for Binding:
* **null** logical null value
* **array** all elements will be cast accordingly and list is returned as comma seperated values (eg for use in "WHERE column IN (:myarray)"
* **integer** Any number not starting with zeroes and not containing decimals
* **decimal** Any number not starting with zeroes and containing decimals
* **boolean** boolean true or false values
* **datetime** DateTime objects
* **string** a normal string

**Note** Placeholders are replaced with `str_replace`. This means that if you have two placeholders ":street" and ":streetnumber", the first replaceing might also hit :streetnumber, leaving you with something like "'123'number" which breaks your SQL.
This can be avoided either by using unique placeholder names or by plaing ":streetnumber" before ":street" in your placeholders array.

```
$query = "SELECT * FROM table WHERE id = :id and date > :date";
$param = array("id" => 123, "date" => "2001-01-31");
$result = $Connector->query($query, $param);

while($row = $connector->fetch($result))
    var_dump($row);
```

## Simple Select
The connector can also provide the result of a select query as a already fetched multi-dimensional array. First dimension is the row-number (or index column, if specified) and second dimension is the rows as usual.

The third parameter optionally specifies the column to use as first dimension. If not specified, all rows get a numerical index.

```
$query = "SELECT * FROM table WHERE id = :id and date > :date";
$param = array("id" => 123, "date" => "2001-01-31");
$table = $Connector->select($query, $param, "id");

var_dump($table);
```

**Note** If you use a query that does not return a result set, this function will generate an error.


## Insert with LastId
If you insert a row into a table with an auto incrementing index column, you can get this id as follows:

```
$query = "INSERT INTO table (col1, col2) VALUES (:col1, :col2)";
$param = array("col1" => "Hello", "col2" => "World");
$result = $Connector->query($query, $param);
$lastid = $Connector->lastId($result);
```

## Enforcing Parameters with Mapper
The connector has an optional Mapper class, that can be used to specify rules for all given parameters for binding. The $map array used during a query has to follow this format:

```
$map = array( string    "name" => 
           array ( string   "type",       // type to validate ( one of boolean, integer, double, string )
                   bool     "mandatory",  // (Optional) either true or false
                   mixed    "default"     // (Optional) default value if none given and not mandatory
           )
       )
```

```
$query = "UPDATE guest SET name = :name WHERE id = :id";

$param = array("name" => $name, "id" => $id);

$map = array();
$map["id"]   = array ("type" => "integer", "mandatory" => true );
$map["name"] = array ("type" => "string",  "mandatory" => false, "default" => "Anonymous" );

$result = $Connector->query($query, $param, $map);
```

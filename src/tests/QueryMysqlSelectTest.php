<?php
/**
 * @package DatabaseConnector
 * @subpackage UnitTest
 */

require_once dirname(__FILE__).'/../Common/Query/Column.class.php';
require_once dirname(__FILE__).'/../Common/Query/Condition.class.php';
require_once dirname(__FILE__).'/../Common/Query/Order.class.php';
require_once dirname(__FILE__).'/../Common/Query/Syntax.class.php';
require_once dirname(__FILE__).'/../Common/Query/Table.class.php';
require_once dirname(__FILE__).'/../Common/Query/Statement.class.php';
require_once dirname(__FILE__).'/../Common/Query/Select.class.php';

require_once 'PHPUnit/Framework/TestCase.php';

use \Topolis\DatabaseConnector\Common\Query\Syntax;
use \Topolis\DatabaseConnector\Common\Query\Select;

/**
 * An abstract class that defines all unit tests. These tests MUST NOT BE OVERRIDDEN and define
 * the absolute minimum of methods every compatible connector class has to support.
 * The concrete connector test has to override these three methods:
 *
 * setUpDatabase()      create database and fill with defined values (@see setUp())
 * tearDownDatabase()   drop database after use
 * getConnectorType()   return identifier of this connector's test
 *
 * see MysqlConnectorTest for examples
 *
 * @package ConnectorConnector
 * @subpackage UnitTest
 * @author ToBe
 */
class QueryMysqlSelectTest extends PHPUnit_Framework_TestCase {

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp() {
        parent::setUp ();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown() {
        parent::tearDown ();
    }

    /**
     * Constructs the test case.
     */
    public function __construct() {
    }

    // ------------------------------------------------------------------------

    public function testColumnA() {

        $Select = new Select( new Syntax());
        $Select->column("ColA");
        $Select->column("ColB");

        $this->assertEquals("SELECT `ColA`,`ColB`", (string)$Select);
    }

    public function testColumnB() {

        $Select = new Select( new Syntax());
        $Select->column("ColA")->column("ColB");

        $this->assertEquals("SELECT `ColA`,`ColB`", (string)$Select);
    }

    public function testColumns() {

        $Select = new Select( new Syntax());
        $Select->columns("ColA","ColB");

        $this->assertEquals("SELECT `ColA`,`ColB`", (string)$Select);
    }

    public function testFrom() {

        $Select = new Select( new Syntax());
        $Select->columns("ColA","ColB");
        $Select->from("Tab1");

        $this->assertEquals("SELECT `ColA`,`ColB` FROM `Tab1`", (string)$Select);
    }

    public function testColumnTables() {

        $Select = new Select( new Syntax());
        $Select->from("Tab3");
        $Select->column("ColA","Tab1");
        $Select->column("ColB","Tab2");

        $this->assertEquals("SELECT `Tab1`.`ColA`,`Tab2`.`ColB` FROM `Tab3`", (string)$Select);
    }

    public function testWhere() {

        $Select = new Select( new Syntax());
        $Select->from("Tab1");
        $Select->columns("ColA","ColB");
        $Select->where("ColA",">",100);

        $this->assertEquals("SELECT `ColA`,`ColB` FROM `Tab1` WHERE `ColA` > 100", (string)$Select);
    }

    public function testWhereMulti() {

        $Select = new Select( new Syntax());
        $Select->from("Tab1");
        $Select->columns("ColA","ColB");
        $Select->where("ColA",">",100);
        $Select->where("ColB","<","test");
        $Select->where("ColC","=","hello");
        $Select->where("ColC","LIKE","test2");

        $this->assertEquals("SELECT `ColA`,`ColB` FROM `Tab1` WHERE `ColA` > 100 AND `ColB` < test AND `ColC` = hello AND `ColC` LIKE test2", (string)$Select);
    }

    public function testWhereTable() {

        $Select = new Select( new Syntax());
        $Select->from("Tab1");
        $Select->columns("ColA","ColB");
        $Select->where("ColA",">",100, "Tab2");

        $this->assertEquals("SELECT `ColA`,`ColB` FROM `Tab1` WHERE `Tab2`.`ColA` > 100", (string)$Select);
    }

    public function testOrder() {

        $Select = new Select( new Syntax());
        $Select->from("Tab1");
        $Select->columns("ColA","ColB");
        $Select->order("ColA", true);

        $this->assertEquals("SELECT `ColA`,`ColB` FROM `Tab1` ORDER BY `ColA` ASC", (string)$Select);
    }

    public function testOrderMulti() {

        $Select = new Select( new Syntax());
        $Select->from("Tab1");
        $Select->columns("ColA","ColB");
        $Select->order("ColA", true);
        $Select->order("ColB", false);

        $this->assertEquals("SELECT `ColA`,`ColB` FROM `Tab1` ORDER BY `ColA` ASC,`ColB` DESC", (string)$Select);
    }

    public function testLimit() {

        $Select = new Select( new Syntax());
        $Select->from("Tab1");
        $Select->columns("ColA","ColB");
        $Select->limit(123);

        $this->assertEquals("SELECT `ColA`,`ColB` FROM `Tab1` LIMIT 0,123", (string)$Select);
    }

    public function testLimitOffset() {

        $Select = new Select( new Syntax());
        $Select->from("Tab1");
        $Select->columns("ColA","ColB");
        $Select->limit(123,456);

        $this->assertEquals("SELECT `ColA`,`ColB` FROM `Tab1` LIMIT 456,123", (string)$Select);
    }

}
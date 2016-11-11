<?php
/**
 * @package DatabaseConnector
 * @subpackage UnitTest 
 */

// We need Topolis files for the autoloader inside Include
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(__DIR__.'/../../../'),
    get_include_path(),
)));
require_once "Topolis/Autoloader/SimpleNamespaceLoader.class.php";
use Topolis\Autoloader\SimpleNamespaceLoader;
$ExternalAutoloader = new SimpleNamespaceLoader();

require_once 'PHPUnit/Framework/TestSuite.php';
require_once dirname(__FILE__).'/MysqlConnectorTest.php';
require_once dirname(__FILE__).'/MysqliConnectorTest.php';

require_once dirname(__FILE__).'/QueryMysqlSelectTest.php';

/**
 * Complete Testsuite
 * 
 * @package DatabaseConnector
 * @subpackage UnitTest
 * @author tbulla
 */
class unittestSuite extends PHPUnit_Framework_TestSuite {
    
    /**
     * Constructs the test suite handler.
     */
    public function __construct() {
        $this->setName ( 'unittestSuite' );
        $this->addTestSuite ( 'QueryMysqlSelectTest' );
        $this->addTestSuite ( 'MysqliConnectorTest' );
        $this->addTestSuite ( 'MysqlConnectorTest' );    
    }
    
    /**
     * Creates the suite.
     */
    public static function suite() {
        return new self ();
    }
}


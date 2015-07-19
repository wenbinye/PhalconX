<?php
namespace PhalconX\Test;

use Phalcon\Di\InjectionAwareInterface;
use PhalconX\Di\Injectable;

abstract class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase implements InjectionAwareInterface
{
    use Injectable;
    use Dataset;
    use Accessible;
    
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;
    private $cache = array();

    protected function setUp()
    {
        $dataset = $this->getDataSet();
        if ($dataset!=null) {
            parent::setUp();
        }
    }

    protected function tearDown()
    {
        $dataset = $this->getDataSet();
        if ($dataset!=null) {
            parent::tearDown();
        }
    }
    
    final public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = $this->getDI()->getDb()->getInternalHandler();
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $this->getDI()->getConfig()->database->dbname);
        }
        return $this->conn;
    }

    public function createDataSet($file)
    {
        $path = $this->config->fixturesDir . '/' . $file;
        if (isset($this->cache[$path])) {
            return $this->cache[$path];
        }
        if (preg_match('/\.ya?ml$/', $file)) {
            $dataset = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet($path);
        } else {
            $dataset = parent::createFlatXmlDataSet($path);
        }
        return $this->cache[$path] = $dataset;
    }
}

<?php
namespace PhalconX\Test;

abstract class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    use DiService;
    use Dataset;
    use Accessible;
    
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;
    private $cache = array();
    protected $connectionService = 'db';

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
        $this->restoreServices();
    }

    protected function setConnectionService($service)
    {
        $this->connectionService = $service;
    }
    
    public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = $this->getDI()->get($this->connectionService)->getInternalHandler();
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
            $dataset = parent::createArrayDataSet(yaml_parse_file($path));
        } elseif (preg_match('/\.json$/', $file)) {
            $dataset = parent::createArrayDataSet(json_decode(file_get_contents($path), true));
        } else {
            $dataset = parent::createFlatXmlDataSet($path);
        }
        return $this->cache[$path] = $dataset;
    }
}

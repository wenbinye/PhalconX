<?php
namespace PhalconX\Test;

abstract class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    use DiService;
    use Dataset;

    /**
     * only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
     * @var PDO
     */
    private $conn = null;

    /**
     * @var array dataset memory cache
     */
    private $cache = array();

    /**
     * @var string db connection service name
     */
    protected $connectionService = 'db';

    protected function setUp()
    {
        $dataset = $this->getDataSet();
        if ($dataset) {
            parent::setUp();
        }
    }

    protected function tearDown()
    {
        $dataset = $this->getDataSet();
        if ($dataset) {
            parent::tearDown();
        }
    }

    protected function setConnectionService($service)
    {
        $this->connectionService = $service;
    }
    
    public function getConnection()
    {
        if ($this->conn === null) {
            $pdo = $this->getDi()->get($this->connectionService)->getInternalHandler();
            $dbname = '';
            if (isset($this->config->database)) {
                $dbname = $this->config->database->dbname;
            }
            $this->conn = $this->createDefaultDBConnection($pdo, $dbname);
        }
        return $this->conn;
    }

    public function createDataSet($file)
    {
        if (isset($this->cache[$file])) {
            return $this->cache[$file];
        }
        if (preg_match('/\.\(json|ya?ml|php|xml)$/', $file, $matches)) {
            if ($matches[1] == 'xml') {
                $dataset = parent::createFlatXMLDataSet($this->getDatasetFile($file));
            } else {
                $dataset = parent::createArrayDataSet($this->dataset($file));
            }
            return $this->cache[$path] = $dataset;
        } else {
            throw new \InvalidArgumentException("Could not load dataset, support only json, yaml, php, xml files");
        }
    }
}

<?php
namespace PhalconX\Console\Commands\Db;

use Phalcon\Config;
use PhalconX\Console\Command;
use PhalconX\Console\Annotations\Option;
use PhalconX\Helper\Mixin;
use PhalconX\Db\DbHelper;

abstract class BaseCommand extends Command
{
    /**
     * @Option(required, shortcut='-c', desc='Connection service name or dsn')
     */
    public $connection = 'db';

    private $db;
    
    protected function getConnection()
    {
        if (!$this->db) {
            $di = $this->getDi();
            if ($di->has($this->connection)) {
                $this->db = Mixin::create($di->get($this->connection), new DbHelper);
            } else {
                $di = $this->getDi();
                if ($di->has('config')
                    && isset($this->config->databases[$this->connection])) {
                    $dsn = $this->config['databases'][$this->connection];
                    if ($dsn instanceof Config) {
                        $dsn = $dsn->toArray();
                    }
                    $this->db = DbHelper::getConnection($dsn);
                } else {
                    $this->db = DbHelper::getConnection($this->connection);
                }
            }
        }
        return $this->db;
    }
}

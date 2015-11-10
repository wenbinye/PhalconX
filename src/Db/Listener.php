<?php
namespace PhalconX\Db;

use Phalcon\DI\Injectable;
use PhalconX\Helper\ArrayHelper;

class Listener extends Injectable
{
    /**
     * @var int timeout seconds
     */
    private $timeout;

    /**
     * @var boolean
     */
    private $logging;

    /**
     * last active time
     *
     * @var int
     */
    private $lastActiveTime;
    
    public function __construct($options = null)
    {
        $this->lastActiveTime = time();
        $this->timeout = ArrayHelper::fetch($options, 'timeout', 60);
        $this->logging = ArrayHelper::fetch($options, 'logging', true);
    }

    public function beforeQuery($event, $connection, $binds)
    {
        $this->reconnectIfTimeout();
        $this->loggingStatment();
    }

    private function loggingStatment()
    {
        if (!$this->logging) {
            return;
        }
        
        $sql = $connection->getSQLStatement();
        // Ignores these SQL:
        //   SELECT IF(COUNT(*)>0, 1 , 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME`='table_name'
        //   DESCRIBE `table_name`
        if (strpos($sql, 'FROM `INFORMATION_SCHEMA`') !== false
            || strpos($sql, 'DESCRIBE') === 0 ) {
            return;
        }
        if (empty($binds)) {
            $bind_params = '';
        } else {
            $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
            $bind_params = ' bind=' . json_encode($binds, $flags);
        }
        $this->logger->info($sql . $bind_params, 'info');
    }

    private function reconnectIfTimeout()
    {
        if (!$this->isTimeout() || !$connection->isUnderTransaction()) {
            return;
        }
        $this->logger->info("connection timeout, reconnecting");
        $this->lastActiveTime = time();
        $connection->connect();
    }

    private function isTimeout()
    {
        return time() - $this->lastActiveTime > $this->timeout;
    }
}

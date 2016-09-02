<?php
namespace PhalconX\Db;

use PhalconX\Helper\ArrayHelper;
use Phalcon\Logger;
use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;

class Listener implements InjectionAwareInterface
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
     * @var DiInterface
     */
    private $di;

    /**
     * @var Logger\AdapterInterface
     */
    private $logger;
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
        $this->reconnectIfTimeout($connection);
        $this->loggingStatment($connection, $binds);
    }

    private function reconnectIfTimeout($connection)
    {
        if (!$this->isTimeout()) {
            return;
        }
        if ($connection->isUnderTransaction()) {
            if (time() - $this->lastActiveTime < $this->timeout + 60) {
                return;
            }
            $this->getLogger()->error("transaction is timeout");
            try {
                $connection->commit();
            } catch (\PDOException $e) {
                $this->getLogger()->error("transaction commit failed: " . json_encode($e->errorInfo));
            }
        }
        $this->getLogger()->debug("connection timeout, reconnecting");
        $this->lastActiveTime = time();
        $connection->connect();
    }

    private function isTimeout()
    {
        return time() - $this->lastActiveTime > $this->timeout;
    }

    private function loggingStatment($connection, $binds)
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
        $this->getLogger()->debug($sql . $bind_params);
    }
    
    /**
     * @return Logger\AdapterInterface
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            $di = $this->getDi();
            if ($di->has('logger')) {
                $this->logger = $di->getLogger();
            } else {
                $logger = new Logger\Adapter\Stream('php://stderr');
                $logger->setLogLevel(Logger::WARNING);
                $this->logger = $logger;
            }
        }
        return $this->logger;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function getDi()
    {
        if ($this->di === null) {
            $this->di = Di::getDefault();
        }
        return $this->di;
    }

    public function setDi(DiInterface $di)
    {
        $this->di = $di;
        return $this;
    }
}

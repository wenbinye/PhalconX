<?php
namespace PhalconX\Db;

use Phalcon\DI\Injectable;
use PhalconX\Util;
use PhalconX\Util\Logger;

class DbListener extends Injectable
{
    private $activeTime;
    private $timeout;
    private $logging;

    public function __construct($options = null)
    {
        $this->activeTime = time();
        $this->timeout = Util::fetch($options, 'timeout', 60);
        $this->logging = Util::fetch($options, 'logging', true);
    }

    public function beforeQuery($event, $connection, $binds)
    {
        if (time() - $this->activeTime > $this->timeout) {
            Logger::log("connection timeout, reconnecting", "info");
            $connection->connect();
        }
        if ($this->logging) {
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
            Logger::log($sql . $bind_params, 'info', 2);
        }
    }
}

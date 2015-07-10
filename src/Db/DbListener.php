<?php
namespace PhalconX\Db;

use Phalcon\DI\Injectable;
use PhalconX\Logger;

class DbListener extends Injectable
{
    public function beforeQuery($event, $connection)
    {
        $sql = $connection->getSQLStatement();
        // Ignores these SQL:
        //   SELECT IF(COUNT(*)>0, 1 , 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME`='table_name'
        //   DESCRIBE `table_name`
        if (strpos($sql, 'FROM `INFORMATION_SCHEMA`') !== false
             || strpos($sql, 'DESCRIBE') === 0 ) {
            return;
        }
        $bind = $connection->getSQLVariables();
        if (empty($bind)) {
            $bind_params = '';
        } else {
            $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
            $bind_params = ' bind=' . json_encode($bind, $flags);
        }
        Logger::log($sql . $bind_params, 'info', 2);
    }
}

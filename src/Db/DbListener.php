<?php
namespace PhalconX\Db;

use Phalcon\DI\Injectable;

use PhalconX\Errors;

class DbListener extends Injectable
{
    public function beforeQuery($event, $connection)
    {
        $sql = $connection->getSQLStatement();
        // Ignores these SQL:
        //   SELECT IF(COUNT(*)>0, 1 , 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME`='table_name'
        //   DESCRIBE `table_name`
        if ( strpos($sql, 'FROM `INFORMATION_SCHEMA`') !== false
             || strpos($sql, 'DESCRIBE') === 0 ) {
            return;
        }
        $bind = $connection->getSQLVariables();
        $bind_params = (empty($bind) ? '' : ' bind='.json_encode($bind, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        Errors::log($sql . $bind_params, 'info', 2);
    }
}
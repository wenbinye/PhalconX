<?php
namespace PhalconX\Db;

use PhalconX\Test\TestCase;
use Phalcon\Events;

/**
 * TestCase for Listener
 */
class ListenerTransactionTest extends TestCase
{
    private $db;
    
    /**
     * @before
     */
    public function beforeMethod()
    {
        $this->db = DbHelper::getConnection($this->config->mysql->toArray());
        $em = new Events\Manager;
        $em->attach('db', new Listener);
        $this->db->setEventsManager($em);
    }

    public function testTransaction()
    {
        $pdo = $this->db->getInternalHandler();
        // $refl = new \ReflectionClass('PDO');
        // $attrs = [];
        // foreach ($refl->getConstants() as $const => $val) {
        //     if (\Phalcon\Text::startsWith($const, 'ATTR_')) {
        //         try {
        //             $attrs[$const] = $pdo->getAttribute($val);
        //         } catch (\PDOException $e) {
        //             error_log("$const: " . $e->getMessage());
        //         }
        //     } else {
        //         echo "$const = $val\n";
        //     }
        // }
        // print_r($attrs);

        $this->assertTrue($pdo->getAttribute(\PDO::ATTR_AUTOCOMMIT) == 1);
        $this->db->begin();
        $this->db->query('select database()')->fetch();
        $this->assertTrue($pdo->getAttribute(\PDO::ATTR_AUTOCOMMIT) == 0);
        $this->db->commit();
        $this->assertTrue($pdo->getAttribute(\PDO::ATTR_AUTOCOMMIT) == 1);
    }
}

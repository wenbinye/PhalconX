<?php
namespace PhalconX\Helper;

use PhalconX\Test\TestCase;
use PhalconX\Test\Helper\User;

/**
 * TestCase for ArrayHelper
 */
class ArrayHelperTest extends TestCase
{
    public function testFetch()
    {
        $arr = ['foo' => 1];
        $this->assertEquals(ArrayHelper::fetch($arr, 'foo'), 1, 'key exists');
        $this->assertEquals(ArrayHelper::fetch($arr, 'bar'), null, 'key does not exists');
        $this->assertEquals(ArrayHelper::fetch($arr, 'bar', 10), 10, 'key does not exists and with default value');
    }

    public function testPull()
    {
        $arr = [['name' => 'john'], ['name' => 'jim']];
        $this->assertEquals(ArrayHelper::pull($arr, 'name'), ['john','jim']);

        $objs = array_map(function($a) { return (object) $a; }, $arr);
        $this->assertEquals(ArrayHelper::pull($objs, 'name', ArrayHelper::OBJ), ['john','jim']);

        $users = array_map(function($a) { return new User($a['name']); }, $arr);
        $this->assertEquals(ArrayHelper::pull($users, 'name', ArrayHelper::GETTER), ['john','jim']);
    }

    public function testAssoc()
    {
        $arr = [['name' => 'john'], ['name' => 'jim']];
        $this->assertEquals(ArrayHelper::assoc($arr, 'name'), [
            'john' => ['name' => 'john'],
            'jim' => ['name' => 'jim']
        ]);
        
        $objs = array_map(function($a) { return (object) $a; }, $arr);
        $this->assertEquals(ArrayHelper::assoc($objs, 'name', ArrayHelper::OBJ), [
            'john' => $objs[0],
            'jim' => $objs[1]
        ]);

        $users = array_map(function($a) { return new User($a['name']); }, $arr);
        $this->assertEquals(ArrayHelper::assoc($users, 'name', ArrayHelper::GETTER), [
            'john' => $users[0],
            'jim' => $users[1]
        ]);
    }

    public function testExclude()
    {
        $arr = ['foo' => 1, 'bar' => 2];
        $this->assertEquals(ArrayHelper::exclude($arr, ['foo']), ['bar' => 2]);
    }

    public function testSelect()
    {
        $arr = ['foo' => 1, 'bar' => 2];
        $this->assertEquals(ArrayHelper::select($arr, ['foo']), ['foo' => 1]);
    }

    public function testFilter()
    {
        $arr = ['foo' => 0, 'bar' => '', 'baz' => 1, 'bzz' => null];
        $this->assertEquals(ArrayHelper::filter($arr), [
             'foo' => 0, 'bar' => '', 'baz' => 1
        ]);
    }

    public function testSorter()
    {
        $arr = [['name' => 'john'], ['name' => 'jim']];
        $users = array_map(function($a) { return new User($a['name']); }, $arr);

        usort($users, ArrayHelper::sorter('name', 'strcmp', ArrayHelper::GETTER));
        $this->assertEquals($users[0]->name, 'jim');
    }
}

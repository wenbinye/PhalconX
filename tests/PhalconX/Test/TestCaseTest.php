<?php
namespace PhalconX\Test;

class TestCaseTest extends TestCase
{
    public function testDataset()
    {
        $ret = $this->dataset("db/table1.json");
        $this->assertEquals($ret['name'], 'user');
    }
}

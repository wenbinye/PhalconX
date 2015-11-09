<?php
namespace PhalconX\Test;

class TestCaseTest extends TestCase
{
    public function testDataset()
    {
        $ret = $this->dataset("db/user.json");
        $this->assertEquals($ret['name'], 'user');
    }
}

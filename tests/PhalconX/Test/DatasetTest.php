<?php
namespace PhalconX\Test;

use PhalconX\Test\Dataset;
use PHPUnit_Framework_TestCase;
use org\bovigo\vfs\vfsStream;

class TestCaseTest extends PHPUnit_Framework_TestCase
{
    use Dataset;
    
    private $dir;

    public function setUp()
    {
        vfsStream::setup('root');
        $this->dir = vfsStream::url('root');
    }

    public function getFixturesDir()
    {
        return $this->dir;
    }
    
    public function testDataset()
    {
        file_put_contents($this->dir.'/user.json', json_encode(['name' => 'john']));
        $ret = $this->dataset("user.json");
        $this->assertEquals($ret['name'], 'john');
    }
}

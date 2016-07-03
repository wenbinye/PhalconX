<?php
namespace PhalconX;

use PhalconX\Logger\File;
use Psr\Log\LoggerInterface;
use org\bovigo\vfs\vfsStream;

class LoggerTest extends TestCase
{
    private $dir;

    public function setUp()
    {
        parent::setUp();
        vfsStream::setUp('root');
        $this->dir = vfsStream::url('root');
    }
    
    public function testLogger()
    {
        $logger = new Logger;
        $handler = new File($this->dir.'/default.log');
        $handler->setLogLevel(Logger::INFO);
        $logger->push($handler);
        $logger->info('this is info');
        $logger->debug('this is debug');

        $this->assertTrue($logger instanceof LoggerInterface);
        $this->assertContains(
            'this is info',
            file_get_contents($this->dir.'/default.log')
        );
        $this->assertNotContains(
            'this is debug',
            file_get_contents($this->dir.'/default.log')
        );
    }
}
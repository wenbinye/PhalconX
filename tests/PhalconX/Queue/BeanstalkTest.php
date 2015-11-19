<?php
namespace PhalconX\Queue;

use PhalconX\Test\TestCase;
use PhalconX\Test\Queue\MyJob;
use PhalconX\Helper\ArrayHelper;
use Phalcon\Cache;
use PhalconX\Cache\Frontend\None;

/**
 * TestCase for Beanstalk
 */
class BeanstalkTest extends TestCase
{
    private $queue;
    
    /**
     * @before
     */
    public function beforeMethod()
    {
        $di = $this->getDi();
        $di['cache'] = new Cache\Backend\Memory(new None(['lifetime' => 10000]));

        $beanstalk = new Beanstalk([
            'host' => ArrayHelper::fetch($_SERVER, 'BEANSTALK_HOST', '127.0.0.1'),
            'port' => ArrayHelper::fetch($_SERVER, 'BEANSTALK_PORT', '11300')
        ]);
        $beanstalk->watch('test');
        $beanstalk->choose('test');
        $beanstalk->ignoreDefault();
        // clear job queue
        while (($job = $beanstalk->reserve(0))) {
            $job->delete();
        }
        $this->queue = $beanstalk;
    }

    public function testWatching()
    {
        $tubes = $this->queue->watching();
        $this->assertEquals($tubes, ['test']);
    }

    public function testPut()
    {
        $job = new MyJob(['query' => 'phalcon']);
        $ret = $this->queue->put($job);
        $this->assertTrue(is_numeric($ret));
        $theJob = $this->queue->reserve(0);
        $this->assertTrue($theJob instanceof JobInterface);
    }

    public function testPutOnce()
    {
        $job = new MyJob(['query' => 'phalcon']);
        $job->setRunOnce(true);
        $ret = $this->queue->put($job);
        $this->assertTrue(is_numeric($ret));
        $ret = $this->queue->put($job);
        $this->assertTrue($ret === true);
        $theJob = $this->queue->reserve(0);
        $theJob->delete();
        $theJob = $this->queue->reserve(0);
        $this->assertNull($theJob);

        $ret = $this->queue->put($job);
        $this->assertTrue(is_numeric($ret));
    }
}

<?php
namespace PhalconX\Queue;

use PhalconX\Test\TestCase;
use PhalconX\Test\Queue\MyJob;

/**
 * TestCase for Job
 */
class JobTest extends TestCase
{
    /**
     * @before
     */
    public function beforeMethod()
    {
        $di = $this->getDi();
        $di['fooService'] = new \stdClass;
    }
    
    public function testJob()
    {
        $job = new MyJob(['query' => 'abc']);
        $job->di->getConfig();
        $this->assertNotNull($job->fooService);
        $data = serialize($job);
        $this->assertFalse(strpos($data, 'fooService'));
        $this->assertFalse(strpos($data, 'di'));
    }
}

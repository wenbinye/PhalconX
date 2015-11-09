<?php
namespace PhalconX\Di;

use Phalcon\Config;
use PhalconX\Test\TestCase;
use PhalconX\Test\Di\MyServiceProvider;

/**
 * TestCase for FactoryDefault
 */
class FactoryDefaultTest extends TestCase
{
    public function testAutoload()
    {
        $di = new FactoryDefault;
        $di->autoload(["fs"], MyServiceProvider::class);
        $fs = $di->getFs();
        $this->assertTrue(isset($fs->fs));
        $fs2 = $di->getFs();
        $this->assertTrue($fs === $fs2);
    }

    public function testAutoloadInstance()
    {
        $di = new FactoryDefault;
        $di->autoload(["finder"], MyServiceProvider::class, ['shared' => false]);

        $finder = $di->getFinder();
        $finder2 = $di->getFinder();
        $this->assertFalse($finder === $finder2);
    }

    public function testAutoloadAliases()
    {
        $di = new FactoryDefault;
        $di->autoload(["fs" => 'files', 'finder'], MyServiceProvider::class);
        $fs = $di->getFiles();
        $this->assertTrue(isset($fs->fs));
        $finder = $di->getFinder();
        $this->assertTrue($finder instanceof Config);
    }

    public function testAutoloadPrefix()
    {
        $di = new FactoryDefault;
        $di->autoload(["fs" => 'files', 'finder'], MyServiceProvider::class, ['prefix' => 'fs']);
        $fs = $di->getFsFiles();
        $this->assertTrue(isset($fs->fs));
        $finder = $di->getFsFinder();
        $this->assertTrue($finder instanceof Config);
    }

    public function testLoad()
    {
        $di = new FactoryDefault;
        $di->load(MyServiceProvider::class);
        $fs = $di->getFs();
        $this->assertTrue(isset($fs->fs));
        $fs2 = $di->getFs();
        $this->assertTrue($fs === $fs2);
    }

    public function testLoadInstances()
    {
        $di = new FactoryDefault;
        $di->load(MyServiceProvider::class, ['instances' => ['finder']]);
        $fs = $di->getFs();
        $this->assertTrue(isset($fs->fs));
        $fs2 = $di->getFs();
        $this->assertTrue($fs === $fs2);

        $finder = $di->getFinder();
        $finder2 = $di->getFinder();
        $this->assertFalse($finder === $finder2);
    }

    public function testLoadPrefix()
    {
        $di = new FactoryDefault;
        $di->load(MyServiceProvider::class, [
            'aliases' => ['fs' => 'files'],
            'instances' => ['finder'],
            'prefix' => 'fs'
        ]);
        $fs = $di->getFsFiles();
        $this->assertTrue(isset($fs->fs));

        $finder = $di->getFsFinder();
        $this->assertTrue($finder instanceof Config);
    }
}

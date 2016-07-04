<?php
namespace PhalconX;

use org\bovigo\vfs\vfsStream;
use Dotenv;
use Psr\Log\LoggerInterface;
use PhalconX\Logger\File;
use PhalconX\Helper\Di\Foo;

class DiTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        vfsStream::setup('root');
    }
    
    public function createDi($definitions)
    {
        $di = new Di;
        $di->addDefinitions($definitions);
        return $di;
    }
    
    public function testSimpleValue()
    {
        $di = $this->createDi($defs = [
            'foo' => 'foo-string',
            'bar' => (object) ['name' => 'bar']
        ]);
        $this->assertEquals($di->get('foo'), $defs['foo']);
        $this->assertEquals($di->get('bar'), $defs['bar']);
    }

    public function testArrayValue()
    {
        $di = $this->createDi($defs = [
            'foo' => 'foo->string',
            'bar' => [
                Di\get('foo'),
                [Di\get('foo') ]
            ]
        ]);
        $this->assertEquals($di->get('bar'), [
            $defs['foo'], [$defs['foo']]
        ]);
    }

    public function testEnvValue()
    {
        vfsStream::setup('root', null, ['.env' => 'FOO="foo->string"']);
        Dotenv::load(vfsStream::url('root'));
        $di = $this->createDi($defs = [
            'foo' => Di\env('FOO')
        ]);
        $this->assertEquals($di->get('foo'), "foo->string");
    }

    public function testStringValue()
    {
        $di = $this->createDi($defs = [
            'foo' => 'foo->string',
            'bar' => Di\string('{foo}\bar')
        ]);
        $this->assertEquals($di->get('bar'), 'foo->string\bar');
    }

    public function testSimpleFactory()
    {
        $di = $this->createDi($defs = [
            'foo' => function() {
                return 'foo->string';
            }
        ]);
        $this->assertEquals($di->get('foo'), 'foo->string');
    }

    public function testSimpleObject()
    {
        $di = $this->createDi($defs = [
            LoggerInterface::class => Di\object(File::class)
            ->constructor($logfile = vfsStream::url('root/app.log'))
        ]);
        $logger = $di->get(LoggerInterface::class);
        $this->assertTrue($logger instanceof File);
        $logger->info($log = 'this is info log');
        $this->assertContains(
            $log,
            $content = file_get_contents($logfile)
        );
    }

    public function testObjectConstructor()
    {
        $di = $this->createDi($defs = [
            LoggerInterface::class => Di\object(File::class)
            ->constructor($logfile = vfsStream::url('root/app.log'))
        ]);
       // var_export($content);
        $instance = $di->get(Foo::class);
        $this->assertTrue($instance instanceof Foo);
        $this->assertTrue($instance->logger instanceof File);
        // print_r($instance);
    }

    public function testScope()
    {
        $di = $this->createDi($defs = [
            LoggerInterface::class => Di\object(File::class)
            ->constructor($logfile = vfsStream::url('root/app.log'))
        ]);
        $instance1 = $di->get(Foo::class);
        $instance2 = $di->get(Foo::class);
        $this->assertTrue($instance1 === $instance2);
    }

    public function testObjectProperty()
    {
        $di = $this->createDi($defs = [
            LoggerInterface::class => Di\object(File::class)
            ->constructor($logfile = vfsStream::url('root/app.log')),
            'foo.name' => 'foo name',
            Foo::class => Di\object()
            ->property('name', Di\get('foo.name'))
        ]);
        $instance = $di->get(Foo::class);
        // print_r($instance);
        $this->assertEquals($instance->name, $defs['foo.name']);
    }

    public function testObjectMethod()
    {
        $di = $this->createDi($defs = [
            LoggerInterface::class => Di\object(File::class)
            ->constructor($logfile = vfsStream::url('root/app.log')),
            'foo.name' => 'foo name',
            Foo::class => Di\object()
            ->method('addName', Di\get('foo.name'))
            ->method('addName', 'bar')
        ]);
        $instance = $di->get(Foo::class);
        // print_r($instance);
        $this->assertEquals($instance->names, [
            $defs['foo.name'], 'bar'
        ]);
    }

    public function testInjectionAware()
    {
        $di = $this->createDi($defs = []);
        // print_r($di);
        $instance = $di->get(\PhalconX\Helper\Di\Injected::class);
        $this->assertTrue($instance->di === $di);
    }

    public function testParameters()
    {
        $args = ['foo' => 'foo->string'];
        $di = $this->createDi($defs = [
            'foo' => function($params) use ($args) {
                $this->assertEquals($args, $params);
            }
        ]);
        $di->get('foo', [$args]);
    }
}

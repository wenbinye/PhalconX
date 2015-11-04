<?php
namespace PhalconX\Console;

use PhalconX\Annotation\Annotations;
use PhalconX\Test\TestCase;
use PhalconX\Test\Console\GreetCommand;

/**
 * TestCase for Command
 */
class CommandTest extends TestCase
{
    /**
     * @before
     */
    public function setupAnnotations()
    {
        $this->getDi()['annotations'] = new Annotations();
    }
    
    public function testCommand()
    {
        $refl = new \ReflectionClass(GreetCommand::class);
        $application = Command::createApplication(dirname($refl->getFilename()));
        $command = $application->find('greet');
        $this->assertTrue($command instanceof GreetCommand);
    }
}

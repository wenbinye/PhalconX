<?php
namespace PhalconX\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Phalcon\Di;
use PhalconX\Di\Injectable;
use PhalconX\Exception\Exception;

abstract class Command extends BaseCommand
{
    use Injectable;

    protected $input;

    protected $output;

    private $arguments;

    abstract protected function call();
    
    protected function configure()
    {
        $annotations = $this->annotations->get(get_class($this));
        $command = $this->annotations->filter($annotations)
            ->onClass()
            ->is(Annotations\Command::class)
            ->toArray();
        if (!$command) {
            throw new Exception("Could not find then Command annotation in ". get_class($this));
        }
        $command = $command[0];
        $this->setName($command->name)
            ->setProcessTitle($command->title)
            ->setDescription($command->getDescription());
        if ($command->aliases) {
            $this->setAliases($command->aliases);
        }
        $reflection = new \ReflectionClass($this);
        $this->setHelp($this->parseDocCommentHelp($reflection->getDocComment()));
        
        $defaults = $reflection->getDefaultProperties();
        $it = $this->annotations->filter($annotations)
            ->onProperties()
            ->is(Annotations\Argument::class);
        foreach ($it as $annotation) {
            $property = $annotation->getPropertyName();
            $name = $annotation->name ?: $property;
            $this->addArgument(
                $name,
                $annotation->getMode(),
                $annotation->getDescription(),
                $defaults[$property]
            );
            $this->arguments['arguments'][] = $name;
        }
        $it = $this->annotations->filter($annotations)
            ->onProperties()
            ->is(Annotations\Option::class);
        foreach ($it as $annotation) {
            $property = $annotation->getPropertyName();
            $name = $annotation->name ?: $property;
            $this->addOption(
                $name,
                $annotation->shortcut,
                $annotation->getMode(),
                $annotation->getDescription(),
                $defaults[$property]
            );
            $this->arguments['options'][] = $name;
        }
    }

    private function parseDocCommentHelp($doc)
    {
        $help = '';
        foreach (explode("\n", $doc) as $line) {
            if (preg_match('/^\s*\/\*/', $line)) {
                continue;
            } elseif (preg_match('/^\s*\*\s*@/', $line)) {
                break;
            } else {
                $help .= preg_replace('/^\s*\*\s*/', '', $line) . "\n";
            }
        }
        return trim($help);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        if (!empty($this->arguments['arguments'])) {
            foreach ($this->arguments['arguments'] as $name) {
                $this->$name = $input->getArgument($name);
            }
        }
        if (!empty($this->arguments['options'])) {
            foreach ($this->arguments['options'] as $name) {
                $this->$name = $input->getOption($name);
            }
        }
        return $this->call();
    }

    public static function createApplication($dirs)
    {
        if (!is_array($dirs)) {
            $dirs = [$dirs];
        }
        $application = new Application();
        $annotations = Di::getDefault()->getAnnotations();
        foreach ($dirs as $dir) {
            $it = $annotations->scan($dir)
                ->is(Annotations\Command::class)
                ->onClass();
            foreach ($it as $annotation) {
                $class = $annotation->getClass();
                $application->add(new $class);
            }
        }
        return $application;
    }
}

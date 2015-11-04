命令行
==============================

.. code-block:: php

    use PhalconX\Console\Command as BaseCommand;
    use PhalconX\Console\Annotations\Command;
    use PhalconX\Console\Annotations\Argument;
    use PhalconX\Console\Annotations\Option;
    
    /**
     * The <info>%command.name%</info> command displays greet:
     *
     *   <info>php %command.full_name% greet</info>
     *
     * Enjoy yourself.
     * 
     * @Command("greet", desc="Greet someone")
     */
    class GreetCommand extends BaseCommand
    {
        /**
         * @Argument(optional, desc="Who do you want to greet?")
         */
        public $name;
    
        /**
         * @Option(none, shortcut=y, desc="If set, the task will yell in uppercase letters")
         */
        public $yell;
        
        protected function call()
        {
            $text = 'Hello' . ($this->name ? ' ' . $this->name : '');
            if ($this->yell) {
                $text = strtoupper($text);
            }
            $this->output->writeln($text);
        }
    }

<?php
namespace PhalconX\Console\Commands\Db;

use Phalcon\Db;
use PhalconX\Console\Annotations\Option;
use PhalconX\Console\Annotations\Argument;
use PhalconX\Console\Annotations\Command;

/**
 * @Command('db:schema', desc="Exports database table schema")
 */
class SchemaCommand extends BaseSchemaCommand
{
    /**
     * @Option(optional, shortcut='-s', desc="database source")
     */
    public $source;

    protected function call()
    {
        $this->exportTables($this->loadTables($this->source));
    }
}

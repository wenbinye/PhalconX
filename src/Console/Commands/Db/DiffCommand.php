<?php
namespace PhalconX\Console\Commands\Db;

use Phalcon\Db;
use PhalconX\Console\Annotations\Option;
use PhalconX\Console\Annotations\Argument;
use PhalconX\Console\Annotations\Command;
use PhalconX\Helper\ExportHelper;

/**
 * @Command('db:diff', desc="Show differences between source with target")
 */
class DiffCommand extends BaseSchemaCommand
{
    /**
     * @Option(required, shortcut='-s', desc="diff from")
     */
    public $source;
    /**
     * @Option(required, shortcut='-t', desc="diff target")
     */
    public $target;

    protected function call()
    {
        $source = $this->loadTables($this->source);
        $target = $this->loadTables($this->target);
        $tables = [];
        foreach ($target as $name => $def) {
            if (isset($source[$name])) {
                $diff = $source[$name]->compare($def);
                if ($diff->isChanged()) {
                    $tables['changed'][] = $diff;
                }
                unset($source[$name]);
            } else {
                $tables['new'][] = $def;
            }
        }
        if (empty($tables)) {
            echo "No changes\n";
        } else {
            if ($this->isOutputSql()) {
                foreach ($tables as $defs) {
                    echo implode(";\n", $this->export($defs)), ";\n";
                }
            } else {
                $arr = [];
                foreach ($tables as $name => $defs) {
                    $arr[$name] = $this->export($defs);
                }
                echo ExportHelper::export($arr, $this->format);
            }
        }
    }
}

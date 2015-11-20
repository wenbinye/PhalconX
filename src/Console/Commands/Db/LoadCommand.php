<?php
namespace PhalconX\Console\Commands\Db;

use Phalcon\Db;
use PhalconX\Console\Annotations\Option;
use PhalconX\Console\Annotations\Argument;
use PhalconX\Console\Annotations\Command;
use PhalconX\Helper\ExportHelper;

/**
 * @Command('db:load', desc="Load data to database table")
 */
class LoadCommand extends BaseCommand
{
    /**
     * @Option(required, shortcut='-f', desc="input format，support json/yaml/php. required when input from stdin")
     */
    public $format;
    /**
     * @Option(shortcut="-t", desc="truncate table")
     */
    public $truncate;
    /**
     * @Argument(required, desc="input file, use - to read from stdin")
     */
    public $file;
 
    protected function call()
    {
        if ($this->file === '-') {
            if (!$this->format) {
                throw new \InvalidArgumentException("The option -f|--format is required when input from stdin");
            }
            $this->file = "php://stdin";
        }
        $dataset = ExportHelper::loadFile($this->file, $this->format);
        $db = $this->getConnection();
        try {
            foreach ($dataset as $table => $rows) {
                if ($this->truncate) {
                    $db->execute("truncate `$table`");
                }
                foreach ($rows as $row) {
                    $db->insert($table, array_values($row), array_keys($row));
                }
                printf("Load %d records to table %s\n", count($rows), $table);
            }
        } catch (\PDOException $e) {
            if ($e->errorInfo[0] == 23000) {
                global $argv;
                array_splice($argv, 2, 0, "-t");
                echo "Data integrity violation occur. Use -t to truncate table before load data, for example: \n"
                    . implode(" ", $argv) . "\n";
            } else {
                throw $e;
            }
        }
    }
}

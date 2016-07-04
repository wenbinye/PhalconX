<?php
namespace PhalconX\Console\Commands\Db;

use Phalcon\Db;
use PhalconX\Console\Annotations\Option;
use PhalconX\Console\Annotations\Argument;
use PhalconX\Console\Annotations\Command;
use PhalconX\Helper\ExportHelper;

/**
 * @Command('db:dump', desc="Dumps records from database table")
 */
class DumpCommand extends BaseCommand
{
    /**
     * @Option(required, shortcut='-f', desc="output format, supprot json/yaml/php")
     */
    public $format = 'yaml';
    /**
     * @Option(required, shortcut='-l', desc="number of records")
     */
    public $limit = 10;
    /**
     * @Option(optional, shortcut='-s', desc="query SQL")
     */
    public $sql;
    /**
     * @Argument(desc="table name")
     */
    public $table;

    protected function call()
    {
        if ($this->sql) {
            $sql = $this->sql;
        } elseif ($this->table) {
            $sql = "SELECT * FROM {$this->table}";
        } else {
            throw new \InvalidArgumentException("请指定数据库表名");
        }
        if ($this->limit && !preg_match('/ limit \d+/i', $sql)) {
            $sql = $sql . ' LIMIT ' . $this->limit;
        }
        $this->dump($sql, $this->format);
    }

    private function dump($sql, $format = null)
    {
        if (preg_match('/select .* from (\w+) ([^;]+)/i', $sql, $matches)) {
            $table = $matches[1];
            $rs = $this->getConnection()->query($sql);
            $rs->setFetchMode(Db::FETCH_ASSOC);
            $data = [$table => $rs->fetchAll()];
            if ($format == 'json') {
                echo ExportHelper::json($data), "\n";
            } else if ($format == 'php') {
                echo "<?php\n return ", var_export($data, true), ";\n";
            } else {
                echo sprintf(
                    "# dump with %s %s %s -s \"%s\"\n",
                    $_SERVER['argv'][0],
                    $this->input->getFirstArgument(),
                    isset($this->connection) ? " -c $this->connection" : '',
                    $sql
                );
                echo ExportHelper::yaml($data), "\n";
            }
        } else {
            throw new \InvalidArgumentException("Invalid data query SQL: '$sql'");
        }
    }
}

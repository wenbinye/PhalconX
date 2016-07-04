<?php
namespace PhalconX\Console\Commands\Db;

use Phalcon\Db;
use PhalconX\Console\Annotations\Option;
use PhalconX\Console\Annotations\Command;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @Command('db:sync', desc="Update database schema")
 */
class SyncCommand extends BaseSchemaCommand
{
    /**
     * @Option(required, shortcut='-t', desc="database schema file")
     */
    public $target;

    protected function call()
    {
        if (!$this->target) {
            throw new \InvalidArgumentException('The "--target" option is required');
        }
        $current = $this->loadTables(null);
        $dest = $this->loadTables($this->target);
        $sql = [];
        $db = $this->getConnection();
        foreach ($dest as $name => $def) {
            if (isset($current[$name])) {
                $diff = $current[$name]->compare($def);
                if ($diff->isChanged()) {
                    $sql[] = $diff->toSQL($db);
                }
            } else {
                $sql[] = $def->toSQL($db);
            }
        }
        if (empty($sql)) {
            echo "No changes\n";
        } else {
            $sql = implode(";\n", $sql);
            if ($this->input->isInteractive()) {
                echo $sql, "\n";
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion("Continue execute above sql? (y/n) [n] ", false);
                if (!$helper->ask($this->input, $this->output, $question)) {
                    return;
                }
            }
            foreach (explode(";\n", $sql) as $one) {
                try {
                    $one = trim($one);
                    if (!$one) {
                        continue;
                    }
                    $db->execute($one);
                    $errorInfo = $db->getErrorInfo();
                    if ($errorInfo && isset($errorInfo['1'])) {
                        echo "Sql execute failed： $one\n";
                        print_r($errorInfo);
                    }
                } catch (\PDOException $e) {
                    echo "Sql execute failed： $one\n";
                    echo $e->getMessage(), " ", var_export($e->errorInfo, true), "\n";
                }
            }
        }
    }
}

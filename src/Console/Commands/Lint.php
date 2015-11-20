<?php
namespace PhalconX\Console\Commands;

use Phalcon\Text;
use PhalconX\Console\Annotations\Command;
use PhalconX\Console\Annotations\Argument;
use PhalconX\Console\Annotations\Option;
use PhalconX\Console\Command as BaseCommand;
use PhalconX\Php\Lint as PhpLint;
use PhalconX\Php\Lint\Reporters\TextReporter;

/**
 * @Command('lint', desc="phplint")
 */
class Lint extends BaseCommand
{
    /**
     * @Argument(optional, desc="php code directory")
     */
    public $dir;

    /**
     * @Option('optional|is_array', shortcut="-e", desc="directory to exclude")
     */
    public $exclude;

    /**
     * @Option(optional, shortcut="-l", desc="autoload file")
     */
    public $autoload;

    private $excludePattern;

    private $failCount = 0;

    private $passCount = 0;

    private $fileCount = 0;
    
    protected function call()
    {
        if ($this->autoload) {
            include_once $this->autoload;
        } elseif (file_exists('vendor/autoload.php')) {
            include_once 'vendor/autoload.php';
        }
        
        $this->excludePattern = $this->buildExcludePattern();
        if (!$this->dir) {
            $this->dir = '.';
        }
        if (is_file($this->dir)) {
            $this->lint($this->dir);
        } elseif (is_dir($this->dir)) {
            $dir_it = new \RecursiveDirectoryIterator($this->dir);
            $filter_it = new \RecursiveCallbackFilterIterator($dir_it, [$this, 'filter']);
            foreach (new \RecursiveIteratorIterator($filter_it) as $file => $fileinfo) {
                if (!is_file($file)) {
                    continue;
                }
                $this->logger->info("lint $file");
                $this->lint($file);
            }
        } else {
            die("Cannot find source file from '{$this->dir}'\n");
        }
        if ($this->passCount === $this->fileCount) {
            echo "{$this->fileCount} files passed\n";
        } else {
            echo "{$this->fileCount} files was scanned, {$this->passCount} passed, {$this->failCount} failed\n";
            return -1;
        }
    }

    public function filter($current, $file, $it)
    {
        return !preg_match($this->excludePattern, $file)
            && is_readable($file)
            && (is_dir($file) || Text::endsWith($file, '.php'));
    }

    public function lint($file)
    {
        $this->fileCount++;
        $reporter = (new PhpLint($file, new TextReporter))
            ->lint()
            ->getReporter();
        if ($reporter->getErrors()) {
            echo $reporter, "\n\n";
            $this->failCount++;
        } else {
            $this->passCount++;
        }
    }

    private function buildExcludePattern()
    {
        $exclude = array_merge($this->exclude, ['.git', 'tests', 'vendor']);
        return '#(' . implode('|', array_map('preg_quote', $exclude)) . ')$#';
    }
}

<?php
namespace PhalconX\Console\Commands;

use Phalcon\Text;
use PhalconX\Console\Annotations\Command;
use PhalconX\Console\Annotations\Argument;
use PhalconX\Console\Annotations\Option;
use PhalconX\Console\Command as BaseCommand;
use PhalconX\Php\ClassHierarchy;
use PhalconX\Php\ClassExtractor;
use PhalconX\Php\AutoUseFixer;
use PhalconX\Exception\IOException;
use PhpParser\Error;

/**
 * @Command('auto-use', desc="add use statment automaticly")
 */
class AutoUse extends BaseCommand
{
    /**
     * @Argument(optional, desc="file to add use statment automaticly")
     */
    public $file;

    /**
     * @Option('required|is_array', shortcut="-e", desc="directory to exclude")
     */
    public $exclude;

    private $hierarchy;

    private $excludePattern;

    private $projectDir;

    private $stdin = false;

    protected function call()
    {
        if (!$this->file) {
            $this->file = '-';
        }
        if ($this->file === '-') {
            $this->stdin = true;
            $this->file = "php://stdin";
            $this->projectDir = $this->getProjectDir(getcwd());
        } else {
            $this->file = realpath($this->file);
            if (!$this->file) {
                die("File not exists $this->file");
            }
            $this->projectDir = $this->getProjectDir($this->file);
        }
        $this->excludePattern = $this->buildExcludePattern(false);
        $this->hierarchy = $this->createClassHierarchy();
        $this->excludePattern = $this->buildExcludePattern();
        if (is_dir($this->file)) {
            $dir_it = new \RecursiveDirectoryIterator($this->file);
            $filter_it = new \RecursiveCallbackFilterIterator($dir_it, [$this, 'filter']);
            foreach (new \RecursiveIteratorIterator($filter_it) as $file => $fileinfo) {
                if (is_file($file)) {
                    $this->autouse($file);
                }
            }
        } else {
            $this->autouse($this->file);
        }
    }

    private function autouse($file)
    {
        $this->logger->debug("auto use fix $file");
        $fixer = new AutoUseFixer($file, $this->hierarchy, $this->logger);
        try {
            if ($this->stdin) {
                echo $fixer->fix();
            } else {
                file_put_contents($file, $fixer->fix());
            }
        } catch (Error $e) {
            $this->logger->error("Syntax error on {$file} line " . $e->getStartLine());
        }
    }

    private function createClassHierarchy()
    {
        $name = str_replace('/', '_', $this->projectDir);
        $dir = '/tmp/phalconx/' . $name;
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            throw new IOException("Cannot create directory '$dir'", 0, null, $dir);
        }
        $cache = $dir . '/classes.data';
        if (file_exists($cache)) {
            return unserialize(file_get_contents($cache));
        } else {
            $hierarchy = (new ClassHierarchy())
                       ->addDeclared();
            $this->addClasses($hierarchy, $this->projectDir);
            file_put_contents($cache, serialize($hierarchy));
            return $hierarchy;
        }
    }

    private function isProjectDir($dir)
    {
        return file_exists($dir . '/composer.json');
    }

    private function getProjectDir($path)
    {
        if (is_dir($path) && $this->isProjectDir($path)) {
            return $path;
        }
        $parent = dirname($path);
        if ($parent === $path) {
            return false;
        }
        return $this->getProjectDir($parent);
    }

    private function addClasses($hierarchy, $projectDir)
    {
        $dir_it = new \RecursiveDirectoryIterator($projectDir);
        $filter_it = new \RecursiveCallbackFilterIterator($dir_it, [$this, 'filter']);
        foreach (new \RecursiveIteratorIterator($filter_it) as $file => $fileinfo) {
            if (!is_file($file)) {
                continue;
            }
            $this->logger->debug("add class in '$file'");
            $extractor = new ClassExtractor($file);
            foreach ($extractor->getClasses() as $class => $info) {
                $hierarchy->addClass($class, $info['extends'], $info['implements']);
            }
            foreach ($extractor->getInterfaces() as $class => $info) {
                $hierarchy->addInterface($class, $info['extends']);
            }
        }
        return $hierarchy;
    }

    public function filter($current, $file, $it)
    {
        return !preg_match($this->excludePattern, $file)
            && is_readable($file)
            && (is_dir($file) || Text::endsWith($file, '.php'));
    }

    private function buildExcludePattern($withVendor = true)
    {
        $exclude = array_merge($this->exclude, ['.git', 'tests']);
        if ($withVendor) {
            $exclude[] = 'vendor';
        }
        return '#(' . implode('|', array_map('preg_quote', array_unique($exclude))) . ')$#';
    }
}

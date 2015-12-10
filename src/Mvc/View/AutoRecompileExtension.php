<?php
namespace PhalconX\Mvc\View;

use Phalcon\Cache;
use Phalcon\Logger;
use Phalcon\Di\Injectable;
use PhalconX\Helper\ArrayHelper;

/**
 * Automatic recompile template when base template changed
 *
 * volt inheritance will include base template to compiled file.
 * if base template was changed, the child template will not
 * recompile. this volt extension resolve this problem.
 *
 * <code>
 * $ext = new AutoRecompileExtension($cacheDir);
 * $volt->getCompiler()->addExtension($ext);
 * </code>
 */
class AutoRecompileExtension extends Injectable
{
    const T_EXTENDS = 310;

    private $compiler;
    private $logger;
    private $cache;

    private $metadata;
    private $options;

    public function setOptions($options)
    {
        $this->options = $options;
    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function initialize($compiler)
    {
        $this->compiler = $compiler;
        $refl = new \ReflectionClass($compiler);
        $prop = $refl->getProperty('_view');
        $prop->setAccessible(true);
        $view = $prop->getValue($compiler);
        if (isset($view)) {
            $this->setOption('viewsDir', $view->getViewsDir());
        }
        $this->setOption('compiledPath', $compiler->getOption('compiledPath'));
        $this->setOption('compiledSeparator', $compiler->getOption('compiledSeparator'));
        $this->setOption('compiledExtension', $compiler->getOption('compiledExtension'));

        $this->compiler->setOption('compiledPath', function ($templatePath, $options, $extendMode) {
            $templatePath = realpath($templatePath);
            $compiledPath = $this->getCompiledPath($templatePath, $extendMode);
            if ($this->hasDependency($templatePath)) {
                if ($this->isDependencyNewer($templatePath)) {
                    $this->getLogger()->debug("remove $compiledPath");
                    @unlink($compiledPath);
                }
            }
            return $compiledPath;
        });
        $this->readMetadata();
    }
    
    public function compileStatement($statment)
    {
        if ($statment['type'] === self::T_EXTENDS) {
            $this->addDependency($statment['path']['file'], $statment['path']['value']);
        }
    }

    private function getCompiledPath($templatePath, $extendsMode = null)
    {
        if (!isset($extendsMode)) {
            $extendsMode = $this->getExtendsMode($templatePath);
        }
        $sep = ArrayHelper::fetch($this->options, 'compiledSeparator', '_');
        $ext = ArrayHelper::fetch($this->options, 'compiledExtension', '.php');
        $compiledPath = $this->getOption('compiledPath')
                      . str_replace(['/', '\\', ':'], $sep, $templatePath);
        if ($extendsMode) {
            $compiledPath .= $sep . 'e' . $sep;
        }
        return $compiledPath . $ext;
    }
    
    private function getExtendsMode($path)
    {
        return isset($this->metadata['extends'][$path])
            ? $this->metadata['extends'][$path]
            : false;
    }

    private function isDependencyNewer($path)
    {
        if ($this->hasDependency($path)) {
            $compiled = $this->getCompiledPath($path);
            if (!file_exists($compiled)) {
                return false;
            }
            $mtime = filemtime($compiled);
            $dep = $this->metadata['depends'][$path];
            if (filemtime($dep) > $mtime) {
                return true;
            }
            if ($this->hasDependency($dep)) {
                return $this->isDependencyNewer($dep);
            }
        }
        return false;
    }

    private function hasDependency($path)
    {
        return isset($this->metadata['depends'][$path]);
    }

    private function addDependency($path, $base)
    {
        $base = realpath($this->getOption('viewsDir') . $base);
        $path = realpath($path);
        $this->metadata['extends'][$base] = 1;
        $this->metadata['depends'][$path] = $base;
        $this->saveMetadata();
    }

    private function readMetadata()
    {
        $this->metadata = $this->getCache()->get('_PHX.volt.recompile');
    }

    private function saveMetadata()
    {
        $this->getCache()->save('_PHX.volt.recompile', $this->metadata, 0);
    }

    public function getCache()
    {
        if (!$this->cache) {
            $di = $this->getDi();
            $this->cache = $di->has('apcCache') ? $di->getApcCache()
                         : new Cache\Backend\File(new Cache\Frontend\Data([
                             'lifetime' => 365*86400,
                         ]), [
                             'cacheDir' => $this->getOption('compiledPath') ?: getcwd()
                         ]);
        }
        return $this->cache;
    }

    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    public function getLogger()
    {
        if (!$this->logger) {
            $di = $this->getDi();
            $this->logger = $di->has('logger') ? $di->getLogger()
                          : new Logger\Adapter\Stream('php://stderr');
        }
        return $this->logger;
    }
}

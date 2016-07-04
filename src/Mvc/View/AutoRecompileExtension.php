<?php
namespace PhalconX\Mvc\View;

use ReflectionClass;
use Psr\Log\LoggerInterface;
use PhalconX\Helper\ArrayHelper;

/**
 * Automatic recompile template when base template changed
 *
 * volt inheritance will include base template to compiled file.
 * if base template was changed, the child template will not
 * recompile. this volt extension resolve this problem.
 *
 * Note: used in development only
 *
 * <code>
 * $ext = new AutoRecompileExtension();
 * $volt->getCompiler()->addExtension($ext);
 * </code>
 */
class AutoRecompileExtension
{
    const T_EXTENDS = 310;

    /**
     * @var \Phalcon\Mvc\View\Engine\Volt\Compiler
     */
    private $compiler;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    private $options = [];
    /**
     * @var array
     */
    private $deps = [];
    /**
     * @var array
     */
    private $baseViews = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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
        $view = $this->getView();
        if (isset($view)) {
            $this->setOption('viewsDir', $view->getViewsDir());
        }
        $this->setOption('compiledPath', $compiler->getOption('compiledPath'));
        $this->setOption('compiledSeparator', $compiler->getOption('compiledSeparator'));
        $this->setOption('compiledExtension', $compiler->getOption('compiledExtension'));
        $compiler->setOption('compiledPath', function ($templatePath, $options, $extendMode) {
            $templatePath = realpath($templatePath);
            $compiledPath = $this->getCompiledPath($templatePath, $extendMode);
            if ($this->hasDependency($templatePath)) {
                if ($this->isDependencyNewer($templatePath)) {
                    @unlink($compiledPath);
                }
            }
            return $compiledPath;
        });
        $this->load();
    }
    
    public function compileStatement($statment)
    {
        if ($statment['type'] === self::T_EXTENDS) {
            $this->addDependency(
                $statment['path']['file'],
                $statment['path']['value']
            );
        }
    }

    private function getView()
    {
        $refl = new ReflectionClass($this->compiler);
        $prop = $refl->getProperty('_view');
        $prop->setAccessible(true);
        return $prop->getValue($this->compiler);
    }

    /**
     * @param string $templatePath view file path
     * @param bool $extendsMode
     * @return string
     */
    private function getCompiledPath($templatePath, $extendsMode = null)
    {
        if (!isset($extendsMode)) {
            $extendsMode = $this->isBaseView($templatePath);
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
    
    private function isBaseView($path)
    {
        return in_array($path, $this->baseViews);
    }

    private function isDependencyNewer($path)
    {
        if ($this->hasDependency($path)) {
            if (!file_exists($compiled = $this->getCompiledPath($path))) {
                return false;
            }
            $mtime = filemtime($compiled);
            if (filemtime($base = $this->getBaseView($path)) > $mtime) {
                $this->logger->debug("base view $base is newer than $path, recompile $path");
                return true;
            }
            if ($this->hasDependency($base)) {
                return $this->isDependencyNewer($base);
            }
        }
        return false;
    }

    private function hasDependency($path)
    {
        return isset($this->deps[$path]);
    }

    private function getBaseView($path)
    {
        return isset($this->deps[$path]) ? $this->deps[$path] : null;
    }

    /**
     * @param string $path current view file
     * @param string $base base view name
     */
    private function addDependency($path, $base)
    {
        $base = realpath($this->getOption('viewsDir') . $base);
        $path = realpath($path);
        
        $this->baseViews[] = $base;
        $this->deps[$path] = $base;
        $this->save();
    }

    private function load()
    {
        if (file_exists($file = $this->getFile())) {
            $data = json_decode(file_get_contents($file), true);
            $this->deps = ArrayHelper::fetch($data, 'deps', []);
            $this->baseViews = ArrayHelper::fetch($data, 'baseViews', []);
        }
    }

    private function save()
    {
        file_put_contents(
            $this->getFile(),
            json_encode([
                'deps' => $this->deps,
                'baseViews' => $this->baseViews
            ])
        );
    }

    private function getFile()
    {
        return $this->getOption('compiledPath') . '/.template-extends';
    }
}

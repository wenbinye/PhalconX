<?php
namespace PhalconX\Php;

use Phalcon\Text;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class NodeVisitor extends NodeVisitorAbstract
{
    /**
     * @var array key is NodeVisitor class name, value is [nodeClass => methods ]
     */
    protected static $enterMethods = [];

    /**
     * @var array key is NodeVisitor class name, value is [nodeClass => methods ]
     */
    protected static $leaveMethods = [];

    /**
     * @var array key is Node class name, value is methods
     */
    private $cachedMethods;

    public function __construct()
    {
        if (!isset(self::$enterMethods[get_class($this)])) {
            $this->buildMethods();
        }
    }

    public function enterNode(Node $node)
    {
        foreach ($this->getEnterMethods($node) as $method) {
            if (is_string($method)) {
                $this->$method($node);
            } else {
                call_user_func($method, $this, $node);
            }
        }
    }

    public function leaveNode(Node $node)
    {
        foreach ($this->getLeaveMethods($node) as $method) {
            if (is_string($method)) {
                $this->$method($node);
            } else {
                call_user_func($method, $this, $node);
            }
        }
    }

    /**
     * collect all rule method according to node type of parameter
     */
    protected function buildMethods()
    {
        $refl = new \ReflectionClass($this);
        $enterMethods = [];
        $leaveMethods = [];

        foreach ($refl->getMethods() as $method) {
            $name = $method->getName();
            if (preg_match('/^(enter|leave).+/', $name) && !preg_match('/^(enter|leave)Node$/', $name)) {
                $params = $method->getParameters();
                if ($params && $params[0]->getClass()) {
                    $nodeType = $params[0]->getClass()->getName();
                    if (Text::startsWith($name, 'enter')) {
                        $enterMethods[$nodeType][] = $name;
                    } else {
                        $leaveMethods[$nodeType][] = $name;
                    }
                }
            }
        }
        self::$enterMethods[get_class($this)] = $enterMethods;
        self::$leaveMethods[get_class($this)] = $leaveMethods;
    }

    protected function getEnterMethods($node)
    {
        return $this->getRuleMethods($node, 'enter');
    }

    protected function getLeaveMethods($node)
    {
        return $this->getRuleMethods($node, 'leave');
    }

    protected function getRuleMethods($node, $type)
    {
        $class = is_object($node) ? get_class($node) : $node;
        if (!isset($this->cachedMethods[$type][$class])) {
            $rules = $type == 'enter' ? self::$enterMethods : self::$leaveMethods;
            $matches = [];
            foreach ($rules[get_class($this)] as $nodeType => $methods) {
                if (is_a($node, $nodeType)) {
                    $matches = array_merge($matches, $methods);
                }
            }
            $this->cachedMethods[$type][$class] = $matches;
        }
        return $this->cachedMethods[$type][$class];
    }

    protected function addRule($nodeClass, $callback, $type)
    {
        $this->cachedMethods[$type][$nodeClass]
            = array_merge($this->getRuleMethods($nodeClass, $type), [$callback]);
        return $this;
    }

    public function ifEnter($nodeClass, $callback)
    {
        return $this->addRule($nodeClass, $callback, 'enter');
    }

    public function ifLeave($nodeClass, $callback)
    {
        return $this->addRule($nodeClass, $callback, 'leave');
    }
}

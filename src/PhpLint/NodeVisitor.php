<?php
namespace PhalconX\PhpLint;

use Phalcon\Text;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class NodeVisitor extends NodeVisitorAbstract
{
    private $file;
    private $namespace;
    private $uses;
    private $reporter;

    private $cachedRules;

    private static $rules;

    public function __construct(Reporters\ReporterInterface $reporter, $file)
    {
        if (!isset(self::$rules[get_class($this)])) {
            $this->buildRules();
        }
        $this->reporter = $reporter;
        $this->file = $file;
    }

    /**
     * collect all rule method according to node type of parameter
     */
    private function buildRules()
    {
        $refl = new \ReflectionClass($this);
        $rules = [];

        foreach ($refl->getMethods() as $method) {
            if (Text::startsWith($method->getName(), 'rule')) {
                $params = $method->getParameters();
                if ($params && $params[0]->getClass()) {
                    $rules[$params[0]->getClass()->getName()][] = $method->getName();
                }
            }
        }
        self::$rules[get_class($this)] = $rules;
    }

    public function enterNode(Node $node)
    {
        foreach ($this->getRuleMethods($node) as $method) {
            $this->$method($node);
        }
    }

    protected function getRuleMethods(Node $node)
    {
        $type = get_class($node);
        if (!isset($this->cachedRules[$type])) {
            foreach (self::$rules[get_class($this)] as $nodeType => $methods) {
                if (is_a($node, $nodeType)) {
                    return $this->cachedRules[$type] = $methods;
                }
            }
            $this->cachedRules[$type] = [];
        }
        return $this->cachedRules[$type];
    }

    protected function checkClassExists(Node $name)
    {
        if (!$name instanceof Node\Name) {
            return;
        }
        if ($name->isFullyQualified()) {
            $class = (string) $name;
        } else {
            $class = (string) $name;
            if (in_array($class, ['self','static', 'parent'])) {
                return;
            }
            if (isset($this->uses[$name->getFirst()])) {
                $parts = $name->parts;
                array_shift($parts);
                $class = $this->uses[$name->getFirst()]->name .
                       ($parts ? '\\' . implode('\\', $parts) : '');
            } else {
                $class = $this->namespace . '\\' . $name;
            }
        }
        if (!class_exists($class) && !interface_exists($class)) {
            $this->addError(Errors\ClassNotExist::class, $name);
        }
    }

    protected function parseDocComment($doc)
    {
        if (!$doc) {
            return;
        }
        $ln = $doc->getLine();
        foreach (explode("\n", $doc->getText()) as $line) {
            if (preg_match('#\s*\*\s*\@(\\\\?[A-Z][\w\\\\]+)#', $line, $matches)) {
                $attributes = ['startLine' => $ln, 'annotation' => $line];
                $name = $matches[1];
                if (Text::startsWith($name, '\\')) {
                    $node = new Node\Name\FullyQualified(explode('\\', $name), $attributes);
                } else {
                    $node = new Node\Name(explode('\\', $name), $attributes);
                }
                $this->checkClassExists($node);
            }
            $ln++;
        }
    }

    public function getUses()
    {
        return $this->uses;
    }

    public function addError($errorType, $node)
    {
        $error = new $errorType($node);
        $error->setFile($this->file)
            ->setLine($node->getLine());
        $this->reporter->add($error);
    }

    protected function ruleNamespace(Node\Stmt\Namespace_ $node)
    {
        $this->namespace = (string) $node->name;
    }

    /**
     * collect use
     */
    protected function ruleUse(Node\Stmt\Use_ $node)
    {
        foreach ($node->uses as $use) {
            if (isset($this->uses[$use->alias])) {
                $this->addError(Errors\UseConflict::class, $use);
            } elseif ($node->type === Node\Stmt\Use_::TYPE_NORMAL) {
                $this->uses[$use->alias] = $use;
            }
        }
    }

    protected function ruleClass(Node\Stmt\Class_ $node)
    {
        if ($node->extends) {
            $this->checkClassExists($node->extends);
        }
        if ($node->implements) {
            foreach ($node->implements as $name) {
                $this->checkClassExists($name);
            }
        }
        $this->parseDocComment($node->getDocComment());
    }

    protected function ruleMethod(Node\Stmt\ClassMethod $node)
    {
        $this->parseDocComment($node->getDocComment());
    }

    protected function ruleMethodParam(Node\Param $node)
    {
        if ($node->type && $node->type instanceof Node\Name) {
            $this->checkClassExists($node->type);
        }
    }

    protected function ruleProperty(Node\Stmt\Property $node)
    {
        $this->parseDocComment($node->getDocComment());
    }

    protected function ruleNewClass(Node\Expr\New_ $node)
    {
        $this->checkClassExists($node->class);
    }

    protected function ruleStaticClass(Node\Expr\StaticCall $node)
    {
        $this->checkClassExists($node->class);
    }

    protected function ruleInstanceof(Node\Expr\Instanceof_ $node)
    {
        $this->checkClassExists($node->class);
    }

    protected function ruleTryCatch(Node\Stmt\Catch_ $node)
    {
        $this->checkClassExists($node->type);
    }

    protected function ruleFuncall(Node\Expr\FuncCall $node)
    {
        $name = $node->name;
        // not Node\Expr\Variable and not global function
        if ($name instanceof Node\Name && count($name->parts) > 1) {
            array_pop($name->parts);
            $this->checkClassExists($name);
        }
    }

    protected function ruleClassConstFetch(Node\Expr\ClassConstFetch $node)
    {
        if ($node->class instanceof Node\Name) { // 可能为 PhpParser\Node\Expr\Variable
            $this->checkClassExists($node->class);
        }
    }
}

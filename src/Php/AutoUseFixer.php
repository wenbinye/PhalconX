<?php
namespace PhalconX\Php;

use PhalconX\Annotation\Annotation;
use Phalcon\Text;
use PhalconX\Text\LineEditor;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Error;
use PhpParser\Node;

class AutoUseFixer extends NodeVisitor
{
    private $source;

    private $hierarchy;

    private $logger;

    private $namespace;

    private $uses;

    private $stmts;

    private $replacements;

    private $lines;
    
    public function __construct($source, ClassHierarchy $hierarchy, $logger = null)
    {
        $this->source = $source;
        $this->hierarchy = $hierarchy;
        $this->logger = $logger;
        parent::__construct();
    }

    public function fix()
    {
        $code = is_resource($this->source) ? stream_get_contents($this->source)
              : file_get_contents($this->source);
        $this->lines = new LineEditor(trim($code));
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);
        $traverser = new NodeTraverser;
        $stmts = $parser->parse($code);
        $traverser->addVisitor($this);
        $traverser->traverse($stmts);
        $this->fixUse();
        return $this->lines . "\n";
    }

    public function enterNamespace(Node\Stmt\Namespace_ $node)
    {
        $this->namespace = $node->name;
        $this->uses = [];
        $this->stmts = [];
    }

    public function leaveNamespace(Node\Stmt\Namespace_ $node)
    {
        $this->fixUse();
        $this->namespace = null;
        $this->uses = [];
        $this->stmts = [];
    }

    /**
     * collect use
     */
    public function enterUse(Node\Stmt\Use_ $node)
    {
        $this->stmts[] = $node;
        foreach ($node->uses as $use) {
            if (isset($this->uses[$use->alias])) {
                if ($this->logger) {
                    $this->logger->warning("use {0} conflicts", [$use->name]);
                }
            } elseif ($node->type === Node\Stmt\Use_::TYPE_NORMAL) {
                $this->uses[$use->alias] = $use;
            }
        }
    }

    protected function checkClassExists(Node $name)
    {
        if (!$name instanceof Node\Name
            || $name->isFullyQualified()
            || count($name->parts) > 1) {
            return;
        }
        $class = (string) $name;
        if (in_array($class, ['self','static', 'parent'])
            || isset($this->uses[$class])) {
            return;
        }
        $expand = $this->namespace . '\\' . $name;
        if ($this->hierarchy->classExists($expand)
            || $this->hierarchy->interfaceExists($expand)) {
            return;
        }
        $this->import($name, $name->hasAttribute('annotation'));
    }

    protected function fixUse()
    {
        if (!$this->uses) {
            return;
        }
        $code = [];
        foreach ($this->uses as $alias => $stmt) {
            if (is_string($stmt)) {
                $code[] = "use $stmt;";
            } else {
                if ($alias == $stmt->name->getLast()) {
                    $code[] = "use " . $stmt->name . ";";
                } else {
                    $code[] = "use " . $stmt->name . " as $alias;";
                }
            }
        }
        if ($code) {
            $len = 0;
            if ($this->stmts) {
                $first = array_shift($this->stmts);
                $offset = $first->getLine();
                $len = $first->getAttribute('endLine') - $offset + 1;
                foreach ($this->stmts as $stmt) {
                    $lines = $stmt->getAttribute('endLine') - $stmt->getLine() + 1;
                    if (trim($this->lines->get($stmt->getLine()+1)) === '') {
                        $lines++;
                    }
                    $this->lines->delete($stmt->getLine(), $lines);
                }
            } elseif ($this->namespace) {
                $offset = $this->namespace->getLine() + 1;
                array_unshift($code, '');
            } else {
                $offset = 2;
                $code[] = '';
            }
            $this->lines->replace($offset, $len, implode("\n", $code));
        }
    }

    protected function import($name, $isAnnotation)
    {
        $suffix = '\\' . $name;
        $matches = [];
        foreach ($this->hierarchy->getClasses() as $class) {
            if (Text::endsWith($class, $suffix)) {
                $matches[] = $class;
            }
        }
        foreach ($this->hierarchy->getInterfaces() as $class) {
            if (Text::endsWith($class, $suffix)) {
                $matches[] = $class;
            }
        }
        if ($isAnnotation) {
            foreach ($matches as $i => $match) {
                if (!$this->hierarchy->isA($match, Annotation::class)) {
                    unset($matches[$i]);
                }
            }
            $matches = array_values($matches);
        }
        if (count($matches) == 1) {
            $this->uses[(string) $name] = $matches[0];
        } elseif ($this->logger) {
            if (count($matches) > 1) {
                $this->logger->warning("multiple class match {0}: {1}", [$name, implode(', ', $matches)]);
            } else {
                $this->logger->warning("No class match " . $name);
            }
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

    public function enterClass(Node\Stmt\Class_ $node)
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

    public function enterMethod(Node\Stmt\ClassMethod $node)
    {
        $this->parseDocComment($node->getDocComment());
    }

    public function enterMethodParam(Node\Param $node)
    {
        if ($node->type && $node->type instanceof Node\Name) {
            $this->checkClassExists($node->type);
        }
    }

    public function enterProperty(Node\Stmt\Property $node)
    {
        $this->parseDocComment($node->getDocComment());
    }

    public function enterNewClass(Node\Expr\New_ $node)
    {
        $this->checkClassExists($node->class);
    }

    public function enterStaticClass(Node\Expr\StaticCall $node)
    {
        $this->checkClassExists($node->class);
    }

    public function enterInstanceof(Node\Expr\Instanceof_ $node)
    {
        $this->checkClassExists($node->class);
    }

    public function enterTryCatch(Node\Stmt\Catch_ $node)
    {
        $this->checkClassExists($node->type);
    }

    public function enterFuncall(Node\Expr\FuncCall $node)
    {
        $name = $node->name;
        // not Node\Expr\Variable and not global function
        if ($name instanceof Node\Name && count($name->parts) > 1) {
            array_pop($name->parts);
            $this->checkClassExists($name);
        }
    }

    public function enterClassConstFetch(Node\Expr\ClassConstFetch $node)
    {
        if ($node->class instanceof Node\Name) { // 可能为 PhpParser\Node\Expr\Variable
            $this->checkClassExists($node->class);
        }
    }
}

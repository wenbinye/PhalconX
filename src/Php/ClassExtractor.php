<?php
namespace PhalconX\Php;

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Error;
use PhpParser\Node;

/**
 * php lint
 */
class ClassExtractor extends NodeVisitor
{
    private $source;

    private $classes;

    private $interfaces;

    private $namespace;

    private $uses;
    
    /**
     * Constructor.
     *
     * @param string $source file name or code
     * @param Reporters\ReporterInterface $reporter
     */
    public function __construct($source)
    {
        $this->source = $source;
        parent::__construct();
    }
    
    protected function extract()
    {
        $this->classes = [];
        $this->interfaces = [];
        $code = is_resource($this->source) ? stream_get_contents($this->source)
              : file_get_contents($this->source);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);
        $traverser = new NodeTraverser;
        try {
            $stmts = $parser->parse($code);
            $traverser->addVisitor($this);
            $traverser->traverse($stmts);
        } catch (Error $e) {
        }
        return $this;
    }

    public function getClasses()
    {
        if (!isset($this->classes)) {
            $this->extract();
        }
        return $this->classes;
    }

    public function getInterfaces()
    {
        if (!isset($this->interfaces)) {
            $this->extract();
        }
        return $this->interfaces;
    }

    public function enterNamespace(Node\Stmt\Namespace_ $node)
    {
        $this->namespace = (string) $node->name;
        $this->uses = [];
    }

    public function leaveNamespace(Node\Stmt\Namespace_ $node)
    {
        $this->namespace = '';
        $this->uses = [];
    }

    /**
     * collect use
     */
    public function enterUse(Node\Stmt\Use_ $node)
    {
        foreach ($node->uses as $use) {
            if (isset($this->uses[$use->alias])) {
                // ignore
            } elseif ($node->type === Node\Stmt\Use_::TYPE_NORMAL) {
                $this->uses[$use->alias] = $use;
            }
        }
    }

    public function enterClass(Node\Stmt\Class_ $node)
    {
        $name = $this->namespace . '\\' . $node->name;
        $extends = null;
        if ($node->extends) {
            $extends = $this->resolveClassName($node->extends);
        }
        $implements = [];
        if ($node->implements) {
            foreach ($node->implements as $impl) {
                $implements[] = $this->resolveClassName($impl);
            }
        }
        $this->classes[$name] = [
            'extends' => $extends,
            'implements' => $implements
        ];
    }

    public function enterInterface(Node\Stmt\Interface_ $node)
    {
        $name = $this->namespace . '\\' . $node->name;
        $extends = [];
        if ($node->extends) {
            foreach ($node->extends as $impl) {
                $extends[] = $this->resolveClassName($impl);
            }
        }
        $this->interfaces[$name] = ['extends' => $extends];
    }

    private function resolveClassName(Node\Name $name)
    {
        if ($name->isFullyQualified()) {
            return (string) $name;
        }
        if (isset($this->uses[$name->getFirst()])) {
            $parts = $name->parts;
            array_shift($parts);
            $class = $this->uses[$name->getFirst()]->name .
                   ($parts ? '\\' . implode('\\', $parts) : '');
        } else {
            $class = $this->namespace . '\\' . $name;
        }
        return $class;
    }
}

<?php
namespace PhalconX\Php;

use Phalcon\Text;
use PhalconX\Php\Lint\Reporters\ReporterInterface;
use PhalconX\Php\Lint\Errors;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Error;
use PhpParser\Node;

/**
 * php lint
 */
class Lint extends NodeVisitor
{
    private $file;

    private $stream;

    private $reporter;

    private $namespace;

    private $uses;

    /**
     * Constructor.
     *
     * @param string $source file name or code
     * @param Reporters\ReporterInterface $reporter
     */
    public function __construct($source, ReporterInterface $reporter)
    {
        parent::__construct();
        if (is_resource($source)) {
            $this->stream = $source;
        } else {
            $this->file = $source;
        }
        $this->reporter = $reporter;
    }
    
    public function lint()
    {
        $code = $this->stream ? stream_get_contents($this->stream)
              : file_get_contents($this->file);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);
        $traverser = new NodeTraverser;
        try {
            $stmts = $parser->parse($code);
            // print_r($stmts);
            $traverser->addVisitor($this);
            $traverser->traverse($stmts);
        } catch (Error $e) {
            $error = (new Errors\SyntaxError())
                ->setLine($e->getStartLine())
                ->setFile($this->file);
            $this->reporter->add($error);
        }
        return $this;
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

    protected function addError($errorType, $node)
    {
        $error = new $errorType($node);
        $error->setFile($this->file)
            ->setLine($node->getLine());
        $this->reporter->add($error);
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
                $this->addError(Errors\UseConflict::class, $use);
            } elseif ($node->type === Node\Stmt\Use_::TYPE_NORMAL) {
                $this->uses[$use->alias] = $use;
            }
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

    public function getReporter()
    {
        return $this->reporter;
    }

    public function setReporter($reporter)
    {
        $this->reporter = $reporter;
        return $this;
    }
}

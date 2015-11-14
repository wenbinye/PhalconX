<?php
namespace PhalconX\PhpLint;

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Error;

/**
 * php lint
 */
class PhpLint
{
    private $file;
    private $stream;
    private $reporter;

    /**
     * Constructor.
     *
     * @param string $source file name or code
     * @param Reporters\ReporterInterface $reporter
     */
    public function __construct($source, Reporters\ReporterInterface $reporter)
    {
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
        $visitor = new NodeVisitor($this->reporter, $this->file);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);
        $traverser = new NodeTraverser;
        try {
            $stmts = $parser->parse($code);
            // print_r($stmts);
            $traverser->addVisitor($visitor);
            $traverser->traverse($stmts);
        } catch (Error $e) {
            $error = (new Errors\SyntaxError())
                ->setLine($e->getStartLine())
                ->setFile($this->file);
            $this->reporter->add($error);
        }
        return $this;
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

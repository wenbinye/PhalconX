<?php
namespace PhalconX\PhpLint\Errors;

use PhpParser\Node;

class UseConflict extends AbstractError
{
    private $node;
    
    public function __construct(Node\Stmt\UseUse $node)
    {
        $this->node = $node;
    }

    public function getDescription()
    {
        return strtr('The import :class name conflicts with previous one', [
            ':class' => $this->node->name
        ]);
    }
}

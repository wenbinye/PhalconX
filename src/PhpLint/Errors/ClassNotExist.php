<?php
namespace PhalconX\PhpLint\Errors;

use PhpParser\Node;

class ClassNotExist extends AbstractError
{
    private $node;

    public function __construct(Node\Name $node)
    {
        $this->node = $node;
    }

    public function getDescription()
    {
        return strtr('The class :class not exist', [
            ':class' => $this->node
        ]);
    }
}

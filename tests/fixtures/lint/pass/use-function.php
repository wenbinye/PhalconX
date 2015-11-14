<?php
namespace Chaozhuo 
{
    const HTML = 'html';
    function foo() 
    {
    }
}

namespace Chaozhuo\Helper 
{
    use const Chaozhuo\HTML;
    use function Chaozhuo\foo;
}

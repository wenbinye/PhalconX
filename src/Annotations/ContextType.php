<?php
namespace PhalconX\Annotations;

use PhalconX\Enums\Enum;

class ContextType extends Enum
{
    const T_CLASS = 'class';

    const T_METHOD = 'method';

    const T_PROPERTY = 'property';
}

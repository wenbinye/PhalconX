<?php
namespace PhalconX\Test\Enum;

use PhalconX\Enum\Enum;

class Gender extends Enum
{
    const MALE = 'm';

    const FEMALE = 'f';

    protected static $PROPERTIES = [
        'description' => [
            self::MALE => '男',
            self::FEMALE => '女'
        ],
    ];
}

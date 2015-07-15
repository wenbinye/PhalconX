<?php
namespace PhalconX\Validators;

use PhalconX\Test\TestCase;
use Phalcon\Validation;

abstract class BaseValidatorTest extends TestCase
{
    protected $validation;

    public function setUp()
    {
        $this->validation = new Validation;
    }
}

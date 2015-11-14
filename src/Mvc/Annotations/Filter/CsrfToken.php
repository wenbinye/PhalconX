<?php
namespace PhalconX\Mvc\Annotations\Filter;

use PhalconX\Exception\HttpMethodNotAllowedException;
use PhalconX\Exception\CsrfTokenInvalidException;

class CsrfToken extends AbstractFilter
{
    private static $ALLOWED_METHODS = ['PUT', 'POST'];

    public $repeatOk = false;
    
    public function filter()
    {
        if (!in_array($this->request->getMethod(), self::$ALLOWED_METHODS)) {
            throw new HttpMethodNotAllowedException();
        }
        $destroy = !$this->repeatOk;
        if (!$this->security->checkToken(null, null, $destroy)) {
            throw new CsrfTokenInvalidException();
        }
    }
}

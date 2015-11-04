<?php
namespace PhalconX\Mvc\Annotations\Filter;

use PhalconX\Exception\FilterException;
use PhalconX\Enum\ErrorCode;

class CsrfToken extends AbstractFilter
{
    private static $ALLOWED_METHODS = ['PUT', 'POST'];

    public $repeatOk = false;
    
    public function filter()
    {
        if (!in_array($this->request->getMethod(), self::$ALLOWED_METHODS)) {
            $this->response->setStatusCode(405);
            throw new FilterException(ErrorCode::HTTP_METHOD_INVALID);
        }
        $destroy = !$this->repeatOk;
        if (!$this->security->checkToken(null, null, $destroy)) {
            $this->response->setStatusCode(400);
            throw new FilterException(ErrorCode::CSRF_TOKEN_INVALID);
        }
    }
}

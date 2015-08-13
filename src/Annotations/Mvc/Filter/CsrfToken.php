<?php
namespace PhalconX\Annotations\Mvc\Filter;

use PhalconX\Exception;

class CsrfToken extends AbstractFilter
{
    private static $ALLOWED_METHODS = ['PUT', 'POST'];

    public $repeatOk = false;
    
    public function filter()
    {
        if (!in_array($this->request->getMethod(), self::$ALLOWED_METHODS)) {
            $this->response->setStatusCode(405);
            throw new Exception(
                'HTTP method is not suported for this request',
                Exception::ERROR_HTTP_METHOD_INVALID
            );
        }
        $destroy = !$this->repeatOk;
        if (!$this->security->checkToken(null, null, $destroy)) {
            $this->response->setStatusCode(400);
            throw new Exception(
                'Invalid request, likely attacking',
                Exception::ERROR_CSRF_TOKEN_INVALID
            );
        }
    }
}

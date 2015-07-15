<?php
namespace PhalconX\Mvc\Controller\Filter;

use Phalcon\Di\Injectable;
use PhalconX\Exception;

class CsrfToken extends Injectable implements FilterInterface
{
    private static $methods = ['PUT', 'POST'];
    
    public function filter($dispatcher)
    {
        if (!in_array($this->request->getMethod(), $this->methods)) {
            $this->response->setStatusCode(405);
            throw new Exception(
                'HTTP method is not suported for this request',
                Exception::ERROR_HTTP_METHOD_INVALID
            );
        }
        if (!$this->security->checkToken()) {
            throw new Exception(
                'Invalid request, likely attacking',
                Exception::ERROR_CSRF_TOKEN_INVALID
            );
        }
    }
}

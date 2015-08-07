<?php
namespace PhalconX\Mvc\Controller\Filter;

use Phalcon\Di\Injectable;
use PhalconX\Util;
use PhalconX\Exception;

class CsrfToken extends Injectable implements FilterInterface
{
    private static $ALLOWED_METHODS = ['PUT', 'POST'];
    private $repeatOk;

    public function __construct($options = null)
    {
        $this->repeatOk = Util::fetch($options, 'repeatOk', false);
    }
    
    public function filter($dispatcher)
    {
        if (!in_array($this->request->getMethod(), self::$ALLOWED_METHODS)) {
            $this->response->setStatusCode(405);
            throw new Exception(
                'HTTP method is not suported for this request',
                Exception::ERROR_HTTP_METHOD_INVALID
            );
        }
        $destroy = !$this->repeatOk;
        $this->logger->info("destroy security token $destroy");
        if (!$this->security->checkToken(null, null, $destroy)) {
            $this->response->setStatusCode(400);
            throw new Exception(
                'Invalid request, likely attacking',
                Exception::ERROR_CSRF_TOKEN_INVALID
            );
        }
    }
}

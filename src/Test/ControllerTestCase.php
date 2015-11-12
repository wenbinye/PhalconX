<?php
namespace PhalconX\Test;

abstract class ControllerTestCase extends DatabaseTestCase
{
    protected function getDataSet()
    {
    }
    
    public function setUp()
    {
        $_SERVER = array (
            'USER' => 'www-data',
            'HOME' => '/var/www',
            'FCGI_ROLE' => 'RESPONDER',
            'SCRIPT_FILENAME' => __DIR__ . '/index.php',
            'QUERY_STRING' => '',
            'REQUEST_METHOD' => 'GET',
            'CONTENT_TYPE' => '',
            'CONTENT_LENGTH' => '',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/index.php',
            'DOCUMENT_URI' => '/index.php',
            'DOCUMENT_ROOT' => __DIR__,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'SERVER_SOFTWARE' => 'nginx/1.6.2',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => '48910',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => '80',
            'SERVER_NAME' => 'example.com',
            'REDIRECT_STATUS' => '200',
            'HTTP_USER_AGENT' => 'curl/7.35.0',
            'HTTP_HOST' => 'localhost',
            'HTTP_ACCEPT' => '*/*',
            'PHP_SELF' => '/index.php',
            'REQUEST_TIME_FLOAT' => microtime(true),
            'REQUEST_TIME' => time(),
        );
        parent::setUp();
    }
    
    public function getResponse($format = null)
    {
        $router = $this->router;
        $dispatcher = $this->dispatcher;
        $router->handle();
        $dispatcher->setModuleName($router->getModuleName());
        $dispatcher->setNamespaceName($router->getNamespaceName());
        $dispatcher->setControllerName($router->getControllerName());
        $dispatcher->setActionName($router->getActionName());
        $dispatcher->dispatch();
        $response = $this->response->getContent();
        if (isset($format)) {
            if ($format == 'json') {
                return json_decode($response, true);
            } else {
                throw new \InvalidArgumentException("unknown format '$format'");
            }
        } else {
            return $response;
        }
    }

    public function getJsonResponse()
    {
        return $this->getResponse('json');
    }
    
    public function setRequest($vars)
    {
        $_REQUEST = $vars;
    }
}

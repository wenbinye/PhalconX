<?php
namespace PhalconX\Http;

class Request extends \Phalcon\Http\Request
{
    private $body;
    private $files;
    
    public function getFile($name)
    {
        if (!isset($this->files)) {
            $files = $this->getUploadedFiles();
            foreach ($files as $file) {
                $key = $file->getKey();
                if (isset($key)) {
                    $this->files[$key] = $file;
                } else {
                    $this->files[] = $file;
                }
            }
        }
        if (isset($this->files[$name])) {
            return $this->files[$name];
        } else {
            throw new \InvalidArgumentException("Upload file '$name' is empty");
        }
    }

    public function getBody()
    {
        if (!isset($this->body)) {
            if (function_exists('http_get_request_body')) {
                $this->body = http_get_request_body();
            } else {
                $this->body = @file_get_contents('php://input');
            }
        }
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }
}

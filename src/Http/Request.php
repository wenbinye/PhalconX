<?php
namespace PhalconX\Http;

/**
 * Http Request
 *
 * Add functions for files and post body
 */
class Request extends \Phalcon\Http\Request
{
    /**
     * @var string $body post body content
     */
    private $body;

    /**
     * @var array $files
     */
    private $files;

    /**
     * @return int
     */
    public function hasFiles($onlySuccessful = true)
    {
        if (!is_array($_FILES)) {
            return 0;
        }
        $files = 0;
        foreach ($_FILES as $file) {
            if (isset($file['error'])) {
                $error = $file['error'];
                if (is_array($error)) {
                    $files += $this->hasFileHelper($error, $onlySuccessful);
                } elseif ($error == UPLOAD_ERR_NO_FILE) {
                } elseif ($error == UPLOAD_ERR_OK
                          || !$onlySuccessful) {
                    $files++;
                }
            }
        }
        return $files;
    }

    /**
     * Gets upload file object by name
     *
     * @return Phalcon\Http\Request\File
     */
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

    /**
     * Gets post body content
     *
     * @return string
     */
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

    /**
     * Sets post body content
     *
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }
}

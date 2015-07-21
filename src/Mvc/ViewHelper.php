<?php
namespace PhalconX\Mvc;

use Phalcon\Di\Injectable;

class ViewHelper extends Injectable
{
    private $clips = array();
    private $clipId;
    private $baseUrl;
        
    public function beginclip($name)
    {
        ob_start();
        $this->clipId = $name;
    }

    public function endclip()
    {
        $name = $this->clipId;
        if (isset($this->clips[$name])) {
            $this->clips[$name] .= ob_get_clean();
        } else {
            $this->clips[$name] = ob_get_clean();
        }
    }

    public function clip($name)
    {
        return isset($this->clips[$name]) ? $this->clips[$name] : '';
    }

    public function baseUrl()
    {
        if (!$this->baseUrl) {
            $this->baseUrl = $this->request->getScheme()
                . '://' . $this->request->getHttpHost();
        }
        return $this->baseUrl;
    }
    
    public function absoluteUrl($uri = null, $args = null)
    {
        if (isset($uri)) {
            $uri = ltrim($uri, "/");
            return $this->baseUrl() . $this->url->get($uri, $args);
        } else {
            return $this->baseUrl() . $this->url->getBaseUri();
        }
    }

    public function assertConstant($constant)
    {
        return defined($constant) && constant($constant);
    }

    public function trim($str, $charlist = null)
    {
        return isset($charlist) ? trim($str, $charlist) : trim($str);
    }

    public function ltrim($str, $charlist = null)
    {
        return isset($charlist) ? ltrim($str, $charlist) : ltrim($str);
    }

    public function rtrim($str, $charlist = null)
    {
        return isset($charlist) ? rtrim($str, $charlist) : rtrim($str);
    }
}

<?php
namespace PhalconX\Mvc;

use Phalcon\Di\Injectable;

/**
 * Provides helper functions to volt template
 */
class ViewHelper extends Injectable
{
    /**
     * @var array
     */
    private $clips = array();

    /**
     * @var string the last clip name
     */
    private $clipName;

    /**
     * @var string base url
     */
    private $baseUrl;

    /**
     * Starts clip
     *
     * @param string $name
     */
    public function beginclip($name)
    {
        ob_start();
        $this->clipName = $name;
    }

    /**
     * Ends clip
     */
    public function endclip()
    {
        $name = $this->clipName;
        if (isset($this->clips[$name])) {
            $this->clips[$name] .= ob_get_clean();
        } else {
            $this->clips[$name] = ob_get_clean();
        }
    }

    /**
     * Gets clip content
     *
     * @param string $name
     * @return string
     */
    public function clip($name)
    {
        return isset($this->clips[$name]) ? $this->clips[$name] : '';
    }

    /**
     * Gets the base url
     *
     * @return string
     */
    public function baseUrl()
    {
        if (!$this->baseUrl) {
            $this->baseUrl = $this->request->getScheme()
                . '://' . $this->request->getHttpHost();
        }
        return $this->baseUrl;
    }

    /**
     * Gets absolute url
     *
     * @return string
     */
    public function absoluteUrl($uri = null, $args = null)
    {
        if (isset($uri)) {
            $uri = '/'. ltrim($uri, "/");
            return $this->baseUrl() . $this->url->get($uri, $args);
        } else {
            return $this->baseUrl() . $this->url->getBaseUri();
        }
    }

    /**
     * trim string
     *
     * @param string $str
     * @param array $charlist
     * @return string
     */
    public function trim($str, $charlist = null)
    {
        return isset($charlist) ? trim($str, $charlist) : trim($str);
    }

    /**
     * left trim string
     *
     * @param string $str
     * @param array $charlist
     * @return string
     */
    public function ltrim($str, $charlist = null)
    {
        return isset($charlist) ? ltrim($str, $charlist) : ltrim($str);
    }

    /**
     * right trim string
     *
     * @param string $str
     * @param array $charlist
     * @return string
     */
    public function rtrim($str, $charlist = null)
    {
        return isset($charlist) ? rtrim($str, $charlist) : rtrim($str);
    }
}

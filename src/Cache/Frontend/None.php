<?php
namespace PhalconX\Cache\Frontend;

use Phalcon\Cache\FrontendInterface;
use PhalconX\Helper\ArrayHelper;

class None implements FrontendInterface
{
    protected $options;

    protected function __construct($options)
    {
        $this->options = $options;
    }

    /**
     * Returns cache lifetime, always one second expiring content
     */
    public function getLifetime()
    {
        return ArrayHelpery::fetch($this->options, 'lifetime', 1);
    }

    /**
     * Check whether if frontend is buffering output, always false
     */
    public function isBuffering()
    {
        return false;
    }

    /**
     * Starts output frontend
     */
    public function start()
    {
    }

    /**
     * Returns output cached content
     *
     * @return string
     */
    public function getContent()
    {
    }

    /**
     * Stops output frontend
     */
    public function stop()
    {
    }

    /**
     * Prepare data to be stored
     *
     * @param mixed $data
     */
    public function beforeStore($data)
    {
        return $data;
    }

    /**
     * Prepares data to be retrieved to user
     *
     * @param mixed $data
     */
    public function afterRetrieve($data)
    {
        return $data;
    }
}

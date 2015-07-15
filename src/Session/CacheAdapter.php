<?php
namespace PhalconX\Session;

use Phalcon\Session\Adapter;
use Phalcon\Session\AdapterInterface;
use PhalconX\Di\Injectable;
use PhalconX\Util;

class CacheAdapter extends Adapter implements AdapterInterface
{
    private $cache;
    private $lifetime = 3600;
    private $prefix = 'session:';

    use Injectable;
    
    /**
     *
     * @param array $options
     *  - prefix cache key prefix
     *  - lifetime session lifetime
     *  - cookie_lifetime session cookie expiration
     *  - cookie_name session cookie name
     */
    public function __construct($options = null)
    {
        $this->cache = Util::service('cache', $options);

        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }

        if (isset($options['lifetime'])) {
            $this->lifetime = $options['lifetime'];
        }

        if (isset($options['cookie_lifetime'])) {
            ini_set('session.cookie_lifetime', $options['cookie_lifetime']);
        }
        if (isset($options['cookie_name'])) {
            ini_set('session.name', $options['cookie_name']);
        }

        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );

        parent::__construct($options);
    }

    public function open()
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    /**
     * Reads the data from the table
     *
     * @param string $sessionId
     * @return string
     */
    public function read($sessionId)
    {
        return $this->cache->get($this->prefix .$sessionId);
    }

    /**
     * Writes the data to the table
     *
     * @param string $sessionId
     * @param string $data
     */
    public function write($sessionId, $data)
    {
        $this->cache->save($sessionId, $data, $this->lifetime);
    }

    /**
     * Destroyes the session
     *
     */
    public function destroy($session_id = null)
    {
        @session_unset();
        if ($session_id === null) {
            $session_id = $this->getId();
        }
        return $this->cache->delete($this->prefix.$session_id);
    }

    /**
     * Performs garbage-collection on the session table
     *
     */
    public function gc()
    {
        return true;
    }
}

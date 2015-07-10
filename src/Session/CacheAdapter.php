<?php
namespace PhalconX\Session;

use Phalcon\DI\InjectionAwareInterface;
use Phalcon\Session\Adapter;
use Phalcon\Session\AdapterInterface;

class CacheAdapter extends Adapter implements AdapterInterface, InjectionAwareInterface
{
    private $di;
    private $cache;
    private $lifetime = 3600;
    private $prefix = 'session:';

    public function setDI($di)
    {
        $this->di = $di;
    }

    public function getDI()
    {
        return $this->di;
    }
    
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
        if (!isset($options['cache'])) {
            throw new \RuntimeException("The parameter 'cache' is required");
        }
        $this->cache = $options['cache'];

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
        return false;
    }

    /**
     * Reads the data from the table
     *
     * @param string $sessionId
     * @return string
     */
    public function read($sessionId)
    {
        $sessionData = $this->cache->get($this->prefix .$sessionId);
        // $this->getDI()->getLogger()->info("read session: $sessionId " . $sessionData);
        if (isset($sessionData)) {
            return json_decode($sessionData, true);
        }
        return null;
    }

    /**
     * Writes the data to the table
     *
     * @param string $sessionId
     * @param string $data
     */
    public function write($sessionId, $data)
    {
        $content = json_encode($data);
        // $this->getDI()->getLogger()->info( $_SERVER['REQUEST_URI'] . " write session: $sessionId " . $content);
        if ($this->lifetime > 0) {
            $this->cache->save($this->prefix.$sessionId, $content, $this->lifetime);
        } else {
            $this->cache->save($this->prefix.$sessionId, $content);
        }
    }

    /**
     * Destroyes the session
     *
     */
    public function destroy($session_id = '')
    {
        @session_unset();
        return $this->cache->delete($this->prefix.$session_id);
    }

    /**
     * Performs garbage-collection on the session table
     *
     */
    public function gc()
    {
    }
}

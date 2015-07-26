<?php
namespace PhalconX\Mvc\Metadata;

use PhalconX\Util;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\MetaDataInterface;
use Phalcon\Mvc\Model\Exception;

class Memory extends MetaData implements MetaDataInterface
{
    private $metadata;

    /**
     * Reads the meta-data from temporal memory
     *
     * @param string key
     * @return array
     */
    public function read($key)
    {
        return Util::fetch($this->metadata, $key);
    }

    /**
     * Writes the meta-data to temporal memory
     *
     * @param string key
     * @param array data
     */
    public function write($key, $data)
    {
        $this->metadata[$key] = $data;
    }
}

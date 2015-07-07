<?php
namespace PhalconX\Test;

use Symfony\Component\Yaml\Yaml;

/**
 * Create dataset from file
 */
trait Dataset
{
    public function dataset($file)
    {
        $ext = pathinfo($file, \PATHINFO_EXTENSION);
        $content = file_get_contents($this->config->fixturesDir . '/' . $file);
        if ($ext == 'json') {
            return json_decode($content, true);
        } elseif (in_array($ext, array('yml', 'yaml'))) {
            return Yaml::parse($content);
        } else {
            return $content;
        }
    }
}

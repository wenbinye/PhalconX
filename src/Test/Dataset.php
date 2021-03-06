<?php
namespace PhalconX\Test;

/**
 * Create dataset from file
 */
trait Dataset
{
    /**
     * load json, yaml data set
     *
     * <code>
     *   $this->dataset("file.json"); // read file in fixtures/file.json
     * </code>
     *
     * @return array
     */
    public function dataset($file, $format = null)
    {
        $file = $this->getDatasetFile($file);
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("Could not find dataset file '$file'");
        }
        if (!$format) {
            $format = pathinfo($file, \PATHINFO_EXTENSION);
        }
        if ($format == 'json') {
            return json_decode(file_get_contents($file), true);
        } elseif (in_array($format, array('yml', 'yaml'))) {
            return yaml_parse_file($file);
        } elseif ($format == 'php') {
            return require($file);
        } else {
            return file_get_contents($file);
        }
    }

    public function getDatasetFile($file)
    {
        return $this->config->fixturesDir . '/' . $file;
    }
}

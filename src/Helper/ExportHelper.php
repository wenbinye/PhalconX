<?php
namespace PhalconX\Helper;

use PhalconX\Helper\ArrayHelper;
use Symfony\Component\Yaml\Yaml;

class ExportHelper
{
    public static $FORMATS = [
        'json' => 'json',
        'yaml' => 'yaml',
        'yml' => 'yaml',
        'php' => 'php'
    ];
    
    public static function json($data, $pretty = true)
    {
        if ($pretty) {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            return json_encode($data);
        }
    }

    public static function yaml($data, $pretty = true)
    {
        return Yaml::dump($data, $pretty ? 4 : 2, 2);
    }

    public static function php($data)
    {
        return var_export($data, true);
    }

    /**
     * 序列化成指定格式
     *
     * @param mixed $data
     * @param string $format
     * @return string
     */
    public static function export($data, $format = 'yaml')
    {
        return self::$format($data);
    }

    /**
     * 读取序列化文件
     *
     * @param string $file
     * @param string $format 文件格式，如果未指定，使用文件后缀判断格式
     * @return array
     */
    public static function loadFile($file, $format = null)
    {
        if (!isset($format)) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $format = ArrayHelper::fetch(self::$FORMATS, $ext);
            if (!isset($format)) {
                throw new \InvalidArgumentException("Cannot infer format from file '{$file}'");
            }
        }
        if ($format == 'json') {
            return json_decode(file_get_contents($file), true);
        } elseif ($format == 'yaml') {
            return Yaml::parse(file_get_contents($file));
        } elseif ($format == 'php') {
            return require($file);
        } else {
            throw new \InvalidArgumentException("Invalid format '{$format}'");
        }
    }
}

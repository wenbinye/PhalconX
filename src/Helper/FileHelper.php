<?php
namespace PhalconX\Helper;

class FileHelper
{
    /**
     * Finds file
     */
    public static function find($dir, array $options = null)
    {
        $options = array_merge([
            'excludeHiddenFiles' => true,
        ], $options);
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($it as $file => $fileInfo) {
            $name = $fileInfo->getFilename();
            if ($name == '.' || $name == '..') {
                continue;
            }
            if ($options['excludeHiddenFiles'] && $name[0] == '.') {
                continue;
            }
            if (isset($options['extensions']) && !in_array($fileInfo->getExtension(), $options['extensions'])) {
                continue;
            }
            if (isset($options['includes']) && !preg_match($options['includes'], $file)) {
                continue;
            }
            if (isset($options['excludes']) && preg_match($options['excludes'], $file)) {
                continue;
            }
            yield $file => $fileInfo;
        }
    }
}

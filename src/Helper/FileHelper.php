<?php
namespace PhalconX\Helper;

use PhalconX\Exception\IOException;

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

    /**
     * join file path
     */
    public static function catfile($dir, $file)
    {
        if ($dir) {
            return rtrim($dir, '/') . '/' . ltrim($file, '/');
        } else {
            return $file;
        }
    }

    /**
     * rm -r path
     */
    public static function recursiveRemove($path)
    {
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    self::recursiveRemove("$path/$file");
                }
            }
            if (!rmdir($path)) {
                throw new IOException("Cannot rmdir '$path'", 0, null, $path);
            }
        } elseif (file_exists($path)) {
            if (!unlink($path)) {
                throw new IOException("Cannot unlink '$path'", 0, null, $path);
            }
        }
    }

    /**
     * cp -r
     */
    public static function recursiveCopy($src, $dst)
    {
        if (is_dir($src)) {
            if (!is_dir($dst) && !mkdir($dst)) {
                throw new IOException("Cannot mkdir '$dst'", 0, null, $path);
            }
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    self::recursiveCopy("$src/$file", "$dst/$file");
                }
            }
        } elseif (file_exists($src)) {
            if (!copy($src, $dst)) {
                throw new IOException("Cannot copy '$src' to '$dst'", 0, null, $src);
            }
        }
    }

    public static function isWindows()
    {
        return DIRECTORY_SEPARATOR != '/';
    }

    public static function isAbsolute($path)
    {
        if (self::isWindows()) {
            return preg_match('/^[A-Za-z]+:/', $path);
        } else {
            return !strncmp($path, DIRECTORY_SEPARATOR, 1);
        }
    }
    
    /**
     * Canonicalize a path by resolving it relative to some directory (by
     * default PWD), following parent symlinks and removing artifacts. If the
     * path is itself a symlink it is left unresolved.
     *
     * @param  string    Path, absolute or relative to PWD.
     * @return string    Canonical, absolute path.
     *
     * @task   path
     */
    public static function absolutePath($path, $relative_to = null)
    {
        if (self::isWindows()) {
            $is_absolute = preg_match('/^[A-Za-z]+:/', $path);
        } else {
            $is_absolute = !strncmp($path, DIRECTORY_SEPARATOR, 1);
        }

        if (!$is_absolute) {
            if (!$relative_to) {
                $relative_to = getcwd();
            }
            $path = $relative_to.DIRECTORY_SEPARATOR.$path;
        }

        if (is_link($path)) {
            $parent_realpath = realpath(dirname($path));
            if ($parent_realpath !== false) {
                return $parent_realpath.DIRECTORY_SEPARATOR.basename($path);
            }
        }

        $realpath = realpath($path);
        if ($realpath !== false) {
            return $realpath;
        }

        // This won't work if the file doesn't exist or is on an unreadable mount
        // or something crazy like that. Try to resolve a parent so we at least
        // cover the nonexistent file case.
        $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
        while (end($parts) !== false) {
            array_pop($parts);
            if (self::isWindows()) {
                $attempt = implode(DIRECTORY_SEPARATOR, $parts);
            } else {
                $attempt = DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $parts);
            }
            $realpath = realpath($attempt);
            if ($realpath !== false) {
                $path = $realpath.substr($path, strlen($attempt));
                break;
            }
        }

        return $path;
    }
}

<?php
namespace PhalconX\PhpLint\Errors;

interface ErrorInterface
{
    /**
     * Sets error source file name
     *
     * @param string $file
     * @return self
     */
    public function setFile($file);

    /**
     * Gets the source file name
     *
     * @return string
     */
    public function getFile();

    /**
     * Sets error source line number
     *
     * @param int $line
     * @return self
     */
    public function setLine($line);

    /**
     * Gets error line number
     *
     * @return int
     */
    public function getLine();

    /**
     * Gets error description
     *
     * @return string
     */
    public function getDescription();
}

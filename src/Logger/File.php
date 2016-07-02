<?php
namespace PhalconX\Logger;

use Phalcon\Logger\Adapter\File as BaseLogger;
use Psr\Log\LoggerInterface;

class File extends BaseLogger implements LoggerInterface
{
}

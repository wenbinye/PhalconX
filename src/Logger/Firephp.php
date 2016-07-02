<?php
namespace PhalconX\Logger;

use Phalcon\Logger\Adapter\Firephp as BaseLogger;
use Psr\Log\LoggerInterface;

class Firephp extends BaseLogger implements LoggerInterface
{
}

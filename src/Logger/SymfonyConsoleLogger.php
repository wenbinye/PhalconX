<?php
namespace PhalconX\Logger;

use PhalconX\Logger;
use Phalcon\Logger\Adapter;
use Phalcon\Logger\AdapterInterface;
use Psr\Log\LoggerInterface;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use InvalidArgumentException;

class SymfonyConsoleLogger extends Adapter implements AdapterInterface, LoggerInterface
{
    const INFO = 'info';
    const ERROR = 'error';

    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var array
     */
    private $verbosityLevelMap = array(
        Logger::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
        Logger::ALERT => OutputInterface::VERBOSITY_NORMAL,
        Logger::CRITICAL => OutputInterface::VERBOSITY_NORMAL,
        Logger::ERROR => OutputInterface::VERBOSITY_NORMAL,
        Logger::WARNING => OutputInterface::VERBOSITY_NORMAL,
        Logger::NOTICE => OutputInterface::VERBOSITY_NORMAL,
        Logger::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
        Logger::DEBUG => OutputInterface::VERBOSITY_DEBUG,
    );
    /**
     * @var array
     */
    private $formatLevelMap = array(
        Logger::EMERGENCY => self::ERROR,
        Logger::ALERT => self::ERROR,
        Logger::CRITICAL => self::ERROR,
        Logger::ERROR => self::ERROR,
        Logger::WARNING => self::INFO,
        Logger::NOTICE => self::INFO,
        Logger::INFO => self::INFO,
        Logger::DEBUG => self::INFO,
    );

    public function __construct(OutputInterface $output, array $verbosityLevelMap = array(), array $formatLevelMap = array())
    {
        $this->output = $output;
        $this->verbosityLevelMap = $verbosityLevelMap + $this->verbosityLevelMap;
        $this->formatLevelMap = $formatLevelMap + $this->formatLevelMap;
    }

    public function getFormatter()
    {
        if ($this->_formatter === null) {
            $formatter = new LineFormatter();
            $formatter->setDateFormat('y-m-d H:i:s');
            $formatter->setFormat('%date% [%type%] %message%');
            $this->_formatter = $formatter;
        }
        return $this->_formatter;
    }

    public function close()
    {
        return true;
    }

    public function logInternal($message, $level, $time, array $context)
    {
        if (!isset($this->verbosityLevelMap[$level])) {
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
        }

        // Write to the error output if necessary and available
        if ($this->formatLevelMap[$level] === self::ERROR && $this->output instanceof ConsoleOutputInterface) {
            $output = $this->output->getErrorOutput();
        } else {
            $output = $this->output;
        }

        if ($output->getVerbosity() >= $this->verbosityLevelMap[$level]) {
            $output->writeln(sprintf(
                '<%1$s>%2$s</%1$s>',
                $this->formatLevelMap[$level],
                trim($this->getFormatter()->format($message, $level, $time, $context))
            ));
        }
    }
}

<?php
namespace PhalconX\Cli;

use PhalconX\Util;

class Getopt
{
    private $options;
    private $operands;
    private $values;
    
    public function __construct($options)
    {
        $this->options = $options;
    }

    public function parse($arguments = null)
    {
        $this->values = [];
        $it = Util::iterator($arguments);
        while ($it->valid()) {
            $arg = $it->current();
            if ($arg == '--' || mb_substr($arg, 0, 1) != '-') {
                if ($arg == '--') {
                    $it->next();
                }
                break;
            }
            $this->matchOption($it, $arg);
        }
            
        $operands = [];
        while ($it->valid()) {
            $operands[] = $it->current();
            $it->next();
        }
        $this->operands = $operands;
    }

    public function getOperands()
    {
        return $this->operands;
    }

    public function getOptions()
    {
        return $this->options;
    }
    
    private function getOption($option, $isLong)
    {
        foreach ($this->options as $opt) {
            if (($isLong && $opt->long == $option)
                || $opt->short == $option) {
                return $opt;
            }
        }
    }

    private function matchOption($it, $arg)
    {
        if (mb_substr($arg, 0, 2) == '--') {
            $this->matchLongOption($it, $arg);
        } else {
            $this->matchShortOption($it, $arg);
        }
        $it->next();
    }

    private function matchLongOption($it, $arg)
    {
        // long option
        $option = mb_substr($arg, 2);
        if (strpos($option, '=') === false) {
            $this->addOptionValue($option, $it, true);
        } else {
            list($option, $value) = explode('=', $option, 2);
            $this->addOption($option, $value, true);
        }
    }
    
    private function matchShortOption($it, $arg)
    {
        // short option
        $option = mb_substr($arg, 1);
        if (mb_strlen($option) > 1) {
            // multiple options strung together
            $options = $this->splitString($option, 1);
            foreach ($options as $j => $ch) {
                $opt = $this->getOption($ch, false);
                if ($j < count($options) - 1) {
                    if (!$opt->optional) {
                        throw new \UnexpectedValueException("Option $ch in '$arg' should be optional");
                    }
                    $this->addOption($opt, null, false);
                } else {
                    $this->addOptionValue($ch, $it, false);
                }
            }
        } else {
            $this->addOptionValue($option, $it, false);
        }
    }
    
    /**
     * Add an option to the list of known options.
     *
     * @param string $option the option's name
     * @param string $value the option's value (or null)
     * @param boolean $is_long whether the option name is long or short
     *
     * @throws \UnexpectedValueException
     * @return void
     * @internal
     */
    private function addOption($option, $value, $isLong)
    {
        if (is_string($option)) {
            $opt = $this->getOption($option, $isLong);
        } else {
            $opt = $option;
        }
        if ($opt) {
            // for no-argument option, check if they are duplicate
            if ($opt->optional) {
                $value = isset($opt->value) ? $opt->value + 1 : 1;
            }
            $opt->value = $value;
        } else {
            throw new \UnexpectedValueException("Option '$option' is unknown");
        }
    }

    private function addOptionValue($option, $it, $isLong)
    {
        if (is_string($option)) {
            $opt = $this->getOption($option, $isLong);
        } else {
            $opt = $option;
        }
        if (!$opt) {
            throw new \UnexpectedValueException("Option '$option' is unknown");
        }
        if ($opt->optional) {
            $value = null;
        } else {
            $it->next();
            if ($it->valid()) {
                $value = $it->current();
            } else {
                throw new \UnexpectedValueException("Option '$option' need argument");
            }
        }
        $this->addOption($opt, $value, $isLong);
    }

    /**
     * @param string $str string to split
     * @param int $l
     *
     * @return array
     * @internal
     */
    private function splitString($str, $l = 0)
    {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }

            return $ret;
        }

        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }
}

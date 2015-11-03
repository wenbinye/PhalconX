<?php
namespace PhalconX\Validation\Validators;

class Datetime extends Validator
{
    private static $DEFAULT_PATTERN = 'Y-m-d H:i:s';
    
    protected $defaultMessageName = "NotDatetime";

    protected function getMessageVars()
    {
        return [':pattern' => $this->getOption(':pattern', self::$DEFAULT_PATTERN)];
    }
    
    protected function check($value, $validation)
    {
        $pattern = $this->getOption('pattern', self::$DEFAULT_PATTERN);
        $dt = \DateTime::createFromFormat($pattern, $value);
        $lastErrors = \DateTime::getLastErrors();
        return $dt !== false && $lastErrors['warning_count'] == 0;
    }
}

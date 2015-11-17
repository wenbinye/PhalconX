<?php
namespace PhalconX\Php;

use PhalconX\Test\TestCase;
use PhalconX\Php\Lint\Reporters\TextReporter;

/**
 * TestCase for PhpLint
 */
class LintTest extends TestCase
{
    /**
     * @dataProvider passedScripts
     */
    public function testOk($case)
    {
        $reporter = $this->lint('pass/'.$case);
        // print_r($report->getErrors());
        $this->assertTrue(empty($reporter->getErrors()));
    }

    /**
     * @dataProvider failedScripts
     */
    public function testLint($case, $error)
    {
        $report = (string) $this->lint('fail/'.$case);
        $this->assertStringStartsWith('Fatal error: ' . $error, $report);
    }

    public function passedScripts()
    {
        return [
            ['new-self'],
            ['new-static'],
            ['extends-full'],
            ['extends-imported'],
            ['class-annotation-imported'],
            ['funcall'],
            ['funcall-variable'],
            ['use-namespace'],
            ['use-function']
        ];
    }

    public function failedScripts()
    {
        return [
            ['syntax-error', 'Syntax error'],
            ['use-conflict', 'The import Phalcon\Config'],
            ['extends', 'The class Bar'],
            ['implements', 'The class Bar'],
            ['class-annotation', 'The class Command'],
            ['method-annotation', 'The class Command'],
            ['property-annotation', 'The class Command'],
            ['method-parameter', 'The class Bar'],
            ['new-class', 'The class Bar'],
            ['class-funcall', 'The class Bar'],
            ['instanceof', 'The class Bar'],
            ['try-catch', 'The class Exception'],
        ];
    }

    private function lint($case)
    {
        $file = $this->getDatasetFile('lint/'. $case. '.php');
        return (new Lint($file, new TextReporter))
            ->lint()
            ->getReporter();
    }
}

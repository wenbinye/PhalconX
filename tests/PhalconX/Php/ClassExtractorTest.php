<?php
namespace PhalconX\Php;

use PhalconX\Test\TestCase;

/**
 * TestCase for ClassExtractor
 */
class ClassExtractorTest extends TestCase
{
    /**
     * @dataProvider files
     */
    public function testExtract($file, $classes, $interfaces)
    {
        $extractor = new ClassExtractor($this->getDatasetFile("auto-use/$file.php"));
        $this->assertArraySubset($classes, $extractor->getClasses());
        $this->assertArraySubset($interfaces, $extractor->getInterfaces());
        // var_export([$extractor->getClasses(), $extractor->getInterfaces()]);
    }

    public function files()
    {
        return [
            [
                'ClassA',
                [
                    'PhalconX\ClassA' => [
                        'implements' => ['PhalconX\InterfaceA']
                    ]
                ],
                []
            ],
            [
                'InterfaceC',
                [],
                [
                    'PhalconX\InterfaceC' => [
                        'extends' => [
                            'PhalconX\InterfaceA', 'PhalconX\InterfaceB'
                        ]
                    ]
                ]
            ]
        ];
    }
}

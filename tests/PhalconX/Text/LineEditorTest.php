<?php
namespace PhalconX\Text;

use PhalconX\Test\TestCase;

/**
 * TestCase for Buffer
 */
class LineEditorTest extends TestCase
{
    public function testToString()
    {
        $buffer = new LineEditor(implode("\n", range(1, 10)));
        $ret[] = (string) $buffer;
        $buffer->delete(3, 1);
        $ret[] = (string) $buffer;
        $buffer->insert(2, 'hello');
        $buffer->insert(2, 'world');
        $ret[] = (string) $buffer;
        $buffer->replace(5, 2, "hello world\nworld");
        $ret[] = (string) $buffer;

        $this->assertEquals($ret, [
            "1\n2\n3\n4\n5\n6\n7\n8\n9\n10",
            "1\n2\n4\n5\n6\n7\n8\n9\n10",
            "1\nhello\nworld\n2\n4\n5\n6\n7\n8\n9\n10",
            "1\nhello\nworld\n2\n4\nhello world\nworld\n7\n8\n9\n10"
        ]);
        // var_export($ret);
    }
}

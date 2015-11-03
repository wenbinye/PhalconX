<?php
namespace PhalconX\Forms\Annotations;

use PhalconX\Test\TestCase;

use PhalconX\Validation\Form;
use PhalconX\Forms\Annotations\Input;
use PhalconX\Forms\Annotations\Date;
use PhalconX\Forms\Annotations\Email;
use PhalconX\Forms\Annotations\File;
use PhalconX\Forms\Annotations\Hidden;
use PhalconX\Forms\Annotations\Numeric;
use PhalconX\Forms\Annotations\Password;
use PhalconX\Forms\Annotations\Radio;
use PhalconX\Forms\Annotations\Check;
use PhalconX\Forms\Annotations\Submit;
use PhalconX\Forms\Annotations\Text;
use PhalconX\Forms\Annotations\TextArea;
use Phalcon\Forms\Element\Date as DateElement;
use Phalcon\Forms\Element\Email as EmailElement;
use Phalcon\Forms\Element\File as FileElement;
use Phalcon\Forms\Element\Hidden as HiddenElement;
use Phalcon\Forms\Element\Numeric as NumericElement;
use Phalcon\Forms\Element\Password as PasswordElement;
use Phalcon\Forms\Element\Radio as RadioElement;
use Phalcon\Forms\Element\Check as CheckElement;
use Phalcon\Forms\Element\Submit as SubmitElement;
use Phalcon\Forms\Element\Text as TextElement;
use Phalcon\Forms\Element\TextArea as TextAreaElement;
use PhalconX\Forms\Element\Input as InputElement;

/**
 * TestCase for Date
 */
class InputTest extends TestCase
{
    private $form;

    public function setUp()
    {
        $this->form = new Form;
    }
    
    /**
     * @dataProvider inputs
     */
    public function testInput($annotationClass, $elementClass)
    {
        $annotation = new $annotationClass([], null);
        $element = $annotation->getElement($this->form);
        $this->assertEquals(get_class($element), $elementClass);
    }

    public function inputs()
    {
        return [
            [Input::class, InputElement::class],
            [Date::class, DateElement::class],
            [Email::class, EmailElement::class],
            [File::class, FileElement::class],
            [Hidden::class, HiddenElement::class],
            [Numeric::class, NumericElement::class],
            [Password::class, PasswordElement::class],
            [Radio::class, RadioElement::class],
            [Check::class, CheckElement::class],
            [Submit::class, SubmitElement::class],
            [Text::class, TextElement::class],
            [TextArea::class, TextAreaElement::class],
        ];
    }
}

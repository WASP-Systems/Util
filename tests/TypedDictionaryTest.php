<?php
/*
This is part of Wedeto, The WEb DEvelopment TOolkit.
It is published under the MIT Open Source License.

Copyright 2017, Egbert van der Wal

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace Wedeto\Util;

use PHPUnit\Framework\TestCase;

use DateTimeImmutable;
use DateInterval;

use Wedeto\Util\Validation\Type;
use Wedeto\Util\Validation\Validator;

/**
 * @covers Wedeto\Util\TypedDictionary
 */
final class TypedDictionaryTest extends TestCase
{
    public function testBasics()
    {
        $a = new Dictionary;
        $a['float'] = new Type(Type::FLOAT);
        $a['date'] = new Type(Type::DATE);
        $a['email'] = new Type(Type::VALIDATE_FILTER, ['filter' => FILTER_VALIDATE_EMAIL]);

        $dict = new TypedDictionary($a);

        $this->assignment($dict, ['float'], 3, true);
        $this->assignment($dict, ['float'], 3.14, true);
        $this->assignment($dict, ['float'], "3.14", false);

        $this->assignment($dict, ['date'], "2017-01-01", false);
        $this->assignment($dict, ['date'], new \DateTime(), true);
        $this->assignment($dict, ['date'], new \DateTimeImmutable(), true);
    }

    public function testNestedValues()
    {
        $a = new Dictionary;
        $a->set('l1', 'float', new Type(Type::FLOAT));
        $a->set('l2', 'date', new Type(Type::DATE));
        $a->set('l3', 'email', new Type(Type::VALIDATE_FILTER, ['filter' => FILTER_VALIDATE_EMAIL]));

        $dict = new TypedDictionary($a);

        $this->assignment($dict, ['l1', 'float'], 3, true);
        $this->assignment($dict, ['l1', 'float'], 3.14, true);
        $this->assignment($dict, ['l1', 'float'], "3.14", false);

        $this->assignment($dict, ['l2', 'date'], "2017-01-01", false);
        $this->assignment($dict, ['l2', 'date'], new \DateTime(), true);
        $this->assignment($dict, ['l2', 'date'], new \DateTimeImmutable(), true);

        $this->assignment($dict, ['l3', 'email'], "info@example.com", true);
        $this->assignment($dict, ['l3', 'email'], "info@example", false);
        $this->assignment($dict, ['l3', 'email'], "foobar", false);

        // Attempt to overcome this by setting l3
        $this->assignment($dict, ['l3'], ['email' => 'foobar'], false);
    }

    public function testGettingAndSetting()
    {
        $a = new Dictionary;
        $a->set('l1', 'float', new Type(Type::FLOAT));
        $a->set('l2', 'date', new Type(Type::DATE));
        $a->set('l3', 'email', new Type(Type::VALIDATE_FILTER, ['filter' => FILTER_VALIDATE_EMAIL]));

        $dict = new TypedDictionary($a);

        $dt = new \DateTime();
        $dict->set('l1', 'float', 3.14);
        $dict->set('l2', 'date', $dt);
        $dict->set('l3', 'email', 'info@example.com');

        $this->assertEquals(3.14, $dict->get('l1', 'float'));
        $this->assertEquals($dt, $dict->get('l2', 'date'));
        $this->assertEquals('info@example.com', $dict->get('l3', 'email'));

        // Attempt to modify directly
        $ref = &$dict->get('l1', 'float');
        $ref = 6.28;
        $this->assertEquals(3.14, $dict->get('l1', 'float'));

        // Attempt to modify through subdict
        $subdict = $dict->get('l1');
        $this->assertInstanceOf(TypedDictionary::class, $subdict);

        // Check that changing works when proper type
        $this->assignment($subdict, ['float'], 6.28, true);
        $this->assignment($subdict, ['float'], '6.28', false);
    }

    public function testConstructAndInitialize()
    {
        $a = new Dictionary;
        $a->set('l1', 'float', new Type(Type::FLOAT));
        $a->set('l2', 'date', new Type(Type::DATE));
        $a->set('l3', 'email', new Type(Type::VALIDATE_FILTER, ['filter' => FILTER_VALIDATE_EMAIL]));

        $dict = new TypedDictionary($a, ['l1' => ['float' => 4.5], 'l3' => ['email' => 'foo@bar.com']]);
        $this->assertEquals(4.5, $dict->get('l1', 'float'));
        $this->assertEquals('foo@bar.com', $dict->get('l3', 'email'));

        $dict = new TypedDictionary(['fl' => new Type(Type::FLOAT)], ['fl' => 9.9]);
        $this->assertEquals(9.9, $dict->get('fl'));

        $dict = new TypedDictionary($a, new Dictionary(['l1' => ['float' => 4.5], 'l3' => ['email' => 'foo@bar.com']]));
        $this->assertEquals(4.5, $dict->get('l1', 'float'));
        $this->assertEquals('foo@bar.com', $dict->get('l3', 'email'));
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field required");
        $dict = new TypedDictionary($a, ['l1' => ['float' => null], 'l3' => ['email' => 'foo@bar.com']]);
    }

    public function testConstructWithInvalidType()
    {
        $a = new Dictionary;
        $a->set('l1', 'float', 'bar');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown type: bar");
        $dict = new TypedDictionary($a);
    }

    public function testConstructWithInvalidType2()
    {
        $a = new Dictionary;
        $a->set('l1', 'float', null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown type: NULL");
        $dict = new TypedDictionary($a);
    }
    
    public function testWithUnknownKey()
    {
        $a = new Dictionary;
        $dict = new TypedDictionary($a);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Undefined key: foo.bar");
        $dict->set('foo', 'bar', true);
    }

    public function testSettingInvalidSubArray()
    {
        $dict = new TypedDictionary(['l1' => ['float' => Type::FLOAT]]);

        $dict->set('l1', 'float', 1.0);
        $this->assertEquals(1.0, $dict->get('l1', 'float'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be array at: l1');
        $dict->set('l1', 'foo');
    }

    public function testGetWithWrappedArguments()
    {
        $dict = new TypedDictionary(['l1' => ['float' => Type::FLOAT]]);
        $dict->set('l1', 'float', 1.0);

        $this->assertInstanceOf(Dictionary::class, $dict->dget('l1', null));
        $this->assertEquals(1.0, $dict->dget(['l1', 'float'], null));
        $this->assertEquals(5.0, $dict->dget(['l2', 'float', new DefVal(5.0)]));
    }

    public function assignment(TypedDictionary $dict, array $key, $value, bool $should_work)
    {
        $worked = false;
        $key[] = $value;
        try
        {
            $dict->set($key, null);
            $worked = true;
        }
        catch (\InvalidArgumentException $e)
        {
            $this->assertContains("required", $e->getMessage());
        }
        $this->assertEquals($should_work, $worked);
    }

    public function testBlockedFunctions()
    {
        $types = new Dictionary;
        $a = new TypedDictionary($types);

        $this->wrapExpectError($a, "append", "", \RuntimeException::class, "TypedDictionary cannot be used as a stack");
        $this->wrapExpectError($a, "push", "", \RuntimeException::class, "TypedDictionary cannot be used as a stack");
        $this->wrapExpectError($a, "unshift", "", \RuntimeException::class, "TypedDictionary cannot be used as a stack");
        $this->wrapExpectError($a, "shift", "", \RuntimeException::class, "TypedDictionary cannot be used as a stack");
        $this->wrapExpectError($a, "pop", "", \RuntimeException::class, "TypedDictionary cannot be used as a stack");
    }

    public function wrapExpectError($dict, $func, $arg, $class, $msg)
    {
        try
        {
            $dict->$func($arg);
            $this->assertTrue(false, "Error $class was not thrown");
        }
        catch (\Throwable $e)
        {
            $this->assertInstanceOf($class, $e);
            $this->assertContains($msg, $e->getMessage());
        }
    }

    public function testToString()
    {
        $types = ['a' => Type::STRING, 'b' => Type::INT];
        $vals = ['a' => 'foo', 'b' => 3];
        $dict = new TypedDictionary($types, $vals);
        
        $expected = Functions::str($vals) . " (Type: " . Functions::str($types) . ")";

        $this->assertEquals($expected, $dict->__toString());
    }

    public function testSetType()
    {
        $dict = new TypedDictionary([]);

        $dict->setType('foo', Type::STRING);
        $dict->setType('foo2', new Type(Type::STRING));

        $dict->setType('bar', Type::INT);
        $dict->setType('bar2', new Type(Type::INT));

        $this->assignment($dict, ['foo'], 'str', true);
        $this->assignment($dict, ['foo2'], 'str', true);
        $this->assignment($dict, ['foo'], 3.5, false);
        $this->assignment($dict, ['foo2'], 3.5, false);

        $this->assignment($dict, ['bar'], 15, true);
        $this->assignment($dict, ['bar2'], 15, true);
        $this->assignment($dict, ['bar'], '15', false);
        $this->assignment($dict, ['bar2'], '15', false);

        // Overwriting with same type should work
        $this->assertInstanceOf(TypedDictionary::class, $dict->setType('bar', Type::INT));

        // Overwriting with different type should throw an exception
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Duplicate key: [bar]");
        $dict->setType('bar', Type::STRING);
    }

    public function testWrap()
    {
        $fh = fopen("php://memory", "rw");
        $arr = [
            'fl' => 3.5,
            'int' => 15,
            'str' => "string",
            'date' => new \DateTime(),
            'stdcl' => new \StdClass(),
            'resource' => $fh,
            'null' => NULL,
            'bool' => true
        ];

        $dict = TypedDictionary::wrap($arr);
        fclose($fh);

        $fh2 = fopen("php://memory", "rw");
        $this->assignment($dict, ['fl'], 4.5, true);
        $this->assignment($dict, ['fl'], 3, true);
        $this->assignment($dict, ['fl'], "str", false);
        $this->assignment($dict, ['fl'], false, false);
        $this->assignment($dict, ['fl'], new \DateTime, false);
        $this->assignment($dict, ['fl'], new \stdClass, false);
        $this->assignment($dict, ['fl'], $fh2, false);
        $this->assignment($dict, ['fl'], null, false);
        $this->assignment($dict, ['int'], 4.5, false);
        $this->assignment($dict, ['int'], 3, true);
        $this->assignment($dict, ['int'], "str", false);
        $this->assignment($dict, ['int'], false, false);
        $this->assignment($dict, ['int'], new \DateTime, false);
        $this->assignment($dict, ['int'], new \stdClass, false);
        $this->assignment($dict, ['int'], $fh2, false);
        $this->assignment($dict, ['int'], null, false);

        $this->assignment($dict, ['str'], 4.5, false);
        $this->assignment($dict, ['str'], 3, false);
        $this->assignment($dict, ['str'], "str", true);
        $this->assignment($dict, ['str'], false, false);
        $this->assignment($dict, ['str'], new \DateTime, false);
        $this->assignment($dict, ['str'], new \stdClass, false);
        $this->assignment($dict, ['str'], $fh2, false);
        $this->assignment($dict, ['str'], null, false);

        $this->assignment($dict, ['date'], 4.5, false);
        $this->assignment($dict, ['date'], 3, false);
        $this->assignment($dict, ['date'], "str", false);
        $this->assignment($dict, ['date'], false, false);
        $this->assignment($dict, ['date'], new \DateTime, true);
        $this->assignment($dict, ['date'], new \stdClass, false);
        $this->assignment($dict, ['date'], $fh2, false);
        $this->assignment($dict, ['date'], null, false);

        $this->assignment($dict, ['stdcl'], 4.5, false);
        $this->assignment($dict, ['stdcl'], 3, false);
        $this->assignment($dict, ['stdcl'], "str", false);
        $this->assignment($dict, ['stdcl'], false, false);
        $this->assignment($dict, ['stdcl'], new \DateTime, false);
        $this->assignment($dict, ['stdcl'], new \stdClass, true);
        $this->assignment($dict, ['stdcl'], $fh2, false);
        $this->assignment($dict, ['stdcl'], null, false);

        $this->assignment($dict, ['resource'], 4.5, false);
        $this->assignment($dict, ['resource'], 3, false);
        $this->assignment($dict, ['resource'], "str", false);
        $this->assignment($dict, ['resource'], false, false);
        $this->assignment($dict, ['resource'], new \DateTime, false);
        $this->assignment($dict, ['resource'], new \stdClass, false);
        $this->assignment($dict, ['resource'], $fh2, true);
        $this->assignment($dict, ['resource'], null, false);

        $this->assignment($dict, ['null'], 4.5, true);
        $this->assignment($dict, ['null'], 3, true);
        $this->assignment($dict, ['null'], "str", true);
        $this->assignment($dict, ['null'], false, true);
        $this->assignment($dict, ['null'], new \DateTime, true);
        $this->assignment($dict, ['null'], new \stdClass, true);
        $this->assignment($dict, ['null'], $fh2, true);
        $this->assignment($dict, ['null'], null, true);

        $this->assignment($dict, ['bool'], 4.5, false);
        $this->assignment($dict, ['bool'], 3, false);
        $this->assignment($dict, ['bool'], "str", false);
        $this->assignment($dict, ['bool'], false, true);
        $this->assignment($dict, ['bool'], new \DateTime, false);
        $this->assignment($dict, ['bool'], new \stdClass, false);
        $this->assignment($dict, ['bool'], $fh2, false);
        fclose($fh2);

        $arr['foo']['bar'] = 3.5;
        unset($arr['resource']); // Closed already
        $dict = TypedDictionary::wrap($arr);
        $this->assignment($dict, ['foo', 'bar'], 4.5, true);
        $this->assignment($dict, ['foo', 'bar'], 3, true);
        $this->assignment($dict, ['foo', 'bar'], "str", false);
        $this->assignment($dict, ['foo', 'bar'], false, false);
        $this->assignment($dict, ['foo', 'bar'], new \DateTime, false);
        $this->assignment($dict, ['foo', 'bar'], new \stdClass, false);
        $this->assignment($dict, ['foo', 'bar'], $fh2, false);
    }

    public function testNonTypedArrayInsideTypedDictionary()
    {
        $dict = new TypedDictionary(
            ['list' => Type::ARRAY, 'test' => new Type(Type::OBJECT, ['instanceof' => 'DateTime'])], 
            ['list' => ['foo' => 'bar'], 'test' => new \DateTime()]
        );

        $this->assignment($dict, ['test'], "loremipsum", false);
        $this->assignment($dict, ['test'], new \DateTime(), true);
        $this->assignment($dict, ['list', 'foo'], 'bar2', true);
        $this->assignment($dict, ['list', 'foo'], new \DateTime(), true);
        $this->assignment($dict, ['list', 'ipsum'], 'lorem', true);
        $this->assignment($dict, ['list', 'ipsum2'], true, true);
    }
}

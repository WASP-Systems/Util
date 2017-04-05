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

use Wedeto\Util\Functions as WF;

/**
 * @covers Wedeto\Util\Functions
 */
final class FunctionsTest extends TestCase
{
    /**
     * @covers Wedeto\Util\Functions::is_int_val
     */
    public function testIsInt()
    {
        $this->assertTrue(WF::is_int_val(1));
        $this->assertTrue(WF::is_int_val("1"));
        $this->assertTrue(WF::is_int_val("5"));

        $this->assertFalse(WF::is_int_val("5.0"));
        $this->assertFalse(WF::is_int_val(" 5"));
        $this->assertFalse(WF::is_int_val("5 "));
        $this->assertFalse(WF::is_int_val(true));
    }

    /**
     * @covers Wedeto\Util\Functions::parse_bool
     */
    public function testParseBool()
    {
        $this->assertTrue(WF::parse_bool('true'));
        $this->assertTrue(WF::parse_bool('yes'));
        $this->assertTrue(WF::parse_bool('positive'));
        $this->assertTrue(WF::parse_bool('on'));
        $this->assertTrue(WF::parse_bool('enabled'));
        $this->assertTrue(WF::parse_bool('enable'));
        $this->assertTrue(WF::parse_bool('random_string'));
        $this->assertTrue(WF::parse_bool(1));
        $this->assertTrue(WF::parse_bool(0.1));
        $this->assertTrue(WF::parse_bool("0.1"));
        $this->assertTrue(WF::parse_bool(new DummyBoolA()));
        $this->assertTrue(WF::parse_bool([0]));
        $this->assertTrue(WF::parse_bool(new DummyBoolC()));

        $this->assertFalse(WF::parse_bool('false'));
        $this->assertFalse(WF::parse_bool('no'));
        $this->assertFalse(WF::parse_bool('negative'));
        $this->assertFalse(WF::parse_bool('off'));
        $this->assertFalse(WF::parse_bool('disabled'));
        $this->assertFalse(WF::parse_bool('disable'));
        $this->assertFalse(WF::parse_bool("0.0"));
        $this->assertFalse(WF::parse_bool(0));
        $this->assertFalse(WF::parse_bool(0.1, 0.2));
        $this->assertFalse(WF::parse_bool(new DummyBoolB()));
        $this->assertFalse(WF::parse_bool([]));
    }

    /**
     * @covers Wedeto\Util\Functions::is_array_like
     */
    public function testIsArrayLike()
    {
        $this->assertTrue(WF::is_array_like(array()));
        $this->assertTrue(WF::is_array_like(new Dictionary()));
        $this->assertTrue(WF::is_array_like(new \ArrayObject()));
        $this->assertFalse(WF::is_array_like("string"));
        $this->assertFalse(WF::is_array_like(3.5));;
        $this->assertFalse(WF::is_array_like(null));;
    }

    public function testIsNumericArray()
    {
        $this->assertTrue(WF::is_numeric_array([]));
        $this->assertTrue(WF::is_numeric_array([1, 2, 3]));
        $this->assertTrue(WF::is_numeric_array([1 => 1, 5 => 2, 15 => 3]));
        $this->assertTrue(WF::is_numeric_array(["1" => 1, "5" => 2, 15 => 3]));

        $this->assertFalse(WF::is_numeric_array(["1" => 1, "5" => 2, "a" => 3]));

        $this->assertFalse(WF::is_numeric_array(new \StdClass));
    }

    public function testFlattenArray()
    {
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], WF::flatten_array([[1, 2, [3, 4, [5, 6]], 7], 8, 9]));
        $this->assertEquals([1, 2, 3, 4, ['a' => 5, 'b' => 6], 7, 8, 9], WF::flatten_array([[1, 2, [3, 4, ['a' => 5, 'b' => 6]], 7], 8, 9]));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Not an array");
        WF::flatten_array(new \StdClass);
    }

    /**
     * @covers Wedeto\Util\Functions::to_array
     */
    public function testToArray()
    {
        $arr = array(1, 2, 'a' => true);
        $dict = new Dictionary($arr);
        $arr_object = new \ArrayObject($arr);

        $this->assertEquals($arr, WF::to_array($arr));
        $this->assertEquals($arr, WF::to_array($dict));
        $this->assertEquals($arr, WF::to_array($arr_object));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot convert argument to array');
        WF::to_array("string");
    }

    /**
     * @covers Wedeto\Util\Functions::cast_array
     */
    public function testCastArray()
    {
        $arr = array(1, 2, 'a' => true);
        $str = "string";
        $integer = 3;
        $floating = 6.4;
        $dict = new Dictionary($arr);

        $this->assertEquals($arr, WF::cast_array($arr));
        $this->assertEquals([$str], WF::cast_array($str));
        $this->assertEquals([$integer], WF::cast_array($integer));
        $this->assertEquals([$floating], WF::cast_array($floating));
        $this->assertEquals($arr, WF::cast_array($dict));
    }


    /**
     * @covers Wedeto\Util\Functions::check_extension
     */
    public function testCheckExtensionClass()
    {
        $this->expectException(\RuntimeException::class);
        WF::check_extension('non_existing_extension', 'non_existing_namespace\\non_existing_class');
    }

    /**
     * @covers Wedeto\Util\Functions::check_extension
     */
    public function testCheckExtensionFunction()
    {
        $this->expectException(\RuntimeException::class);
        WF::check_extension('non_existing_extension', null, 'non_existing_namespace\\non_existing_function');
    }

    /**
     * @covers Wedeto\Util\Functions::check_extension
     */
    public function testCheckExtensionExists()
    {
        $exception = false;
        try
        {
            WF::check_extension('PHP', null, 'substr');
            WF::check_extension('PHP', 'ArrayObject');
        }
        catch (\Throwable $e)
        {
            $exception = true;
        }
        $this->assertFalse($exception);
    }

    public function testStr()
    {
        $this->assertEquals('TRUE', WF::str(true));
        $this->assertEquals('FALSE', WF::str(FALSE));
        $this->assertEquals('NULL', WF::str(null));
        $this->assertEquals('foo', WF::str("foo"));
        $this->assertEquals('3', WF::str(3));
        $this->assertEquals('3.5', WF::str(3.5));
        $this->assertEquals('off', WF::str(new DummyBoolB));

        $this->assertEquals('[1, 2, 3]', WF::str([1, 2, 3]));
        $this->assertEquals('[1, 2, [3, 4]]', WF::str([1, 2, [3, 4]]));
        $this->assertEquals('[1, 2, [3, [...]]]', WF::str([1, 2, [3, [4, 5]]]));

        $this->assertEquals('[\'a\' => 1, 2, 3]', WF::str(['a' => 1, 2, 3]));

        $a = new \Exception("foo");
        $expected = WF::exceptionToString($a);
        $this->assertEquals($expected, WF::str($a));
        $expected = nl2br($expected);
        $expected = str_replace('  ', '&nbsp;&nbsp;', $expected);
        $this->assertEquals($expected, WF::str($a, true));
        $this->assertEquals($expected, WF::html($a));

        $actual = WF::str(new \StdClass);
        $expected = "class stdClass#";
        $this->assertTrue(strpos($actual, $expected) !== false);
    }

    public function testExceptionToString()
    {
        $ex1 = new \Exception("Foo1", 1);
        $ex2 = new \Exception("Foo2", 1, $ex1);
        $ex3 = new \Exception("Foo3", 1, $ex2);
        $ex4 = new \Exception("Foo4", 1, $ex3);
        $ex5 = new \Exception("Foo5", 1, $ex4);
        $ex6 = new \Exception("Foo6", 1, $ex5);

        $actual = WF::exceptionToString($ex6);
        $expected = "** Recursion limit reached at Exception of class Exception **";

        $this->assertTrue(strpos($actual, $expected) !== false);
    }
}

class DummyBoolA
{
    public function to_bool()
    {
        return true;
    }
}

class DummyBoolB
{
    public function __tostring()
    {
        return "off";
    }
}

class DummyBoolC
{}
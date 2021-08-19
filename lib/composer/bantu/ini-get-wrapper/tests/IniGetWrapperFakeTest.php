<?php

/*
* (c) Andreas Fischer <git@andreasfischer.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace bantu\IniGetWrapper;

class IniGetWrapperFakeTest extends \PHPUnit_Framework_TestCase
{
    protected $wrapper;

    public function setUp()
    {
        $this->wrapper = new IniGetWrapperFake;
    }

    public function testGetFalseToNull()
    {
        $this->assertNull($this->wrapper->get(false));
    }

    /**
    * @dataProvider getRawnessData
    */
    public function testGetRawness($value)
    {
        $this->assertSame($value, $this->wrapper->get($value));
    }

    public function getRawnessData()
    {
        return array(
            array('ON'),
            array('on'),
            array('1'),
            array('OFF'),
            array('off'),
            array('0'),
            array(''),
            array(' php.ini '),
            array('256M'),
        );
    }

    /**
    * @dataProvider getStringData
    */
    public function testGetString($expected, $value)
    {
        $this->assertSame($expected, $this->wrapper->getString($value));
    }

    public function getStringData()
    {
        return array(
            array('ON', 'ON'),
            array('on', 'on'),
            array('1', '1'),
            array('OFF', 'OFF'),
            array('off', 'off'),
            array('0', '0'),
            array('', ''),
            array('php.ini', ' php.ini '),
            array('256M', '256M'),
        );
    }

    /**
    * @dataProvider getBoolTrueData
    */
    public function testGetBoolTrue($value)
    {
        $this->assertTrue($this->wrapper->getBool($value));
    }

    public function getBoolTrueData()
    {
        return array(
            array('ON'),
            array('On'),
            array('on'),
            array('1'),
            array('foo'),
            array('bar'),
        );
    }

    /**
    * @dataProvider getBoolFalseData
    */
    public function testGetBoolFalse($value)
    {
        $this->assertFalse($this->wrapper->getBool($value));
    }

    public function getBoolFalseData()
    {
        return array(
            array('OFF'),
            array('Off'),
            array('off'),
            array('0'),
            array(''),
        );
    }

    /**
    * @dataProvider getNumericData
    */
    public function testGetNumeric($expected, $value)
    {
        $this->assertSame($expected, $this->wrapper->getNumeric($value));
    }

    public function getNumericData()
    {
        return array(
            array(1234, '1234'),
            array(-12345, '-12345'),
            array(1234.0, '1234.0'),
            array(-12345.0, '-12345.0'),
            array(null, 'someString'),
        );
    }

    /**
    * @dataProvider getBytesInvalidData
    */
    public function testGetBytesInvalid($value)
    {
        $this->assertNull($this->wrapper->getBytes($value));
    }

    public function getBytesInvalidData()
    {
        return array(
            array('somestring'),
            array('foo'),
            array('k'),
            array('-k'),
            array('M'),
            array('-M'),
        );
    }

    /**
    * @dataProvider getBytesValidData
    */
    public function testGetBytesValid($expected, $value)
    {
        $this->assertSame($expected, $this->wrapper->getBytes($value));
    }

    public function getBytesValidData()
    {
        return array(
            array(32 * pow(2, 20),   '32m'),
            array(- 32 * pow(2, 20), '-32m'),
            array(8 * pow(2, 30),    '8G'),
            array(- 8 * pow(2, 30),  '-8G'),
            array(1234,              '1234'),
            array(-12345,            '-12345'),
        );
    }

    /**
    * @dataProvider getListData
    */
    public function testGetList($expected, $value)
    {
        $this->assertSame($expected, $this->wrapper->getList($value));
    }

    public function getListData()
    {
        return array(
            array(
                array('pcntl_alarm', 'pcntl_fork', 'pcntl_waitpid'),
                'pcntl_alarm,pcntl_fork,pcntl_waitpid',
            ),
        );
    }

    /**
    * @dataProvider listContainsDataTrue
    */
    public function testListContainsTrue($value, $needle)
    {
        $this->assertTrue($this->wrapper->listContains($value, $needle));
    }

    public function listContainsDataTrue()
    {
        return array(
            array(
                'pcntl_alarm,pcntl_fork,pcntl_waitpid',
                'pcntl_alarm',
            ),
            array(
                'pcntl_alarm,pcntl_fork,pcntl_waitpid',
                'pcntl_fork',
            ),
            array(
                'pcntl_alarm,pcntl_fork,pcntl_waitpid',
                'pcntl_waitpid',
            ),
        );
    }

    /**
    * @dataProvider listContainsDataFalse
    */
    public function testListContainsFalse($value, $needle)
    {
        $this->assertFalse($this->wrapper->listContains($value, $needle));
    }

    public function listContainsDataFalse()
    {
        return array(
            array(
                'pcntl_alarm,pcntl_fork,pcntl_waitpid',
                'foo',
            ),
        );
    }
}

<?php

/*
* (c) Andreas Fischer <git@andreasfischer.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace bantu\IniGetWrapper;

class IniGetWrapperTest extends \PHPUnit_Framework_TestCase
{
    protected $wrapper;
    protected $nonExistentVarname = 'xoomaet0booteiqu1ei8ooH4utaesoon';

    public function setUp()
    {
        $this->wrapper = new IniGetWrapper;
    }

    public function testGetNxNull()
    {
        $this->assertNull($this->wrapper->get($this->nonExistentVarname));
    }

    public function testGetStringNxNull()
    {
        $this->assertNull($this->wrapper->getString($this->nonExistentVarname));
    }

    public function testGetBoolNxNull()
    {
        $this->assertNull($this->wrapper->getBool($this->nonExistentVarname));
    }

    public function testGetNumericNxNull()
    {
        $this->assertNull($this->wrapper->getNumeric($this->nonExistentVarname));
    }

    public function testGetBytesNxNull()
    {
        $this->assertNull($this->wrapper->getBytes($this->nonExistentVarname));
    }

    public function testGetListNxNull()
    {
        $this->assertNull($this->wrapper->getList($this->nonExistentVarname));
    }

    public function testListContainsNxNull()
    {
        $this->assertNull($this->wrapper->listContains($this->nonExistentVarname, 'someneedle'));
    }

    public function testGetBytesIsNumeric()
    {
        $this->assertInternalType('numeric', $this->wrapper->getBytes('memory_limit'));
    }
}

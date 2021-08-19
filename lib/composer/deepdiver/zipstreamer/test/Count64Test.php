<?php
/**
* Copyright (C) 2014 Nicolai Ehemann <en@enlightened.de>
*
* This file is licensed under the GNU GPL version 3 or later. 
* See COPYING for details.
*/

use \ZipStreamer\Count64;

class TestPack extends \PHPUnit\Framework\TestCase
{
  public function providerPack16leValues() {
    # input value, description
    return array(
      array(0,      "packing 0"),
      array(1,      "packing positive integer"),
      array(-1,     "packing negative integer"),
      array(0x0f0f, "packing pattern (0x0f0f)"),
      array(0xf0f0, "packing pattern (0xf0f0)"),
      array(0x00ff, "packing pattern (0x00ff)"),
      array(0xff00, "packing pattern (0xff00)"),
      array(0xffff, "packing maximum 16 bit value (0xffff)")
    );
  }
  
  /**
  * @dataProvider providerPack16leValues
  */
  public function testPack16le($value, $description) {
    $this->assertEquals(ZipStreamer\pack16le($value), pack('v', $value), $description);
  }

  public function providerPack32leValues() {
    # input value, description
    return array(
      array(0,          "packing 0"),
      array(1,          "packing positive integer"),
      array(-1,         "packing negative integer"),
      array(0x0000ffff, "packing pattern (0x0000ffff)"),
      array(0xffff0000, "packing pattern (0xffff0000"),
      array(0x0f0f0f0f, "packing pattern (0x0f0f0f0f)"),
      array(0xf0f0f0f0, "packing pattern (0xf0f0f0f0)"),
      array(0xffffffff, "packing maximum 32 bit value (0xffffffff)")
    );
  }

  /**
  * @dataProvider providerPack32leValues
  */
  public function testPack32le($value, $description) {
    $this->assertEquals(ZipStreamer\pack32le($value), pack('V', $value), $description);
  }

  public function providerPack64leValues() {
    # input value, expected high bytes, expected low bytes, description
    return array(
      array(0,                             0,          0,          "packing 0"),
      array(ZipStreamer\Count64::construct(array(0xffffffff, 0x00000000)), 0xffffffff, 0x00000000, "packing pattern 0x00000000ffffffff"),
      array(ZipStreamer\Count64::construct(array(0x00000000, 0xffffffff)), 0x00000000, 0xffffffff, "packing pattern 0xffffffff00000000"),
      array(ZipStreamer\Count64::construct(array(0x0f0f0f0f, 0x0f0f0f0f)), 0x0f0f0f0f, 0x0f0f0f0f, "packing pattern 0x0f0f0f0f0f0f0f0f"),
      array(ZipStreamer\Count64::construct(array(0xf0f0f0f0, 0xf0f0f0f0)), 0xf0f0f0f0, 0xf0f0f0f0, "packing pattern 0x00f0f0f0f0f0f0f0"),
      array(ZipStreamer\Count64::construct(array(0xffffffff, 0xffffffff)), 0xffffffff, 0xffffffff, "packing maximum 64 bit value (0xffffffffffffffff)")
    );
  }

  /**
  * @dataProvider providerPack64leValues
  */
  public function testPack64le($inVal, $cmpVal1, $cmpVal2, $description) {
    $this->assertEquals(ZipStreamer\pack64le($inVal), pack('VV', $cmpVal1, $cmpVal2), $description);
  }

  public function providerGoodCount64InitializationValues() {
    // expected low bytes, expected high bytes, input value, message
    return array(
      array(0x00000000, 0x00000000, 0, "integer 0"),
      array(0x00000000, 0x00000000, array(0, 0), "integer array(0, 0)"),
      array(0xffffffff, 0xffffffff, array(0xffffffff, 0xffffffff), "bit pattern array(0xffffffff, 0xffffffff)"),
      array(0x00000000, 0xffffffff, array(0x00000000, 0xffffffff), "bit pattern array(0x00000000, 0xffffffff)"),
      array(0xffffffff, 0x00000000, array(0xffffffff, 0x00000000), "bit pattern array(0xffffffff, 0x00000000)"),
      array(0x0f0f0f0f, 0x0f0f0f0f, array(0x0f0f0f0f, 0x0f0f0f0f), "bit pattern array(0x0f0f0f0f, 0x0f0f0f0f)"),
      array(0xf0f0f0f0, 0xf0f0f0f0, array(0xf0f0f0f0, 0xf0f0f0f0), "bit pattern array(0xf0f0f0f0, 0xf0f0f0f0)"),
      array(0x00000000, 0x00000000, ZipStreamer\Count64::construct(0), "Count64Base object (value 0)")
    );
  }

  /**
  * @dataProvider providerGoodCount64InitializationValues
  */
  public function testCount64Construct($loBytes, $hiBytes, $value, $description) {
    $count64 = ZipStreamer\Count64::construct($value);
    $this->assertInstanceOf('ZipStreamer\Count64Base', $count64, $description . ' (instanceof)');
    $this->assertEquals($loBytes, $count64->getLoBytes(), $description . " (loBytes)");
    $this->assertEquals($hiBytes, $count64->getHiBytes(), $description . " (hiBytes)");
  }

  public function providerBadCount64InitializationValues() {
    # input value
    return array(
      array("a"),
      array(0.0),
      array(1.0),
      array(array())
    );
  }

  /**
  * @dataProvider providerBadCount64InitializationValues
  * @expectedException InvalidArgumentException
  */
  public function testCount64ConstructFail($badValue) {
    $count64 = ZipStreamer\Count64::construct($badValue);
  }

  /**
  * @dataProvider providerGoodCount64InitializationValues
  */
  public function testCount64Set($loBytes, $hiBytes, $value, $description) {
    $count64 = ZipStreamer\Count64::construct();
    $count64->set($value);
    $this->assertInstanceOf('ZipStreamer\Count64Base', $count64, $description . ' (instanceof)');
    $this->assertEquals($loBytes, $count64->getLoBytes(), $description . " (loBytes)");
    $this->assertEquals($hiBytes, $count64->getHiBytes(), $description . " (hiBytes)");
  }

  /**
  * @dataProvider providerBadCount64InitializationValues
  * @expectedException InvalidArgumentException
  */
  public function testCount64SetFail($badValue) {
    $count64 = ZipStreamer\Count64::construct();
    $count64->set($badValue);
  }

  /**
  * @dataProvider providerCount64AddValues
  */
  public function providerCount64AddValues() {
    # input start value, input add value, expected low bytes, expected high bytes, description
    return array(
      array(0, 0, 0x00000000, 0x00000000, "adding 0 to 0"),
      array(0, 1, 0x00000001, 0x00000000, "adding 1 to 0"),
      array(1, 0, 0x00000001, 0x00000000, "adding 0 to 1"),
      array(0xffffffff, 1, 0x00000000, 0x00000001, "adding 1 to 0xffffffff"),
      array(0x00000001, 0xffffffff, 0x00000000, 0x00000001, "adding 0xfffffff to 0x00000001"),
      array(0xffffffff, 0xffffffff, 0xfffffffe, 0x00000001, "adding 0xfffffff to 0xffffffff")
    );
  }

  /**
  * @dataProvider providerCount64AddValues
  */
  public function testCount64Add($value, $add, $loBytes, $hiBytes, $description) {
    $count64 = ZipStreamer\Count64::construct($value);
    $count64->add($add);
    $this->assertEquals($loBytes, $count64->getLoBytes(), $description . " (loBytes)".sprintf("%x=%x", $loBytes, $count64->getLoBytes()));
    $this->assertEquals($hiBytes, $count64->getHiBytes(), $description . " (hiBytes)");
  }
}
?>

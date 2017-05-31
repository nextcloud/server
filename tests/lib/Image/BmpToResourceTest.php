<?php

namespace Test\Image;

use OC\Image\BmpToResource;
use Test\TestCase;

class BmpToResourceTest extends TestCase {

	public function test24bitBitmap(){
		$instance = new BmpToResource(__DIR__ . '/../../data/image/24bit2x2.bmp');

		$instance->toResource();
		$header = $instance->getHeader();
		$this->assertEquals(70, $header['filesize']);
		$this->assertEquals(54, $header['offset']);
		$this->assertEquals(2, $header['width']);
		$this->assertEquals(2, $header['height']);
		$this->assertEquals(24, $header['bits']);
		$this->assertEquals(16, $header['imagesize']);
	}

	public function test4bitBitmap(){
		$instance = new BmpToResource(__DIR__ . '/../../data/image/4bit2x2.bmp');

		$instance->toResource();
		$header = $instance->getHeader();
		$this->assertEquals(78, $header['filesize']);
		$this->assertEquals(70, $header['offset']);
		$this->assertEquals(2, $header['width']);
		$this->assertEquals(2, $header['height']);
		$this->assertEquals(4, $header['bits']);
		$this->assertEquals(8, $header['imagesize']);
	}

	public function testReadBitmapHeader(){
		$headerHex = '42 4D 9A 00 00 00 00 00 00 00 7A 00 00 00';
		$headerBin = hex2bin(str_replace(' ', '', $headerHex));
		$stub = $this->getReadFileStub([$headerBin]);
		$bitmapHeader = self::invokePrivate($stub, 'readBitmapHeader');
		$this->assertEquals(154, $bitmapHeader['filesize']);
		$this->assertEquals(122, $bitmapHeader['offset']);
	}

	/**
	 * @expectedException \DomainException
	 */
	public function testReadWrongBitmapSignature(){
		$headerHex = '99 4D 9A 00 00 00 00 00 00 00 7A 00 00 00';
		$headerBin = hex2bin(str_replace(' ', '', $headerHex));
		$stub = $this->getReadFileStub([$headerBin]);
		self::invokePrivate($stub, 'readBitmapHeader');
	}

	public function testReadDibHeader(){
		$headerLengthHex = '6C 00 00 00 ';
		$headerHex = '20 00 00 00 12 00 00 00 01 00 20 00 03 00 00 00 20 00 00 00 14 0B 00 00 13 0B 00 00 00 00 00 00 00 00 00 00';
		$headerLengthBin = hex2bin(str_replace(' ', '', $headerLengthHex));
		$headerBin = hex2bin(str_replace(' ', '', $headerHex));
		$stub = $this->getReadFileStub([$headerLengthBin, $headerBin]);
		$dibHeader = self::invokePrivate($stub, 'readDibHeader');
		$this->assertEquals(32, $dibHeader['width']);
		$this->assertEquals(18, $dibHeader['height']);
		$this->assertEquals(1, $dibHeader['planes']);
		$this->assertEquals(32, $dibHeader['bits']);
		$this->assertEquals(BmpToResource::COMPRESSION_BI_BITFIELDS, $dibHeader['compression']);
		$this->assertEquals(32, $dibHeader['imagesize']);
		$this->assertEquals(2836, $dibHeader['xres']);
		$this->assertEquals(2835, $dibHeader['yres']);
		$this->assertEquals(pow(2, $dibHeader['bits']), $dibHeader['colors']);
		$this->assertEquals(0, $dibHeader['important']);
	}

	/**
	 * @expectedException  \UnexpectedValueException
	 */
	public function testReadUnsupportedDibHeaderBitDepth(){
		$headerLengthHex = '6C 00 00 00 ';
		$headerHex = '20 00 00 00 12 00 00 00 01 00 07 00 03 00 00 00 20 00 00 00 14 0B 00 00 13 0B 00 00 00 00 00 00 00 00 00 00';
		$headerLengthBin = hex2bin(str_replace(' ', '', $headerLengthHex));
		$headerBin = hex2bin(str_replace(' ', '', $headerHex));
		$stub = $this->getReadFileStub([$headerLengthBin, $headerBin]);
		self::invokePrivate($stub, 'readDibHeader');
	}

	/**
	 * @expectedException  \UnexpectedValueException
	 */
	public function testReadWrongDibHeaderLength(){
		$headerHex = '10 00 00 00 00 00 00 00 00 00 7A 00 00 00';
		$headerBin = hex2bin(str_replace(' ', '', $headerHex));
		$stub = $this->getReadFileStub([$headerBin]);
		self::invokePrivate($stub, 'readDibHeader');
	}

	public function testReadBitMasks(){
		$bitMasksHex = '00 00 FF 00 00 FF 00 00 FF 00 00 00';
		$bitMasksBin = hex2bin(str_replace(' ', '', $bitMasksHex));
		$stub = $this->getReadFileStub([$bitMasksBin]);
		$bitMasks = self::invokePrivate($stub, 'readBitMasks');

		$this->assertEquals(0xFF0000, $bitMasks['rMask']);
		$this->assertEquals(0x00FF00, $bitMasks['gMask']);
		$this->assertEquals(0x0000FF, $bitMasks['bMask']);
	}

	public function testReadColorTable(){
		$colors = 2;
		// Little Endian: B  G  R  00 B  G  R  00
		$colorTableHex = '0A FF 32 00 34 56 EF 00';
		$colorTableBin = hex2bin(str_replace(' ', '', $colorTableHex));
		$stub = $this->getReadFileStub([$colorTableBin]);
		$colorTable = self::invokePrivate($stub, 'readColorTable', [$colors]);
		$this->assertEquals(2, count($colorTable));
		$this->assertEquals([0 => 0x32ff0a, 1 => 0xef5634], $colorTable);
	}

	/**
	 * @dataProvider bytesProvider
	 * @param $char
	 * @param $bitsPerPart
	 * @param $expectedArray
	 */
	public function testSplitByteIntoArray($char, $bitsPerPart, $expectedArray){
		$stub = $this->getReadFileStub([]);
		$resultArray = self::invokePrivate($stub, 'splitByteIntoArray', [chr($char), $bitsPerPart]);
		$this->assertEquals($expectedArray, $resultArray);
	}

	public function bytesProvider(){
		return [
			[ 0b1110001, 2, [ '01', '11', '00', '01' ] ],
			[ 0b01101101, 4, [ '0110', '1101' ] ],
		];
	}

	protected function getReadFileStub($values){
		$instanceMock = $this->getMockBuilder(BmpToResource::class)
			->disableOriginalConstructor()
			->setMethods(['readFile', 'getFilename'])
			->getMock()
		;

		$instanceMock->method('readFile')
			->will(
				new \PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls($values)
			)
		;

		$instanceMock->method('getFilename')
			->willReturn(
				'boo.txt'
			)
		;

		return $instanceMock;
	}

}
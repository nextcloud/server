<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC;
use OC\Image;
use OCP\IAppConfig;
use OCP\IConfig;

class ImageTest extends \Test\TestCase {
	public static function tearDownAfterClass(): void {
		@unlink(OC::$SERVERROOT . '/tests/data/testimage2.png');
		@unlink(OC::$SERVERROOT . '/tests/data/testimage2.jpg');

		parent::tearDownAfterClass();
	}

	public function testConstructDestruct(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$this->assertInstanceOf('\OC\Image', $img);
		$this->assertInstanceOf('\OCP\IImage', $img);
		unset($img);

		$imgcreate = imagecreatefromjpeg(OC::$SERVERROOT . '/tests/data/testimage.jpg');
		$img = new Image();
		$img->setResource($imgcreate);
		$this->assertInstanceOf('\OC\Image', $img);
		$this->assertInstanceOf('\OCP\IImage', $img);
		unset($img);

		$base64 = base64_encode(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.gif'));
		$img = new Image();
		$img->loadFromBase64($base64);
		$this->assertInstanceOf('\OC\Image', $img);
		$this->assertInstanceOf('\OCP\IImage', $img);
		unset($img);

		$img = new Image();
		$this->assertInstanceOf('\OC\Image', $img);
		$this->assertInstanceOf('\OCP\IImage', $img);
		unset($img);
	}

	public function testValid(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$this->assertTrue($img->valid());

		$text = base64_encode('Lorem ipsum dolor sir amet …');
		$img = new Image();
		$img->loadFromBase64($text);
		$this->assertFalse($img->valid());

		$img = new Image();
		$this->assertFalse($img->valid());
	}

	public function testMimeType(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$this->assertEquals('image/png', $img->mimeType());

		$img = new Image();
		$this->assertEquals('', $img->mimeType());

		$img = new Image();
		$img->loadFromData(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		$this->assertEquals('image/jpeg', $img->mimeType());

		$img = new Image();
		$img->loadFromBase64(base64_encode(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.gif')));
		$this->assertEquals('image/gif', $img->mimeType());
	}

	public function testWidth(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$this->assertEquals(128, $img->width());

		$img = new Image();
		$img->loadFromData(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		$this->assertEquals(1680, $img->width());

		$img = new Image();
		$img->loadFromBase64(base64_encode(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.gif')));
		$this->assertEquals(64, $img->width());

		$img = new Image();
		$this->assertEquals(-1, $img->width());
	}

	public function testHeight(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$this->assertEquals(128, $img->height());

		$img = new Image();
		$img->loadFromData(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		$this->assertEquals(1050, $img->height());

		$img = new Image();
		$img->loadFromBase64(base64_encode(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.gif')));
		$this->assertEquals(64, $img->height());

		$img = new Image();
		$this->assertEquals(-1, $img->height());
	}

	public function testSave(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$img->resize(16);
		$img->save(OC::$SERVERROOT . '/tests/data/testimage2.png');
		$this->assertEquals(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage2.png'), $img->data());

		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.jpg');
		$img->resize(128);
		$img->save(OC::$SERVERROOT . '/tests/data/testimage2.jpg');
		$this->assertEquals(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage2.jpg'), $img->data());
	}

	public function testData(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$raw = imagecreatefromstring(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.png'));
		// Preserve transparency
		imagealphablending($raw, true);
		imagesavealpha($raw, true);
		ob_start();
		imagepng($raw);
		$expected = ob_get_clean();
		$this->assertEquals($expected, $img->data());

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->expects($this->once())
			->method('getValueInt')
			->with('preview', 'jpeg_quality', 80)
			->willReturn(80);
		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('getSystemValueInt')
			->with('preview_max_memory', 256)
			->willReturn(256);
		$img = new Image(null, $appConfig, $config);
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.jpg');
		$raw = imagecreatefromstring(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		imageinterlace($raw, true);
		ob_start();
		imagejpeg($raw, null, 80);
		$expected = ob_get_clean();
		$this->assertEquals($expected, $img->data());

		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.gif');
		$raw = imagecreatefromstring(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.gif'));
		ob_start();
		imagegif($raw);
		$expected = ob_get_clean();
		$this->assertEquals($expected, $img->data());
	}

	public function testDataNoResource(): void {
		$img = new Image();
		$this->assertNull($img->data());
	}

	/**
	 * @depends testData
	 */
	public function testToString(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$expected = base64_encode($img->data());
		$this->assertEquals($expected, (string)$img);

		$img = new Image();
		$img->loadFromData(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		$expected = base64_encode($img->data());
		$this->assertEquals($expected, (string)$img);

		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.gif');
		$expected = base64_encode($img->data());
		$this->assertEquals($expected, (string)$img);
	}

	public function testResize(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$this->assertTrue($img->resize(32));
		$this->assertEquals(32, $img->width());
		$this->assertEquals(32, $img->height());

		$img = new Image();
		$img->loadFromData(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		$this->assertTrue($img->resize(840));
		$this->assertEquals(840, $img->width());
		$this->assertEquals(525, $img->height());

		$img = new Image();
		$img->loadFromBase64(base64_encode(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.gif')));
		$this->assertTrue($img->resize(100));
		$this->assertEquals(100, $img->width());
		$this->assertEquals(100, $img->height());
	}

	public function testPreciseResize(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$this->assertTrue($img->preciseResize(128, 512));
		$this->assertEquals(128, $img->width());
		$this->assertEquals(512, $img->height());

		$img = new Image();
		$img->loadFromData(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		$this->assertTrue($img->preciseResize(64, 840));
		$this->assertEquals(64, $img->width());
		$this->assertEquals(840, $img->height());

		$img = new Image();
		$img->loadFromBase64(base64_encode(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.gif')));
		$this->assertTrue($img->preciseResize(1000, 1337));
		$this->assertEquals(1000, $img->width());
		$this->assertEquals(1337, $img->height());
	}

	public function testCenterCrop(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$img->centerCrop();
		$this->assertEquals(128, $img->width());
		$this->assertEquals(128, $img->height());

		$img = new Image();
		$img->loadFromData(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		$img->centerCrop();
		$this->assertEquals(1050, $img->width());
		$this->assertEquals(1050, $img->height());

		$img = new Image();
		$img->loadFromBase64(base64_encode(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.gif')));
		$img->centerCrop(512);
		$this->assertEquals(512, $img->width());
		$this->assertEquals(512, $img->height());
	}

	public function testCrop(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$this->assertTrue($img->crop(0, 0, 50, 20));
		$this->assertEquals(50, $img->width());
		$this->assertEquals(20, $img->height());

		$img = new Image();
		$img->loadFromData(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		$this->assertTrue($img->crop(500, 700, 550, 300));
		$this->assertEquals(550, $img->width());
		$this->assertEquals(300, $img->height());

		$img = new Image();
		$img->loadFromBase64(base64_encode(file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.gif')));
		$this->assertTrue($img->crop(10, 10, 15, 15));
		$this->assertEquals(15, $img->width());
		$this->assertEquals(15, $img->height());
	}

	public static function sampleProvider() {
		return [
			['testimage.png', [200, 100], [100, 100]],
			['testimage.jpg', [840, 840], [840, 525]],
			['testimage.gif', [200, 250], [200, 200]]
		];
	}

	/**
	 * @dataProvider sampleProvider
	 *
	 * @param string $filename
	 * @param int[] $asked
	 * @param int[] $expected
	 */
	public function testFitIn($filename, $asked, $expected): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/' . $filename);
		$this->assertTrue($img->fitIn($asked[0], $asked[1]));
		$this->assertEquals($expected[0], $img->width());
		$this->assertEquals($expected[1], $img->height());
	}

	public static function sampleFilenamesProvider() {
		return [
			['testimage.png'],
			['testimage.jpg'],
			['testimage.gif']
		];
	}

	/**
	 * Image should not be resized if it's already smaller than what is required
	 *
	 * @dataProvider sampleFilenamesProvider
	 *
	 * @param string $filename
	 */
	public function testScaleDownToFitWhenSmallerAlready($filename): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/' . $filename);
		$currentWidth = $img->width();
		$currentHeight = $img->height();
		// We pick something larger than the image we want to scale down
		$this->assertFalse($img->scaleDownToFit(4000, 4000));
		// The dimensions of the image should not have changed since it's smaller already
		$resizedWidth = $img->width();
		$resizedHeight = $img->height();
		$this->assertEquals(
			$currentWidth, $img->width(), "currentWidth $currentWidth resizedWidth $resizedWidth \n"
		);
		$this->assertEquals(
			$currentHeight, $img->height(),
			"currentHeight $currentHeight resizedHeight $resizedHeight \n"
		);
	}

	public static function largeSampleProvider() {
		return [
			['testimage.png', [200, 100], [100, 100]],
			['testimage.jpg', [840, 840], [840, 525]],
		];
	}

	/**
	 * @dataProvider largeSampleProvider
	 *
	 * @param string $filename
	 * @param int[] $asked
	 * @param int[] $expected
	 */
	public function testScaleDownWhenBigger($filename, $asked, $expected): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/' . $filename);
		//$this->assertTrue($img->scaleDownToFit($asked[0], $asked[1]));
		$img->scaleDownToFit($asked[0], $asked[1]);
		$this->assertEquals($expected[0], $img->width());
		$this->assertEquals($expected[1], $img->height());
	}

	public function convertDataProvider() {
		return [
			[ 'image/gif'],
			[ 'image/jpeg'],
			[ 'image/png'],
		];
	}

	/**
	 * @dataProvider convertDataProvider
	 */
	public function testConvert($mimeType): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.png');
		$tempFile = tempnam(sys_get_temp_dir(), 'img-test');

		$img->save($tempFile, $mimeType);
		$this->assertEquals($mimeType, image_type_to_mime_type(exif_imagetype($tempFile)));
	}

	public function testMemoryLimitFromFile(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage-badheader.jpg');
		$this->assertFalse($img->valid());
	}

	public function testMemoryLimitFromData(): void {
		$data = file_get_contents(OC::$SERVERROOT . '/tests/data/testimage-badheader.jpg');
		$img = new Image();
		$img->loadFromData($data);
		$this->assertFalse($img->valid());
	}
}

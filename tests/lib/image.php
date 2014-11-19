<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Image extends PHPUnit_Framework_TestCase {
	public static function tearDownAfterClass() {
		@unlink(OC::$SERVERROOT.'/tests/data/testimage2.png');
		@unlink(OC::$SERVERROOT.'/tests/data/testimage2.jpg');
	}

	public function testGetMimeTypeForFile() {
		$mimetype = \OC_Image::getMimeTypeForFile(OC::$SERVERROOT.'/tests/data/testimage.png');
		$this->assertEquals('image/png', $mimetype);

		$mimetype = \OC_Image::getMimeTypeForFile(OC::$SERVERROOT.'/tests/data/testimage.jpg');
		$this->assertEquals('image/jpeg', $mimetype);

		$mimetype = \OC_Image::getMimeTypeForFile(OC::$SERVERROOT.'/tests/data/testimage.gif');
		$this->assertEquals('image/gif', $mimetype);

		$mimetype = \OC_Image::getMimeTypeForFile(null);
		$this->assertEquals('', $mimetype);
	}

	public function testConstructDestruct() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$this->assertInstanceOf('\OC_Image', $img);
		unset($img);

		$imgcreate = imagecreatefromjpeg(OC::$SERVERROOT.'/tests/data/testimage.jpg');
		$img = new \OC_Image($imgcreate);
		$this->assertInstanceOf('\OC_Image', $img);
		unset($img);

		$base64 = base64_encode(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.gif'));
		$img = new \OC_Image($base64);
		$this->assertInstanceOf('\OC_Image', $img);
		unset($img);

		$img = new \OC_Image(null);
		$this->assertInstanceOf('\OC_Image', $img);
		unset($img);
	}

	public function testValid() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$this->assertTrue($img->valid());

		$text = base64_encode("Lorem ipsum dolor sir amet …");
		$img = new \OC_Image($text);
		$this->assertFalse($img->valid());

		$img = new \OC_Image(null);
		$this->assertFalse($img->valid());
	}

	public function testMimeType() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$this->assertEquals('image/png', $img->mimeType());

		$img = new \OC_Image(null);
		$this->assertEquals('', $img->mimeType());

		if (\OC_Util::runningOnWindows()) {
			$this->markTestSkipped('[Windows] Images created with imagecreate() are pngs on windows');
		}

		$img = new \OC_Image(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$this->assertEquals('image/jpeg', $img->mimeType());

		$img = new \OC_Image(base64_encode(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.gif')));
		$this->assertEquals('image/gif', $img->mimeType());
	}

	public function testWidth() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$this->assertEquals(128, $img->width());

		$img = new \OC_Image(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$this->assertEquals(1680, $img->width());

		$img = new \OC_Image(base64_encode(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.gif')));
		$this->assertEquals(64, $img->width());

		$img = new \OC_Image(null);
		$this->assertEquals(-1, $img->width());
	}

	public function testHeight() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$this->assertEquals(128, $img->height());

		$img = new \OC_Image(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$this->assertEquals(1050, $img->height());

		$img = new \OC_Image(base64_encode(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.gif')));
		$this->assertEquals(64, $img->height());

		$img = new \OC_Image(null);
		$this->assertEquals(-1, $img->height());
	}

	public function testSave() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$img->resize(16);
		$img->save(OC::$SERVERROOT.'/tests/data/testimage2.png');
		$this->assertEquals(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage2.png'), $img->data());

		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.jpg');
		$img->resize(128);
		$img->save(OC::$SERVERROOT.'/tests/data/testimage2.jpg');
		$this->assertEquals(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage2.jpg'), $img->data());
	}

	public function testData() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$raw = imagecreatefromstring(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.png'));
		// Preserve transparency
		imagealphablending($raw, true);
		imagesavealpha($raw, true);
		ob_start();
		imagepng($raw);
		$expected = ob_get_clean();
		$this->assertEquals($expected, $img->data());

		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.jpg');
		$raw = imagecreatefromstring(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		ob_start();
		imagejpeg($raw);
		$expected = ob_get_clean();
		$this->assertEquals($expected, $img->data());

		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.gif');
		$raw = imagecreatefromstring(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.gif'));
		ob_start();
		imagegif($raw);
		$expected = ob_get_clean();
		$this->assertEquals($expected, $img->data());
	}

	/**
	 * @depends testData
	 */
	public function testToString() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$expected = base64_encode($img->data());
		$this->assertEquals($expected, (string)$img);

		$img = new \OC_Image(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$expected = base64_encode($img->data());
		$this->assertEquals($expected, (string)$img);

		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.gif');
		$expected = base64_encode($img->data());
		$this->assertEquals($expected, (string)$img);
	}

	public function testResize() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$this->assertTrue($img->resize(32));
		$this->assertEquals(32, $img->width());
		$this->assertEquals(32, $img->height());

		$img = new \OC_Image(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$this->assertTrue($img->resize(840));
		$this->assertEquals(840, $img->width());
		$this->assertEquals(525, $img->height());

		$img = new \OC_Image(base64_encode(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.gif')));
		$this->assertTrue($img->resize(100));
		$this->assertEquals(100, $img->width());
		$this->assertEquals(100, $img->height());
	}

	public function testPreciseResize() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$this->assertTrue($img->preciseResize(128, 512));
		$this->assertEquals(128, $img->width());
		$this->assertEquals(512, $img->height());

		$img = new \OC_Image(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$this->assertTrue($img->preciseResize(64, 840));
		$this->assertEquals(64, $img->width());
		$this->assertEquals(840, $img->height());

		$img = new \OC_Image(base64_encode(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.gif')));
		$this->assertTrue($img->preciseResize(1000, 1337));
		$this->assertEquals(1000, $img->width());
		$this->assertEquals(1337, $img->height());
	}

	public function testCenterCrop() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$img->centerCrop();
		$this->assertEquals(128, $img->width());
		$this->assertEquals(128, $img->height());

		$img = new \OC_Image(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$img->centerCrop();
		$this->assertEquals(1050, $img->width());
		$this->assertEquals(1050, $img->height());

		$img = new \OC_Image(base64_encode(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.gif')));
		$img->centerCrop(512);
		$this->assertEquals(512, $img->width());
		$this->assertEquals(512, $img->height());
	}

	public function testCrop() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$this->assertTrue($img->crop(0, 0, 50, 20));
		$this->assertEquals(50, $img->width());
		$this->assertEquals(20, $img->height());

		$img = new \OC_Image(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$this->assertTrue($img->crop(500, 700, 550, 300));
		$this->assertEquals(550, $img->width());
		$this->assertEquals(300, $img->height());

		$img = new \OC_Image(base64_encode(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.gif')));
		$this->assertTrue($img->crop(10, 10, 15, 15));
		$this->assertEquals(15, $img->width());
		$this->assertEquals(15, $img->height());
	}

	public function testFitIn() {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$this->assertTrue($img->fitIn(200, 100));
		$this->assertEquals(100, $img->width());
		$this->assertEquals(100, $img->height());

		$img = new \OC_Image(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$this->assertTrue($img->fitIn(840, 840));
		$this->assertEquals(840, $img->width());
		$this->assertEquals(525, $img->height());

		$img = new \OC_Image(base64_encode(file_get_contents(OC::$SERVERROOT.'/tests/data/testimage.gif')));
		$this->assertTrue($img->fitIn(200, 250));
		$this->assertEquals(200, $img->width());
		$this->assertEquals(200, $img->height());
	}

	function convertDataProvider() {
		return array(
			array( 'image/gif'),
			array( 'image/jpeg'),
			array( 'image/png'),
		);
	}

	/**
	 * @dataProvider convertDataProvider
	 */
	public function testConvert($mimeType) {
		$img = new \OC_Image(OC::$SERVERROOT.'/tests/data/testimage.png');
		$tempFile = tempnam(sys_get_temp_dir(), 'img-test');

		$img->save($tempFile, $mimeType);
		$actualMimeType = \OC_Image::getMimeTypeForFile($tempFile);
		$this->assertEquals($mimeType, $actualMimeType);
	}
}

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
	#[\Override]
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

	#[\PHPUnit\Framework\Attributes\Depends('testData')]
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

	public static function sampleProvider(): array {
		return [
			['testimage.png', [200, 100], [100, 100]],
			['testimage.jpg', [840, 840], [840, 525]],
			['testimage.gif', [200, 250], [200, 200]]
		];
	}

	/**
	 *
	 * @param string $filename
	 * @param int[] $asked
	 * @param int[] $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('sampleProvider')]
	public function testFitIn($filename, $asked, $expected): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/' . $filename);
		$this->assertTrue($img->fitIn($asked[0], $asked[1]));
		$this->assertEquals($expected[0], $img->width());
		$this->assertEquals($expected[1], $img->height());
	}

	public static function sampleFilenamesProvider(): array {
		return [
			['testimage.png'],
			['testimage.jpg'],
			['testimage.gif']
		];
	}

	/**
	 * Image should not be resized if it's already smaller than what is required
	 *
	 *
	 * @param string $filename
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('sampleFilenamesProvider')]
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

	public static function largeSampleProvider(): array {
		return [
			['testimage.png', [200, 100], [100, 100]],
			['testimage.jpg', [840, 840], [840, 525]],
		];
	}

	/**
	 *
	 * @param string $filename
	 * @param int[] $asked
	 * @param int[] $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('largeSampleProvider')]
	public function testScaleDownWhenBigger($filename, $asked, $expected): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/' . $filename);
		//$this->assertTrue($img->scaleDownToFit($asked[0], $asked[1]));
		$img->scaleDownToFit($asked[0], $asked[1]);
		$this->assertEquals($expected[0], $img->width());
		$this->assertEquals($expected[1], $img->height());
	}

	public static function convertDataProvider(): array {
		return [
			[ 'image/gif'],
			[ 'image/jpeg'],
			[ 'image/png'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('convertDataProvider')]
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

	/**
	 * Returns the reassembled ICC profile from the APP2 segments of a JPEG,
	 * or null if there is none.
	 */
	private static function iccProfileOfJpeg(string $data): ?string {
		$chunks = [];
		$offset = 0;
		while (($pos = strpos($data, "ICC_PROFILE\x00", $offset)) !== false) {
			$length = (ord($data[$pos - 2]) << 8) | ord($data[$pos - 1]);
			$sequence = ord($data[$pos + 12]);
			$chunks[$sequence] = substr($data, $pos + 14, $length - 2 - 12 - 2);
			$offset = $pos + 14;
		}
		if ($chunks === []) {
			return null;
		}
		ksort($chunks);
		return implode('', $chunks);
	}

	/**
	 * Returns the ICC profile from the iCCP chunk of a PNG, or null if there is none.
	 */
	private static function iccProfileOfPng(string $data): ?string {
		$pos = strpos($data, 'iCCP');
		if ($pos === false) {
			return null;
		}
		$length = unpack('N', substr($data, $pos - 4, 4))[1];
		$chunk = substr($data, $pos + 4, $length);
		$separator = strpos($chunk, "\x00");
		return gzuncompress(substr($chunk, $separator + 2)) ?: null;
	}

	public function testIccProfilePreservedInJpeg(): void {
		$source = file_get_contents(OC::$SERVERROOT . '/tests/data/testimage-icc-p3.jpg');
		$sourceProfile = self::iccProfileOfJpeg($source);
		$this->assertNotNull($sourceProfile);

		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage-icc-p3.jpg');
		$this->assertTrue($img->valid());
		$this->assertEquals($sourceProfile, self::iccProfileOfJpeg($img->data()));

		$img = new Image();
		$img->loadFromData($source);
		$this->assertTrue($img->valid());
		$this->assertEquals($sourceProfile, self::iccProfileOfJpeg($img->data()));

		$img = new Image();
		$img->loadFromBase64(base64_encode($source));
		$this->assertTrue($img->valid());
		$this->assertEquals($sourceProfile, self::iccProfileOfJpeg($img->data()));
	}

	public function testIccProfilePreservedAcrossResizeAndCrop(): void {
		$source = file_get_contents(OC::$SERVERROOT . '/tests/data/testimage-icc-p3.jpg');
		$sourceProfile = self::iccProfileOfJpeg($source);

		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage-icc-p3.jpg');
		$img->resize(32);
		$this->assertEquals($sourceProfile, self::iccProfileOfJpeg($img->data()));

		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage-icc-p3.jpg');
		$this->assertEquals($sourceProfile, self::iccProfileOfJpeg($img->resizeCopy(32)->data()));
		$this->assertEquals($sourceProfile, self::iccProfileOfJpeg($img->preciseResizeCopy(32, 16)->data()));
		$this->assertEquals($sourceProfile, self::iccProfileOfJpeg($img->cropCopy(0, 0, 16, 16)->data()));
	}

	public function testIccProfilePreservedInPngOutput(): void {
		$source = file_get_contents(OC::$SERVERROOT . '/tests/data/testimage-icc-p3.jpg');
		$sourceProfile = self::iccProfileOfJpeg($source);

		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage-icc-p3.jpg');
		$tempFile = tempnam(sys_get_temp_dir(), 'img-test');
		$img->save($tempFile, 'image/png');
		$this->assertEquals($sourceProfile, self::iccProfileOfPng(file_get_contents($tempFile)));
		unlink($tempFile);
	}

	public function testIccProfilePreservedFromPngSource(): void {
		$source = file_get_contents(OC::$SERVERROOT . '/tests/data/testimage-icc-p3.png');
		$sourceProfile = self::iccProfileOfPng($source);
		$this->assertNotNull($sourceProfile);

		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage-icc-p3.png');
		$this->assertTrue($img->valid());
		$this->assertEquals($sourceProfile, self::iccProfileOfPng($img->data()));
	}

	public function testIccProfileNotInventedForUntaggedImage(): void {
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage.jpg');
		$this->assertTrue($img->valid());
		$this->assertNull(self::iccProfileOfJpeg($img->data()));
	}

	/**
	 * Returns a structurally valid RGB ICC profile of the given size.
	 */
	private static function rgbIccProfile(int $size): string {
		$header = pack('N', $size) . str_repeat("\x00", 12) . 'RGB XYZ ' . str_repeat("\x00", 12) . 'acsp';
		return str_pad($header, $size, "\x00");
	}

	/**
	 * Inserts the profile as APP2 segments right after the SOI marker of a JPEG.
	 */
	private static function jpegWithIccProfile(string $jpeg, string $profile): string {
		$chunks = str_split($profile, 65519);
		$segments = '';
		foreach ($chunks as $index => $chunk) {
			$payload = "ICC_PROFILE\x00" . chr($index + 1) . chr(count($chunks)) . $chunk;
			$segments .= "\xFF\xE2" . pack('n', strlen($payload) + 2) . $payload;
		}
		return substr($jpeg, 0, 2) . $segments . substr($jpeg, 2);
	}

	public function testOversizedIccProfileNotPreserved(): void {
		$jpeg = file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.jpg');

		$img = new Image();
		$img->loadFromData(self::jpegWithIccProfile($jpeg, self::rgbIccProfile(1024)));
		$this->assertNotNull(self::iccProfileOfJpeg($img->data()));

		$img = new Image();
		$img->loadFromData(self::jpegWithIccProfile($jpeg, self::rgbIccProfile(100 * 1024)));
		$this->assertTrue($img->valid());
		$this->assertNull(self::iccProfileOfJpeg($img->data()));
	}

	public function testIccProfileWithWrongDeclaredSizeNotPreserved(): void {
		$jpeg = file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.jpg');
		$profile = substr_replace(self::rgbIccProfile(1024), pack('N', 2048), 0, 4);

		$img = new Image();
		$img->loadFromData(self::jpegWithIccProfile($jpeg, $profile));
		$this->assertTrue($img->valid());
		$this->assertNull(self::iccProfileOfJpeg($img->data()));
	}

	public function testCompressedIccProfileBombNotInflated(): void {
		ob_start();
		imagepng(imagecreatetruecolor(4, 4));
		$png = ob_get_clean();
		$chunkData = "ICC profile\x00\x00" . gzcompress(str_repeat("\x00", 16 * 1024 * 1024), 9);
		$chunk = pack('N', strlen($chunkData)) . 'iCCP' . $chunkData . pack('N', crc32('iCCP' . $chunkData));
		$png = substr($png, 0, 33) . $chunk . substr($png, 33);

		$memoryBefore = memory_get_usage();
		memory_reset_peak_usage();
		$img = new Image();
		$img->loadFromData($png);
		$this->assertLessThan(4 * 1024 * 1024, memory_get_peak_usage() - $memoryBefore);
		$this->assertTrue($img->valid());
		$this->assertNull(self::iccProfileOfPng($img->data()));
	}

	public function testSrgbIccProfileNotEmbedded(): void {
		$source = file_get_contents(OC::$SERVERROOT . '/tests/data/testimage-icc-srgb.jpg');
		$this->assertNotNull(self::iccProfileOfJpeg($source));

		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage-icc-srgb.jpg');
		$this->assertTrue($img->valid());
		$this->assertNull(self::iccProfileOfJpeg($img->data()));
	}

	/**
	 * Returns a matrix/TRC RGB ICC profile with the given colorants and a pure gamma tone curve.
	 *
	 * @param list<list<float>> $colorants red, green and blue XYZ values
	 */
	private static function matrixIccProfile(array $colorants, float $gamma): string {
		$tags = [];
		foreach (['rXYZ', 'gXYZ', 'bXYZ'] as $index => $signature) {
			$tags[$signature] = 'XYZ ' . str_repeat("\x00", 4)
				. implode('', array_map(fn (float $value) => pack('N', (int)round($value * 65536) & 0xFFFFFFFF), $colorants[$index]));
		}
		foreach (['rTRC', 'gTRC', 'bTRC'] as $signature) {
			$tags[$signature] = 'curv' . str_repeat("\x00", 4) . pack('N', 1) . pack('n', (int)round($gamma * 256)) . "\x00\x00";
		}
		$offset = 132 + 12 * count($tags);
		$table = pack('N', count($tags));
		$data = '';
		foreach ($tags as $signature => $tag) {
			$table .= $signature . pack('NN', $offset + strlen($data), strlen($tag));
			$data .= $tag;
		}
		$size = $offset + strlen($data);
		$header = pack('N', $size) . str_repeat("\x00", 12) . 'RGB XYZ ' . str_repeat("\x00", 12) . 'acsp';
		return str_pad($header, 128, "\x00") . $table . $data;
	}

	public static function dataMatrixIccProfiles(): array {
		$srgb = [[0.4361, 0.2225, 0.0139], [0.3851, 0.7169, 0.0971], [0.1431, 0.0606, 0.7141]];
		$displayP3 = [[0.5151, 0.2412, -0.0011], [0.2920, 0.6922, 0.0419], [0.1571, 0.0666, 0.7841]];
		return [
			'sRGB primaries with gamma 2.2' => [$srgb, 2.2, false],
			'linear sRGB' => [$srgb, 1.0, true],
			'Display P3 primaries' => [$displayP3, 2.2, true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataMatrixIccProfiles')]
	public function testOnlyNonSrgbIccProfilesEmbedded(array $colorants, float $gamma, bool $embedded): void {
		$jpeg = file_get_contents(OC::$SERVERROOT . '/tests/data/testimage.jpg');
		$profile = self::matrixIccProfile($colorants, $gamma);

		$img = new Image();
		$img->loadFromData(self::jpegWithIccProfile($jpeg, $profile));
		$this->assertTrue($img->valid());
		$this->assertSame($embedded ? $profile : null, self::iccProfileOfJpeg($img->data()));
	}

	public function testNonRgbIccProfileNotPreserved(): void {
		// GD converts CMYK pixel data to RGB on load, the source profile no longer applies
		$img = new Image();
		$img->loadFromFile(OC::$SERVERROOT . '/tests/data/testimage-icc-cmyk.jpg');
		$this->assertTrue($img->valid());
		$this->assertNull(self::iccProfileOfJpeg($img->data()));
	}
}

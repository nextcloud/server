<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Tests;

use OC\Files\AppData\AppData;
use OCA\Theming\IconBuilder;
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\App\IAppManager;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class IconBuilderTest extends TestCase {
	protected IConfig&MockObject $config;
	protected AppData&MockObject $appData;
	protected ThemingDefaults&MockObject $themingDefaults;
	protected ImageManager&MockObject $imageManager;
	protected IAppManager&MockObject $appManager;
	protected Util&MockObject $util;
	protected IconBuilder $iconBuilder;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->appData = $this->createMock(AppData::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->util = $this->createMock(Util::class);
		$this->iconBuilder = new IconBuilder($this->themingDefaults, $this->util, $this->imageManager);
	}

	/**
	 * Checks if Imagick and the required format are available.
	 * If provider is null, only checks for Imagick extension.
	 */
	private function checkImagick(?string $provider = null) {
		if (!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagemagick is required for dynamic icon generation.');
		}
		if ($provider !== null) {
			$checkImagick = new \Imagick();
			if (count($checkImagick->queryFormats($provider)) < 1) {
				$this->markTestSkipped('Imagemagick ' . $provider . ' support is required for this icon generation test.');
			}
		}
	}

	/**
	 * Data provider for app icon rendering tests (SVG only).
	 */
	public static function dataRenderAppIconSvg(): array {
		return [
			['logo', '#0082c9', 'logo.svg'],
			['settings', '#FF0000', 'settings.svg'],
		];
	}

	/**
	 * Data provider for app icon rendering tests (PNG only).
	 */
	public static function dataRenderAppIconPng(): array {
		return [
			['logo', '#0082c9', 'logo.png'],
			['settings', '#FF0000', 'settings.png'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRenderAppIconSvg')]
	public function testRenderAppIconSvg(string $app, string $color, string $file): void {
		$this->checkImagick('SVG');
		// mock required methods
		$this->imageManager->expects($this->any())
			->method('canConvert')
			->willReturnMap([
				['SVG', true],
				['PNG', true]
			]);
		$this->util->expects($this->once())
			->method('getAppIcon')
			->with($app, true)
			->willReturn(__DIR__ . '/data/' . $file);
		$this->themingDefaults->expects($this->any())
			->method('getColorPrimary')
			->willReturn($color);
		// generate expected output from source file
		$expectedIcon = $this->generateTestIcon($file, 'SVG', 512, $color);
		// run test
		$icon = $this->iconBuilder->renderAppIcon($app, 512);
		$this->assertEquals(true, $icon->valid());
		$this->assertEquals(512, $icon->getImageWidth());
		$this->assertEquals(512, $icon->getImageHeight());
		$icon->setImageFormat('SVG');
		$expectedIcon->setImageFormat('SVG');
		$this->assertEquals($expectedIcon->getImageBlob(), $icon->getImageBlob(), 'Generated icon differs from expected');
		$icon->destroy();
		$expectedIcon->destroy();
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRenderAppIconPng')]
	public function testRenderAppIconPng(string $app, string $color, string $file): void {
		$this->checkImagick('PNG');
		// mock required methods
		$this->imageManager->expects($this->any())
			->method('canConvert')
			->willReturnMap([
				['SVG', false],
				['PNG', true]
			]);
		$this->util->expects($this->once())
			->method('getAppIcon')
			->with($app, false)
			->willReturn(__DIR__ . '/data/' . $file);
		$this->themingDefaults->expects($this->any())
			->method('getColorPrimary')
			->willReturn($color);
		// generate expected output from source file
		$expectedIcon = $this->generateTestIcon($file, 'PNG', 512, $color);
		// run test
		$icon = $this->iconBuilder->renderAppIcon($app, 512);
		$this->assertEquals(true, $icon->valid());
		$this->assertEquals(512, $icon->getImageWidth());
		$this->assertEquals(512, $icon->getImageHeight());
		$icon->setImageFormat('PNG');
		$expectedIcon->setImageFormat('PNG');
		$this->assertEquals($expectedIcon->getImageBlob(), $icon->getImageBlob(), 'Generated icon differs from expected');
		$icon->destroy();
		$expectedIcon->destroy();
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRenderAppIconSvg')]
	public function testGetTouchIconSvg(string $app, string $color, string $file): void {
		$this->checkImagick('SVG');
		// mock required methods
		$this->imageManager->expects($this->any())
			->method('canConvert')
			->willReturnMap([
				['SVG', true],
				['PNG', true]
			]);
		$this->util->expects($this->once())
			->method('getAppIcon')
			->with($app, true)
			->willReturn(__DIR__ . '/data/' . $file);
		$this->themingDefaults->expects($this->any())
			->method('getColorPrimary')
			->willReturn($color);
		// generate expected output from source file
		$expectedIcon = $this->generateTestIcon($file, 'SVG', 512, $color);
		$expectedIcon->setImageFormat('PNG32');
		// run test
		$result = $this->iconBuilder->getTouchIcon($app);
		$this->assertIsString($result, 'Touch icon generation should return a PNG blob');
		$this->assertEquals($expectedIcon->getImageBlob(), $result, 'Generated touch icon differs from expected');
		$expectedIcon->destroy();
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRenderAppIconPng')]
	public function testGetTouchIconPng(string $app, string $color, string $file): void {
		$this->checkImagick('PNG');
		// mock required methods
		$this->imageManager->expects($this->any())
			->method('canConvert')
			->willReturnMap([
				['SVG', false],
				['PNG', true]
			]);
		$this->util->expects($this->once())
			->method('getAppIcon')
			->with($app, false)
			->willReturn(__DIR__ . '/data/' . $file);
		$this->themingDefaults->expects($this->any())
			->method('getColorPrimary')
			->willReturn($color);
		// generate expected output from source file
		$expectedIcon = $this->generateTestIcon($file, 'PNG', 512, $color);
		$expectedIcon->setImageFormat('PNG32');
		// run test
		$result = $this->iconBuilder->getTouchIcon($app);
		$this->assertIsString($result, 'Touch icon generation should return a PNG blob');
		$this->assertEquals($expectedIcon->getImageBlob(), $result, 'Generated touch icon differs from expected');
		$expectedIcon->destroy();
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRenderAppIconSvg')]
	public function testGetFavIconSvg(string $app, string $color, string $file): void {
		$this->checkImagick('SVG');
		// mock required methods
		$this->imageManager->expects($this->any())
			->method('canConvert')
			->willReturnMap([
				['ICO', true],
				['SVG', true],
				['PNG', true]
			]);
		$this->util->expects($this->once())
			->method('getAppIcon')
			->with($app, true)
			->willReturn(__DIR__ . '/data/' . $file);
		$this->themingDefaults->expects($this->any())
			->method('getColorPrimary')
			->willReturn($color);
		// generate expected output from source file
		$expectedIcon = $this->generateTestFavIcon($file, 'SVG', $color);
		// run test
		$result = $this->iconBuilder->getFavicon($app);
		$this->assertIsString($result, 'Favicon generation should return a ICO blob');
		$this->assertEquals($expectedIcon->getImagesBlob(), $result, 'Generated favicon differs from expected');
		$expectedIcon->destroy();
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRenderAppIconPng')]
	public function testGetFaviconPng(string $app, string $color, string $file): void {
		$this->checkImagick('PNG');
		// mock required methods
		$this->imageManager->expects($this->any())
			->method('canConvert')
			->willReturnMap([
				['ICO', true],
				['SVG', false],
				['PNG', true]
			]);
		$this->util->expects($this->once())
			->method('getAppIcon')
			->with($app, false)
			->willReturn(__DIR__ . '/data/' . $file);
		$this->themingDefaults->expects($this->any())
			->method('getColorPrimary')
			->willReturn($color);
		// generate expected output from source file
		$expectedIcon = $this->generateTestFavIcon($file, 'PNG', $color);
		// run test
		$result = $this->iconBuilder->getFavicon($app);
		$this->assertIsString($result, 'Favicon generation should return a ICO blob');
		$this->assertEquals($expectedIcon->getImagesBlob(), $result, 'Generated favicon differs from expected');
		$expectedIcon->destroy();
	}

	public function testGetFaviconNotFound(): void {
		$this->checkImagick('ICO');
		$util = $this->createMock(Util::class);
		$iconBuilder = new IconBuilder($this->themingDefaults, $util, $this->imageManager);
		$this->imageManager->expects($this->any())
			->method('canConvert')
			->willReturn(true);
		$util->expects($this->once())
			->method('getAppIcon')
			->willReturn('notexistingfile');
		$result = $iconBuilder->getFavicon('noapp');
		$this->assertFalse($result, 'Favicon generation should fail for missing file');
	}

	public function testGetTouchIconNotFound(): void {
		$this->checkImagick();
		$util = $this->createMock(Util::class);
		$iconBuilder = new IconBuilder($this->themingDefaults, $util, $this->imageManager);
		$util->expects($this->once())
			->method('getAppIcon')
			->willReturn('notexistingfile');
		$this->assertFalse($iconBuilder->getTouchIcon('noapp'));
	}

	public function testColorSvgNotFound(): void {
		$this->checkImagick();
		$util = $this->createMock(Util::class);
		$iconBuilder = new IconBuilder($this->themingDefaults, $util, $this->imageManager);
		$util->expects($this->once())
			->method('getAppImage')
			->willReturn('notexistingfile');
		$this->assertFalse($iconBuilder->colorSvg('noapp', 'noimage'));
	}

	/**
	 * Helper to generate expected icon from source file for tests.
	 */
	public static function dataGetUploadedTouchIcon(): array {
		return [
			['logo.svg', 'SVG', 'image/svg+xml', '#0082c9'],
		];
	}

	public function testGetUploadedTouchIconRasterNotUpscaled(): void {
		$this->checkImagick('PNG');
		$content = file_get_contents(__DIR__ . '/data/logo.png');
		$this->mockUploadedFavicon($content, 'image/png');
		$this->themingDefaults->method('getColorPrimary')
			->willReturn('#FF0000');
		$result = $this->iconBuilder->getUploadedTouchIcon();
		$this->assertIsString($result, 'Touch icon generation should return a PNG blob');

		// the 252x120 px image fits into the padding of a 297 px icon unscaled
		$icon = new \Imagick();
		$icon->readImageBlob($result);
		$this->assertSame([297, 297], [$icon->getImageWidth(), $icon->getImageHeight()]);
		$this->assertSame(297, $this->iconBuilder->getUploadedTouchIconSize());
		$icon->cropImage(252, 120, 22, 88);
		$expected = new \Imagick();
		$expected->readImageBlob($content);
		$expected->setImageBackgroundColor(new \ImagickPixel('#FF0000'));
		$expected = $expected->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
		[, $distortion] = $icon->compareImages($expected, \Imagick::METRIC_ROOTMEANSQUAREDERROR);
		$this->assertLessThan(0.01, $distortion, 'The image was resampled');
		$icon->destroy();
		$expected->destroy();
	}

	public static function dataGetUploadedTouchIconTooSmall(): array {
		$tile = new \Imagick();
		$tile->newImage(140, 140, new \ImagickPixel('blue'));
		$tile->setImageFormat('PNG');
		$logo = new \Imagick();
		$logo->newImage(120, 60, new \ImagickPixel('blue'));
		$logo->borderImage(new \ImagickPixel('transparent'), 1, 1);
		$logo->setImageFormat('PNG');
		return [
			'tile' => [$tile->getImageBlob()],
			// 122 px with the padding of the app icons is 144 px, the minimum size
			'image on the primary color' => [$logo->getImageBlob(), 144],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetUploadedTouchIconTooSmall')]
	public function testGetUploadedTouchIconTooSmall(string $content, ?int $size = null): void {
		$this->checkImagick('PNG');
		$this->mockUploadedFavicon($content, 'image/png');
		$this->themingDefaults->method('getColorPrimary')
			->willReturn('#0082c9');
		$this->assertSame($size, $this->iconBuilder->getUploadedTouchIconSize());
		$result = $this->iconBuilder->getUploadedTouchIcon();
		if ($size === null) {
			// a raster favicon is not enlarged, the generated touch icon is used instead
			$this->assertFalse($result);
		} else {
			$this->assertIsString($result);
			$icon = new \Imagick();
			$icon->readImageBlob($result);
			$this->assertSame([$size, $size], [$icon->getImageWidth(), $icon->getImageHeight()]);
		}
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetUploadedTouchIcon')]
	public function testGetUploadedTouchIcon(string $file, string $format, string $mime, string $color): void {
		$this->checkImagick($format);
		$this->mockUploadedFavicon(file_get_contents(__DIR__ . '/data/' . $file), $mime);
		$this->themingDefaults->method('getColorPrimary')
			->willReturn($color);
		$this->util->expects($this->never())
			->method('getAppIcon');
		$expectedIcon = $this->generateTestIcon($file, $format, 512, $color);
		$expectedIcon->setImageFormat('PNG');
		$expectedIcon->setOption('png:compression-level', '9');
		$result = $this->iconBuilder->getUploadedTouchIcon();
		$this->assertIsString($result, 'Touch icon generation should return a PNG blob');
		$this->assertEquals($expectedIcon->getImageBlob(), $result, 'Generated touch icon differs from expected');
		$expectedIcon->destroy();
	}

	public static function dataGetUploadedTouchIconMultipleImages(): array {
		return [
			// the largest image of an ICO file
			['ICO', 'image/x-icon', [[16, 'red'], [160, 'blue'], [32, 'red']], 'blue'],
			// the first frame of an animation
			['GIF', 'image/gif', [[160, 'red'], [160, 'blue']], 'red'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetUploadedTouchIconMultipleImages')]
	public function testGetUploadedTouchIconMultipleImages(string $format, string $mime, array $images, string $expected): void {
		$this->checkImagick($format);
		$file = new \Imagick();
		foreach ($images as [$size, $color]) {
			$file->newImage($size, $size, new \ImagickPixel($color));
			$file->setImageFormat($format);
		}
		$this->mockUploadedFavicon($file->getImagesBlob(), $mime);
		$this->themingDefaults->method('getColorPrimary')
			->willReturn('#ffffff');
		$result = $this->iconBuilder->getUploadedTouchIcon();
		$this->assertIsString($result, 'Touch icon generation should return a PNG blob');

		$icon = new \Imagick();
		$icon->readImageBlob($result);
		$this->assertSame([160, 160], [$icon->getImageWidth(), $icon->getImageHeight()]);
		$this->assertTrue($icon->getImagePixelColor(80, 80)->isSimilar(new \ImagickPixel($expected), 0.1));
	}

	public static function dataGetUploadedTouchIconTile(): array {
		$whiteTile = new \Imagick();
		$whiteTile->newImage(180, 180, new \ImagickPixel('white'));
		$draw = new \ImagickDraw();
		$draw->setFillColor('#d81b60');
		$draw->circle(90, 90, 90, 40);
		$whiteTile->drawImage($draw);
		$whiteTile->setImageFormat('PNG');
		$photo = new \Imagick();
		$photo->newImage(640, 480, new \ImagickPixel('#2e7d32'));
		$photo->setImageFormat('JPEG');
		$circle = new \Imagick();
		$circle->newImage(180, 180, new \ImagickPixel('transparent'));
		$circle->drawImage($draw);
		$circle->setImageFormat('PNG');
		return [
			// a tile with rounded corners fills the icon, the corners get the edge color
			'SVG tile with rounded corners' => ['SVG', '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" rx="22" fill="#2e7d32"/></svg>', 'image/svg+xml', 512, '#2e7d32', '#2e7d32'],
			'raster tile on white' => ['PNG', $whiteTile->getImageBlob(), 'image/png', 180, '#ffffff', '#ffffff'],
			// not square or transparent edges: rendered on the primary color like the app icons
			'opaque photo' => ['JPEG', $photo->getImageBlob(), 'image/jpeg', 512, '#0082c9', 'transparent'],
			'transparent edges' => ['PNG', $circle->getImageBlob(), 'image/png', 212, '#0082c9', 'transparent'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetUploadedTouchIconTile')]
	public function testGetUploadedTouchIconTile(string $format, string $content, string $mime, int $size, string $edge, string $corner): void {
		$this->checkImagick($format);
		$this->mockUploadedFavicon($content, $mime);
		$this->themingDefaults->method('getColorPrimary')
			->willReturn('#0082c9');
		$result = $this->iconBuilder->getUploadedTouchIcon();
		$this->assertIsString($result, 'Touch icon generation should return a PNG blob');

		$icon = new \Imagick();
		$icon->readImageBlob($result);
		$this->assertSame([$size, $size], [$icon->getImageWidth(), $icon->getImageHeight()]);
		$this->assertSame($size, $this->iconBuilder->getUploadedTouchIconSize());
		// inside the 7.5 % margin of the app icons
		$this->assertTrue($icon->getImagePixelColor(10, intdiv($size, 2))->isSimilar(new \ImagickPixel($edge), 0.05), 'edge color');
		$cornerPixel = $icon->getImagePixelColor(0, 0);
		if ($corner === 'transparent') {
			$this->assertSame(0.0, $cornerPixel->getColorValue(\Imagick::COLOR_ALPHA), 'rounded corner of the app icon');
		} else {
			$this->assertTrue($cornerPixel->isSimilar(new \ImagickPixel($corner), 0.05), 'filled corner');
		}
		$icon->destroy();
	}

	public function testGetUploadedTouchIconNotRenderable(): void {
		$this->imageManager->method('canRenderImage')
			->with('favicon')
			->willReturn(false);
		$this->imageManager->expects($this->never())
			->method('getImage');
		$this->assertFalse($this->iconBuilder->getUploadedTouchIcon());
	}

	public static function dataGetUploadedFavicon(): array {
		$small = new \Imagick();
		$small->newImage(20, 10, new \ImagickPixel('blue'));
		$small->setImageFormat('PNG');
		$tiny = new \Imagick();
		$tiny->newImage(10, 5, new \ImagickPixel('blue'));
		$tiny->setImageFormat('PNG');
		return [
			'large raster with 2:1 aspect ratio' => ['PNG', file_get_contents(__DIR__ . '/data/logo.png'), 'image/png', ['16x16', '32x32', '64x64', '128x128'], 128],
			'small raster is not upscaled' => ['PNG', $small->getImageBlob(), 'image/png', ['16x16'], 16],
			'raster smaller than 16 px keeps its size' => ['PNG', $tiny->getImageBlob(), 'image/png', ['10x10'], 10],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetUploadedFavicon')]
	public function testGetUploadedFavicon(string $format, string $content, string $mime, array $expectedSizes, int $largest): void {
		$this->checkImagick($format);
		$this->checkImagick('ICO');
		$this->mockUploadedFavicon($content, $mime);
		$result = $this->iconBuilder->getUploadedFavicon();
		$this->assertIsString($result, 'Favicon generation should return an ICO blob');

		$favicon = new \Imagick();
		$favicon->setFormat('ICO');
		$favicon->readImageBlob($result);
		$this->assertSame('ICO', $favicon->getImageFormat());
		$sizes = [];
		foreach ($favicon as $image) {
			$sizes[] = $image->getImageWidth() . 'x' . $image->getImageHeight();
		}
		$this->assertSame($expectedSizes, $sizes);
		// the image is centered on a transparent background, not stretched or padded with a color
		$favicon->setIteratorIndex(count($sizes) - 1);
		$this->assertSame(0, $this->countOpaquePixels($favicon, 0));
		$this->assertSame(0, $this->countOpaquePixels($favicon, $largest - 1));
		$this->assertGreaterThan(0, $this->countOpaquePixels($favicon, intdiv($largest, 2)));
		$favicon->destroy();
	}

	public function testGetUploadedTouchIconEmptyRendering(): void {
		// ImageMagick renders some SVG images it can not parse as fully transparent
		$this->checkImagick('SVG');
		$this->mockUploadedFavicon('<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"/>', 'image/svg+xml');
		$this->assertFalse($this->iconBuilder->getUploadedTouchIcon());
	}

	public function testGetUploadedManifestIconSvg(): void {
		$this->checkImagick('SVG');
		$this->mockUploadedFavicon(file_get_contents(__DIR__ . '/data/logo.svg'), 'image/svg+xml');
		$result = $this->iconBuilder->getUploadedManifestIcon();
		$this->assertIsString($result);
		$this->assertSame(512, $this->iconBuilder->getUploadedManifestIconSize());

		// the favicon is rendered at 512 px without a background
		$icon = new \Imagick();
		$icon->readImageBlob($result);
		$this->assertSame('PNG', $icon->getImageFormat());
		$this->assertSame([512, 512], [$icon->getImageWidth(), $icon->getImageHeight()]);
		$this->assertEqualsWithDelta(0.0, $icon->getImagePixelColor(0, 0)->getColorValue(\Imagick::COLOR_ALPHA), 0.01);
		$this->assertGreaterThan(0, $this->countOpaquePixels($icon, 256));
		$icon->destroy();
	}

	public function testGetUploadedManifestIconRasterNotResampled(): void {
		$this->checkImagick('PNG');
		$content = file_get_contents(__DIR__ . '/data/logo.png');
		$this->mockUploadedFavicon($content, 'image/png');
		$result = $this->iconBuilder->getUploadedManifestIcon();
		$this->assertIsString($result);
		$this->assertSame(252, $this->iconBuilder->getUploadedManifestIconSize());

		// the 252x120 px image is centered on a transparent square, its pixels are unchanged
		$icon = new \Imagick();
		$icon->readImageBlob($result);
		$this->assertSame([252, 252], [$icon->getImageWidth(), $icon->getImageHeight()]);
		$this->assertEqualsWithDelta(0.0, $icon->getImagePixelColor(0, 0)->getColorValue(\Imagick::COLOR_ALPHA), 0.01);
		$icon->cropImage(252, 120, 0, 66);
		$icon->setImagePage(0, 0, 0, 0);
		$expected = new \Imagick();
		$expected->readImageBlob($content);
		[, $distortion] = $icon->compareImages($expected, \Imagick::METRIC_ROOTMEANSQUAREDERROR);
		$this->assertLessThan(0.001, $distortion, 'The image was resampled');
		$icon->destroy();
		$expected->destroy();
	}

	public static function dataGetUploadedManifestIconSize(): array {
		$small = new \Imagick();
		$small->newImage(143, 100, new \ImagickPixel('blue'));
		$small->setImageFormat('PNG');
		$minimum = new \Imagick();
		$minimum->newImage(144, 100, new \ImagickPixel('blue'));
		$minimum->setImageFormat('PNG');
		$large = new \Imagick();
		$large->newImage(1024, 600, new \ImagickPixel('blue'));
		$large->setImageFormat('PNG');
		return [
			// a raster favicon is not enlarged
			'too small' => [$small->getImageBlob(), null],
			'minimum size' => [$minimum->getImageBlob(), 144],
			'scaled down' => [$large->getImageBlob(), 512],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetUploadedManifestIconSize')]
	public function testGetUploadedManifestIconSize(string $content, ?int $size): void {
		$this->checkImagick('PNG');
		$this->mockUploadedFavicon($content, 'image/png');
		$this->assertSame($size, $this->iconBuilder->getUploadedManifestIconSize());
		$result = $this->iconBuilder->getUploadedManifestIcon();
		if ($size === null) {
			$this->assertFalse($result);
			return;
		}
		$this->assertIsString($result);
		$icon = new \Imagick();
		$icon->readImageBlob($result);
		$this->assertSame([$size, $size], [$icon->getImageWidth(), $icon->getImageHeight()]);
		$icon->destroy();
	}

	public function testGetUploadedManifestIconNotRenderable(): void {
		$this->imageManager->method('canRenderImage')
			->with('favicon')
			->willReturn(false);
		$this->imageManager->expects($this->never())
			->method('getImage');
		$this->assertFalse($this->iconBuilder->getUploadedManifestIcon());
		$this->assertNull($this->iconBuilder->getUploadedManifestIconSize());
	}

	public function testGetUploadedManifestIconEmptyRendering(): void {
		$this->checkImagick('SVG');
		$this->mockUploadedFavicon('<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"/>', 'image/svg+xml');
		$this->assertFalse($this->iconBuilder->getUploadedManifestIcon());
		$this->assertNull($this->iconBuilder->getUploadedManifestIconSize());
	}

	public static function dataGetUploadedFaviconNotConverted(): array {
		return [
			['image/x-icon'],
			['image/vnd.microsoft.icon'],
			['image/svg+xml'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetUploadedFaviconNotConverted')]
	public function testGetUploadedFaviconNotConverted(string $mime): void {
		$this->imageManager->method('getImageMime')
			->with('favicon')
			->willReturn($mime);
		$this->imageManager->method('isSvgImage')
			->with('favicon')
			->willReturn($mime === 'image/svg+xml');
		$this->imageManager->method('canRenderImage')
			->willReturn(true);
		$this->imageManager->method('canConvert')
			->willReturn(true);
		$this->imageManager->expects($this->never())
			->method('getImage');
		$this->assertFalse($this->iconBuilder->getUploadedFavicon());
	}

	private function countOpaquePixels(\Imagick $image, int $row): int {
		$count = 0;
		for ($x = 0; $x < $image->getImageWidth(); $x++) {
			if ($image->getImagePixelColor($x, $row)->getColorValue(\Imagick::COLOR_ALPHA) > 0) {
				$count++;
			}
		}
		return $count;
	}

	private function mockUploadedFavicon(string $content, string $mime): void {
		$this->imageManager->method('getImageMime')
			->with('favicon')
			->willReturn($mime);
		$this->imageManager->method('isSvgImage')
			->with('favicon')
			->willReturn($mime === 'image/svg+xml');
		$this->imageManager->method('canRenderImage')
			->with('favicon')
			->willReturn(true);
		$this->imageManager->method('canConvert')
			->willReturn(true);
		$favicon = $this->createMock(ISimpleFile::class);
		$favicon->method('getContent')
			->willReturn($content);
		$this->imageManager->method('getImage')
			->with('favicon')
			->willReturn($favicon);
	}

	private function generateTestIcon(string $file, string $format, int $size, string $color): \Imagick {
		$filePath = realpath(__DIR__ . '/data/' . $file);
		$appIconFile = new \Imagick();
		if ($format === 'SVG') {
			$svgContent = file_get_contents($filePath);
			if (substr($svgContent, 0, 5) !== '<?xml') {
				$svgContent = '<?xml version="1.0"?>' . $svgContent;
			}
			// get dimensions for resolution calculation
			$tmp = new \Imagick();
			$tmp->setBackgroundColor(new \ImagickPixel('transparent'));
			$tmp->setResolution(72, 72);
			$tmp->readImageBlob($svgContent);
			$x = $tmp->getImageWidth();
			$y = $tmp->getImageHeight();
			$tmp->destroy();
			$res = (int)(72 * $size / max($x, $y));
			$appIconFile->setBackgroundColor(new \ImagickPixel('transparent'));
			$appIconFile->setResolution($res, $res);
			$appIconFile->readImageBlob($svgContent);
		} else {
			$appIconFile->readImage($filePath);
		}
		$padding = 0.85;
		$original_w = $appIconFile->getImageWidth();
		$original_h = $appIconFile->getImageHeight();
		$contentSize = (int)floor($size * $padding);
		$scale = min($contentSize / $original_w, $contentSize / $original_h);
		$new_w = max(1, (int)floor($original_w * $scale));
		$new_h = max(1, (int)floor($original_h * $scale));
		$offset_w = (int)floor(($size - $new_w) / 2);
		$offset_h = (int)floor(($size - $new_h) / 2);
		$cornerRadius = 0.2 * $size;
		$appIconFile->resizeImage($new_w, $new_h, \Imagick::FILTER_LANCZOS, 1);
		$finalIconFile = new \Imagick();
		$finalIconFile->setBackgroundColor(new \ImagickPixel('transparent'));
		$finalIconFile->newImage($size, $size, new \ImagickPixel('transparent'));
		$draw = new \ImagickDraw();
		$draw->setFillColor($color);
		$draw->roundRectangle(0, 0, $size, $size, $cornerRadius, $cornerRadius);
		$finalIconFile->drawImage($draw);
		$draw->destroy();
		$finalIconFile->setImageVirtualPixelMethod(\Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
		$finalIconFile->setImageArtifact('compose:args', '1,0,-0.5,0.5');
		$finalIconFile->compositeImage($appIconFile, \Imagick::COMPOSITE_ATOP, $offset_w, $offset_h);
		$finalIconFile->setImageFormat('PNG32');
		if (defined('Imagick::INTERPOLATE_BICUBIC') === true) {
			$filter = \Imagick::INTERPOLATE_BICUBIC;
		} else {
			$filter = \Imagick::FILTER_LANCZOS;
		}
		$finalIconFile->resizeImage($size, $size, $filter, 1, false);
		$finalIconFile->setImageFormat('png');
		$appIconFile->destroy();
		return $finalIconFile;
	}

	/**
	 * Helper to generate expected favicon from source file for tests.
	 */
	private function generateTestFavIcon(string $file, string $format, string $color): \Imagick {
		$baseIcon = $this->generateTestIcon($file, $format, 128, $color);
		$baseIcon->setImageFormat('PNG32');

		$testIcon = new \Imagick();
		$testIcon->setFormat('ICO');
		foreach ([16, 32, 64, 128] as $size) {
			$clone = clone $baseIcon;
			$clone->scaleImage($size, 0);
			$testIcon->addImage($clone);
			$clone->destroy();
		}
		$baseIcon->destroy();
		return $testIcon;
	}
}

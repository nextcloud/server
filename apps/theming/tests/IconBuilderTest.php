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
				['SVG', false],
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
		$this->assertEquals($expectedIcon->getImageBlob(), $result, 'Generated favicon differs from expected');
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
		$this->assertIsString($result, 'Favicon generation should return a PNG blob');
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
	private function generateTestIcon(string $file, string $format, int $size, string $color): \Imagick {
		$filePath = realpath(__DIR__ . '/data/' . $file);
		$appIconFile = new \Imagick();
		if ($format === 'SVG') {
			$svgContent = file_get_contents($filePath);
			if (substr($svgContent, 0, 5) !== '<?xml') {
				$svgContent = '<?xml version="1.0\"?>' . $svgContent;
			}
			$appIconFile->setResolution($size, $size);
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
		$draw->roundRectangle(0, 0, $size - 1, $size - 1, $cornerRadius, $cornerRadius);
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

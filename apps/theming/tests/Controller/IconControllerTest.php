<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Tests\Controller;

use OC\Files\SimpleFS\SimpleFile;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OCA\Theming\Controller\IconController;
use OCA\Theming\IconBuilder;
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class IconControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private ThemingDefaults&MockObject $themingDefaults;
	private ITimeFactory&MockObject $timeFactory;
	private IconBuilder&MockObject $iconBuilder;
	private FileAccessHelper&MockObject $fileAccessHelper;
	private IAppManager&MockObject $appManager;
	private ImageManager&MockObject $imageManager;
	private IconController $iconController;
	private IConfig&MockObject $config;

	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->iconBuilder = $this->createMock(IconBuilder::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->fileAccessHelper = $this->createMock(FileAccessHelper::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->config = $this->createMock(IConfig::class);

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(123);

		$this->overwriteService(ITimeFactory::class, $this->timeFactory);

		$this->iconController = new IconController(
			'theming',
			$this->request,
			$this->config,
			$this->themingDefaults,
			$this->iconBuilder,
			$this->imageManager,
			$this->fileAccessHelper,
			$this->appManager,
		);

		parent::setUp();
	}

	private function iconFileMock($filename, $data): SimpleFile {
		$icon = $this->createMock(File::class);
		$icon->expects($this->any())->method('getContent')->willReturn($data);
		$icon->expects($this->any())->method('getMimeType')->willReturn('image type');
		$icon->expects($this->any())->method('getEtag')->willReturn('my etag');
		$icon->expects($this->any())->method('getName')->willReturn('my name');
		$icon->expects($this->any())->method('getMTime')->willReturn(42);
		$icon->method('getName')->willReturn($filename);
		return new SimpleFile($icon);
	}

	public static function dataGetThemedIcon(): array {
		return [
			['filetypes/folder.svg', 'icon-core-filetypes_folder.svg'],
			['filetypes/x-office-document.svg', 'icon-core-filetypes_x-office-document.svg'],
			['actions/add.svg', 'icon-core-actions_add.svg'],
			['app_dark.svg', 'icon-core-app_dark.svg'],
			['logo/logo.v2.svg', 'icon-core-logo_logo.v2.svg'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataGetThemedIcon')]
	public function testGetThemedIcon(string $image, string $cacheKey): void {
		$file = $this->iconFileMock($cacheKey, 'filecontent');
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->with($cacheKey)
			->willReturn($file);
		$expected = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'image/svg+xml']);
		$expected->cacheFor(86400, false, true);
		$this->assertEquals($expected, $this->iconController->getThemedIcon('core', $image));
	}

	public static function dataGetThemedIconInvalidImage(): array {
		return [
			'not an svg' => ['background/eduardo-neves-pedra-azul.jpg'],
			'png' => ['favicon.png'],
			'missing extension' => ['filetypes/folder'],
			'double dot' => ['filetypes/fol..der.svg'],
			'dot segment' => ['filetypes/./folder.svg'],
			'parent segment' => ['filetypes/../filetypes/folder.svg'],
			'leading slash' => ['/filetypes/folder.svg'],
			'double slash' => ['filetypes//folder.svg'],
			'trailing newline' => ["filetypes/folder.svg\n"],
			'extension only' => ['.svg'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataGetThemedIconInvalidImage')]
	public function testGetThemedIconInvalidImage(string $image): void {
		$this->imageManager->expects($this->never())
			->method('getCachedImage');
		$this->imageManager->expects($this->never())
			->method('setCachedImage');
		$this->iconBuilder->expects($this->never())
			->method('colorSvg');
		$this->assertInstanceOf(NotFoundResponse::class, $this->iconController->getThemedIcon('core', $image));
	}

	public function testGetFaviconThemed(): void {
		if (!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagemagick is required for dynamic icon generation.');
		}
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) < 1) {
			$this->markTestSkipped('No SVG provider present.');
		}
		$file = $this->iconFileMock('filename', 'filecontent');
		$this->imageManager->expects($this->once())
			->method('getImage', false)
			->with('favicon')
			->willThrowException(new NotFoundException());
		$this->imageManager->expects($this->any())
			->method('canConvert')
			->willReturnMap([
				['SVG', true],
				['PNG', true],
				['ICO', true],
			]);
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->willThrowException(new NotFoundException());
		$this->iconBuilder->expects($this->once())
			->method('getFavicon')
			->with('core')
			->willReturn('filecontent');
		$this->imageManager->expects($this->once())
			->method('setCachedImage')
			->willReturn($file);

		$expected = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		$expected->cacheFor(86400);
		$this->assertEquals($expected, $this->iconController->getFavicon());
	}

	public function testGetFaviconUploaded(): void {
		// a custom favicon was uploaded, so it must be served as-is and the
		// app-specific generation path must not overwrite it
		$file = $this->iconFileMock('favicon.ico', 'filecontent');
		$this->imageManager->expects($this->once())
			->method('getImage')
			->with('favicon', false)
			->willReturn($file);
		$this->imageManager->expects($this->never())
			->method('getCachedImage');
		$this->iconBuilder->expects($this->never())
			->method('getFavicon');

		$expected = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		$expected->cacheFor(86400);
		$this->assertEquals($expected, $this->iconController->getFavicon());
	}

	public function testGetFaviconDefault(): void {
		$this->imageManager->expects($this->once())
			->method('getImage')
			->with('favicon', false)
			->willThrowException(new NotFoundException());
		$this->imageManager->expects($this->any())
			->method('canConvert')
			->willReturnMap([
				['SVG', false],
				['PNG', false],
				['ICO', false],
			]);
		$fallbackLogo = \OC::$SERVERROOT . '/core/img/favicon.png';
		$this->fileAccessHelper->expects($this->once())
			->method('file_get_contents')
			->with($fallbackLogo)
			->willReturn(file_get_contents($fallbackLogo));
		$expected = new DataDisplayResponse(file_get_contents($fallbackLogo), Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$expected->cacheFor(86400);
		$this->assertEquals($expected, $this->iconController->getFavicon());
	}

	public function testGetTouchIconDefault(): void {
		if (!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagemagick is required for dynamic icon generation.');
		}
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) < 1) {
			$this->markTestSkipped('No SVG provider present.');
		}

		$this->imageManager->expects($this->once())
			->method('getImage')
			->willThrowException(new NotFoundException());
		$this->imageManager->expects($this->any())
			->method('canConvert')
			->with('PNG')
			->willReturn(true);
		$this->iconBuilder->expects($this->once())
			->method('getTouchIcon')
			->with('core')
			->willReturn('filecontent');
		$file = $this->iconFileMock('filename', 'filecontent');
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->willThrowException(new NotFoundException());
		$this->imageManager->expects($this->once())
			->method('setCachedImage')
			->willReturn($file);

		$expected = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$expected->cacheFor(86400);
		$this->assertEquals($expected, $this->iconController->getTouchIcon());
	}

	public function testGetTouchIconUploaded(): void {
		// a custom favicon was uploaded, so it must be served as-is and the
		// app-specific generation path must not overwrite it
		$file = $this->iconFileMock('favicon.png', 'filecontent');
		$this->imageManager->expects($this->once())
			->method('getImage')
			->with('favicon')
			->willReturn($file);
		$this->imageManager->expects($this->never())
			->method('getCachedImage');
		$this->iconBuilder->expects($this->never())
			->method('getTouchIcon');

		$expected = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'image type']);
		$expected->cacheFor(86400);
		$this->assertEquals($expected, $this->iconController->getTouchIcon());
	}

	public function testGetTouchIconFail(): void {
		$this->imageManager->expects($this->once())
			->method('getImage')
			->with('favicon')
			->willThrowException(new NotFoundException());
		$this->imageManager->expects($this->any())
			->method('canConvert')
			->with('PNG')
			->willReturn(false);
		$fallbackLogo = \OC::$SERVERROOT . '/core/img/favicon-touch.png';
		$this->fileAccessHelper->expects($this->once())
			->method('file_get_contents')
			->with($fallbackLogo)
			->willReturn(file_get_contents($fallbackLogo));
		$expected = new DataDisplayResponse(file_get_contents($fallbackLogo), Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$expected->cacheFor(86400);
		$this->assertEquals($expected, $this->iconController->getTouchIcon());
	}
}

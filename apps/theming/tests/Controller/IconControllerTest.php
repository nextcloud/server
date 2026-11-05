<?php
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
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IRequest;
use Test\TestCase;

class IconControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var ThemingDefaults|\PHPUnit\Framework\MockObject\MockObject */
	private $themingDefaults;
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var IconController|\PHPUnit\Framework\MockObject\MockObject */
	private $iconController;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IconBuilder|\PHPUnit\Framework\MockObject\MockObject */
	private $iconBuilder;
	/** @var FileAccessHelper|\PHPUnit\Framework\MockObject\MockObject */
	private $fileAccessHelper;
	/** @var IAppManager|\PHPUnit\Framework\MockObject\MockObject */
	private $appManager;
	/** @var ImageManager */
	private $imageManager;

	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->iconBuilder = $this->createMock(IconBuilder::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->fileAccessHelper = $this->createMock(FileAccessHelper::class);
		$this->appManager = $this->createMock(IAppManager::class);

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(123);

		$this->overwriteService(ITimeFactory::class, $this->timeFactory);

		$this->iconController = new IconController(
			'theming',
			$this->request,
			$this->themingDefaults,
			$this->iconBuilder,
			$this->imageManager,
			$this->fileAccessHelper,
			$this->appManager,
		);

		parent::setUp();
	}

	private function iconFileMock($filename, $data) {
		$icon = $this->getMockBuilder('OCP\Files\File')->getMock();
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

	/**
	 * @dataProvider dataGetThemedIcon
	 */
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

	/**
	 * @dataProvider dataGetThemedIconInvalidImage
	 */
	public function testGetThemedIconInvalidImage(string $image): void {
		$this->imageManager->expects($this->never())
			->method('getCachedImage');
		$this->imageManager->expects($this->never())
			->method('setCachedImage');
		$this->iconBuilder->expects($this->never())
			->method('colorSvg');
		$this->assertInstanceOf(NotFoundResponse::class, $this->iconController->getThemedIcon('core', $image));
	}

	public function testGetFaviconDefault(): void {
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
			->will($this->throwException(new NotFoundException()));
		$this->imageManager->expects($this->any())
			->method('shouldReplaceIcons')
			->willReturn(true);
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->will($this->throwException(new NotFoundException()));
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

	public function testGetFaviconFail(): void {
		$this->imageManager->expects($this->once())
			->method('getImage')
			->with('favicon', false)
			->will($this->throwException(new NotFoundException()));
		$this->imageManager->expects($this->any())
			->method('shouldReplaceIcons')
			->willReturn(false);
		$fallbackLogo = \OC::$SERVERROOT . '/core/img/favicon.png';
		$this->fileAccessHelper->expects($this->once())
			->method('file_get_contents')
			->with($fallbackLogo)
			->willReturn(file_get_contents($fallbackLogo));
		$expected = new DataDisplayResponse(file_get_contents($fallbackLogo), Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
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
			->will($this->throwException(new NotFoundException()));
		$this->imageManager->expects($this->any())
			->method('shouldReplaceIcons')
			->willReturn(true);
		$this->iconBuilder->expects($this->once())
			->method('getTouchIcon')
			->with('core')
			->willReturn('filecontent');
		$file = $this->iconFileMock('filename', 'filecontent');
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->will($this->throwException(new NotFoundException()));
		$this->imageManager->expects($this->once())
			->method('setCachedImage')
			->willReturn($file);

		$expected = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$expected->cacheFor(86400);
		$this->assertEquals($expected, $this->iconController->getTouchIcon());
	}

	public function testGetTouchIconFail(): void {
		$this->imageManager->expects($this->once())
			->method('getImage')
			->with('favicon')
			->will($this->throwException(new NotFoundException()));
		$this->imageManager->expects($this->any())
			->method('shouldReplaceIcons')
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

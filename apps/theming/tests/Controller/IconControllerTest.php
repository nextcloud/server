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
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
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

	public function testGetThemedIcon(): void {
		$file = $this->iconFileMock('icon-core-filetypes_folder.svg', 'filecontent');
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->with('icon-core-filetypes_folder.svg')
			->willReturn($file);
		$expected = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'image/svg+xml']);
		$expected->cacheFor(86400, false, true);
		$this->assertEquals($expected, $this->iconController->getThemedIcon('core', 'filetypes/folder.svg'));
	}

	private function iconResponse(SimpleFile $file, string $mime): FileDisplayResponse {
		$response = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => $mime]);
		$csp = new EmptyContentSecurityPolicy();
		$csp->addAllowedImageDomain('data:');
		$response->setContentSecurityPolicy($csp);
		$response->cacheFor(86400);
		return $response;
	}

	public function testGetFaviconThemed(): void {
		$file = $this->iconFileMock('filename', 'filecontent');
		$this->imageManager->method('getImage')->with('favicon')->willThrowException(new NotFoundException());
		$this->themingDefaults->method('getColorPrimary')->willReturn('#0082c9');
		$this->imageManager->expects($this->never())->method('canConvert');
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->with('favIconSvg-core#0082c9')
			->willThrowException(new NotFoundException());
		$this->iconBuilder->expects($this->once())
			->method('getFavicon')
			->with('core')
			->willReturn('<svg/>');
		$this->imageManager->expects($this->once())
			->method('setCachedImage')
			->with('favIconSvg-core#0082c9', '<svg/>')
			->willReturn($file);

		$this->assertEquals($this->iconResponse($file, 'image/svg+xml'), $this->iconController->getFavicon());
	}

	public function testGetFaviconCached(): void {
		$file = $this->iconFileMock('filename', 'filecontent');
		$this->imageManager->method('getImage')->with('favicon')->willThrowException(new NotFoundException());
		$this->themingDefaults->method('getColorPrimary')->willReturn('#0082c9');
		$this->appManager->method('isEnabledForUser')->with('files')->willReturn(true);
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->with('favIconSvg-files#0082c9')
			->willReturn($file);
		$this->iconBuilder->expects($this->never())->method('getFavicon');
		$this->imageManager->expects($this->never())->method('setCachedImage');

		$this->assertEquals($this->iconResponse($file, 'image/svg+xml'), $this->iconController->getFavicon('files'));
	}

	public function testGetFaviconDisabledApp(): void {
		$file = $this->iconFileMock('filename', 'filecontent');
		$this->imageManager->method('getImage')->with('favicon')->willThrowException(new NotFoundException());
		$this->themingDefaults->method('getColorPrimary')->willReturn('#0082c9');
		$this->appManager->method('isEnabledForUser')->with('disabledapp')->willReturn(false);
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->with('favIconSvg-core#0082c9')
			->willReturn($file);

		$this->assertEquals($this->iconResponse($file, 'image/svg+xml'), $this->iconController->getFavicon('disabledapp'));
	}

	public function testGetFaviconFail(): void {
		$this->imageManager->method('getImage')->with('favicon')->willThrowException(new NotFoundException());
		$this->imageManager->method('getCachedImage')->willThrowException(new NotFoundException());
		$this->iconBuilder->method('getFavicon')->willReturn(false);
		$this->imageManager->expects($this->never())->method('setCachedImage');

		$this->assertEquals(new NotFoundResponse(), $this->iconController->getFavicon());
	}

	public static function dataIconEndpoints(): array {
		return [
			['getFavicon'],
			['getTouchIcon'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataIconEndpoints')]
	public function testGetIconUploaded(string $method): void {
		// a custom favicon was uploaded, so it must be served as-is and the
		// app-specific generation path must not overwrite it
		$file = $this->iconFileMock('favicon', 'filecontent');
		$this->imageManager->method('getImage')->with('favicon')->willReturn($file);
		$this->imageManager->method('getImageMime')->with('favicon')->willReturn('image/png');
		$this->imageManager->expects($this->never())->method('getCachedImage');
		$this->iconBuilder->expects($this->never())->method('getFavicon');
		$this->iconBuilder->expects($this->never())->method('getTouchIcon');

		$this->assertEquals($this->iconResponse($file, 'image/png'), $this->iconController->$method());
	}

	public function testGetTouchIconThemed(): void {
		$file = $this->iconFileMock('filename', 'filecontent');
		$this->imageManager->method('getImage')->with('favicon')->willThrowException(new NotFoundException());
		$this->imageManager->method('canConvert')->with('PNG')->willReturn(true);
		$this->themingDefaults->method('getColorPrimary')->willReturn('#0082c9');
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->with('touchIcon-core#0082c9')
			->willThrowException(new NotFoundException());
		$this->iconBuilder->expects($this->once())
			->method('getTouchIcon')
			->with('core')
			->willReturn('pngcontent');
		$this->imageManager->expects($this->once())
			->method('setCachedImage')
			->with('touchIcon-core#0082c9', 'pngcontent')
			->willReturn($file);

		$this->assertEquals($this->iconResponse($file, 'image/png'), $this->iconController->getTouchIcon());
	}

	public function testGetTouchIconCached(): void {
		$file = $this->iconFileMock('filename', 'filecontent');
		$this->imageManager->method('getImage')->with('favicon')->willThrowException(new NotFoundException());
		$this->imageManager->method('canConvert')->with('PNG')->willReturn(true);
		$this->themingDefaults->method('getColorPrimary')->willReturn('#0082c9');
		$this->appManager->method('isEnabledForUser')->with('files')->willReturn(true);
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->with('touchIcon-files#0082c9')
			->willReturn($file);
		$this->iconBuilder->expects($this->never())->method('getTouchIcon');

		$this->assertEquals($this->iconResponse($file, 'image/png'), $this->iconController->getTouchIcon('files'));
	}

	public function testGetTouchIconWithoutImagick(): void {
		$this->imageManager->method('getImage')->with('favicon')->willThrowException(new NotFoundException());
		$this->imageManager->method('canConvert')->with('PNG')->willReturn(false);
		$this->iconBuilder->expects($this->never())->method('getTouchIcon');
		$this->iconBuilder->expects($this->never())->method('getFavicon');

		$this->assertEquals($this->fallbackTouchIconResponse(), $this->iconController->getTouchIcon());
	}

	public function testGetTouchIconFail(): void {
		$this->imageManager->method('getImage')->with('favicon')->willThrowException(new NotFoundException());
		$this->imageManager->method('canConvert')->with('PNG')->willReturn(true);
		$this->imageManager->method('getCachedImage')->willThrowException(new NotFoundException());
		$this->iconBuilder->method('getTouchIcon')->willReturn(false);
		$this->imageManager->expects($this->never())->method('setCachedImage');

		$this->assertEquals($this->fallbackTouchIconResponse(), $this->iconController->getTouchIcon());
	}

	private function fallbackTouchIconResponse(): DataDisplayResponse {
		$fallbackLogo = \OC::$SERVERROOT . '/core/img/favicon-touch.png';
		$this->fileAccessHelper->expects($this->once())
			->method('file_get_contents')
			->with($fallbackLogo)
			->willReturn('fallbackcontent');
		$response = new DataDisplayResponse('fallbackcontent', Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$response->cacheFor(86400);
		return $response;
	}
}

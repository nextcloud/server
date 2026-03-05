<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests;

use OCA\Theming\ImageManager;
use OCA\Theming\Service\BackgroundService;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\ITempManager;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ImageManagerTest extends TestCase {
	protected IConfig&MockObject $config;
	protected IAppData&MockObject $appData;
	private IURLGenerator&MockObject $urlGenerator;
	private ICacheFactory&MockObject $cacheFactory;
	private LoggerInterface&MockObject $logger;
	private ITempManager&MockObject $tempManager;
	private ISimpleFolder&MockObject $rootFolder;
	protected ImageManager $imageManager;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->tempManager = $this->createMock(ITempManager::class);
		$this->rootFolder = $this->createMock(ISimpleFolder::class);
		$backgroundService = $this->createMock(BackgroundService::class);
		$this->imageManager = new ImageManager(
			$this->config,
			$this->appData,
			$this->urlGenerator,
			$this->cacheFactory,
			$this->logger,
			$this->tempManager,
			$backgroundService,
		);
		$this->appData
			->expects($this->any())
			->method('getFolder')
			->with('global')
			->willReturn($this->rootFolder);
	}

	private function checkImagick() {
		if (!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagemagick is required for dynamic icon generation.');
		}
		$checkImagick = new \Imagick();
		if (empty($checkImagick->queryFormats('SVG'))) {
			$this->markTestSkipped('No SVG provider present.');
		}
		if (empty($checkImagick->queryFormats('PNG'))) {
			$this->markTestSkipped('No PNG provider present.');
		}
	}

	public function mockGetImage($key, $file) {
		/** @var MockObject $folder */
		$folder = $this->createMock(ISimpleFolder::class);
		if ($file === null) {
			$folder->expects($this->once())
				->method('getFile')
				->with('logo')
				->willThrowException(new NotFoundException());
		} else {
			$file->expects($this->once())
				->method('getContent')
				->willReturn(file_get_contents(__DIR__ . '/../../../tests/data/testimage.png'));
			$folder->expects($this->exactly(2))
				->method('fileExists')
				->willReturnMap([
					['logo', true],
					['logo.png', false],
				]);
			$folder->expects($this->once())
				->method('getFile')
				->with('logo')
				->willReturn($file);
			$newFile = $this->createMock(ISimpleFile::class);
			$folder->expects($this->once())
				->method('newFile')
				->with('logo.png')
				->willReturn($newFile);
			$newFile->expects($this->once())
				->method('putContent');
			$this->rootFolder->expects($this->once())
				->method('getFolder')
				->with('images')
				->willReturn($folder);
		}
	}

	public function testGetImageUrl(): void {
		$this->checkImagick();
		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'cachebuster', '0', '0'],
				['theming', 'logoMime', '', '0'],
			]);
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->willReturn('url-to-image');
		$this->assertEquals('url-to-image?v=0', $this->imageManager->getImageUrl('logo', false));
	}

	public function testGetImageUrlDefault(): void {
		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'cachebuster', '0', '0'],
				['theming', 'logoMime', '', ''],
			]);
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('core', 'logo/logo.png')
			->willReturn('logo/logo.png');
		$this->assertEquals('logo/logo.png?v=0', $this->imageManager->getImageUrl('logo'));
	}

	public function testGetImageUrlAbsolute(): void {
		$this->checkImagick();
		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'cachebuster', '0', '0'],
				['theming', 'logoMime', '', ''],
			]);
		$this->urlGenerator->expects($this->any())
			->method('getAbsoluteUrl')
			->willReturn('url-to-image-absolute?v=0');
		$this->assertEquals('url-to-image-absolute?v=0', $this->imageManager->getImageUrlAbsolute('logo', false));
	}

	public function testGetImage(): void {
		$this->checkImagick();
		$this->config->expects($this->once())
			->method('getAppValue')->with('theming', 'logoMime', false)
			->willReturn('png');
		$file = $this->createMock(ISimpleFile::class);
		$this->mockGetImage('logo', $file);
		$this->assertEquals($file, $this->imageManager->getImage('logo', false));
	}


	public function testGetImageUnset(): void {
		$this->expectException(NotFoundException::class);

		$this->config->expects($this->once())
			->method('getAppValue')->with('theming', 'logoMime', false)
			->willReturn(false);
		$this->imageManager->getImage('logo');
	}

	public function testGetCacheFolder(): void {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->rootFolder->expects($this->once())
			->method('getFolder')
			->with('0')
			->willReturn($folder);
		$this->assertEquals($folder, $this->imageManager->getCacheFolder());
	}
	public function testGetCacheFolderCreate(): void {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->rootFolder->expects($this->exactly(2))
			->method('getFolder')
			->with('0')
			->willReturnOnConsecutiveCalls(
				$this->throwException(new NotFoundException()),
				$folder,
			);
		$this->rootFolder->expects($this->once())
			->method('newFolder')
			->with('0')
			->willReturn($folder);
		$this->rootFolder->expects($this->once())
			->method('getDirectoryListing')
			->willReturn([]);
		$this->assertEquals($folder, $this->imageManager->getCacheFolder());
	}

	public function testGetCachedImage(): void {
		$expected = $this->createMock(ISimpleFile::class);
		$folder = $this->setupCacheFolder();
		$folder->expects($this->once())
			->method('getFile')
			->with('filename')
			->willReturn($expected);
		$this->assertEquals($expected, $this->imageManager->getCachedImage('filename'));
	}


	public function testGetCachedImageNotFound(): void {
		$this->expectException(NotFoundException::class);

		$folder = $this->setupCacheFolder();
		$folder->expects($this->once())
			->method('getFile')
			->with('filename')
			->willThrowException(new NotFoundException());
		$image = $this->imageManager->getCachedImage('filename');
	}

	public function testSetCachedImage(): void {
		$folder = $this->setupCacheFolder();
		$file = $this->createMock(ISimpleFile::class);
		$folder->expects($this->once())
			->method('fileExists')
			->with('filename')
			->willReturn(true);
		$folder->expects($this->once())
			->method('getFile')
			->with('filename')
			->willReturn($file);
		$file->expects($this->once())
			->method('putContent')
			->with('filecontent');
		$this->assertEquals($file, $this->imageManager->setCachedImage('filename', 'filecontent'));
	}

	public function testSetCachedImageCreate(): void {
		$folder = $this->setupCacheFolder();
		$file = $this->createMock(ISimpleFile::class);
		$folder->expects($this->once())
			->method('fileExists')
			->with('filename')
			->willReturn(false);
		$folder->expects($this->once())
			->method('newFile')
			->with('filename')
			->willReturn($file);
		$file->expects($this->once())
			->method('putContent')
			->with('filecontent');
		$this->assertEquals($file, $this->imageManager->setCachedImage('filename', 'filecontent'));
	}

	private function setupCacheFolder() {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->rootFolder->expects($this->once())
			->method('getFolder')
			->with('0')
			->willReturn($folder);
		return $folder;
	}

	public function testCleanup(): void {
		$folders = [
			$this->createMock(ISimpleFolder::class),
			$this->createMock(ISimpleFolder::class),
			$this->createMock(ISimpleFolder::class)
		];
		foreach ($folders as $index => $folder) {
			$folder->expects($this->any())
				->method('getName')
				->willReturn("$index");
		}
		$folders[0]->expects($this->once())->method('delete');
		$folders[1]->expects($this->once())->method('delete');
		$folders[2]->expects($this->never())->method('delete');
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('2');
		$this->rootFolder->expects($this->once())
			->method('getDirectoryListing')
			->willReturn($folders);
		$this->rootFolder->expects($this->once())
			->method('getFolder')
			->with('2')
			->willReturn($folders[2]);
		$this->imageManager->cleanup();
	}


	public static function dataUpdateImage(): array {
		return [
			['background', __DIR__ . '/../../../tests/data/testimage.png', true, false],
			['background', __DIR__ . '/../../../tests/data/testimage.png', false, false],
			['background', __DIR__ . '/../../../tests/data/testimage.jpg', true, false],
			['background', __DIR__ . '/../../../tests/data/testimage.webp', true, false],
			['background', __DIR__ . '/../../../tests/data/testimage-large.jpg', true, true],
			['background', __DIR__ . '/../../../tests/data/testimage-wide.png', true, true],
			['logo', __DIR__ . '/../../../tests/data/testimagelarge.svg', true, false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataUpdateImage')]
	public function testUpdateImage(string $key, string $tmpFile, bool $folderExists, bool $shouldConvert): void {
		$file = $this->createMock(ISimpleFile::class);
		$folder = $this->createMock(ISimpleFolder::class);
		$oldFile = $this->createMock(ISimpleFile::class);
		$folder->expects($this->any())
			->method('getFile')
			->willReturn($oldFile);

		if ($folderExists) {
			$this->rootFolder
				->expects($this->any())
				->method('getFolder')
				->with('images')
				->willReturn($folder);
		} else {
			$this->rootFolder
				->expects($this->any())
				->method('getFolder')
				->with('images')
				->willThrowException(new NotFoundException());
			$this->rootFolder
				->expects($this->any())
				->method('newFolder')
				->with('images')
				->willReturn($folder);
		}

		$folder->expects($this->once())
			->method('newFile')
			->with($key)
			->willReturn($file);

		if ($shouldConvert) {
			$this->tempManager->expects($this->once())
				->method('getTemporaryFile')
				->willReturn('/tmp/randomtempfile-theming');
		}

		$this->imageManager->updateImage($key, $tmpFile);
	}

	public function testUnsupportedImageType(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Unsupported image type: text/plain');

		$file = $this->createMock(ISimpleFile::class);
		$folder = $this->createMock(ISimpleFolder::class);
		$oldFile = $this->createMock(ISimpleFile::class);

		$folder->expects($this->any())
			->method('getFile')
			->willReturn($oldFile);

		$this->rootFolder
			->expects($this->any())
			->method('getFolder')
			->with('images')
			->willReturn($folder);

		$folder->expects($this->once())
			->method('newFile')
			->with('favicon')
			->willReturn($file);

		$this->imageManager->updateImage('favicon', __DIR__ . '/../../../tests/data/lorem.txt');
	}
}

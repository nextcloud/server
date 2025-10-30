<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\Generator;
use OC\Preview\GeneratorHelper;
use OC\Preview\Storage\StorageFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IImage;
use OCP\IPreview;
use OCP\Preview\BeforePreviewFetchedEvent;
use OCP\Preview\IProviderV2;
use OCP\Preview\IVersionedPreviewFile;
use OCP\Snowflake\IGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

abstract class VersionedPreviewFile implements IVersionedPreviewFile, File {

}

class GeneratorTest extends TestCase {
	private IConfig&MockObject $config;
	private IPreview&MockObject $previewManager;
	private GeneratorHelper&MockObject $helper;
	private IEventDispatcher&MockObject $eventDispatcher;
	private Generator $generator;
	private LoggerInterface&MockObject $logger;
	private StorageFactory&MockObject $storageFactory;
	private PreviewMapper&MockObject $previewMapper;
	private IGenerator&MockObject $snowflakeGenerator;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->previewManager = $this->createMock(IPreview::class);
		$this->helper = $this->createMock(GeneratorHelper::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->previewMapper = $this->createMock(PreviewMapper::class);
		$this->storageFactory = $this->createMock(StorageFactory::class);
		$this->snowflakeGenerator = $this->createMock(IGenerator::class);

		$this->generator = new Generator(
			$this->config,
			$this->previewManager,
			$this->helper,
			$this->eventDispatcher,
			$this->logger,
			$this->previewMapper,
			$this->storageFactory,
			$this->snowflakeGenerator,
		);
	}

	private function getFile(int $fileId, string $mimeType, bool $hasVersion = false): File {
		$mountPoint = $this->createMock(IMountPoint::class);
		$mountPoint->method('getNumericStorageId')->willReturn(42);
		if ($hasVersion) {
			$file = $this->createMock(VersionedPreviewFile::class);
			$file->method('getPreviewVersion')->willReturn('abc');
		} else {
			$file = $this->createMock(File::class);
		}
		$file->method('isReadable')
			->willReturn(true);
		$file->method('getMimeType')
			->willReturn($mimeType);
		$file->method('getId')
			->willReturn($fileId);
		$file->method('getMountPoint')
			->willReturn($mountPoint);
		return $file;
	}

	#[TestWith([true])]
	#[TestWith([false])]
	public function testGetCachedPreview(bool $hasPreview): void {
		$file = $this->getFile(42, 'myMimeType', $hasPreview);

		$this->previewManager->method('isMimeSupported')
			->with($this->equalTo('myMimeType'))
			->willReturn(true);

		$maxPreview = new Preview();
		$maxPreview->setWidth(1000);
		$maxPreview->setHeight(1000);
		$maxPreview->setMax(true);
		$maxPreview->setSize(1000);
		$maxPreview->setCropped(false);
		$maxPreview->setStorageId(1);
		$maxPreview->setVersion($hasPreview ? 'abc' : null);
		$maxPreview->setMimeType('image/png');

		$previewFile = new Preview();
		$previewFile->setWidth(256);
		$previewFile->setHeight(256);
		$previewFile->setMax(false);
		$previewFile->setSize(1000);
		$previewFile->setVersion($hasPreview ? 'abc' : null);
		$previewFile->setCropped(false);
		$previewFile->setStorageId(1);
		$previewFile->setMimeType('image/png');

		$this->previewMapper->method('getAvailablePreviews')
			->with($this->equalTo([42]))
			->willReturn([42 => [
				$maxPreview,
				$previewFile,
			]]);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new BeforePreviewFetchedEvent($file, 100, 100, false, IPreview::MODE_FILL, null));

		$result = $this->generator->getPreview($file, 100, 100);
		$this->assertSame($hasPreview ? 'abc-256-256.png' : '256-256.png', $result->getName());
		$this->assertSame(1000, $result->getSize());
	}

	#[TestWith([true])]
	#[TestWith([false])]
	public function testGetNewPreview(bool $hasVersion): void {
		$file = $this->getFile(42, 'myMimeType', $hasVersion);

		$this->previewManager->method('isMimeSupported')
			->with($this->equalTo('myMimeType'))
			->willReturn(true);

		$this->previewMapper->method('getAvailablePreviews')
			->with($this->equalTo([42]))
			->willReturn([42 => []]);

		$this->config->method('getSystemValueString')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$this->config->method('getSystemValueInt')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$invalidProvider = $this->createMock(IProviderV2::class);
		$invalidProvider->method('isAvailable')
			->willReturn(true);
		$unavailableProvider = $this->createMock(IProviderV2::class);
		$unavailableProvider->method('isAvailable')
			->willReturn(false);
		$validProvider = $this->createMock(IProviderV2::class);
		$validProvider->method('isAvailable')
			->with($file)
			->willReturn(true);

		$this->previewManager->method('getProviders')
			->willReturn([
				'/image\/png/' => ['wrongProvider'],
				'/myMimeType/' => ['brokenProvider', 'invalidProvider', 'unavailableProvider', 'validProvider'],
			]);

		$this->helper->method('getProvider')
			->willReturnCallback(function ($provider) use ($invalidProvider, $validProvider, $unavailableProvider) {
				if ($provider === 'wrongProvider') {
					$this->fail('Wrongprovider should not be constructed!');
				} elseif ($provider === 'brokenProvider') {
					return false;
				} elseif ($provider === 'invalidProvider') {
					return $invalidProvider;
				} elseif ($provider === 'validProvider') {
					return $validProvider;
				} elseif ($provider === 'unavailableProvider') {
					return $unavailableProvider;
				}
				$this->fail('Unexpected provider requested');
			});

		$image = $this->createMock(IImage::class);
		$image->method('width')->willReturn(2048);
		$image->method('height')->willReturn(2048);
		$image->method('valid')->willReturn(true);
		$image->method('dataMimeType')->willReturn('image/png');

		$this->helper->method('getThumbnail')
			->willReturnCallback(function ($provider, $file, $x, $y) use ($invalidProvider, $validProvider, $image): false|IImage {
				if ($provider === $validProvider) {
					return $image;
				} else {
					return false;
				}
			});

		$image->method('data')
			->willReturn('my data');

		$this->previewMapper->method('insert')
			->willReturnCallback(fn (Preview $preview): Preview => $preview);

		$this->previewMapper->method('update')
			->willReturnCallback(fn (Preview $preview): Preview => $preview);

		$this->storageFactory->method('writePreview')
			->willReturnCallback(function (Preview $preview, mixed $data) use ($hasVersion): int {
				$data = stream_get_contents($data);
				if ($hasVersion) {
					switch ($preview->getName()) {
						case 'abc-2048-2048-max.png':
							$this->assertSame('my data', $data);
							return 1000;
						case 'abc-256-256.png':
							$this->assertSame('my resized data', $data);
							return 1000;
					}
				} else {
					switch ($preview->getName()) {
						case '2048-2048-max.png':
							$this->assertSame('my data', $data);
							return 1000;
						case '256-256.png':
							$this->assertSame('my resized data', $data);
							return 1000;
					}
				}
				$this->fail('file name is wrong:' . $preview->getName());
			});

		$image = $this->getMockImage(2048, 2048, 'my resized data');
		$this->helper->method('getImage')
			->willReturn($image);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new BeforePreviewFetchedEvent($file, 100, 100, false, IPreview::MODE_FILL, null));

		$result = $this->generator->getPreview($file, 100, 100);
		$this->assertSame($hasVersion ? 'abc-256-256.png' : '256-256.png', $result->getName());
		$this->assertSame(1000, $result->getSize());
	}

	public function testInvalidMimeType(): void {
		$this->expectException(NotFoundException::class);

		$file = $this->getFile(42, 'invalidType');

		$this->previewManager->method('isMimeSupported')
			->with('invalidType')
			->willReturn(false);

		$maxPreview = new Preview();
		$maxPreview->setWidth(2048);
		$maxPreview->setHeight(2048);
		$maxPreview->setMax(true);
		$maxPreview->setSize(1000);
		$maxPreview->setVersion(null);
		$maxPreview->setMimetype('image/png');

		$this->previewMapper->method('getAvailablePreviews')
			->with($this->equalTo([42]))
			->willReturn([42 => [
				$maxPreview,
			]]);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new BeforePreviewFetchedEvent($file, 1024, 512, true, IPreview::MODE_COVER, 'invalidType'));

		$this->generator->getPreview($file, 1024, 512, true, IPreview::MODE_COVER, 'invalidType');
	}

	public function testReturnCachedPreviewsWithoutCheckingSupportedMimetype(): void {
		$file = $this->getFile(42, 'myMimeType');

		$maxPreview = new Preview();
		$maxPreview->setWidth(2048);
		$maxPreview->setHeight(2048);
		$maxPreview->setMax(true);
		$maxPreview->setSize(1000);
		$maxPreview->setVersion(null);
		$maxPreview->setMimeType('image/png');

		$previewFile = new Preview();
		$previewFile->setWidth(1024);
		$previewFile->setHeight(512);
		$previewFile->setMax(false);
		$previewFile->setSize(1000);
		$previewFile->setCropped(true);
		$previewFile->setVersion(null);
		$previewFile->setMimeType('image/png');

		$this->previewMapper->method('getAvailablePreviews')
			->with($this->equalTo([42]))
			->willReturn([42 => [
				$maxPreview,
				$previewFile,
			]]);

		$this->previewManager->expects($this->never())
			->method('isMimeSupported');

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new BeforePreviewFetchedEvent($file, 1024, 512, true, IPreview::MODE_COVER, 'invalidType'));

		$result = $this->generator->getPreview($file, 1024, 512, true, IPreview::MODE_COVER, 'invalidType');
		$this->assertSame('1024-512-crop.png', $result->getName());
	}

	public function testNoProvider(): void {
		$file = $this->getFile(42, 'myMimeType');

		$this->previewMapper->method('getAvailablePreviews')
			->with($this->equalTo([42]))
			->willReturn([42 => []]);

		$this->previewManager->method('getProviders')
			->willReturn([]);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new BeforePreviewFetchedEvent($file, 100, 100, false, IPreview::MODE_FILL, null));

		$this->expectException(NotFoundException::class);
		$this->generator->getPreview($file, 100, 100);
	}

	private function getMockImage(int $width, int $height, string $data = '') {
		$image = $this->createMock(IImage::class);
		$image->method('height')->willReturn($width);
		$image->method('width')->willReturn($height);
		$image->method('valid')->willReturn(true);
		$image->method('dataMimeType')->willReturn('image/png');
		$image->method('data')->willReturn($data);

		$image->method('resizeCopy')->willReturnCallback(function ($size) use ($data) {
			return $this->getMockImage($size, $size, $data);
		});
		$image->method('preciseResizeCopy')->willReturnCallback(function ($width, $height) use ($data) {
			return $this->getMockImage($width, $height, $data);
		});
		$image->method('cropCopy')->willReturnCallback(function ($x, $y, $width, $height) use ($data) {
			return $this->getMockImage($width, $height, $data);
		});

		return $image;
	}

	public static function dataSize(): array {
		return [
			[1024, 2048, 512, 512, false, IPreview::MODE_FILL, 256, 512],
			[1024, 2048, 512, 512, false, IPreview::MODE_COVER, 512, 1024],
			[1024, 2048, 512, 512, true, IPreview::MODE_FILL, 1024, 1024],
			[1024, 2048, 512, 512, true, IPreview::MODE_COVER, 1024, 1024],

			[1024, 2048, -1, 512, false, IPreview::MODE_COVER, 256, 512],
			[1024, 2048, 512, -1, false, IPreview::MODE_FILL, 512, 1024],

			[1024, 2048, 250, 1100, true, IPreview::MODE_COVER, 256, 1126],
			[1024, 1100, 250, 1100, true, IPreview::MODE_COVER, 250, 1100],

			[1024, 2048, 4096, 2048, false, IPreview::MODE_FILL, 1024, 2048],
			[1024, 2048, 4096, 2048, false, IPreview::MODE_COVER, 1024, 2048],


			[2048, 1024, 512, 512, false, IPreview::MODE_FILL, 512, 256],
			[2048, 1024, 512, 512, false, IPreview::MODE_COVER, 1024, 512],
			[2048, 1024, 512, 512, true, IPreview::MODE_FILL, 1024, 1024],
			[2048, 1024, 512, 512, true, IPreview::MODE_COVER, 1024, 1024],

			[2048, 1024, -1, 512, false, IPreview::MODE_FILL, 1024, 512],
			[2048, 1024, 512, -1, false, IPreview::MODE_COVER, 512, 256],

			[2048, 1024, 4096, 1024, true, IPreview::MODE_FILL, 2048, 512],
			[2048, 1024, 4096, 1024, true, IPreview::MODE_COVER, 2048, 512],

			//Test minimum size
			[2048, 1024, 32, 32, false, IPreview::MODE_FILL, 64, 32],
			[2048, 1024, 32, 32, false, IPreview::MODE_COVER, 64, 32],
			[2048, 1024, 32, 32, true, IPreview::MODE_FILL, 64, 64],
			[2048, 1024, 32, 32, true, IPreview::MODE_COVER, 64, 64],
		];
	}

	#[DataProvider('dataSize')]
	public function testCorrectSize(int $maxX, int $maxY, int $reqX, int $reqY, bool $crop, string $mode, int $expectedX, int $expectedY): void {
		$file = $this->getFile(42, 'myMimeType');

		$this->previewManager->method('isMimeSupported')
			->with($this->equalTo('myMimeType'))
			->willReturn(true);

		$maxPreview = new Preview();
		$maxPreview->setWidth($maxX);
		$maxPreview->setHeight($maxY);
		$maxPreview->setMax(true);
		$maxPreview->setSize(1000);
		$maxPreview->setVersion(null);
		$maxPreview->setMimeType('image/png');

		$this->assertSame($maxPreview->getName(), $maxX . '-' . $maxY . '-max.png');
		$this->assertSame($maxPreview->getMimeType(), 'image/png');

		$this->previewMapper->method('getAvailablePreviews')
			->with($this->equalTo([42]))
			->willReturn([42 => [
				$maxPreview,
			]]);

		$filename = $expectedX . '-' . $expectedY;
		if ($crop) {
			$filename .= '-crop';
		}
		$filename .= '.png';

		$image = $this->getMockImage($maxX, $maxY);
		$this->helper->method('getImage')
			->willReturn($image);

		$this->previewMapper->method('insert')
			->willReturnCallback(function (Preview $preview) use ($filename): Preview {
				$this->assertSame($preview->getName(), $filename);
				return $preview;
			});

		$this->previewMapper->method('update')
			->willReturnCallback(fn (Preview $preview): Preview => $preview);

		$this->storageFactory->method('writePreview')
			->willReturn(1000);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new BeforePreviewFetchedEvent($file, $reqX, $reqY, $crop, $mode, null));

		$result = $this->generator->getPreview($file, $reqX, $reqY, $crop, $mode);
		if ($expectedX === $maxX && $expectedY === $maxY) {
			$this->assertSame($maxPreview->getName(), $result->getName());
		} else {
			$this->assertSame($filename, $result->getName());
		}
	}

	public function testUnreadbleFile(): void {
		$file = $this->createMock(File::class);
		$file->method('isReadable')
			->willReturn(false);

		$this->expectException(NotFoundException::class);

		$this->generator->getPreview($file, 100, 100, false);
	}
}

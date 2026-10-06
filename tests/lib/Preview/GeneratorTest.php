<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Core\AppInfo\ConfigLexicon;
use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\Generator;
use OC\Preview\GeneratorHelper;
use OC\Preview\PreviewMigrationService;
use OC\Preview\Storage\StorageFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IImage;
use OCP\IPreview;
use OCP\Preview\BeforePreviewFetchedEvent;
use OCP\Preview\IProviderV2;
use OCP\Preview\IVersionedPreviewFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Test\TestCase;

abstract class VersionedPreviewFile implements IVersionedPreviewFile, File {

}

class GeneratorTest extends TestCase {
	private Generator $generator;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->generator = $this->createInstanceWithMocks(Generator::class);
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

		$this->getAutoMock(IPreview::class)->method('isMimeSupported')
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

		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
			->with($this->equalTo([42]))
			->willReturn([42 => [
				$maxPreview,
				$previewFile,
			]]);

		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
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

		$this->getAutoMock(IPreview::class)->method('isMimeSupported')
			->with($this->equalTo('myMimeType'))
			->willReturn(true);

		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
			->with($this->equalTo([42]))
			->willReturn([42 => []]);

		$this->getAutoMock(IConfig::class)->method('getSystemValueString')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$this->getAutoMock(IConfig::class)->method('getSystemValueInt')
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

		$this->getAutoMock(IPreview::class)->method('getProviders')
			->willReturn([
				'/image\/png/' => ['wrongProvider'],
				'/myMimeType/' => ['brokenProvider', 'invalidProvider', 'unavailableProvider', 'validProvider'],
			]);

		$this->getAutoMock(GeneratorHelper::class)->method('getProvider')
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
		$image->method('data')->willReturn('my data');
		$image->method('resizeCopy')
			->willReturnCallback(fn (int $size): IImage => $this->getMockImage($size, $size, 'my resized data'));

		$this->getAutoMock(GeneratorHelper::class)->method('getThumbnail')
			->willReturnCallback(function ($provider, $file, $x, $y) use ($invalidProvider, $validProvider, $image): false|IImage {
				if ($provider === $validProvider) {
					return $image;
				} else {
					return false;
				}
			});

		$this->getAutoMock(PreviewMapper::class)->method('insert')
			->willReturnCallback(fn (Preview $preview): Preview => $preview);

		$this->getAutoMock(PreviewMapper::class)->method('update')
			->willReturnCallback(fn (Preview $preview): Preview => $preview);

		$this->getAutoMock(StorageFactory::class)->method('writePreview')
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

		// The generated max preview is reused, not decoded again
		$this->getAutoMock(GeneratorHelper::class)->expects($this->never())
			->method('getImage');

		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
			->method('dispatchTyped')
			->with(new BeforePreviewFetchedEvent($file, 100, 100, false, IPreview::MODE_FILL, null));

		$result = $this->generator->getPreview($file, 100, 100);
		$this->assertSame($hasVersion ? 'abc-256-256.png' : '256-256.png', $result->getName());
		$this->assertSame(1000, $result->getSize());
	}

	public function testMigrateOldPreview(): void {
		$file = $this->getFile(42, 'myMimeType', false);

		$maxPreview = new Preview();
		$maxPreview->setWidth(1000);
		$maxPreview->setHeight(1000);
		$maxPreview->setMax(true);
		$maxPreview->setSize(1000);
		$maxPreview->setCropped(false);
		$maxPreview->setStorageId(1);
		$maxPreview->setVersion(null);
		$maxPreview->setMimeType('image/png');

		$previewFile = new Preview();
		$previewFile->setWidth(256);
		$previewFile->setHeight(256);
		$previewFile->setMax(false);
		$previewFile->setSize(1000);
		$previewFile->setVersion(null);
		$previewFile->setCropped(false);
		$previewFile->setStorageId(1);
		$previewFile->setMimeType('image/png');

		$this->getAutoMock(IPreview::class)->method('isMimeSupported')
			->with($this->equalTo('myMimeType'))
			->willReturn(true);

		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
			->with($this->equalTo([42]))
			->willReturn([42 => []]);

		$this->getAutoMock(IConfig::class)->method('getSystemValueString')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$this->getAutoMock(IConfig::class)->method('getSystemValueInt')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$this->getAutoMock(IAppConfig::class)->method('getValueBool')
			->willReturnCallback(fn ($app, $key, $default) => match ($key) {
				ConfigLexicon::ON_DEMAND_PREVIEW_MIGRATION => true,
				'previewMovedDone' => false,
			});

		$this->getAutoMock(PreviewMigrationService::class)->expects($this->exactly(1))
			->method('migrateFileId')
			->willReturn([$maxPreview, $previewFile]);

		$result = $this->generator->getPreview($file, 100, 100);
		$this->assertSame('256-256.png', $result->getName());
		$this->assertSame(1000, $result->getSize());
	}

	public function testInvalidMimeType(): void {
		$this->expectException(NotFoundException::class);

		$file = $this->getFile(42, 'invalidType');

		$this->getAutoMock(IPreview::class)->method('isMimeSupported')
			->with('invalidType')
			->willReturn(false);

		$maxPreview = new Preview();
		$maxPreview->setWidth(2048);
		$maxPreview->setHeight(2048);
		$maxPreview->setMax(true);
		$maxPreview->setSize(1000);
		$maxPreview->setVersion(null);
		$maxPreview->setMimetype('image/png');

		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
			->with($this->equalTo([42]))
			->willReturn([42 => [
				$maxPreview,
			]]);

		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
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

		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
			->with($this->equalTo([42]))
			->willReturn([42 => [
				$maxPreview,
				$previewFile,
			]]);

		$this->getAutoMock(IPreview::class)->expects($this->never())
			->method('isMimeSupported');

		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
			->method('dispatchTyped')
			->with(new BeforePreviewFetchedEvent($file, 1024, 512, true, IPreview::MODE_COVER, 'invalidType'));

		$result = $this->generator->getPreview($file, 1024, 512, true, IPreview::MODE_COVER, 'invalidType');
		$this->assertSame('1024-512-crop.png', $result->getName());
	}

	public function testNoProvider(): void {
		$file = $this->getFile(42, 'myMimeType');

		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
			->with($this->equalTo([42]))
			->willReturn([42 => []]);

		$this->getAutoMock(IPreview::class)->method('getProviders')
			->willReturn([]);

		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
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

		$this->getAutoMock(IPreview::class)->method('isMimeSupported')
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

		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
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
		$this->getAutoMock(GeneratorHelper::class)->method('getImage')
			->willReturn($image);

		$this->getAutoMock(PreviewMapper::class)->method('insert')
			->willReturnCallback(function (Preview $preview) use ($filename): Preview {
				$this->assertSame($preview->getName(), $filename);
				return $preview;
			});

		$this->getAutoMock(PreviewMapper::class)->method('update')
			->willReturnCallback(fn (Preview $preview): Preview => $preview);

		$this->getAutoMock(StorageFactory::class)->method('writePreview')
			->willReturn(1000);

		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
			->method('dispatchTyped')
			->with(new BeforePreviewFetchedEvent($file, $reqX, $reqY, $crop, $mode, null));

		$result = $this->generator->getPreview($file, $reqX, $reqY, $crop, $mode);
		if ($expectedX === $maxX && $expectedY === $maxY) {
			$this->assertSame($maxPreview->getName(), $result->getName());
		} else {
			$this->assertSame($filename, $result->getName());
		}
	}

	#[TestWith([false, '256-256.png'])]
	#[TestWith([true, '2048-2048-max.png'])]
	public function testResizeFromSmallestCachedPreview(bool $cropped, string $expectedSource): void {
		$file = $this->getFile(42, 'myMimeType');

		$this->getAutoMock(IPreview::class)->method('isMimeSupported')
			->willReturn(true);

		$maxPreview = new Preview();
		$maxPreview->setWidth(2048);
		$maxPreview->setHeight(2048);
		$maxPreview->setMax(true);
		$maxPreview->setSize(1000);
		$maxPreview->setVersion(null);
		$maxPreview->setMimeType('image/png');

		$previews = [$maxPreview];
		foreach ([1024, 256] as $size) {
			$preview = new Preview();
			$preview->setWidth($size);
			$preview->setHeight($size);
			$preview->setMax(false);
			$preview->setSize(1000);
			$preview->setCropped($cropped);
			$preview->setVersion(null);
			$preview->setMimeType('image/png');
			$previews[] = $preview;
		}

		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
			->willReturn([42 => $previews]);

		$this->getAutoMock(GeneratorHelper::class)->expects($this->once())
			->method('getImage')
			->willReturnCallback(function (ISimpleFile $source) use ($expectedSource): IImage {
				$this->assertSame($expectedSource, $source->getName());
				return $this->getMockImage(256, 256);
			});

		$this->getAutoMock(PreviewMapper::class)->method('insert')
			->willReturnCallback(fn (Preview $preview): Preview => $preview);
		$this->getAutoMock(StorageFactory::class)->method('writePreview')
			->willReturn(1000);

		$result = $this->generator->getPreview($file, 32, 32);
		$this->assertSame('64-64.png', $result->getName());
	}

	#[TestWith([32, true, false, '64-64.png'])]
	#[TestWith([32, false, true, '64-64.png'])]
	#[TestWith([512, false, true, '1024-1024.png'])]
	public function testResizeFromScaledMaxPreview(int $size, bool $scaledAvailable, bool $expectFullDecode, string $expectedName): void {
		$file = $this->getFile(42, 'myMimeType');

		$this->getAutoMock(IPreview::class)->method('isMimeSupported')
			->willReturn(true);

		$maxPreview = new Preview();
		$maxPreview->setWidth(2048);
		$maxPreview->setHeight(2048);
		$maxPreview->setMax(true);
		$maxPreview->setSize(1000);
		$maxPreview->setVersion(null);
		$maxPreview->setMimeType('image/jpeg');

		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
			->willReturn([42 => [$maxPreview]]);

		$this->getAutoMock(GeneratorHelper::class)->expects($size === 32 ? $this->once() : $this->never())
			->method('getScaledImage')
			->with($this->anything(), 64, 64)
			->willReturn($scaledAvailable ? $this->getMockImage(256, 256) : null);
		$this->getAutoMock(GeneratorHelper::class)->expects($expectFullDecode ? $this->once() : $this->never())
			->method('getImage')
			->willReturn($this->getMockImage(2048, 2048));

		$this->getAutoMock(PreviewMapper::class)->method('insert')
			->willReturnCallback(fn (Preview $preview): Preview => $preview);
		$this->getAutoMock(StorageFactory::class)->method('writePreview')
			->willReturn(1000);

		$result = $this->generator->getPreview($file, $size, $size);
		$this->assertSame($expectedName, $result->getName());
	}

	#[TestWith(['image/png', false, true])]
	#[TestWith(['image/jpeg', false, false])]
	#[TestWith(['image/png', true, false])]
	public function testResizeWithResizingProvider(string $maxMimeType, bool $hasSmallerPreview, bool $expectProviderResult): void {
		$file = $this->getFile(42, 'myMimeType');

		$this->getAutoMock(IPreview::class)->method('isMimeSupported')
			->willReturn(true);

		$maxPreview = new Preview();
		$maxPreview->setWidth(2048);
		$maxPreview->setHeight(2048);
		$maxPreview->setMax(true);
		$maxPreview->setSize(1000);
		$maxPreview->setVersion(null);
		$maxPreview->setMimeType($maxMimeType);
		$previews = [$maxPreview];

		if ($hasSmallerPreview) {
			$smallerPreview = new Preview();
			$smallerPreview->setWidth(256);
			$smallerPreview->setHeight(256);
			$smallerPreview->setMax(false);
			$smallerPreview->setSize(1000);
			$smallerPreview->setCropped(false);
			$smallerPreview->setVersion(null);
			$smallerPreview->setMimeType($maxMimeType);
			$previews[] = $smallerPreview;
		}

		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
			->willReturn([42 => $previews]);

		$provider = $this->createMock(IProviderV2::class);
		$provider->method('isAvailable')->willReturn(true);
		$this->getAutoMock(IPreview::class)->method('getProviders')
			->willReturn(['/.*/' => ['provider']]);
		$this->getAutoMock(GeneratorHelper::class)->method('getProvider')
			->willReturn($provider);
		$this->getAutoMock(GeneratorHelper::class)->method('resizesEfficiently')
			->with($provider)
			->willReturn(true);

		$this->getAutoMock(GeneratorHelper::class)->expects($hasSmallerPreview ? $this->never() : $this->once())
			->method('getThumbnail')
			->with($provider, $file, 64, 64, false)
			->willReturn($this->getMockImage(64, 64, 'provider data'));
		$this->getAutoMock(GeneratorHelper::class)->expects($expectProviderResult ? $this->never() : $this->once())
			->method('getImage')
			->willReturn($this->getMockImage(256, 256, 'resized data'));

		$this->getAutoMock(PreviewMapper::class)->method('insert')
			->willReturnCallback(fn (Preview $preview): Preview => $preview);
		$this->getAutoMock(StorageFactory::class)->method('writePreview')
			->willReturnCallback(function (Preview $preview, mixed $data) use ($expectProviderResult): int {
				$this->assertSame($expectProviderResult ? 'provider data' : 'resized data', stream_get_contents($data));
				return 1000;
			});

		$result = $this->generator->getPreview($file, 32, 32);
		$this->assertSame('64-64.png', $result->getName());
	}

	private function getJpegFile(int $width, int $height, ?int $orientation = null): File {
		ob_start();
		imagejpeg(imagecreatetruecolor($width, $height));
		$jpeg = ob_get_clean();
		if ($orientation !== null) {
			// EXIF segment with only the orientation
			$tiff = "II*\0" . pack('V', 8) . pack('v', 1) . pack('vvVvv', 0x0112, 3, 1, $orientation, 0) . pack('V', 0);
			$jpeg = substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', 2 + 6 + strlen($tiff)) . "Exif\0\0" . $tiff . substr($jpeg, 2);
		}

		$file = $this->getFile(42, 'image/jpeg');
		$file->method('fopen')->willReturnCallback(function () use ($jpeg) {
			$stream = fopen('php://memory', 'r+');
			fwrite($stream, $jpeg);
			rewind($stream);
			return $stream;
		});
		return $file;
	}

	#[TestWith([2000, 1500, null, false, 64, 48, '64-48.png'])]
	#[TestWith([2000, 1500, null, true, 86, 64, '64-64-crop.png'])]
	#[TestWith([2000, 1500, 6, false, 48, 64, '48-64.png'])]
	public function testLazyMaxPreview(int $width, int $height, ?int $orientation, bool $crop, int $expectedBoxWidth, int $expectedBoxHeight, string $expectedName): void {
		$file = $this->getJpegFile($width, $height, $orientation);

		$this->getAutoMock(IPreview::class)->method('isMimeSupported')
			->willReturn(true);
		$this->getAutoMock(IConfig::class)->method('getSystemValueInt')
			->willReturnCallback(fn ($key, $default) => $default);
		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
			->willReturn([42 => []]);

		$provider = $this->createMock(IProviderV2::class);
		$provider->method('isAvailable')->willReturn(true);
		$this->getAutoMock(IPreview::class)->method('getProviders')
			->willReturn(['/image\/jpeg/' => ['provider']]);
		$this->getAutoMock(GeneratorHelper::class)->method('getProvider')
			->willReturn($provider);

		$this->getAutoMock(GeneratorHelper::class)->expects($this->once())
			->method('getThumbnail')
			->with($provider, $file, $expectedBoxWidth, $expectedBoxHeight)
			->willReturn($this->getMockImage($expectedBoxHeight, $expectedBoxWidth));

		$this->getAutoMock(PreviewMapper::class)->expects($this->once())
			->method('insert')
			->willReturnCallback(function (Preview $preview): Preview {
				$this->assertFalse($preview->isMax());
				return $preview;
			});
		$this->getAutoMock(StorageFactory::class)->method('writePreview')
			->willReturn(1000);

		$result = $this->generator->getPreview($file, 32, 32, $crop);
		$this->assertSame($expectedName, $result->getName());
	}

	#[TestWith([-1, -1])]
	#[TestWith([1000, 1000])]
	public function testLazyMaxPreviewGeneratedWhenRequested(int $requestedWidth, int $requestedHeight): void {
		$file = $this->getJpegFile(2000, 1000);

		$this->getAutoMock(IConfig::class)->method('getSystemValueInt')
			->willReturnCallback(fn ($key, $default) => in_array($key, ['preview_max_x', 'preview_max_y'], true) ? 1000 : $default);
		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
			->willReturn([42 => []]);

		$provider = $this->createMock(IProviderV2::class);
		$provider->method('isAvailable')->willReturn(true);
		$this->getAutoMock(IPreview::class)->method('getProviders')
			->willReturn(['/image\/jpeg/' => ['provider']]);
		$this->getAutoMock(GeneratorHelper::class)->method('getProvider')
			->willReturn($provider);

		$this->getAutoMock(GeneratorHelper::class)->expects($this->once())
			->method('getThumbnail')
			->with($provider, $file, 1000, 1000)
			->willReturn($this->getMockImage(500, 1000, 'max data'));

		$this->getAutoMock(PreviewMapper::class)->method('insert')
			->willReturnCallback(fn (Preview $preview): Preview => $preview);
		$this->getAutoMock(StorageFactory::class)->method('writePreview')
			->willReturn(1000);

		$result = $this->generator->getPreview($file, $requestedWidth, $requestedHeight);
		$this->assertSame('1000-500-max.png', $result->getName());
	}

	public function testLazyMaxPreviewCachedPreview(): void {
		$file = $this->getJpegFile(2000, 1500);

		$this->getAutoMock(IConfig::class)->method('getSystemValueInt')
			->willReturnCallback(fn ($key, $default) => $default);

		$cachedPreview = new Preview();
		$cachedPreview->setWidth(64);
		$cachedPreview->setHeight(48);
		$cachedPreview->setMax(false);
		$cachedPreview->setSize(1000);
		$cachedPreview->setCropped(false);
		$cachedPreview->setVersion(null);
		$cachedPreview->setMimeType('image/webp');
		$this->getAutoMock(PreviewMapper::class)->method('getAvailablePreviews')
			->willReturn([42 => [$cachedPreview]]);

		$this->getAutoMock(GeneratorHelper::class)->expects($this->never())
			->method('getThumbnail');
		$this->getAutoMock(PreviewMapper::class)->expects($this->never())
			->method('insert');

		$result = $this->generator->getPreview($file, 32, 32);
		$this->assertSame('64-48.webp', $result->getName());
	}

	public function testUnreadbleFile(): void {
		$file = $this->createMock(File::class);
		$file->method('isReadable')
			->willReturn(false);

		$this->expectException(NotFoundException::class);

		$this->generator->getPreview($file, 100, 100, false);
	}
}

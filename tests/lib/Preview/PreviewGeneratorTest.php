<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\Generator;
use OCP\Files\File;
use OCP\IImage;
use OCP\IPreview;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[CoversClass(Generator::class)]
#[Group('Preview')]
class PreviewGeneratorTest extends TestCase {

	/** @var Generator|\PHPUnit\Framework\MockObject\MockObject */
	private $generator;

	protected function setUp(): void {
		parent::setUp();

		// Note: guardWithSemaphore and unguardWithSemaphore are static.
		// In Nextcloud's test environment, they gracefully bypass or use local memory.
		$this->generator = $this->getMockBuilder(Generator::class)
			->disableOriginalConstructor()
			->onlyMethods(['getNumConcurrentPreviews', 'savePreview'])
			->getMock();

		$this->generator->method('getNumConcurrentPreviews')->willReturn(1);
	}

	private function invokePrivateMethod(string $methodName, array $parameters): mixed {
		$method = new \ReflectionMethod(Generator::class, $methodName);
		$method->setAccessible(true);
		return $method->invokeArgs($this->generator, $parameters);
	}

	public static function calculateSizeProvider(): array {
		return [
			'zero max dimensions' => [100, 100, false, IPreview::MODE_FILL, 0, 0, [0, 0]],
			'both minus one' => [-1, -1, false, IPreview::MODE_COVER, 1920, 1080, [1920, 1080]],
			// Snap to power of 4 alters requested dimensions proportionally
			'width minus one' => [-1, 600, false, IPreview::MODE_FILL, 1920, 1080, [1820, 1024]],
			'height minus one' => [800, -1, false, IPreview::MODE_FILL, 1920, 1080, [1024, 576]],
			'clamp to max square' => [3000, 3000, false, IPreview::MODE_FILL, 1920, 1080, [1080, 1080]],
			'respect crop mode' => [800, 800, true, IPreview::MODE_COVER, 1920, 1080, [1024, 1024]],
		];
	}

	#[DataProvider('calculateSizeProvider')]
	public function testCalculateSize(
		int $width, int $height, bool $crop, string $mode,
		int $maxWidth, int $maxHeight, array $expected
	): void {
		$result = $this->invokePrivateMethod('calculateSize', [
			$width, $height, $crop, $mode, $maxWidth, $maxHeight
		]);
		$this->assertEquals($expected, $result, 'calculateSize logic failed for given dataset');
	}

	public function testGeneratePreviewThrowsOnZeroTargetDimensions(): void {
		$file = $this->createMock(File::class);

		$image = $this->createMock(IImage::class);
		$image->method('valid')->willReturn(true);
		$image->method('width')->willReturn(800);
		$image->method('height')->willReturn(600);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Target preview dimensions must be strictly positive');

		$this->invokePrivateMethod('generatePreview', [
			$file, $image, 0, 0, false, 1000, 1000, null, false
		]);
	}

	public function testGeneratePreviewThrowsOnInvalidImage(): void {
		$file = $this->createMock(File::class);

		$image = $this->createMock(IImage::class);
		$image->method('valid')->willReturn(false);

		// Defensive stubbing: prevents mock errors if the short-circuit
		// order in `if (!$preview->valid() || $preview->width() <= 0 ...)` changes.
		$image->method('width')->willReturn(0);
		$image->method('height')->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Failed to generate preview, invalid image dimensions');

		$this->invokePrivateMethod('generatePreview', [
			$file, $image, 256, 256, false, 1000, 1000, null, false
		]);
	}


	public function testGeneratePreviewThrowsOnInvalidImageDimensions(): void {
		$file = $this->createMock(File::class);

		$image = $this->createMock(IImage::class);
		$image->method('valid')->willReturn(true);
		$image->method('width')->willReturn(0); // Invalid
		$image->method('height')->willReturn(600);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Failed to generate preview, invalid image dimensions');

		$this->invokePrivateMethod('generatePreview', [
			$file, $image, 256, 256, false, 1000, 1000, null, false
		]);
	}

	public function testGeneratePreviewThrowsOnUnpersistedFile(): void {
		$file = $this->createMock(File::class);
		// Unpersisted file (no ID)
		$file->method('getId')->willReturn(null);

		$image = $this->createMock(IImage::class);
		$image->method('valid')->willReturn(true);
		$image->method('width')->willReturn(800);
		$image->method('height')->willReturn(600);
		$image->method('resizeCopy')->willReturnSelf();

		// dataMimeType() must return a valid string to reach the getId() guard
		$image->method('dataMimeType')->willReturn('image/jpeg');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Cannot generate preview for an unpersisted file (null ID)');

		// $crop = false prevents premature execution of preciseResizeCopy()
		$this->invokePrivateMethod('generatePreview', [
			$file, $image, 256, 256, false, 1000, 1000, 'v1', true
		]);
	}

	public function testGeneratePreviewThrowsOnEmptyMime(): void {
		$file = $this->createMock(File::class);

		$image = $this->createMock(IImage::class);
		$image->method('valid')->willReturn(true);
		$image->method('width')->willReturn(800);
		$image->method('height')->willReturn(600);
		$image->method('resizeCopy')->willReturnSelf();

		// Empty or invalid MimeType returned by the image provider
		$image->method('dataMimeType')->willReturn('');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Preview generation failed: invalid or empty MIME type');

		$this->invokePrivateMethod('generatePreview', [
			$file, $image, 256, 256, false, 1000, 1000, null, false
		]);
	}
}

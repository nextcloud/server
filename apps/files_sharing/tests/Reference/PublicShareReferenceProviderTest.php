<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Reference;

use OCA\Files_Sharing\Reference\PublicShareReferenceProvider;
use OCP\Files\File;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\NotFoundException;
use OCP\IPreview;
use OCP\IURLGenerator;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PublicShareReferenceProviderTest extends TestCase {
	private IURLGenerator&MockObject $urlGenerator;
	private IMimeTypeDetector&MockObject $mimeTypeDetector;
	private IPreview&MockObject $previewManager;
	private IManager&MockObject $shareManager;
	private PublicShareReferenceProvider $provider;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->mimeTypeDetector = $this->createMock(IMimeTypeDetector::class);
		$this->previewManager = $this->createMock(IPreview::class);
		$this->shareManager = $this->createMock(IManager::class);

		$this->urlGenerator->method('getAbsoluteURL')
			->willReturnCallback(static fn (string $url): string => 'https://cloud.example.com' . $url);

		$this->provider = new PublicShareReferenceProvider(
			$this->urlGenerator,
			$this->mimeTypeDetector,
			$this->previewManager,
			$this->shareManager,
		);
	}

	public static function dataMatchReference(): array {
		return [
			['https://cloud.example.com/s/abc123def', true],
			['https://cloud.example.com/s/abc123def/sub/folder', true],
			['https://cloud.example.com/s/abc123def?dir=/test&openfile=5', true],
			['https://cloud.example.com/index.php/s/abc123def', true],
			['https://cloud.example.com/index.php/s/abc123def/file.txt', true],
			['https://other.example.com/s/abc123def', false],
			['https://cloud.example.com/apps/files/?dir=/test', false],
			['https://cloud.example.com/index.php/f/1234', false],
			['https://cloud.example.com/something/else', false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataMatchReference')]
	public function testMatchReference(string $reference, bool $expected): void {
		$this->assertSame($expected, $this->provider->matchReference($reference));
	}

	public function testResolveReference(): void {
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn('test.txt');
		$file->method('getSize')->willReturn(1234);
		$file->method('getMimetype')->willReturn('text/plain');
		$file->method('getMimeType')->willReturn('text/plain');
		$file->method('getMTime')->willReturn(1699999999);

		$share = $this->createMock(IShare::class);
		$share->method('getNode')->willReturn($file);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('abc123def')
			->willReturn($share);

		$this->previewManager->method('isMimeSupported')
			->with('text/plain')
			->willReturn(true);
		$this->previewManager->method('isAvailable')
			->with($file)
			->willReturn(true);

		$this->urlGenerator->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('files_sharing.PublicPreview.getPreview', $this->callback(
				static fn (array $params): bool => $params['token'] === 'abc123def'
					&& $params['x'] === 1600
					&& $params['y'] === 630
			))
			->willReturn('https://cloud.example.com/index.php/apps/files_sharing/publicpreview/abc123def');

		$reference = $this->provider->resolveReferencePublic(
			'https://cloud.example.com/s/abc123def',
			'pagetoken'
		);

		$this->assertNotNull($reference);
		$this->assertTrue($reference->getAccessible());
		$this->assertSame('test.txt', $reference->getTitle());
		$this->assertSame('text/plain', $reference->getDescription());
		$this->assertSame('https://cloud.example.com/index.php/apps/files_sharing/publicpreview/abc123def', $reference->getImageUrl());

		$richObject = $reference->getRichObject();
		$this->assertSame('file', $reference->getRichObjectType());
		$this->assertSame('abc123def', $richObject['id']);
		$this->assertSame('test.txt', $richObject['name']);
		$this->assertSame('1234', $richObject['size']);
		$this->assertSame('abc123def', $richObject['path']);
		$this->assertSame('text/plain', $richObject['mimetype']);
		$this->assertSame('1699999999', $richObject['mtime']);
		$this->assertSame('yes', $richObject['preview-available']);
		$this->assertSame('yes', $richObject['is-public-link']);
	}

	public function testResolveReferenceAuthenticatedPath(): void {
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn('test.txt');
		$file->method('getSize')->willReturn(1234);
		$file->method('getMimetype')->willReturn('text/plain');
		$file->method('getMimeType')->willReturn('text/plain');
		$file->method('getMTime')->willReturn(1699999999);

		$share = $this->createMock(IShare::class);
		$share->method('getNode')->willReturn($file);

		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('abc123def')
			->willReturn($share);

		$this->previewManager->method('isMimeSupported')->willReturn(false);
		$this->previewManager->method('isAvailable')->willReturn(false);
		$this->mimeTypeDetector->method('mimeTypeIcon')
			->willReturn('https://cloud.example.com/core/img/filetypes/file.svg');

		$reference = $this->provider->resolveReference('https://cloud.example.com/s/abc123def');

		$this->assertNotNull($reference);
		$this->assertTrue($reference->getAccessible());
		$this->assertSame('no', $reference->getRichObject()['preview-available']);
	}

	public function testResolveReferenceInvalidToken(): void {
		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('invalidtoken')
			->willThrowException(new ShareNotFound());

		$this->expectException(ShareNotFound::class);
		$this->provider->resolveReferencePublic(
			'https://cloud.example.com/s/invalidtoken',
			'pagetoken'
		);
	}

	public function testResolveReferenceMissingNode(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getNode')->willThrowException(new NotFoundException());

		$this->shareManager->method('getShareByToken')
			->willReturn($share);

		$reference = $this->provider->resolveReferencePublic(
			'https://cloud.example.com/s/abc123def',
			'pagetoken'
		);

		$this->assertNotNull($reference);
		$this->assertFalse($reference->getAccessible());
		$this->assertSame('file', $reference->getRichObjectType());
	}

	public function testResolveReferenceNoMatch(): void {
		$this->shareManager->expects($this->never())->method('getShareByToken');

		$this->assertNull($this->provider->resolveReferencePublic(
			'https://cloud.example.com/apps/files/?dir=/',
			'pagetoken'
		));
	}

	public function testGetCachePrefix(): void {
		$this->assertSame('abc123def', $this->provider->getCachePrefix('https://cloud.example.com/s/abc123def'));
		$this->assertSame('abc123def', $this->provider->getCachePrefix('https://cloud.example.com/s/abc123def/sub/dir'));
		$this->assertSame('abc123def', $this->provider->getCachePrefix('https://cloud.example.com/index.php/s/abc123def'));
		$this->assertSame('', $this->provider->getCachePrefix('https://cloud.example.com/index.php/f/1234'));
	}

	public function testGetCacheKey(): void {
		$this->assertNull($this->provider->getCacheKey('https://cloud.example.com/s/abc123def'));
	}

	public function testGetCacheKeyPublic(): void {
		$this->assertSame('pagetoken', $this->provider->getCacheKeyPublic(
			'https://cloud.example.com/s/abc123def',
			'pagetoken'
		));
	}
}

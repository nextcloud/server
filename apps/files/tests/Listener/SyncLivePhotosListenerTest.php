<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Listener;

use OC\Files\View;
use OCA\Files\Listener\SyncLivePhotosListener;
use OCA\Files\Service\LivePhotosService;
use OCP\Files\Cache\CacheEntryRemovedEvent;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use OCP\FilesMetadata\IFilesMetadataManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

final class SyncLivePhotosListenerTest extends TestCase {
	private Folder&MockObject $userFolder;
	private LivePhotosService&MockObject $livePhotosService;
	private SyncLivePhotosListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->userFolder = $this->createMock(Folder::class);
		$this->livePhotosService = $this->createMock(LivePhotosService::class);

		$this->listener = new SyncLivePhotosListener(
			$this->userFolder,
			$this->createMock(IFilesMetadataManager::class),
			$this->livePhotosService,
			$this->createMock(IRootFolder::class),
			$this->createMock(View::class),
		);
	}

	private function createCacheEvent(int $fileId, int $storageId, string $path): CacheEntryRemovedEvent {
		return new CacheEntryRemovedEvent($this->createMock(IStorage::class), $path, $fileId, $storageId);
	}

	private function createPeer(int $storageId, string $internalPath): File&MockObject {
		$cacheEntry = $this->createMock(ICacheEntry::class);
		$cacheEntry->method('getStorageId')->willReturn($storageId);

		$peer = $this->createMock(File::class);
		$peer->method('getData')->willReturn($cacheEntry);
		$peer->method('getInternalPath')->willReturn($internalPath);
		return $peer;
	}

	public function testCacheEntryRemovedDeletesPeer(): void {
		$this->livePhotosService->method('getLivePhotoPeerId')
			->with(1)
			->willReturn(11);

		$peer = $this->createPeer(10, 'files/photos/IMG.mov');
		$peer->expects($this->once())->method('delete');
		$this->userFolder->method('getFirstNodeById')
			->with(11)
			->willReturn($peer);

		$this->listener->handle($this->createCacheEvent(1, 10, 'files/photos/IMG.jpg'));
	}

	public function testCacheEntryRemovedIgnoresNonLivePhoto(): void {
		$this->livePhotosService->method('getLivePhotoPeerId')
			->willReturn(null);
		$this->userFolder->expects($this->never())->method('getFirstNodeById');

		$this->listener->handle($this->createCacheEvent(1, 10, 'files/photos/IMG.jpg'));
	}

	public static function dataCacheEntryRemovedKeepsPeer(): array {
		return [
			'other folder' => [10, 'files/videos/IMG.mov'],
			'other folder with the same name' => [10, 'files/other/photos/IMG.mov'],
			'other storage' => [20, 'files/photos/IMG.mov'],
		];
	}

	#[DataProvider('dataCacheEntryRemovedKeepsPeer')]
	public function testCacheEntryRemovedKeepsPeer(int $peerStorageId, string $peerPath): void {
		$this->livePhotosService->method('getLivePhotoPeerId')
			->willReturn(11);

		$peer = $this->createPeer($peerStorageId, $peerPath);
		$peer->expects($this->never())->method('delete');
		$this->userFolder->method('getFirstNodeById')->willReturn($peer);

		$this->listener->handle($this->createCacheEvent(1, 10, 'files/photos/IMG.jpg'));
	}

	public function testCacheEntryRemovedKeepsPeerThatIsBeingMoved(): void {
		$this->livePhotosService->method('getLivePhotoPeerId')
			->willReturn(11);
		$this->userFolder->expects($this->never())->method('getFirstNodeById');

		self::invokePrivate($this->listener, 'pendingRenames', [[11]]);
		$this->listener->handle($this->createCacheEvent(1, 10, 'files/IMG.jpg'));
	}
}

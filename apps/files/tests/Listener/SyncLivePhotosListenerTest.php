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
use OCP\Files\Cache\CacheEntriesRemovedEvent;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Cache\ICacheEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\FilesMetadata\IFilesMetadataManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

final class SyncLivePhotosListenerTest extends TestCase {
	private Folder&MockObject $userFolder;
	private LivePhotosService&MockObject $livePhotosService;
	private SyncLivePhotosListener $listener;

	#[\Override]
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

	private function createCacheEvent(int $fileId, int $storageId, string $path): ICacheEvent {
		$entry = $this->createMock(ICacheEvent::class);
		$entry->method('getFileId')->willReturn($fileId);
		$entry->method('getStorageId')->willReturn($storageId);
		$entry->method('getPath')->willReturn($path);
		return $entry;
	}

	private function createPeer(int $storageId, string $internalPath): File&MockObject {
		$cacheEntry = $this->createMock(ICacheEntry::class);
		$cacheEntry->method('getStorageId')->willReturn($storageId);

		$peer = $this->createMock(File::class);
		$peer->method('getData')->willReturn($cacheEntry);
		$peer->method('getInternalPath')->willReturn($internalPath);
		return $peer;
	}

	public function testCacheEntriesRemovedDeletesPeersAfterMissingOne(): void {
		$entries = [
			$this->createCacheEvent(1, 10, 'files/IMG_1.jpg'),
			$this->createCacheEvent(2, 10, 'files/IMG_2.jpg'),
		];

		$this->livePhotosService->method('getLivePhotoPeerIds')
			->with([1, 2])
			->willReturn([1 => 11, 2 => 12]);

		$peer = $this->createPeer(10, 'files/IMG_2.mov');
		$peer->expects($this->once())->method('delete');

		$this->userFolder->method('getFirstNodeById')
			->willReturnMap([
				[11, null],
				[12, $peer],
			]);

		$this->listener->handle(new CacheEntriesRemovedEvent($entries));
	}

	public static function dataCacheEntriesRemovedKeepsPeer(): array {
		return [
			'other folder' => [10, 'files/videos/IMG.mov'],
			'other folder with the same name' => [10, 'files/other/photos/IMG.mov'],
			'other storage' => [20, 'files/photos/IMG.mov'],
		];
	}

	#[DataProvider('dataCacheEntriesRemovedKeepsPeer')]
	public function testCacheEntriesRemovedKeepsPeer(int $peerStorageId, string $peerPath): void {
		$this->livePhotosService->method('getLivePhotoPeerIds')
			->willReturn([1 => 11]);

		$peer = $this->createPeer($peerStorageId, $peerPath);
		$peer->expects($this->never())->method('delete');
		$this->userFolder->method('getFirstNodeById')->willReturn($peer);

		$this->listener->handle(new CacheEntriesRemovedEvent([
			$this->createCacheEvent(1, 10, 'files/photos/IMG.jpg'),
		]));
	}

	public function testCacheEntriesRemovedKeepsPeerThatIsBeingMoved(): void {
		$this->livePhotosService->method('getLivePhotoPeerIds')
			->willReturn([1 => 11]);
		$this->userFolder->expects($this->never())->method('getFirstNodeById');

		self::invokePrivate($this->listener, 'pendingRenames', [[11]]);
		$this->listener->handle(new CacheEntriesRemovedEvent([
			$this->createCacheEvent(1, 10, 'files/IMG.jpg'),
		]));
	}
}

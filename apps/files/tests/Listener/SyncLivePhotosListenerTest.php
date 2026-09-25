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
use OCP\Files\Cache\ICacheEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\FilesMetadata\IFilesMetadataManager;
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

	public function testCacheEntriesRemovedDeletesPeersAfterMissingOne(): void {
		$entries = array_map(function (int $fileId): ICacheEvent {
			$entry = $this->createMock(ICacheEvent::class);
			$entry->method('getFileId')->willReturn($fileId);
			return $entry;
		}, [1, 2]);

		$this->livePhotosService->method('getLivePhotoPeerIds')
			->with([1, 2])
			->willReturn([11, 12]);

		$peer = $this->createMock(File::class);
		$peer->expects($this->once())->method('delete');

		$this->userFolder->method('getFirstNodeById')
			->willReturnMap([
				[11, null],
				[12, $peer],
			]);

		$this->listener->handle(new CacheEntriesRemovedEvent($entries));
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Listener;

use OCA\Files_Sharing\Activity\Providers\Downloads;
use OCA\Files_Sharing\Listener\BeforeNodeReadListener;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as IActivityManager;
use OCP\Files\Events\Node\BeforeNodeReadEvent;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Storage\ISharedStorage;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IRequest;
use OCP\ISession;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BeforeNodeReadListenerTest extends TestCase {
	private IRootFolder&MockObject $rootFolder;
	private IActivityManager&MockObject $activityManager;
	private BeforeNodeReadListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->activityManager = $this->createMock(IActivityManager::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn($this->createMock(ICache::class));

		$this->listener = new BeforeNodeReadListener(
			$this->createMock(ISession::class),
			$this->rootFolder,
			$this->activityManager,
			$this->createMock(IRequest::class),
			$cacheFactory,
		);
	}

	private function getUserFolder(string $uid, string $filePath): IUserFolder&MockObject {
		$node = $this->createMock(File::class);
		$node->method('getPath')->willReturn('/' . $uid . '/files' . $filePath);

		$folder = $this->createMock(IUserFolder::class);
		$folder->method('getFirstNodeById')->with(42)->willReturn($node);
		$folder->method('getRelativePath')
			->willReturnCallback(fn (string $path): ?string => str_starts_with($path, '/' . $uid . '/files/') ? substr($path, strlen('/' . $uid . '/files')) : null);

		return $folder;
	}

	public function testReshareLinkDownloadUsesOwnerPath(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getShareOwner')->willReturn('owner');
		$share->method('getSharedBy')->willReturn('sharee');

		$storage = $this->createMock(ISharedStorage::class);
		$storage->method('instanceOfStorage')->willReturn(true);
		$storage->method('getShare')->willReturn($share);

		$node = $this->createMock(File::class);
		$node->method('getId')->willReturn(42);
		$node->method('getPath')->willReturn('/sharee/files/Shared/Sub/file.pdf');
		$node->method('getStorage')->willReturn($storage);

		$this->rootFolder->method('getUserFolder')->willReturnMap([
			['sharee', $this->getUserFolder('sharee', '/Shared/Sub/file.pdf')],
			['owner', $this->getUserFolder('owner', '/F/Sub/file.pdf')],
		]);

		$published = [];
		$this->activityManager->method('generateEvent')
			->willReturnCallback(function () use (&$published) {
				$event = $this->createMock(IEvent::class);
				$event->method('setApp')->willReturnSelf();
				$event->method('setType')->willReturnSelf();
				$event->method('setSubject')->willReturnCallback(function (string $subject, array $parameters) use ($event, &$published) {
					$published[] = [$subject, $parameters[0]];
					return $event;
				});
				$event->method('setAffectedUser')->willReturnSelf();
				$event->method('setObject')->willReturnSelf();
				return $event;
			});

		$this->listener->handle(new BeforeNodeReadEvent($node));

		$this->assertSame([
			[Downloads::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED, '/Shared/Sub/file.pdf'],
			[Downloads::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED, '/F/Sub/file.pdf'],
		], $published);
	}
}

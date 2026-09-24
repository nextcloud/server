<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests;

use OCA\Files_Sharing\Listener\SharesUpdatedListener;
use OCP\Constants;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\Event\UserMountAddedEvent;
use OCP\Files\Config\Event\UserMountRemovedEvent;
use OCP\Server;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Apps like recognize keep per-user data in sync with file access by listening to
 * UserMountAddedEvent and UserMountRemovedEvent. These events have to be dispatched
 * for the recipients of a share when it is created or deleted, at the latest when a
 * recipient sets up their file system afterwards, regardless of whether the share
 * mounts of the recipient are updated immediately or deferred until then.
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ShareMountEventsTest extends TestCase {
	private const FOLDER = 'shared-folder';

	private IEventDispatcher $eventDispatcher;
	/** @var list<Event> */
	private array $events = [];
	/** @var \Closure(Event): void */
	private \Closure $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->eventDispatcher = Server::get(IEventDispatcher::class);
		$this->listener = function (Event $event): void {
			$this->events[] = $event;
		};
		$this->eventDispatcher->addListener(UserMountAddedEvent::class, $this->listener);
		$this->eventDispatcher->addListener(UserMountRemovedEvent::class, $this->listener);

		$this->rootFolder->getUserFolder(self::TEST_FILES_SHARING_API_USER1)->newFolder(self::FOLDER);

		// The recipient has used their file system before
		$this->setupFileSystem(self::TEST_FILES_SHARING_API_USER2);
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->events = [];
	}

	protected function tearDown(): void {
		$this->eventDispatcher->removeListener(UserMountAddedEvent::class, $this->listener);
		$this->eventDispatcher->removeListener(UserMountRemovedEvent::class, $this->listener);
		Server::get(SharesUpdatedListener::class)->setCutOffMarkTime(-1);

		parent::tearDown();
	}

	/**
	 * @return array<string, array{int, string, float}>
	 */
	public static function shareProvider(): array {
		// A cutoff time of -1 updates the share mounts of recipients immediately,
		// 0 marks recipients so their share mounts are updated on their next setup
		return [
			'user share, immediate update' => [IShare::TYPE_USER, self::TEST_FILES_SHARING_API_USER2, -1],
			'user share, deferred update' => [IShare::TYPE_USER, self::TEST_FILES_SHARING_API_USER2, 0],
			'group share, immediate update' => [IShare::TYPE_GROUP, self::TEST_FILES_SHARING_API_GROUP1, -1],
			'group share, deferred update' => [IShare::TYPE_GROUP, self::TEST_FILES_SHARING_API_GROUP1, 0],
		];
	}

	#[DataProvider(methodName: 'shareProvider')]
	public function testMountAddedEventForCreatedShare(int $shareType, string $recipient, float $cutOffTime): void {
		Server::get(SharesUpdatedListener::class)->setCutOffMarkTime($cutOffTime);

		$this->share($shareType, self::FOLDER, self::TEST_FILES_SHARING_API_USER1, $recipient, Constants::PERMISSION_ALL);
		$this->setupFileSystem(self::TEST_FILES_SHARING_API_USER2);

		$this->assertEquals(
			['/' . self::TEST_FILES_SHARING_API_USER2 . '/files/' . self::FOLDER . '/'],
			$this->getMountPointsOfEvents(UserMountAddedEvent::class, self::TEST_FILES_SHARING_API_USER2),
			'A UserMountAddedEvent should be dispatched for the share mount of the recipient',
		);
	}

	#[DataProvider(methodName: 'shareProvider')]
	public function testMountRemovedEventForDeletedShare(int $shareType, string $recipient, float $cutOffTime): void {
		$share = $this->share($shareType, self::FOLDER, self::TEST_FILES_SHARING_API_USER1, $recipient, Constants::PERMISSION_ALL);
		$this->setupFileSystem(self::TEST_FILES_SHARING_API_USER2);
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->events = [];

		Server::get(SharesUpdatedListener::class)->setCutOffMarkTime($cutOffTime);
		$this->shareManager->deleteShare($share);
		$this->setupFileSystem(self::TEST_FILES_SHARING_API_USER2);

		$this->assertEquals(
			['/' . self::TEST_FILES_SHARING_API_USER2 . '/files/' . self::FOLDER . '/'],
			$this->getMountPointsOfEvents(UserMountRemovedEvent::class, self::TEST_FILES_SHARING_API_USER2),
			'A UserMountRemovedEvent should be dispatched for the share mount of the recipient',
		);
	}

	/**
	 * Log in as the user and access their files, as a new request by them would
	 */
	private function setupFileSystem(string $userId): void {
		$this->loginHelper($userId);
		$this->rootFolder->getUserFolder($userId)->getDirectoryListing();
	}

	/**
	 * @param class-string<UserMountAddedEvent|UserMountRemovedEvent> $eventClass
	 * @return list<string>
	 */
	private function getMountPointsOfEvents(string $eventClass, string $userId): array {
		$mountPoints = [];
		foreach ($this->events as $event) {
			if ($event instanceof $eventClass && $event->mountPoint->getUser()->getUID() === $userId) {
				$mountPoints[] = $event->mountPoint->getMountPoint();
			}
		}
		return $mountPoints;
	}
}

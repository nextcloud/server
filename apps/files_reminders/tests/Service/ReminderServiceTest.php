<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\Tests\Service;

use DateTime;
use OCA\FilesReminders\Db\Reminder;
use OCA\FilesReminders\Db\ReminderMapper;
use OCA\FilesReminders\Service\ReminderService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Cache\CappedMemoryCache;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Node;
use OCP\ICacheFactory;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ReminderServiceTest extends TestCase {
	private ReminderMapper&MockObject $reminderMapper;
	private IRootFolder&MockObject $root;
	private IUser&MockObject $user;
	private ReminderService $service;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->reminderMapper = $this->createMock(ReminderMapper::class);
		$this->root = $this->createMock(IRootFolder::class);
		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('alice');

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createInMemory')->willReturnCallback(fn (int $capacity = 512) => new CappedMemoryCache($capacity));

		$this->service = new ReminderService(
			$this->createMock(IUserManager::class),
			$this->createMock(IURLGenerator::class),
			$this->createMock(INotificationManager::class),
			$this->reminderMapper,
			$this->root,
			$this->createMock(LoggerInterface::class),
			$cacheFactory,
		);
	}

	private function createReminder(int $fileId, DateTime $dueDate): Reminder {
		$reminder = new Reminder();
		$reminder->setUserId('alice');
		$reminder->setFileId($fileId);
		$reminder->setDueDate($dueDate);
		return $reminder;
	}

	/**
	 * @param Reminder[] $reminders
	 */
	private function preloadFolder(int $childCount, array $reminders): void {
		$children = array_map(function (int $fileId): Node {
			$node = $this->createMock(Node::class);
			$node->method('getId')->willReturn($fileId);
			return $node;
		}, range(1, $childCount));

		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willReturn($children);
		$this->reminderMapper->method('findAllInFolder')->willReturn($reminders);

		$this->service->cacheFolder($this->user, $folder);
	}

	private function allowNodeAccess(): void {
		$userFolder = $this->createMock(IUserFolder::class);
		$userFolder->method('getFirstNodeById')->willReturn($this->createMock(Node::class));
		$this->root->method('getUserFolder')->willReturn($userFolder);
	}

	/**
	 * A DAV listing preloads the reminders of the whole folder, then asks for
	 * each child. The per-file cache only holds 512 entries, so the preload of
	 * a larger folder must not depend on it: no child may fall back to a query.
	 */
	public function testCacheFolderCoversFoldersLargerThanTheMemoryCache(): void {
		$this->preloadFolder(2000, [$this->createReminder(1500, new DateTime('+1 day'))]);

		$this->reminderMapper->expects($this->never())->method('findDueForUser');

		$found = [];
		for ($fileId = 1; $fileId <= 2000; $fileId++) {
			if ($this->service->getDueForUser($this->user, $fileId, false) !== null) {
				$found[] = $fileId;
			}
		}
		$this->assertSame([1500], $found);
	}

	/**
	 * Files outside any preloaded folder are still looked up one by one,
	 * and a missing reminder is cached so the query only runs once.
	 */
	public function testUncachedFileFallsBackToTheMapper(): void {
		$this->preloadFolder(10, []);

		$this->reminderMapper->expects($this->once())
			->method('findDueForUser')
			->with($this->user, 42)
			->willThrowException(new DoesNotExistException(''));

		$this->assertNull($this->service->getDueForUser($this->user, 42, false));
		// The miss is cached too
		$this->assertNull($this->service->getDueForUser($this->user, 42, false));
	}

	/**
	 * A preloaded reminder can already be past due. It must not be returned,
	 * and dropping it from the preload means the next lookup asks the database.
	 */
	public function testExpiredPreloadedReminderIsNotReturned(): void {
		$this->preloadFolder(600, [$this->createReminder(7, new DateTime('-1 hour'))]);

		$this->reminderMapper->expects($this->once())
			->method('findDueForUser')
			->with($this->user, 7)
			->willThrowException(new DoesNotExistException(''));

		$this->assertNull($this->service->getDueForUser($this->user, 7, false));
		$this->assertNull($this->service->getDueForUser($this->user, 7, false));
	}

	/**
	 * The preload says "no reminder" for every child without one. Creating a
	 * reminder later in the same request must replace that answer.
	 */
	public function testCreateAfterPreloadIsReturned(): void {
		$this->allowNodeAccess();
		$this->preloadFolder(600, []);
		$this->reminderMapper->expects($this->once())->method('insert');

		$dueDate = new DateTime('+2 days');
		$this->assertTrue($this->service->createOrUpdate($this->user, 300, $dueDate));

		$reminder = $this->service->getDueForUser($this->user, 300, false);
		$this->assertNotNull($reminder);
		$this->assertEquals($dueDate, $reminder->getDueDate());
	}

	/**
	 * Updating a preloaded reminder must return the new due date,
	 * not the one loaded with the folder.
	 */
	public function testUpdateAfterPreloadIsReturned(): void {
		$this->allowNodeAccess();
		$this->preloadFolder(600, [$this->createReminder(300, new DateTime('+1 day'))]);
		$this->reminderMapper->expects($this->once())->method('update');

		$dueDate = new DateTime('+5 days');
		$this->assertFalse($this->service->createOrUpdate($this->user, 300, $dueDate));

		$reminder = $this->service->getDueForUser($this->user, 300, false);
		$this->assertNotNull($reminder);
		$this->assertEquals($dueDate, $reminder->getDueDate());
	}

	/**
	 * Removing a preloaded reminder must hide it for the rest of the request,
	 * without querying the database again.
	 */
	public function testRemoveAfterPreloadIsNotReturned(): void {
		$this->allowNodeAccess();
		$this->preloadFolder(600, [$this->createReminder(300, new DateTime('+1 day'))]);
		$this->reminderMapper->expects($this->once())->method('delete');
		$this->reminderMapper->expects($this->never())->method('findDueForUser');

		$this->service->remove($this->user, 300);

		$this->assertNull($this->service->getDueForUser($this->user, 300, false));
	}
}

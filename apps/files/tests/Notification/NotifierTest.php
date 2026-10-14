<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Notification;

use OCA\Files\BackgroundJob\TransferOwnership as TransferOwnershipJob;
use OCA\Files\Db\TransferOwnership;
use OCA\Files\Db\TransferOwnershipMapper;
use OCA\Files\Notification\Notifier;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
final class NotifierTest extends TestCase {
	private const SOURCE_USER = 'transfer-source-notifier';

	private IJobList&MockObject $jobList;
	private IEventDispatcher&MockObject $eventDispatcher;
	private TransferOwnershipMapper $mapper;
	private Notifier $notifier;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$notificationManager = $this->createMock(IManager::class);
		$notificationManager->method('createNotification')
			->willReturnCallback(fn () => $this->createMock(INotification::class));
		$this->jobList = $this->createMock(IJobList::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->mapper = Server::get(TransferOwnershipMapper::class);
		$this->mapper->deleteBy(['sourceUser' => self::SOURCE_USER]);

		$this->notifier = new Notifier(
			$this->createMock(IFactory::class),
			$this->createMock(IURLGenerator::class),
			$this->mapper,
			$notificationManager,
			$this->createMock(IUserManager::class),
			$this->jobList,
			$this->createMock(ITimeFactory::class),
			$this->eventDispatcher,
		);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->mapper->deleteBy(['sourceUser' => self::SOURCE_USER]);
		parent::tearDown();
	}

	private function createRequestNotification(int $transferId): INotification&MockObject {
		$notification = $this->createMock(INotification::class);
		$notification->method('getApp')->willReturn('files');
		$notification->method('getSubject')->willReturn('transferownershipRequest');
		$notification->method('getObjectId')->willReturn((string)$transferId);
		return $notification;
	}

	private function insertTransfer(): TransferOwnership {
		$transfer = new TransferOwnership();
		$transfer->sourceUser = self::SOURCE_USER;
		$transfer->targetUser = 'transfer-target-notifier';
		$transfer->fileId = 42;
		$transfer->nodeName = 'folder';
		return $this->mapper->insert($transfer);
	}

	public function testDismissRequestLogsRejection(): void {
		$transfer = $this->insertTransfer();
		$this->jobList->method('has')->with(TransferOwnershipJob::class, ['id' => $transfer->id])->willReturn(false);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool
				=> $event->getOperation() === 'files.ownership_transfer.rejected'
				&& $event->getLogMessage() === 'Ownership transfer "%s" from user "%s" to user "%s" was rejected'
				&& $event->getParameters() === [
					'transferId' => $transfer->id,
					'sourceUser' => self::SOURCE_USER,
					'targetUser' => 'transfer-target-notifier',
				]));

		$this->notifier->dismissNotification($this->createRequestNotification($transfer->id));

		$this->expectException(DoesNotExistException::class);
		$this->mapper->getById($transfer->id);
	}

	public function testDismissAcceptedRequestDoesNotLog(): void {
		$transfer = $this->insertTransfer();
		$this->jobList->method('has')->willReturn(true);
		$this->eventDispatcher->expects($this->never())->method('dispatchTyped');

		$this->notifier->dismissNotification($this->createRequestNotification($transfer->id));
	}
}

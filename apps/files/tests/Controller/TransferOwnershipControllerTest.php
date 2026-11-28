<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Controller;

use OCA\Files\Controller\TransferOwnershipController;
use OCA\Files\Db\TransferOwnership;
use OCA\Files\Db\TransferOwnershipMapper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\IHomeStorage;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Storage\IStorage;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Notification\IManager as NotificationManager;
use OCP\Notification\INotification;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
final class TransferOwnershipControllerTest extends TestCase {
	private const SOURCE_USER = 'transfer-source-audit';
	private const TARGET_USER = 'transfer-target-audit';

	private NotificationManager&MockObject $notificationManager;
	private IUserManager&MockObject $userManager;
	private IRootFolder&MockObject $rootFolder;
	private IEventDispatcher&MockObject $eventDispatcher;
	private TransferOwnershipMapper $mapper;
	private TransferOwnershipController $controller;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->notificationManager = $this->createMock(NotificationManager::class);
		$this->notificationManager->method('createNotification')
			->willReturnCallback(fn () => $this->createMock(INotification::class));
		$this->userManager = $this->createMock(IUserManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->mapper = Server::get(TransferOwnershipMapper::class);
		$this->resetDB();

		$this->controller = new TransferOwnershipController(
			'files',
			$this->createMock(IRequest::class),
			$this->notificationManager,
			$this->createMock(ITimeFactory::class),
			$this->createMock(IJobList::class),
			$this->mapper,
			$this->userManager,
			$this->rootFolder,
			$this->eventDispatcher,
		);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->resetDB();
		parent::tearDown();
	}

	private function resetDB(): void {
		$this->mapper->deleteBy(['sourceUser' => self::SOURCE_USER]);
	}

	private function createUser(string $uid): IUser&MockObject {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		return $user;
	}

	private function insertTransfer(): TransferOwnership {
		$transfer = new TransferOwnership();
		$transfer->sourceUser = self::SOURCE_USER;
		$transfer->targetUser = self::TARGET_USER;
		$transfer->fileId = 42;
		$transfer->nodeName = 'folder';
		return $this->mapper->insert($transfer);
	}

	private function expectAuditEvent(string $operation, string $message, callable $parameters): void {
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool
				=> $event->getOperation() === $operation
				&& $event->getLogMessage() === $message
				&& $event->getParameters() === $parameters()));
	}

	public function testTransferLogsRequest(): void {
		$source = $this->createUser(self::SOURCE_USER);
		$this->userManager->method('get')->with(self::TARGET_USER)->willReturn($this->createUser(self::TARGET_USER));

		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')->with(IHomeStorage::class)->willReturn(true);
		$node = $this->createMock(Folder::class);
		$node->method('getOwner')->willReturn($source);
		$node->method('getStorage')->willReturn($storage);
		$node->method('getId')->willReturn(42);
		$node->method('getName')->willReturn('folder');
		$userFolder = $this->createMock(IUserFolder::class);
		$userFolder->method('get')->with('/folder')->willReturn($node);
		$this->rootFolder->method('getUserFolder')->with(self::SOURCE_USER)->willReturn($userFolder);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(function (CriticalActionPerformedEvent $event): bool {
				$parameters = $event->getParameters();
				$this->assertSame('files.ownership_transfer.requested', $event->getOperation());
				$this->assertSame('Ownership transfer "%s" from user "%s" to user "%s" was requested for "%s" (file ID "%s")', $event->getLogMessage());
				$this->assertSame(['transferId', 'sourceUser', 'targetUser', 'path', 'fileId'], array_keys($parameters));
				$this->assertSame(self::SOURCE_USER, $parameters['sourceUser']);
				$this->assertSame(self::TARGET_USER, $parameters['targetUser']);
				$this->assertSame('/folder', $parameters['path']);
				$this->assertSame(42, $parameters['fileId']);
				$this->assertSame(self::SOURCE_USER, $this->mapper->getById($parameters['transferId'])->sourceUser);
				return true;
			}));

		$response = $this->controller->transfer($source, self::TARGET_USER, '/folder');
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
	}

	public function testTransferForbiddenDoesNotLog(): void {
		$this->userManager->method('get')->willReturn(null);
		$this->eventDispatcher->expects($this->never())->method('dispatchTyped');

		$response = $this->controller->transfer($this->createUser(self::SOURCE_USER), 'unknown', '/folder');
		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	public function testAcceptLogsAcceptance(): void {
		$transfer = $this->insertTransfer();

		$this->expectAuditEvent(
			'files.ownership_transfer.accepted',
			'Ownership transfer "%s" from user "%s" to user "%s" was accepted',
			fn () => ['transferId' => $transfer->id, 'sourceUser' => self::SOURCE_USER, 'targetUser' => self::TARGET_USER],
		);

		$response = $this->controller->accept($this->createUser(self::TARGET_USER), $transfer->id);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
	}

	public function testAcceptByOtherUserDoesNotLog(): void {
		$transfer = $this->insertTransfer();
		$this->eventDispatcher->expects($this->never())->method('dispatchTyped');

		$response = $this->controller->accept($this->createUser('someone-else'), $transfer->id);
		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function testRejectLogsRejection(): void {
		$transfer = $this->insertTransfer();

		$this->expectAuditEvent(
			'files.ownership_transfer.rejected',
			'Ownership transfer "%s" from user "%s" to user "%s" was rejected',
			fn () => ['transferId' => $transfer->id, 'sourceUser' => self::SOURCE_USER, 'targetUser' => self::TARGET_USER],
		);

		$response = $this->controller->reject($this->createUser(self::TARGET_USER), $transfer->id);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
	}
}

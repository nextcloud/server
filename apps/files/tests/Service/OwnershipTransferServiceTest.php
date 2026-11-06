<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Service;

use OCA\Files\Service\OwnershipTransferService;
use OCP\Encryption\IManager as IEncryptionManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Server;
use OCP\Share\IManager as IShareManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
final class OwnershipTransferServiceTest extends TestCase {
	private IUser $sourceUser;
	private IUser $targetUser;
	private IEventDispatcher&MockObject $eventDispatcher;
	private OwnershipTransferService $service;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$userManager = Server::get(IUserManager::class);
		$this->sourceUser = $userManager->createUser(static::getUniqueID('transfer-source'), 'test');
		$this->targetUser = $userManager->createUser(static::getUniqueID('transfer-target'), 'test');
		// Object storage reports unlimited space as SPACE_UNLIMITED, which the quota check does not accept
		$this->targetUser->setQuota('1 GB');

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->service = new OwnershipTransferService(
			Server::get(IEncryptionManager::class),
			Server::get(IShareManager::class),
			Server::get(IMountManager::class),
			Server::get(IUserMountCache::class),
			$userManager,
			Server::get(IFactory::class),
			Server::get(IRootFolder::class),
			$this->eventDispatcher,
			Server::get(IDBConnection::class),
		);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->sourceUser->delete();
		$this->targetUser->delete();
		parent::tearDown();
	}

	public function testTransferLogsCriticalAction(): void {
		$this->loginAsUser($this->sourceUser->getUID());
		Server::get(IRootFolder::class)
			->getUserFolder($this->sourceUser->getUID())
			->newFolder('transfer')
			->newFile('a.txt', 'content');

		/** @var list<Event> $events */
		$events = [];
		$this->eventDispatcher->method('dispatchTyped')
			->willReturnCallback(function (Event $event) use (&$events): void {
				$events[] = $event;
			});

		$this->service->transfer($this->sourceUser, $this->targetUser, 'transfer', null, true);

		$auditEvents = array_values(array_filter($events, static fn (Event $event): bool => $event instanceof CriticalActionPerformedEvent));
		$this->assertCount(1, $auditEvents);
		$this->assertSame('files.ownership.transferred', $auditEvents[0]->getOperation());
		$this->assertSame('Ownership of "%s" was transferred from user "%s" to user "%s" into "%s"', $auditEvents[0]->getLogMessage());
		$this->assertSame([
			'path' => 'transfer',
			'sourceUser' => $this->sourceUser->getUID(),
			'targetUser' => $this->targetUser->getUID(),
			'targetPath' => $this->targetUser->getUID() . '/files/',
			'move' => true,
		], $auditEvents[0]->getParameters());
	}
}

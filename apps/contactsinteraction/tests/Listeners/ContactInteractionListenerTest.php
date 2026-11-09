<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ContactsInteraction\Tests\Listeners;

use OCA\ContactsInteraction\Db\CardSearchDao;
use OCA\ContactsInteraction\Db\RecentContactMapper;
use OCA\ContactsInteraction\Listeners\ContactInteractionListener;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Contacts\Events\ContactInteractedWithEvent;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\Import\IImportScope;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ContactInteractionListenerTest extends TestCase {
	private RecentContactMapper&MockObject $mapper;
	private CardSearchDao&MockObject $cardSearchDao;
	private IImportScope&MockObject $importScope;
	private ContactInteractionListener $listener;

	protected function setUp(): void {
		parent::setUp();
		$this->mapper = $this->createMock(RecentContactMapper::class);
		$this->cardSearchDao = $this->createMock(CardSearchDao::class);
		$this->importScope = $this->createMock(IImportScope::class);
		$this->listener = new ContactInteractionListener(
			$this->mapper,
			$this->cardSearchDao,
			$this->createMock(IUserManager::class),
			$this->createMock(IDBConnection::class),
			$this->createMock(ITimeFactory::class),
			$this->createMock(IL10N::class),
			$this->createMock(LoggerInterface::class),
			$this->importScope,
		);
	}

	private function event(): ContactInteractedWithEvent {
		$actor = $this->createMock(IUser::class);
		$actor->method('getUID')->willReturn('alice');
		return (new ContactInteractedWithEvent($actor))->setUid('bob');
	}

	public function testAnImportIsNoInteraction(): void {
		$this->importScope->method('isActive')->willReturn(true);
		$this->cardSearchDao->expects($this->never())->method('findExisting');
		$this->mapper->expects($this->never())->method('findMatch');
		$this->mapper->expects($this->never())->method('insert');

		$this->listener->handle($this->event());
	}

	public function testAnInteractionOutsideAnImportIsRecorded(): void {
		$this->importScope->method('isActive')->willReturn(false);
		$this->cardSearchDao->expects($this->once())->method('findExisting')->willReturn('BEGIN:VCARD');
		$this->mapper->expects($this->never())->method('insert');

		$this->listener->handle($this->event());
	}
}

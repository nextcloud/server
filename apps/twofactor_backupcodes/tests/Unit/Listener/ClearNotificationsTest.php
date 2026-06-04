<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Unit\Listener;

use OCA\TwoFactorBackupCodes\Event\CodesGenerated;
use OCA\TwoFactorBackupCodes\Listener\ClearNotifications;
use OCP\IUser;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ClearNotificationsTest extends TestCase {
	private IManager&MockObject $notificationManager;
	private ClearNotifications $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->notificationManager = $this->createMock(IManager::class);
		$this->notificationManager->method('createNotification')
			->willReturn(Server::get(IManager::class)->createNotification());

		$this->listener = new ClearNotifications($this->notificationManager);
	}

	public function testHandleCodesGeneratedEvent(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('fritz');
		$event = new CodesGenerated($user);

		$this->notificationManager->expects($this->once())
			->method('markProcessed')
			->with($this->callback(function (INotification $n) {
				return $n->getUser() === 'fritz'
					&& $n->getApp() === 'twofactor_backupcodes'
					&& $n->getObjectType() === 'create'
					&& $n->getObjectId() === 'codes';
			}));

		$this->listener->handle($event);
	}
}

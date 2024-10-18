<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Listener;

use OCA\TwoFactorBackupCodes\Event\CodesGenerated;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Notification\IManager;

/** @template-implements IEventListener<CodesGenerated> */
class ClearNotifications implements IEventListener {

	public function __construct(
		private IManager $manager,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof CodesGenerated)) {
			return;
		}

		$notification = $this->manager->createNotification();
		$notification->setApp('twofactor_backupcodes')
			->setUser($event->getUser()->getUID())
			->setObject('create', 'codes');
		$this->manager->markProcessed($notification);
	}
}

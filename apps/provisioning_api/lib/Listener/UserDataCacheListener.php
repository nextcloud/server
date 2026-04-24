<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Provisioning_API\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserDeletedEvent;

/**
 * Busts the provisioning API user data cache whenever a user is modified or deleted.
 *
 * @template-implements IEventListener<UserChangedEvent|UserDeletedEvent|PasswordUpdatedEvent>
 */
class UserDataCacheListener implements IEventListener {

	private ICache $cache;

	public function __construct(ICacheFactory $cacheFactory) {
		$this->cache = $cacheFactory->createDistributed('provisioning_api');
	}

	public function handle(Event $event): void {
		if ($event instanceof UserChangedEvent) {
			$uid = $event->getUser()->getUID();
		} elseif ($event instanceof UserDeletedEvent) {
			$uid = $event->getUser()->getUID();
		} elseif ($event instanceof PasswordUpdatedEvent) {
			$uid = $event->getUser()->getUID();
		} else {
			return;
		}

		// Clear all cached variants for this user (admin, noadmin, scoped, etc.)
		$this->cache->clear('user_data_' . $uid);
	}
}

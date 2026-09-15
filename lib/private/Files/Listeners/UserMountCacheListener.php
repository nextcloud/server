<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace lib\private\Files\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Config\IUserMountCache;
use OCP\User\Events\UserDeletedEvent;
use Override;

/**
 * Listen to hooks and update the mount cache as needed
 *
 * @template-implements IEventListener<UserDeletedEvent>
 */
class UserMountCacheListener implements IEventListener {
	public function __construct(
		private IUserMountCache $userMountCache,
	) {
	}

	#[Override]
	public function handle(Event $event): void {
		if (!$event instanceof UserDeletedEvent) {
			return;
		}

		$this->userMountCache->removeUserMounts($event->getUser());
	}
}

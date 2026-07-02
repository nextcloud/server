<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\User\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\IRootFolder;
use OCP\User\Events\UserChangedEvent;

/**
 * @template-implements IEventListener<UserChangedEvent>
 */
class UserQuotaChangedListener implements IEventListener {
	public function __construct(
		private IRootFolder $rootFolder,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof UserChangedEvent) {
			return;
		}

		if ($event->getFeature() !== 'quota') {
			return;
		}

		try {
			$userFolder = $this->rootFolder->getUserFolder($event->getUser()->getUID());
			$userFolder->getStorage()->getCache()->update(
				$userFolder->getId(),
				['etag' => uniqid()]
			);
		} catch (\Throwable) {
			// Non-fatal: best-effort ETag invalidation.
			// Stale quota corrects itself on the client's next full sync.
		}
	}
}

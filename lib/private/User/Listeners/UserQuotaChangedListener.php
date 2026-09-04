<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\User\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\IRootFolder;
use OCP\User\Events\UserChangedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<UserChangedEvent>
 */
class UserQuotaChangedListener implements IEventListener {
	public function __construct(
		private IRootFolder $rootFolder,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
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
		} catch (\Throwable $e) {
			// Non-fatal: best-effort ETag invalidation.
			// Stale quota corrects itself on the client's next full sync.
			$this->logger->warning('Failed to invalidate user root etag after quota change', [
				'user' => $event->getUser()->getUID(),
				'exception' => $e,
			]);
		}
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DirectEditing\Listeners;

use OC\DirectEditing\Manager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @template-implements IEventListener<UserDeletedEvent>
 */
class UserDeletedTokenCleanupListener implements IEventListener {
	public function __construct(
		private Manager $manager,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			// Unrelated
			return;
		}

		/**
		 * Catch any exception during this process as any failure here shouldn't block the
		 * user deletion.
		 */
		try {
			$uid = $event->getUser()->getUID();
			$this->manager->invalidateTokensForUser($uid);
		} catch (Throwable $e) {
			$this->logger->error('Could not clean up direct editing tokens after user deletion: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}
}

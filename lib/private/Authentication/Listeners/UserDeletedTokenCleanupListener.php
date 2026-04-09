<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Listeners;

use OC\Authentication\Token\Manager;
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
			$tokens = $this->manager->getTokenByUser($uid);
			foreach ($tokens as $token) {
				$this->manager->invalidateTokenById($uid, $token->getId());
			}
		} catch (Throwable $e) {
			$this->logger->error('Could not clean up auth tokens after user deletion: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}
}

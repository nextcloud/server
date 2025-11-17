<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\User\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\NotFoundException;
use OCP\IAvatarManager;
use OCP\Security\ICredentialsManager;
use OCP\User\Events\BeforeUserDeletedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<BeforeUserDeletedEvent>
 */
class BeforeUserDeletedListener implements IEventListener {
	public function __construct(
		private LoggerInterface $logger,
		private IAvatarManager $avatarManager,
		private ICredentialsManager $credentialsManager,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeUserDeletedEvent)) {
			return;
		}

		$user = $event->getUser();

		// Delete avatar on user deletion
		try {
			$avatar = $this->avatarManager->getAvatar($user->getUID());
			$avatar->remove(true);
		} catch (NotFoundException $e) {
			// no avatar to remove
		} catch (\Exception $e) {
			// Ignore exceptions
			$this->logger->info('Could not cleanup avatar of ' . $user->getUID(), [
				'exception' => $e,
			]);
		}
		// Delete storages credentials on user deletion
		$this->credentialsManager->erase($user->getUID());
	}
}

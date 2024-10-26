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
use OCP\User\Events\UserChangedEvent;

/**
 * @template-implements IEventListener<UserChangedEvent>
 */
class UserChangedListener implements IEventListener {
	private IAvatarManager $avatarManager;

	public function __construct(IAvatarManager $avatarManager) {
		$this->avatarManager = $avatarManager;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserChangedEvent)) {
			return;
		}
		
		$user = $event->getUser();
		$feature = $event->getFeature();
		$oldValue = $event->getOldValue();
		$value = $event->getValue();

		// We only change the avatar on display name changes
		if ($feature === 'displayName') {
			try {
				$avatar = $this->avatarManager->getAvatar($user->getUID());
				$avatar->userChanged($feature, $oldValue, $value);
			} catch (NotFoundException $e) {
				// no avatar to remove
			}
		}
	}
}

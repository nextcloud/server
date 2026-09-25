<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Listener;

use OCP\Accounts\UserUpdatedEvent;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserChangedEvent;

/**
 * Uploading or removing an avatar bumps it already. The avatar scope lives in
 * the account, so switching to v2-private has to invalidate too, and a disabled
 * account starts serving a guest avatar without touching the avatar at all.
 *
 * Bumping on any account change rather than diffing the avatar scope, because
 * AccountManager only dispatches this when something actually changed. Editing
 * an unrelated profile field then costs one needless refetch per viewer holding
 * that avatar.
 *
 * @template-implements IEventListener<UserUpdatedEvent|UserChangedEvent>
 */
class AvatarVersionListener implements IEventListener {
	public function __construct(
		private IUserConfig $userConfig,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		$accountChanged = $event instanceof UserUpdatedEvent;
		$enabledChanged = $event instanceof UserChangedEvent && $event->getFeature() === 'enabled';
		if (!$accountChanged && !$enabledChanged) {
			return;
		}

		$userId = $event->getUser()->getUID();
		$this->userConfig->setValueInt($userId, 'avatar', 'version',
			$this->userConfig->getValueInt($userId, 'avatar', 'version') + 1);
	}
}

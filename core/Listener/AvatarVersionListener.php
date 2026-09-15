<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Listener;

use OC\Avatar\AvatarVersion;
use OCP\Accounts\UserUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserChangedEvent;

/**
 * Bumps the avatar version when something outside the avatar itself changes
 * what a viewer would be served.
 *
 * Uploading or removing an avatar bumps it already. The avatar scope lives in
 * the account, so switching to v2-private has to invalidate too, and a disabled
 * account starts serving a guest avatar without touching the avatar at all.
 *
 * Bumping on any account change rather than diffing the scope: AccountManager
 * only dispatches this when something actually changed. Editing an unrelated
 * profile field then costs every viewer one avatar refetch, which is cheaper
 * than carrying a second copy of the scope around to compare against.
 *
 * @template-implements IEventListener<UserUpdatedEvent|UserChangedEvent>
 */
class AvatarVersionListener implements IEventListener {
	public function __construct(
		private AvatarVersion $avatarVersion,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof UserUpdatedEvent) {
			$this->avatarVersion->bump($event->getUser()->getUID());
			return;
		}

		if ($event instanceof UserChangedEvent && $event->getFeature() === 'enabled') {
			$this->avatarVersion->bump($event->getUser()->getUID());
		}
	}
}

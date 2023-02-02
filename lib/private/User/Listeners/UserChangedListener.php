<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\User\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserChangedEvent;
use OCP\Files\NotFoundException;
use OCP\IAvatarManager;

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

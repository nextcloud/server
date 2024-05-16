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
use OCP\Files\NotFoundException;
use OCP\IAvatarManager;
use OCP\Security\ICredentialsManager;
use OCP\User\Events\BeforeUserDeletedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<BeforeUserDeletedEvent>
 */
class BeforeUserDeletedListener implements IEventListener {
	private IAvatarManager $avatarManager;
	private ICredentialsManager $credentialsManager;
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger, IAvatarManager $avatarManager, ICredentialsManager $credentialsManager) {
		$this->avatarManager = $avatarManager;
		$this->credentialsManager = $credentialsManager;
		$this->logger = $logger;
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

<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
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
namespace OC\Collaboration\Resources;

use OCP\Collaboration\Resources\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\User\Events\UserDeletedEvent;

class Listener {
	public static function register(IEventDispatcher $eventDispatcher): void {
		$eventDispatcher->addListener(UserAddedEvent::class, function (UserAddedEvent $event) {
			$user = $event->getUser();
			/** @var IManager $resourceManager */
			$resourceManager = \OCP\Server::get(IManager::class);

			$resourceManager->invalidateAccessCacheForUser($user);
		});
		$eventDispatcher->addListener(UserRemovedEvent::class, function (UserRemovedEvent $event) {
			$user = $event->getUser();
			/** @var IManager $resourceManager */
			$resourceManager = \OCP\Server::get(IManager::class);

			$resourceManager->invalidateAccessCacheForUser($user);
		});

		$eventDispatcher->addListener(UserDeletedEvent::class, function (UserDeletedEvent $event) {
			$user = $event->getUser();
			/** @var IManager $resourceManager */
			$resourceManager = \OCP\Server::get(IManager::class);

			$resourceManager->invalidateAccessCacheForUser($user);
		});

		$eventDispatcher->addListener(BeforeGroupDeletedEvent::class, function (BeforeGroupDeletedEvent $event) {
			$group = $event->getGroup();
			/** @var IManager $resourceManager */
			$resourceManager = \OCP\Server::get(IManager::class);

			foreach ($group->getUsers() as $user) {
				$resourceManager->invalidateAccessCacheForUser($user);
			}
		});
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

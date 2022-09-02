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

use OC\EventDispatcher\SymfonyAdapter;
use OCP\Collaboration\Resources\IManager;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IGroup;
use OCP\IUser;
use Symfony\Component\EventDispatcher\GenericEvent;

class Listener {
	public static function register(SymfonyAdapter $symfonyDispatcher, IEventDispatcher $eventDispatcher): void {
		$listener = function (GenericEvent $event) {
			/** @var IUser $user */
			$user = $event->getArgument('user');
			/** @var IManager $resourceManager */
			$resourceManager = \OC::$server->query(IManager::class);

			$resourceManager->invalidateAccessCacheForUser($user);
		};
		$symfonyDispatcher->addListener(IGroup::class . '::postAddUser', $listener);
		$symfonyDispatcher->addListener(IGroup::class . '::postRemoveUser', $listener);

		$symfonyDispatcher->addListener(IUser::class . '::postDelete', function (GenericEvent $event) {
			/** @var IUser $user */
			$user = $event->getSubject();
			/** @var IManager $resourceManager */
			$resourceManager = \OC::$server->query(IManager::class);

			$resourceManager->invalidateAccessCacheForUser($user);
		});

		$symfonyDispatcher->addListener(IGroup::class . '::preDelete', function (GenericEvent $event) {
			/** @var IGroup $group */
			$group = $event->getSubject();
			/** @var IManager $resourceManager */
			$resourceManager = \OC::$server->query(IManager::class);

			foreach ($group->getUsers() as $user) {
				$resourceManager->invalidateAccessCacheForUser($user);
			}
		});

		// Stay backward compatible with the legacy event for now
		$fallbackEventRunning = false;
		$symfonyDispatcher->addListener('\OCP\Collaboration\Resources::loadAdditionalScripts', function () use ($eventDispatcher, &$fallbackEventRunning) {
			if ($fallbackEventRunning) {
				return;
			}
			$fallbackEventRunning = true;
			$eventDispatcher->dispatchTyped(new LoadAdditionalScriptsEvent());
			$fallbackEventRunning = false;
		});
		$eventDispatcher->addListener(LoadAdditionalScriptsEvent::class, static function () use ($symfonyDispatcher, &$fallbackEventRunning) {
			if ($fallbackEventRunning) {
				return;
			}
			$fallbackEventRunning = true;
			$symfonyDispatcher->dispatch('\OCP\Collaboration\Resources::loadAdditionalScripts');
			$fallbackEventRunning = false;
		});
	}
}

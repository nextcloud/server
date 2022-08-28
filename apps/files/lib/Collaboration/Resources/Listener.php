<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019 Joas Schilling <coding@schilljs.com>
 *
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
namespace OCA\Files\Collaboration\Resources;

use OCP\Collaboration\Resources\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Server;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\Events\ShareDeletedFromSelfEvent;

class Listener {
	public static function register(IEventDispatcher $dispatcher): void {
		$dispatcher->addListener(ShareCreatedEvent::class, [self::class, 'shareModification']);
		$dispatcher->addListener(ShareDeletedEvent::class, [self::class, 'shareModification']);
		$dispatcher->addListener(ShareDeletedFromSelfEvent::class, [self::class, 'shareModification']);
	}

	public static function shareModification(): void {
		/** @var IManager $resourceManager */
		$resourceManager = Server::get(IManager::class);
		/** @var ResourceProvider $resourceProvider */
		$resourceProvider = Server::get(ResourceProvider::class);

		$resourceManager->invalidateAccessCacheForProvider($resourceProvider);
	}
}

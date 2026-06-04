<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

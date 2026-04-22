<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Collaboration\Resources;

use OCP\Collaboration\Resources\IManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\Events\ShareDeletedFromSelfEvent;

/**
 * @template-implements IEventListener<ShareCreatedEvent|ShareDeletedEvent|ShareDeletedFromSelfEvent>
 */
class Listener implements IEventListener {
	public function __construct(
		readonly protected IManager $resourceManager,
		readonly protected ResourceProvider $resourceProvider,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof ShareDeletedFromSelfEvent || $event instanceof ShareDeletedEvent || $event instanceof ShareCreatedEvent) {
			$this->shareModification();
		}
	}

	public function shareModification(): void {
		$this->resourceManager->invalidateAccessCacheForProvider($this->resourceProvider);
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Cache;

use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;

/**
 * Meta-event wrapping multiple CacheEntryRemovedEvent for when an existing
 * entry in the cache gets removed.
 *
 * @since 34.0.0
 */
#[Listenable(since: '34.0.0')]
class CacheEntriesRemovedEvent extends Event {
	/**
	 * @param ICacheEvent[] $cacheEntryRemovedEvents
	 */
	public function __construct(
		private readonly array $cacheEntryRemovedEvents,
	) {
		Event::__construct();
	}

	/**
	 * @return ICacheEvent[]
	 */
	public function getCacheEntryRemovedEvents(): array {
		return $this->cacheEntryRemovedEvents;
	}
}

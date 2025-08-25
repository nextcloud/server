<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Cache;

use OCP\EventDispatcher\Event;

/**
 * Meta-event wrapping multiple CacheEntryRemovedEvent for when an existing
 * entry in the cache gets removed.
 *
 * @since 32.0.0
 */
#[\OCP\AppFramework\Attribute\Listenable(since: '32.0.0')]
class CacheEntriesRemovedEvent extends Event {
	/**
	 * @param CacheEntryRemovedEvent[] $cacheEntryRemovedEvents
	 */
	public function __construct(
		private readonly array $cacheEntryRemovedEvents,
	) {
	}

	/**
	 * @return CacheEntryRemovedEvent[]
	 */
	public function getCacheEntryRemovedEvents(): array {
		return $this->cacheEntryRemovedEvents;
	}
}

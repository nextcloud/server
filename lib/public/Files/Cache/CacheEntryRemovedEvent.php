<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Cache;

/**
 * Event for when an existing entry in the cache gets removed
 *
 * Prefer using CacheEntriesRemovedEvent as it is more efficient when deleting
 * multiple files at the same time.
 *
 * @since 21.0.0
 * @see CacheEntriesRemovedEvent
 */
class CacheEntryRemovedEvent extends AbstractCacheEvent implements ICacheEvent {
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Cache;

/**
 * Event for when an existing entry in the cache gets updated
 *
 * @since 21.0.0
 */
class CacheEntryUpdatedEvent extends AbstractCacheEvent implements ICacheEvent {
}

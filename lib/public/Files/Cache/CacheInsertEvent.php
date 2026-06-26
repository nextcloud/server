<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Cache;

/**
 * Event for when a new entry gets added to the cache
 *
 * @since 16.0.0
 * @deprecated 21.0.0 use CacheEntryInsertedEvent instead
 */
class CacheInsertEvent extends CacheEntryInsertedEvent {
}

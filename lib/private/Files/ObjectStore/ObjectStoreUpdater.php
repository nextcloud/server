<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\ObjectStore;

use OC\Files\Cache\Updater;

/**
 * Custom Updater class for `ObjectStoreStorage`.
 * This wrapper will do nothing when the `update` method is called.
 * This is to skip heavy cache operations, as they are already done in `ObjectStoreStorage`.
 */
class ObjectStoreUpdater extends Updater {
	public function update($path, $time = null, ?int $sizeDifference = null) {
		// Noop
	}
}

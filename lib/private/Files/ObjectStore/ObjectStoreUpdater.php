<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\ObjectStore;

use OC\Files\Cache\Updater;
use OCP\Files\Storage\IStorage;

/**
 * Custom wrapper around the Updater for ObjectStoreStorage.
 * This wrapper will skip updating the cache in some scenario.
 * This is because a lot of cache management is already done in ObjectStoreStorage.
 */
class ObjectStoreUpdater extends Updater {
	public function getPropagator() {
		return parent::getPropagator();
	}

	public function propagate($path, $time = null) {
		parent::propagate($path, $time);
	}

	public function update($path, $time = null, ?int $sizeDifference = null) {
		// Noop
	}

	public function remove($path) {
		parent::remove($path);
	}

	public function renameFromStorage(IStorage $sourceStorage, $source, $target) {
		parent::renameFromStorage($sourceStorage, $source, $target);
	}

	public function copyFromStorage(IStorage $sourceStorage, string $source, string $target): void {
		parent::copyFromStorage($sourceStorage, $source, $target);
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Storage;

use OCP\Files\Mount\IMountPoint;

/**
 * Creates storage instances and manages and applies storage wrappers
 * @since 8.0.0
 */
interface IStorageFactory {
	/**
	 * allow modifier storage behaviour by adding wrappers around storages
	 *
	 * $callback should be a function of type (string $mountPoint, Storage $storage) => Storage
	 *
	 * @return bool true if the wrapper was added, false if there was already a wrapper with this
	 *              name registered
	 * @since 8.0.0
	 */
	public function addStorageWrapper(string $wrapperName, callable $callback);

	/**
	 * @return IStorage
	 * @since 8.0.0
	 */
	public function getInstance(IMountPoint $mountPoint, string $class, array $arguments);
}

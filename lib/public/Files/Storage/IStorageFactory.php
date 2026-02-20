<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Storage;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Files\Mount\IMountPoint;

/**
 * Creates storage instances and manages and applies storage wrappers.
 *
 * @since 8.0.0
 */
#[Consumable(since: '8.0.0')]
interface IStorageFactory {
	/**
	 * Allow modifier storage behaviour by adding wrappers around storages.
	 *
	 * @param non-empty-string $wrapperName
	 * @param callable(string, IStorage, IMountPoint):IStorage $callback Callback should be a function of type (string $mountPoint, Storage $storage, IMountPoint) => Storage
	 * @param list<IMountPoint> $existingMounts
	 *
	 * @return bool true if the wrapper was added, false if there was already a wrapper with this
	 *              name registered
	 * @since 8.0.0
	 */
	public function addStorageWrapper(string $wrapperName, callable $callback, int $priority = 50, array $existingMounts = []): bool;

	/**
	 * Create an instance of a storage and apply the registered storage wrappers.
	 *
	 * @param class-string<IStorage> $class
	 * @since 8.0.0
	 */
	public function getInstance(IMountPoint $mountPoint, string $class, array $arguments): IStorage;
}

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
 * Creates storage instances and manages and applies storage wrappers
 * @since 8.0.0
 */
#[Consumable(since: '8.0.0')]
interface IStorageFactory {
	/**
	 * Allow modifier storage behaviour by adding wrappers around storages.
	 *
	 * $callback should be a function of type (string $mountPoint, Storage $storage) => Storage
	 *
	 * @param callable(string $mountPoint, IStorage $storage, IMountPoint $mountPoint): IStorage $callback
	 * @return bool true if the wrapper was added, false if there was already a wrapper with this
	 *              name registered
	 * @since 8.0.0
	 */
	public function addStorageWrapper(string $wrapperName, callable $callback): bool;

	/**
	 * Create an instance of a storage and apply the registered storage wrappers
	 *
	 * @return IStorage
	 * @since 8.0.0
	 */
	public function getInstance(IMountPoint $mountPoint, string $class, array $arguments): IStorage;

	/**
	 * Wrap the storage with the wrapper added to the storage factory.
	 *
	 * @param IMountPoint $mountPoint The mount
	 * @param IStorage $storage The storage to wrap.
	 * @return IStorage
	 * @since 33.0.0
	 */
	public function wrap(IMountPoint $mountPoint, IStorage $storage): IStorage;
}

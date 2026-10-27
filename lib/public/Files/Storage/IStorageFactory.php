<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Files\Storage;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Files\Events\BeforeFileSystemSetupEvent;
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
	 * The BeforeFileSystemSetupEvent should be used in most cases instead to add storage wrappers.
	 *
	 * @param non-empty-string $wrapperName
	 * @param callable(string $mountPoint, IStorage $storage, IMountPoint $mountPoint): IStorage $callback
	 * @param int<0, 100> $priority
	 * @param IMountPoint[] $existingMounts
	 *
	 * @return bool true if the wrapper was added, false if there was already a wrapper with this
	 *              name registered
	 * @since 8.0.0
	 * @see BeforeFileSystemSetupEvent
	 */
	public function addStorageWrapper(string $wrapperName, callable $callback, int $priority = 50, array $existingMounts = []): bool;

	/**
	 * @return IStorage
	 * @since 8.0.0
	 */
	public function getInstance(IMountPoint $mountPoint, string $class, array $arguments): IStorage;
}

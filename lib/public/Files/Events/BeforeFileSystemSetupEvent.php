<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\Storage\IStorage;
use OCP\IUser;

/**
 * Event triggered before the file system is set up.
 *
 * @since 31.0.0
 */
class BeforeFileSystemSetupEvent extends Event {
	/** @var array<string, array{wrapper: callable(string $mountPoint, IStorage $storage): IStorage, priority: int}> $storageWrappers */
	private array $storageWrappers = [];

	/**
	 * @since 31.0.0
	 */
	public function __construct(
		private readonly IUser $user,
	) {
		parent::__construct();
	}

	/**
	 * @since 31.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * Add a storage wrapper to the file system. This allows apps to inject storage wrappers
	 * for every mount.
	 *
	 * @param callable(string $mountPoint, IStorage $storage): IStorage $wrapper
	 * @since 35.0.0
	 */
	public function addStorageWrapper(string $name, callable $wrapper, int $priority = 50): void {
		$this->storageWrappers[$name] = ['wrapper' => $wrapper, 'priority' => $priority];
	}

	/**
	 * Get the storage wrappers.
	 *
	 * @since 35.0.0
	 * @return array<string, array{wrapper: callable(string $mountPoint, IStorage $storage): IStorage, priority: int}>
	 */
	public function getStorageWrappers(): array {
		return $this->storageWrappers;
	}
}

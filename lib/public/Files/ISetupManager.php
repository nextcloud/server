<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IUser;

/**
 * The files setup manager allow to set up the file system for specific users and
 * also to tear it down when no longer needed.
 *
 * This is mostly useful in backgroun jobs, where an operation need to be done for
 * multiple users and their file system need to be setup and teared down between
 * each user.
 *
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
interface ISetupManager {

	/**
	 * Set up the full filesystem for a specified user.
	 *
	 * @throws \OCP\HintException
	 * @throws \OC\ServerNotAvailableException
	 */
	public function setupForUser(IUser $user): void;

	/**
	 * Tear down all file systems to free some memory.
	 */
	public function tearDown(): void;

	/**
	 * Set up the filesystem for the specified path, optionally including all
	 * children mounts.
	 *
	 * @throws \OCP\HintException
	 * @throws \OC\ServerNotAvailableException
	 */
	public function setupForPath(string $path, bool $includeChildren = false): void;

	/**
	 * Get whether the file system is already setup for a specific user.
	 */
	public function isSetupComplete(IUser $user): bool;
}

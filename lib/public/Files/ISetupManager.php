<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Files;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IUser;

/**
 * The files setup manager allow to set up the file system for specific users and
 * also to tear it down when no longer needed.
 *
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
interface ISetupManager {

	/**
	 * Set up the full filesystem for a specified user.
	 */
	public function setupForUser(IUser $user): void;

	/**
	 * Tear down all file systems to free some memory.
	 */
	public function tearDown(): void;
}

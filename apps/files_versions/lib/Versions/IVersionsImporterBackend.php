<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Versions;

use OCP\Files\Node;
use OCP\IUser;

/**
 * @since 29.0.0
 */
interface IVersionsImporterBackend {
	/**
	 * Import the given versions for the target file.
	 *
	 * @param Node $source - The source might not exist anymore.
	 * @param IVersion[] $versions
	 * @since 29.0.0
	 */
	public function importVersionsForFile(IUser $user, Node $source, Node $target, array $versions): void;

	/**
	 * Clear all versions for a file
	 *
	 * @since 29.0.0
	 */
	public function clearVersionsForFile(IUser $user, Node $source, Node $target): void;
}

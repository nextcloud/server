<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Versions;

/**
 * @since 26.0.0
 */
interface IDeletableVersionBackend {
	/**
	 * Delete a version.
	 *
	 * @since 26.0.0
	 */
	public function deleteVersion(IVersion $version): void;
}

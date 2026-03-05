<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\UserMigration;

/**
 * Basic version handling: we can import older versions but not newer ones
 * @since 24.0.0
 */
trait TMigratorBasicVersionHandling {
	protected int $version = 1;

	protected bool $mandatory = false;

	/**
	 * {@inheritDoc}
	 * @since 24.0.0
	 */
	public function getVersion(): int {
		return $this->version;
	}

	/**
	 * {@inheritDoc}
	 * @since 24.0.0
	 */
	public function canImport(
		IImportSource $importSource,
	): bool {
		$version = $importSource->getMigratorVersion($this->getId());
		if ($version === null) {
			return !$this->mandatory;
		}
		return ($this->version >= $version);
	}
}

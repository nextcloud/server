<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Versions;

use OCP\Files\Storage\IStorage;

/**
 * @since 15.0.0
 */
interface IVersionManager extends IVersionBackend {
	/**
	 * Register a new backend
	 *
	 * @param string $storageType
	 * @param IVersionBackend $backend
	 * @since 15.0.0
	 */
	public function registerBackend(string $storageType, IVersionBackend $backend);

	/**
	 * @throws BackendNotFoundException
	 * @since 29.0.0
	 */
	public function getBackendForStorage(IStorage $storage): IVersionBackend;
}

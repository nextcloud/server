<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Cache;

use OCP\Files\Storage\IStorage;

/**
 * @since 16.0.0
 */
interface ICacheEvent {
	/**
	 * @return IStorage
	 * @since 16.0.0
	 */
	public function getStorage(): IStorage;

	/**
	 * @return string
	 * @since 16.0.0
	 */
	public function getPath(): string;

	/**
	 * @param string $path
	 * @since 19.0.0
	 */
	public function setPath(string $path): void;

	/**
	 * @return int
	 * @since 16.0.0
	 */
	public function getFileId(): int;

	/**
	 * @return int
	 * @since 21.0.0
	 */
	public function getStorageId(): int;
}

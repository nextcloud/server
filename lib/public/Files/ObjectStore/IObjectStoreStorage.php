<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\ObjectStore;

use OCP\Files\Storage\IStorage;

/**
 * A storage that keeps its files' content in an object store, one object
 * per file. Storage wrappers pass these calls on; check with
 * {@see IStorage::instanceOfStorage()}.
 *
 * @since 36.0.0
 */
interface IObjectStoreStorage extends IStorage {
	/**
	 * The object store that holds the content.
	 *
	 * @since 36.0.0
	 */
	public function getObjectStore(): IObjectStore;

	/**
	 * The name of the object that holds the content of the file $fileId.
	 *
	 * @since 36.0.0
	 */
	public function getURN(int $fileId): string;
}

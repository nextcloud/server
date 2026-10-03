<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\ObjectStore;

/**
 * Object stores that can enforce write preconditions server-side.
 *
 * Nextcloud derives object urns from the auto-increment file id of the file
 * cache. A freshly created file always targets a urn that must not yet exist in
 * the bucket; an object already being there is evidence that the file cache and
 * the object store are out of sync (for example after restoring the database
 * from a backup, when two instances share a bucket, or when file ids are
 * duplicated). Backends implementing this interface let the storage layer refuse
 * such writes atomically instead of silently overwriting existing data.
 *
 * @psalm-import-type ObjectMetaData from IObjectStoreMetaData
 * @since 35.0.0
 */
interface IObjectStoreConditionalWrite {
	/**
	 * Whether conditional writes are enabled for and supported by the backing store.
	 *
	 * Implementations resolve the configured mode and, when set to automatic
	 * detection, a cached capability probe against the bucket. Cheap to call
	 * repeatedly.
	 *
	 * @since 35.0.0
	 */
	public function supportsConditionalWrites(): bool;

	/**
	 * Write an object only if no object exists at the given urn.
	 *
	 * Behaves like IObjectStoreMetaData::writeObjectWithMetaData() but the write
	 * is rejected server-side when the target urn is already taken.
	 *
	 * @param string $urn the unified resource name used to identify the object
	 * @param resource $stream stream with the data to write
	 * @param ObjectMetaData $metaData the metadata to set for the object
	 * @throws ObjectAlreadyExistsException if an object already exists at $urn
	 * @throws \Exception when something else goes wrong, message will be logged
	 * @since 35.0.0
	 */
	public function writeObjectIfNotExists(string $urn, $stream, array $metaData = []): void;
}

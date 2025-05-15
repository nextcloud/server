<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\ObjectStore;

/**
 * Interface IObjectStoreMetaData
 *
 * @psalm-type ObjectMetaData = array{mtime?: \DateTime, etag?: string, size?: int, mimetype?: string, filename?: string}
 */
interface IObjectStoreMetaData {
	/**
	 * Get metadata for an object.
	 *
	 * @param string $urn
	 * @return ObjectMetaData
	 *
	 * @since 32.0.0
	 */
	public function getObjectMetaData(string $urn): array;

	/**
	 * List all objects in the object store.
	 *
	 * If the object store implementation can do it efficiently, the metadata for each object is also included.
	 *
	 * @param string $prefix
	 * @return \Iterator<array{urn: string, metadata: ?ObjectMetaData}>
	 *
	 * @since 32.0.0
	 */
	public function listObjects(string $prefix = ''): \Iterator;
}

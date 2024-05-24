<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\ObjectStore;

use OCP\Files\NotFoundException;

/**
 * Interface IObjectStore
 *
 * @since 7.0.0
 */
interface IObjectStore {
	/**
	 * @return string the container or bucket name where objects are stored
	 * @since 7.0.0
	 */
	public function getStorageId();

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws \Exception when something goes wrong, message will be logged
	 * @throws NotFoundException if file does not exist
	 * @since 7.0.0
	 */
	public function readObject($urn);

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param resource $stream stream with the data to write
	 * @param string|null $mimetype the mimetype to set for the remove object @since 22.0.0
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	public function writeObject($urn, $stream, ?string $mimetype = null);

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return void
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	public function deleteObject($urn);

	/**
	 * Check if an object exists in the object store
	 *
	 * @param string $urn
	 * @return bool
	 * @since 16.0.0
	 */
	public function objectExists($urn);

	/**
	 * @param string $from the unified resource name used to identify the source object
	 * @param string $to the unified resource name used to identify the target object
	 * @return void
	 * @since 21.0.0
	 */
	public function copyObject($from, $to);
}

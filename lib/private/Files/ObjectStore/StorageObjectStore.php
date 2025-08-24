<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\Storage\IStorage;
use function is_resource;

/**
 * Object store that wraps a storage backend, mostly for testing purposes
 */
class StorageObjectStore implements IObjectStore {
	/** @var IStorage */
	private $storage;

	/**
	 * @param IStorage $storage
	 */
	public function __construct(IStorage $storage) {
		$this->storage = $storage;
	}

	/**
	 * @return string the container or bucket name where objects are stored
	 * @since 7.0.0
	 */
	public function getStorageId(): string {
		return $this->storage->getId();
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	public function readObject($urn) {
		$handle = $this->storage->fopen($urn, 'r');
		if (is_resource($handle)) {
			return $handle;
		}

		throw new \Exception();
	}

	public function writeObject($urn, $stream, ?string $mimetype = null) {
		$handle = $this->storage->fopen($urn, 'w');
		if ($handle) {
			stream_copy_to_stream($stream, $handle);
			fclose($handle);
		} else {
			throw new \Exception();
		}
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return void
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	public function deleteObject($urn) {
		$this->storage->unlink($urn);
	}

	public function objectExists($urn) {
		return $this->storage->file_exists($urn);
	}

	public function copyObject($from, $to) {
		$this->storage->copy($from, $to);
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Backend;

use OCA\Files_External\Lib\StorageConfig;
use OCP\Files\StorageNotAvailableException;
use OCP\IUser;

/**
 * Invalid storage backend representing a backend
 * that could not be resolved
 */
class InvalidBackend extends Backend {

	/**
	 * Constructs a new InvalidBackend with the id of the invalid backend
	 * for display purposes
	 *
	 * @param string $invalidId id of the backend that did not exist
	 */
	public function __construct(
		private $invalidId,
	) {
		$this
			->setIdentifier($this->invalidId)
			->setStorageClass('\OC\Files\Storage\FailedStorage')
			->setText('Unknown storage backend ' . $this->invalidId);
	}

	/**
	 * Returns the invalid backend id
	 *
	 * @return string invalid backend id
	 */
	public function getInvalidId() {
		return $this->invalidId;
	}

	/**
	 * @return void
	 */
	public function manipulateStorageConfig(StorageConfig &$storage, ?IUser $user = null) {
		$storage->setBackendOption('exception', new \Exception('Unknown storage backend "' . $this->invalidId . '"', StorageNotAvailableException::STATUS_ERROR));
	}
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;

class FailWriteObjectStore implements IObjectStore {
	private $objectStore;

	public function __construct(IObjectStore $objectStore) {
		$this->objectStore = $objectStore;
	}

	public function getStorageId() {
		return $this->objectStore->getStorageId();
	}

	public function readObject($urn) {
		return $this->objectStore->readObject($urn);
	}

	public function writeObject($urn, $stream, ?string $mimetype = null) {
		// emulate a failed write that didn't throw an error
		return true;
	}

	public function deleteObject($urn) {
		$this->objectStore->deleteObject($urn);
	}

	public function objectExists($urn) {
		return $this->objectStore->objectExists($urn);
	}

	public function copyObject($from, $to) {
		$this->objectStore->copyObject($from, $to);
	}
}

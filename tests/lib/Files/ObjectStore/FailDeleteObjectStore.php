<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;

class FailDeleteObjectStore implements IObjectStore {
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
		return $this->objectStore->writeObject($urn, $stream, $mimetype);
	}

	public function deleteObject($urn) {
		throw new \Exception();
	}

	public function objectExists($urn) {
		return $this->objectStore->objectExists($urn);
	}

	public function copyObject($from, $to) {
		$this->objectStore->copyObject($from, $to);
	}
}

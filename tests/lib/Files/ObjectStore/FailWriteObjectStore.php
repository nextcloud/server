<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;

class FailWriteObjectStore implements IObjectStore {
	public function __construct(
		private IObjectStore $objectStore,
	) {
	}

	#[\Override]
	public function getStorageId() {
		return $this->objectStore->getStorageId();
	}

	#[\Override]
	public function readObject($urn) {
		return $this->objectStore->readObject($urn);
	}

	#[\Override]
	public function writeObject($urn, $stream, ?string $mimetype = null) {
		// emulate a failed write that didn't throw an error
		return true;
	}

	#[\Override]
	public function deleteObject($urn) {
		$this->objectStore->deleteObject($urn);
	}

	#[\Override]
	public function objectExists($urn) {
		return $this->objectStore->objectExists($urn);
	}

	#[\Override]
	public function copyObject($from, $to) {
		$this->objectStore->copyObject($from, $to);
	}

	#[\Override]
	public function preSignedUrl(string $urn, \DateTimeInterface $expiration): ?string {
		return null;
	}
}

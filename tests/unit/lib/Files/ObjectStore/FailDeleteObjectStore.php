<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;

class FailDeleteObjectStore implements IObjectStore {
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
		return $this->objectStore->writeObject($urn, $stream, $mimetype);
	}

	#[\Override]
	public function deleteObject($urn) {
		throw new \Exception();
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

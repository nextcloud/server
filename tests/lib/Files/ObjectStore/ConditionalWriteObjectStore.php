<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\ObjectStore\IObjectStoreConditionalWrite;
use OCP\Files\ObjectStore\ObjectAlreadyExistsException;

/**
 * Test double that records which write method the storage layer picks and can
 * simulate an object already occupying a target urn (a bucket that is out of
 * sync with the file cache).
 */
class ConditionalWriteObjectStore implements IObjectStore, IObjectStoreConditionalWrite {
	/** @var list<string> the write methods invoked, in order */
	public array $writeCalls = [];

	public function __construct(
		private IObjectStore $wrapped,
		private bool $supported = true,
		private bool $simulateExistingObject = false,
	) {
	}

	public function setSupported(bool $supported): void {
		$this->supported = $supported;
	}

	public function setSimulateExistingObject(bool $simulate): void {
		$this->simulateExistingObject = $simulate;
	}

	#[\Override]
	public function supportsConditionalWrites(): bool {
		return $this->supported;
	}

	#[\Override]
	public function writeObjectIfNotExists(string $urn, $stream, array $metaData = []): void {
		$this->writeCalls[] = 'writeObjectIfNotExists';
		if ($this->simulateExistingObject || $this->wrapped->objectExists($urn)) {
			throw new ObjectAlreadyExistsException($urn);
		}
		$this->wrapped->writeObject($urn, $stream, $metaData['mimetype'] ?? null);
	}

	#[\Override]
	public function writeObject($urn, $stream, ?string $mimetype = null) {
		$this->writeCalls[] = 'writeObject';
		$this->wrapped->writeObject($urn, $stream, $mimetype);
	}

	#[\Override]
	public function getStorageId() {
		return $this->wrapped->getStorageId();
	}

	#[\Override]
	public function readObject($urn) {
		return $this->wrapped->readObject($urn);
	}

	#[\Override]
	public function deleteObject($urn) {
		$this->wrapped->deleteObject($urn);
	}

	#[\Override]
	public function objectExists($urn) {
		return $this->wrapped->objectExists($urn);
	}

	#[\Override]
	public function copyObject($from, $to) {
		$this->wrapped->copyObject($from, $to);
	}

	#[\Override]
	public function preSignedUrl(string $urn, \DateTimeInterface $expiration): ?string {
		return null;
	}
}

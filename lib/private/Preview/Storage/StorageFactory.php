<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Storage;

use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Files\SimpleFS\SimpleFile;
use OC\Preview\Db\Preview;
use OCP\Server;
use Override;

class StorageFactory implements IPreviewStorage {
	private ?IPreviewStorage $backend = null;

	public function __construct(
		private readonly PrimaryObjectStoreConfig $objectStoreConfig,
	) {
	}

	#[Override]
	public function writePreview(Preview $preview, mixed $stream): int {
		return $this->getBackend()->writePreview($preview, $stream);
	}

	#[Override]
	public function readPreview(Preview $preview): mixed {
		return $this->getBackend()->readPreview($preview);
	}

	#[Override]
	public function deletePreview(Preview $preview): void {
		$this->getBackend()->deletePreview($preview);
	}

	private function getBackend(): IPreviewStorage {
		if ($this->backend) {
			return $this->backend;
		}

		if ($this->objectStoreConfig->hasObjectStore()) {
			$this->backend = Server::get(ObjectStorePreviewStorage::class);
		} else {
			$this->backend = Server::get(LocalPreviewStorage::class);
		}

		return $this->backend;
	}

	#[Override]
	public function migratePreview(Preview $preview, SimpleFile $file): void {
		$this->getBackend()->migratePreview($preview, $file);
	}

	#[Override]
	public function scan(): int {
		return $this->getBackend()->scan();
	}
}

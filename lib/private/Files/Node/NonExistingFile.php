<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Node;

use OCP\Files\NotFoundException;
use Override;

class NonExistingFile extends File {
	#[Override]
	public function move(string $targetPath): \OCP\Files\Node {
		throw new NotFoundException();
	}

	#[Override]
	public function delete(): void {
		throw new NotFoundException();
	}

	#[Override]
	public function copy(string $targetPath): \OCP\Files\Node {
		throw new NotFoundException();
	}

	#[Override]
	public function touch(?int $mtime = null): void {
		throw new NotFoundException();
	}

	#[Override]
	public function getId(): int {
		if ($this->fileInfo) {
			return parent::getId();
		} else {
			throw new NotFoundException();
		}
	}

	#[Override]
	public function getInternalPath(): string {
		if ($this->fileInfo) {
			return parent::getInternalPath();
		} else {
			return $this->getParent()->getMountPoint()->getInternalPath($this->getPath());
		}
	}

	#[Override]
	public function stat(): array {
		throw new NotFoundException();
	}

	#[Override]
	public function getMTime(): int {
		if ($this->fileInfo) {
			return parent::getMTime();
		} else {
			throw new NotFoundException();
		}
	}

	#[Override]
	public function getSize(bool $includeMounts = true): int|float {
		if ($this->fileInfo) {
			return parent::getSize($includeMounts);
		} else {
			throw new NotFoundException();
		}
	}

	#[Override]
	public function getEtag(): string {
		if ($this->fileInfo) {
			return parent::getEtag();
		} else {
			throw new NotFoundException();
		}
	}

	#[Override]
	public function getPermissions(): int {
		if ($this->fileInfo) {
			return parent::getPermissions();
		} else {
			throw new NotFoundException();
		}
	}

	#[Override]
	public function isReadable(): bool {
		if ($this->fileInfo) {
			return parent::isReadable();
		} else {
			throw new NotFoundException();
		}
	}

	#[Override]
	public function isUpdateable(): bool {
		if ($this->fileInfo) {
			return parent::isUpdateable();
		} else {
			throw new NotFoundException();
		}
	}

	#[Override]
	public function isDeletable(): bool {
		if ($this->fileInfo) {
			return parent::isDeletable();
		} else {
			throw new NotFoundException();
		}
	}

	#[Override]
	public function isShareable(): bool {
		if ($this->fileInfo) {
			return parent::isShareable();
		} else {
			throw new NotFoundException();
		}
	}

	#[Override]
	public function getContent(): string {
		throw new NotFoundException();
	}

	#[Override]
	public function putContent($data): void {
		throw new NotFoundException();
	}

	#[Override]
	public function getMimeType(): string {
		if ($this->fileInfo) {
			return parent::getMimeType();
		} else {
			throw new NotFoundException();
		}
	}

	#[Override]
	public function fopen($mode) {
		throw new NotFoundException();
	}
}

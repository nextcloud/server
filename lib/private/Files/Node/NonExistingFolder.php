<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Node;

use OCP\Files\NotFoundException;
use OCP\Files\Search\ISearchQuery;
use Override;

class NonExistingFolder extends Folder {
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
	public function stat(): array|false {
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
	public function get(string $path): \OCP\Files\Node {
		throw new NotFoundException();
	}

	#[Override]
	public function getDirectoryListing(): array {
		throw new NotFoundException();
	}

	#[Override]
	public function nodeExists(string $path): bool {
		return false;
	}

	#[Override]
	public function newFolder(string $path): \OCP\Files\Folder {
		throw new NotFoundException();
	}

	#[Override]
	public function newFile(string $path, $content = null): \OCP\Files\File {
		throw new NotFoundException();
	}

	#[Override]
	public function search(string|ISearchQuery $query): array {
		throw new NotFoundException();
	}

	#[Override]
	public function searchByMime(string $mimetype): array {
		throw new NotFoundException();
	}

	#[Override]
	public function searchByTag(int|string $tag, string $userId): array {
		throw new NotFoundException();
	}

	#[Override]
	public function searchBySystemTag(string $tagName, string $userId, int $limit = 0, int $offset = 0): array {
		throw new NotFoundException();
	}

	#[Override]
	public function getById(int $id): array {
		throw new NotFoundException();
	}

	#[Override]
	public function getFirstNodeById(int $id): ?\OCP\Files\Node {
		throw new NotFoundException();
	}

	#[Override]
	public function getFreeSpace(): float|int|false {
		throw new NotFoundException();
	}

	#[Override]
	public function isCreatable(): bool {
		if ($this->fileInfo) {
			return parent::isCreatable();
		} else {
			throw new NotFoundException();
		}
	}
}

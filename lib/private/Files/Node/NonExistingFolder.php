<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Node;

use OCP\Files\Node;
use OCP\Files\NotFoundException;

class NonExistingFolder extends Folder {
	/**
	 * @param string $newPath
	 * @throws \OCP\Files\NotFoundException
	 */
	public function rename($newPath): never {
		throw new NotFoundException();
	}

	public function delete(): never {
		throw new NotFoundException();
	}

	public function copy($targetPath): never {
		throw new NotFoundException();
	}

	public function touch($mtime = null): never {
		throw new NotFoundException();
	}

	public function getId(): int {
		if ($this->fileInfo) {
			return parent::getId();
		} else {
			throw new NotFoundException();
		}
	}

	public function getInternalPath(): string {
		if ($this->fileInfo) {
			return parent::getInternalPath();
		} else {
			return $this->getParent()->getMountPoint()->getInternalPath($this->getPath());
		}
	}

	public function stat(): never {
		throw new NotFoundException();
	}

	public function getMTime(): int {
		if ($this->fileInfo) {
			return parent::getMTime();
		} else {
			throw new NotFoundException();
		}
	}

	public function getSize($includeMounts = true): int|float {
		if ($this->fileInfo) {
			return parent::getSize($includeMounts);
		} else {
			throw new NotFoundException();
		}
	}

	public function getEtag(): string {
		if ($this->fileInfo) {
			return parent::getEtag();
		} else {
			throw new NotFoundException();
		}
	}

	public function getPermissions(): int {
		if ($this->fileInfo) {
			return parent::getPermissions();
		} else {
			throw new NotFoundException();
		}
	}

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

	public function isDeletable(): bool {
		if ($this->fileInfo) {
			return parent::isDeletable();
		} else {
			throw new NotFoundException();
		}
	}

	public function isShareable(): bool {
		if ($this->fileInfo) {
			return parent::isShareable();
		} else {
			throw new NotFoundException();
		}
	}

	public function get($path): never {
		throw new NotFoundException();
	}

	public function getDirectoryListing(): never {
		throw new NotFoundException();
	}

	public function nodeExists($path): false {
		return false;
	}

	public function newFolder($path): never {
		throw new NotFoundException();
	}

	public function newFile($path, $content = null): never {
		throw new NotFoundException();
	}

	public function search($query): never {
		throw new NotFoundException();
	}

	public function searchByMime($mimetype): never {
		throw new NotFoundException();
	}

	public function searchByTag($tag, $userId): never {
		throw new NotFoundException();
	}

	public function searchBySystemTag(string $tagName, string $userId, int $limit = 0, int $offset = 0): never {
		throw new NotFoundException();
	}

	public function getById($id): never {
		throw new NotFoundException();
	}

	public function getFirstNodeById(int $id): ?Node {
		throw new NotFoundException();
	}

	public function getFreeSpace(): never {
		throw new NotFoundException();
	}

	public function isCreatable() {
		if ($this->fileInfo) {
			return parent::isCreatable();
		} else {
			throw new NotFoundException();
		}
	}
}

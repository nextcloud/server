<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Node;

use OCP\Files\NotFoundException;

class NonExistingFolder extends Folder {
	/**
	 * @param string $newPath
	 * @throws \OCP\Files\NotFoundException
	 */
	public function rename($newPath) {
		throw new NotFoundException();
	}

	public function delete() {
		throw new NotFoundException();
	}

	public function copy($targetPath) {
		throw new NotFoundException();
	}

	public function touch($mtime = null) {
		throw new NotFoundException();
	}

	public function getId() {
		if ($this->fileInfo) {
			return parent::getId();
		} else {
			throw new NotFoundException();
		}
	}

	public function getInternalPath() {
		if ($this->fileInfo) {
			return parent::getInternalPath();
		} else {
			return $this->getParent()->getMountPoint()->getInternalPath($this->getPath());
		}
	}

	public function stat() {
		throw new NotFoundException();
	}

	public function getMTime() {
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

	public function getEtag() {
		if ($this->fileInfo) {
			return parent::getEtag();
		} else {
			throw new NotFoundException();
		}
	}

	public function getPermissions() {
		if ($this->fileInfo) {
			return parent::getPermissions();
		} else {
			throw new NotFoundException();
		}
	}

	public function isReadable() {
		if ($this->fileInfo) {
			return parent::isReadable();
		} else {
			throw new NotFoundException();
		}
	}

	public function isUpdateable() {
		if ($this->fileInfo) {
			return parent::isUpdateable();
		} else {
			throw new NotFoundException();
		}
	}

	public function isDeletable() {
		if ($this->fileInfo) {
			return parent::isDeletable();
		} else {
			throw new NotFoundException();
		}
	}

	public function isShareable() {
		if ($this->fileInfo) {
			return parent::isShareable();
		} else {
			throw new NotFoundException();
		}
	}

	public function get($path) {
		throw new NotFoundException();
	}

	public function getDirectoryListing() {
		throw new NotFoundException();
	}

	public function nodeExists($path) {
		return false;
	}

	public function newFolder($path) {
		throw new NotFoundException();
	}

	public function newFile($path, $content = null) {
		throw new NotFoundException();
	}

	public function search($query) {
		throw new NotFoundException();
	}

	public function searchByMime($mimetype) {
		throw new NotFoundException();
	}

	public function searchByTag($tag, $userId) {
		throw new NotFoundException();
	}

	public function searchBySystemTag(string $tagName, string $userId, int $limit = 0, int $offset = 0): array {
		throw new NotFoundException();
	}

	public function getById($id) {
		throw new NotFoundException();
	}

	public function getFirstNodeById(int $id): ?\OCP\Files\Node {
		throw new NotFoundException();
	}

	public function getFreeSpace() {
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

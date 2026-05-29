<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Node;

use OCP\Files\NotFoundException;

class NonExistingFile extends File {
	/**
	 * @param string $newPath
	 * @throws \OCP\Files\NotFoundException
	 */
	public function rename($newPath) {
		throw new NotFoundException();
	}

	#[\Override]
	public function delete() {
		throw new NotFoundException();
	}

	#[\Override]
	public function copy($targetPath) {
		throw new NotFoundException();
	}

	#[\Override]
	public function touch($mtime = null) {
		throw new NotFoundException();
	}

	#[\Override]
	public function getId() {
		if ($this->fileInfo) {
			return parent::getId();
		} else {
			throw new NotFoundException();
		}
	}

	#[\Override]
	public function getInternalPath() {
		if ($this->fileInfo) {
			return parent::getInternalPath();
		} else {
			return $this->getParent()->getMountPoint()->getInternalPath($this->getPath());
		}
	}

	#[\Override]
	public function stat() {
		throw new NotFoundException();
	}

	#[\Override]
	public function getMTime() {
		if ($this->fileInfo) {
			return parent::getMTime();
		} else {
			throw new NotFoundException();
		}
	}

	#[\Override]
	public function getSize($includeMounts = true): int|float {
		if ($this->fileInfo) {
			return parent::getSize($includeMounts);
		} else {
			throw new NotFoundException();
		}
	}

	#[\Override]
	public function getEtag() {
		if ($this->fileInfo) {
			return parent::getEtag();
		} else {
			throw new NotFoundException();
		}
	}

	#[\Override]
	public function getPermissions() {
		if ($this->fileInfo) {
			return parent::getPermissions();
		} else {
			throw new NotFoundException();
		}
	}

	#[\Override]
	public function isReadable() {
		if ($this->fileInfo) {
			return parent::isReadable();
		} else {
			throw new NotFoundException();
		}
	}

	#[\Override]
	public function isUpdateable() {
		if ($this->fileInfo) {
			return parent::isUpdateable();
		} else {
			throw new NotFoundException();
		}
	}

	#[\Override]
	public function isDeletable() {
		if ($this->fileInfo) {
			return parent::isDeletable();
		} else {
			throw new NotFoundException();
		}
	}

	#[\Override]
	public function isShareable() {
		if ($this->fileInfo) {
			return parent::isShareable();
		} else {
			throw new NotFoundException();
		}
	}

	#[\Override]
	public function getContent() {
		throw new NotFoundException();
	}

	#[\Override]
	public function putContent($data) {
		throw new NotFoundException();
	}

	#[\Override]
	public function getMimeType(): string {
		if ($this->fileInfo) {
			return parent::getMimeType();
		} else {
			throw new NotFoundException();
		}
	}

	#[\Override]
	public function fopen($mode) {
		throw new NotFoundException();
	}
}

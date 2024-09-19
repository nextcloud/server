<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage;

class CommonTest extends \OC\Files\Storage\Common {
	/**
	 * underlying local storage used for missing functions
	 * @var \OC\Files\Storage\Local
	 */
	private $storage;

	public function __construct($params) {
		$this->storage = new \OC\Files\Storage\Local($params);
	}

	public function getId(): string {
		return 'test::' . $this->storage->getId();
	}
	public function mkdir($path): bool {
		return $this->storage->mkdir($path);
	}
	public function rmdir($path): bool {
		return $this->storage->rmdir($path);
	}
	public function opendir($path) {
		return $this->storage->opendir($path);
	}
	public function stat($path): array|false {
		return $this->storage->stat($path);
	}
	public function filetype($path): string|false {
		return @$this->storage->filetype($path);
	}
	public function isReadable($path): bool {
		return $this->storage->isReadable($path);
	}
	public function isUpdatable($path): bool {
		return $this->storage->isUpdatable($path);
	}
	public function file_exists($path): bool {
		return $this->storage->file_exists($path);
	}
	public function unlink($path): bool {
		return $this->storage->unlink($path);
	}
	public function fopen($path, $mode) {
		return $this->storage->fopen($path, $mode);
	}
	public function free_space($path): int|float|false {
		return $this->storage->free_space($path);
	}
	public function touch($path, $mtime = null): bool {
		return $this->storage->touch($path, $mtime);
	}
}

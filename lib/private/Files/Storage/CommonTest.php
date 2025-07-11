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

	public function __construct(array $parameters) {
		$this->storage = new \OC\Files\Storage\Local($parameters);
	}

	public function getId(): string {
		return 'test::' . $this->storage->getId();
	}
	public function mkdir(string $path): bool {
		return $this->storage->mkdir($path);
	}
	public function rmdir(string $path): bool {
		return $this->storage->rmdir($path);
	}
	public function opendir(string $path) {
		return $this->storage->opendir($path);
	}
	public function stat(string $path): array|false {
		return $this->storage->stat($path);
	}
	public function filetype(string $path): string|false {
		return @$this->storage->filetype($path);
	}
	public function isReadable(string $path): bool {
		return $this->storage->isReadable($path);
	}
	public function isUpdatable(string $path): bool {
		return $this->storage->isUpdatable($path);
	}
	public function file_exists(string $path): bool {
		return $this->storage->file_exists($path);
	}
	public function unlink(string $path): bool {
		return $this->storage->unlink($path);
	}
	public function fopen(string $path, string $mode) {
		return $this->storage->fopen($path, $mode);
	}
	public function free_space(string $path): int|float|false {
		return $this->storage->free_space($path);
	}
	public function touch(string $path, ?int $mtime = null): bool {
		return $this->storage->touch($path, $mtime);
	}
}

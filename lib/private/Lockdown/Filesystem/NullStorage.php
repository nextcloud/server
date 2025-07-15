<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Lockdown\Filesystem;

use Icewind\Streams\IteratorDirectory;
use OC\Files\FileInfo;
use OC\Files\Storage\Common;
use OCP\Files\Cache\ICache;
use OCP\Files\Storage\IStorage;

class NullStorage extends Common {
	public function __construct(array $parameters) {
		parent::__construct($parameters);
	}

	public function getId(): string {
		return 'null';
	}

	public function mkdir(string $path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function rmdir(string $path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function opendir(string $path): IteratorDirectory {
		return new IteratorDirectory();
	}

	public function is_dir(string $path): bool {
		return $path === '';
	}

	public function is_file(string $path): bool {
		return false;
	}

	public function stat(string $path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function filetype(string $path): string|false {
		return ($path === '') ? 'dir' : false;
	}

	public function filesize(string $path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function isCreatable(string $path): bool {
		return false;
	}

	public function isReadable(string $path): bool {
		return $path === '';
	}

	public function isUpdatable(string $path): bool {
		return false;
	}

	public function isDeletable(string $path): bool {
		return false;
	}

	public function isSharable(string $path): bool {
		return false;
	}

	public function getPermissions(string $path): int {
		return 0;
	}

	public function file_exists(string $path): bool {
		return $path === '';
	}

	public function filemtime(string $path): int|false {
		return ($path === '') ? time() : false;
	}

	public function file_get_contents(string $path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function file_put_contents(string $path, mixed $data): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function unlink(string $path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function rename(string $source, string $target): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function copy(string $source, string $target): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function fopen(string $path, string $mode): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function getMimeType(string $path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function hash(string $type, string $path, bool $raw = false): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function free_space(string $path): int {
		return FileInfo::SPACE_UNKNOWN;
	}

	public function touch(string $path, ?int $mtime = null): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function getLocalFile(string $path): string|false {
		return false;
	}

	public function hasUpdated(string $path, int $time): bool {
		return false;
	}

	public function getETag(string $path): string {
		return '';
	}

	public function isLocal(): bool {
		return false;
	}

	public function getDirectDownload(string $path): array|false {
		return false;
	}

	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath, bool $preserveMtime = false): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function test(): bool {
		return true;
	}

	public function getOwner(string $path): string|false {
		return false;
	}

	public function getCache(string $path = '', ?IStorage $storage = null): ICache {
		return new NullCache();
	}
}

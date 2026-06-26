<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Lockdown\Filesystem;

use Icewind\Streams\IteratorDirectory;
use OC\Files\FileInfo;
use OC\Files\Storage\Common;
use OC\ForbiddenException;
use OCP\Files\Cache\ICache;
use OCP\Files\Storage\IStorage;

class NullStorage extends Common {
	public function __construct(array $parameters) {
		parent::__construct($parameters);
	}

	#[\Override]
	public function getId(): string {
		return 'null';
	}

	#[\Override]
	public function mkdir(string $path): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function rmdir(string $path): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function opendir(string $path): IteratorDirectory {
		return new IteratorDirectory();
	}

	#[\Override]
	public function is_dir(string $path): bool {
		return $path === '';
	}

	#[\Override]
	public function is_file(string $path): bool {
		return false;
	}

	#[\Override]
	public function stat(string $path): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function filetype(string $path): string|false {
		return ($path === '') ? 'dir' : false;
	}

	#[\Override]
	public function filesize(string $path): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function isCreatable(string $path): bool {
		return false;
	}

	#[\Override]
	public function isReadable(string $path): bool {
		return $path === '';
	}

	#[\Override]
	public function isUpdatable(string $path): bool {
		return false;
	}

	#[\Override]
	public function isDeletable(string $path): bool {
		return false;
	}

	#[\Override]
	public function isSharable(string $path): bool {
		return false;
	}

	#[\Override]
	public function getPermissions(string $path): int {
		return 0;
	}

	#[\Override]
	public function file_exists(string $path): bool {
		return $path === '';
	}

	#[\Override]
	public function filemtime(string $path): int|false {
		return ($path === '') ? time() : false;
	}

	#[\Override]
	public function file_get_contents(string $path): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function file_put_contents(string $path, mixed $data): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function unlink(string $path): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function rename(string $source, string $target): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function copy(string $source, string $target): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function fopen(string $path, string $mode): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function getMimeType(string $path): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function hash(string $type, string $path, bool $raw = false): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function free_space(string $path): int {
		return FileInfo::SPACE_UNKNOWN;
	}

	#[\Override]
	public function touch(string $path, ?int $mtime = null): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function getLocalFile(string $path): string|false {
		return false;
	}

	#[\Override]
	public function hasUpdated(string $path, int $time): bool {
		return false;
	}

	#[\Override]
	public function getETag(string $path): string {
		return '';
	}

	#[\Override]
	public function isLocal(): bool {
		return false;
	}

	#[\Override]
	public function getDirectDownload(string $path): array|false {
		return false;
	}

	#[\Override]
	public function getDirectDownloadById(string $fileId): array|false {
		return false;
	}

	#[\Override]
	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath, bool $preserveMtime = false): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function test(): bool {
		return true;
	}

	#[\Override]
	public function getOwner(string $path): string|false {
		return false;
	}

	#[\Override]
	public function getCache(string $path = '', ?IStorage $storage = null): ICache {
		return new NullCache();
	}
}

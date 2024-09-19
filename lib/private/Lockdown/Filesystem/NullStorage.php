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
	public function __construct($parameters) {
		parent::__construct($parameters);
	}

	public function getId(): string {
		return 'null';
	}

	public function mkdir($path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function rmdir($path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function opendir($path): IteratorDirectory {
		return new IteratorDirectory([]);
	}

	public function is_dir($path): bool {
		return $path === '';
	}

	public function is_file($path): bool {
		return false;
	}

	public function stat($path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function filetype($path): string|false {
		return ($path === '') ? 'dir' : false;
	}

	public function filesize($path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function isCreatable($path): bool {
		return false;
	}

	public function isReadable($path): bool {
		return $path === '';
	}

	public function isUpdatable($path): bool {
		return false;
	}

	public function isDeletable($path): bool {
		return false;
	}

	public function isSharable($path): bool {
		return false;
	}

	public function getPermissions($path): int {
		return 0;
	}

	public function file_exists($path): bool {
		return $path === '';
	}

	public function filemtime($path): int|false {
		return ($path === '') ? time() : false;
	}

	public function file_get_contents($path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function file_put_contents($path, $data): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function unlink($path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function rename($source, $target): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function copy($source, $target): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function fopen($path, $mode): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function getMimeType($path): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function hash($type, $path, $raw = false): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function free_space($path): int {
		return FileInfo::SPACE_UNKNOWN;
	}

	public function touch($path, $mtime = null): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function getLocalFile($path): string|false {
		return false;
	}

	public function hasUpdated($path, $time): bool {
		return false;
	}

	public function getETag($path): string {
		return '';
	}

	public function isLocal(): bool {
		return false;
	}

	public function getDirectDownload($path): array|false {
		return false;
	}

	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath, $preserveMtime = false): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): never {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function test(): bool {
		return true;
	}

	public function getOwner($path): string|false {
		return false;
	}

	public function getCache($path = '', $storage = null): ICache {
		return new NullCache();
	}
}

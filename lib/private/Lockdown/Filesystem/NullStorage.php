<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Lockdown\Filesystem;

use Icewind\Streams\IteratorDirectory;
use OC\Files\FileInfo;
use OC\Files\Storage\Common;
use OCP\Files\Storage\IStorage;

class NullStorage extends Common {
	public function __construct($parameters) {
		parent::__construct($parameters);
	}

	public function getId() {
		return 'null';
	}

	public function mkdir($path) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function rmdir($path) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function opendir($path) {
		return new IteratorDirectory([]);
	}

	public function is_dir($path) {
		return $path === '';
	}

	public function is_file($path) {
		return false;
	}

	public function stat($path) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function filetype($path) {
		return ($path === '') ? 'dir' : false;
	}

	public function filesize($path): false|int|float {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function isCreatable($path) {
		return false;
	}

	public function isReadable($path) {
		return $path === '';
	}

	public function isUpdatable($path) {
		return false;
	}

	public function isDeletable($path) {
		return false;
	}

	public function isSharable($path) {
		return false;
	}

	public function getPermissions($path) {
		return null;
	}

	public function file_exists($path) {
		return $path === '';
	}

	public function filemtime($path) {
		return ($path === '') ? time() : false;
	}

	public function file_get_contents($path) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function file_put_contents($path, $data) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function unlink($path) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function rename($source, $target) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function copy($source, $target) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function fopen($path, $mode) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function getMimeType($path) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function hash($type, $path, $raw = false) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function free_space($path) {
		return FileInfo::SPACE_UNKNOWN;
	}

	public function touch($path, $mtime = null) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function getLocalFile($path) {
		return false;
	}

	public function hasUpdated($path, $time) {
		return false;
	}

	public function getETag($path) {
		return '';
	}

	public function isLocal() {
		return false;
	}

	public function getDirectDownload($path) {
		return false;
	}

	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath, $preserveMtime = false) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		throw new \OC\ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function test() {
		return true;
	}

	public function getOwner($path) {
		return null;
	}

	public function getCache($path = '', $storage = null) {
		return new NullCache();
	}
}

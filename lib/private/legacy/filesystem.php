<?php

/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Class for abstraction of filesystem functions
 * This class won't call any filesystem functions for itself but but will pass them to the correct OC_Filestorage object
 * this class should also handle all the file permission related stuff
 *
 * Hooks provided:
 *   read(path)
 *   write(path, &run)
 *   post_write(path)
 *   create(path, &run) (when a file is created, both create and write will be emitted in that order)
 *   post_create(path)
 *   delete(path, &run)
 *   post_delete(path)
 *   rename(oldpath,newpath, &run)
 *   post_rename(oldpath,newpath)
 *   copy(oldpath,newpath, &run) (if the newpath doesn't exists yes, copy, create and write will be emitted in that order)
 *   post_rename(oldpath,newpath)
 *
 *   the &run parameter can be set to false to prevent the operation from occurring
 */

/**
 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
 */
class OC_Filesystem {
	/**
	 * get the mountpoint of the storage object for a path
	 * ( note: because a storage is not always mounted inside the fakeroot, the
	 * returned mountpoint is relative to the absolute root of the filesystem
	 * and doesn't take the chroot into account )
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 * @param string $path
	 * @return string
	 */
	static public function getMountPoint($path) {
		return \OC\Files\Filesystem::getMountPoint($path);
	}

	/**
	 * resolve a path to a storage and internal path
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 * @param string $path
	 * @return array consisting of the storage and the internal path
	 */
	static public function resolvePath($path) {
		return \OC\Files\Filesystem::resolvePath($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function init($user, $root) {
		return \OC\Files\Filesystem::init($user, $root);
	}

	/**
	 * get the default filesystem view
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 * @return \OC\Files\View
	 */
	static public function getView() {
		return \OC\Files\Filesystem::getView();
	}

	/**
	 * tear down the filesystem, removing all storage providers
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function tearDown() {
		\OC\Files\Filesystem::tearDown();
	}

	/**
	 * @brief get the relative path of the root data directory for the current user
	 * @return string
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 * Returns path like /admin/files
	 */
	static public function getRoot() {
		return \OC\Files\Filesystem::getRoot();
	}

	/**
	 * clear all mounts and storage backends
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	public static function clearMounts() {
		\OC\Files\Filesystem::clearMounts();
	}

	/**
	 * mount an \OC\Files\Storage\Storage in our virtual filesystem
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 * @param \OC\Files\Storage\Storage $class
	 * @param array $arguments
	 * @param string $mountpoint
	 */
	static public function mount($class, $arguments, $mountpoint) {
		\OC\Files\Filesystem::mount($class, $arguments, $mountpoint);
	}

	/**
	 * return the path to a local version of the file
	 * we need this because we can't know if a file is stored local or not from
	 * outside the filestorage and for some purposes a local file is needed
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 * @param string $path
	 * @return string
	 */
	static public function getLocalFile($path) {
		return \OC\Files\Filesystem::getLocalFile($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 * @param string $path
	 * @return string
	 */
	static public function getLocalFolder($path) {
		return \OC\Files\Filesystem::getLocalFolder($path);
	}

	/**
	 * return path to file which reflects one visible in browser
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 * @param string $path
	 * @return string
	 */
	static public function getLocalPath($path) {
		return \OC\Files\Filesystem::getLocalPath($path);
	}

	/**
	 * check if the requested path is valid
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 * @param string $path
	 * @return bool
	 */
	static public function isValidPath($path) {
		return \OC\Files\Filesystem::isValidPath($path);
	}

	/**
	 * checks if a file is blacklisted for storage in the filesystem
	 * Listens to write and rename hooks
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 * @param array $data from hook
	 */
	static public function isBlacklisted($data) {
		\OC\Files\Filesystem::isBlacklisted($data);
	}

	/**
	 * following functions are equivalent to their php builtin equivalents for arguments/return values.
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function mkdir($path) {
		return \OC\Files\Filesystem::mkdir($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function rmdir($path) {
		return \OC\Files\Filesystem::rmdir($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function opendir($path) {
		return \OC\Files\Filesystem::opendir($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function readdir($path) {
		return \OC\Files\Filesystem::readdir($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function is_dir($path) {
		return \OC\Files\Filesystem::is_dir($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function is_file($path) {
		return \OC\Files\Filesystem::is_file($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function stat($path) {
		return \OC\Files\Filesystem::stat($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function filetype($path) {
		return \OC\Files\Filesystem::filetype($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function filesize($path) {
		return \OC\Files\Filesystem::filesize($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function readfile($path) {
		return \OC\Files\Filesystem::readfile($path);
	}

	/**
	 * @deprecated Replaced by isReadable() as part of CRUDS
	 */
	static public function is_readable($path) {
		return \OC\Files\Filesystem::isReadable($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function isCreatable($path) {
		return \OC\Files\Filesystem::isCreatable($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function isReadable($path) {
		return \OC\Files\Filesystem::isReadable($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function isUpdatable($path) {
		return \OC\Files\Filesystem::isUpdatable($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function isDeletable($path) {
		return \OC\Files\Filesystem::isDeletable($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function isSharable($path) {
		return \OC\Files\Filesystem::isSharable($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function file_exists($path) {
		return \OC\Files\Filesystem::file_exists($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function filemtime($path) {
		return \OC\Files\Filesystem::filemtime($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function touch($path, $mtime = null) {
		return \OC\Files\Filesystem::touch($path, $mtime);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function file_get_contents($path) {
		return \OC\Files\Filesystem::file_get_contents($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function file_put_contents($path, $data) {
		return \OC\Files\Filesystem::file_put_contents($path, $data);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function unlink($path) {
		return \OC\Files\Filesystem::unlink($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function rename($path1, $path2) {
		return \OC\Files\Filesystem::rename($path1, $path2);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function copy($path1, $path2) {
		return \OC\Files\Filesystem::copy($path1, $path2);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function fopen($path, $mode) {
		return \OC\Files\Filesystem::fopen($path, $mode);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function toTmpFile($path) {
		return \OC\Files\Filesystem::toTmpFile($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function fromTmpFile($tmpFile, $path) {
		return \OC\Files\Filesystem::fromTmpFile($tmpFile, $path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function getMimeType($path) {
		return \OC\Files\Filesystem::getMimeType($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function hash($type, $path, $raw = false) {
		return \OC\Files\Filesystem::hash($type, $path, $raw);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function free_space($path = '/') {
		return \OC\Files\Filesystem::free_space($path);
	}

	/**
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 */
	static public function search($query) {
		return \OC\Files\Filesystem::search($query);
	}

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 * @param string $path
	 * @param int $time
	 * @return bool
	 */
	static public function hasUpdated($path, $time) {
		return \OC\Files\Filesystem::hasUpdated($path, $time);
	}

	/**
	 * normalize a path
	 *
	 * @deprecated OC_Filesystem is replaced by \OC\Files\Filesystem
	 * @param string $path
	 * @param bool $stripTrailingSlash
	 * @return string
	 */
	public static function normalizePath($path, $stripTrailingSlash = true) {
		return \OC\Files\Filesystem::normalizePath($path, $stripTrailingSlash);
	}
}

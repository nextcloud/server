<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;
use OCP\Util;

/**
 * listen to filesystem hooks and change the cache accordingly
 */
class Updater {

	/**
	 * resolve a path to a storage and internal path
	 *
	 * @param string $path the relative path
	 * @return array consisting of the storage and the internal path
	 */
	static public function resolvePath($path) {
		$view = \OC\Files\Filesystem::getView();
		return $view->resolvePath($path);
	}

	/**
	 * perform a write update
	 *
	 * @param string $path the relative path of the file
	 */
	static public function writeUpdate($path) {
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 * @var string $internalPath
		 */
		list($storage, $internalPath) = self::resolvePath($path);
		if ($storage) {
			$cache = $storage->getCache($internalPath);
			$scanner = $storage->getScanner($internalPath);
			$scanner->scan($internalPath, Scanner::SCAN_SHALLOW);
			$cache->correctFolderSize($internalPath);
			self::correctFolder($path, $storage->filemtime($internalPath));
		}
	}

	/**
	 * perform a delete update
	 *
	 * @param string $path the relative path of the file
	 */
	static public function deleteUpdate($path) {
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 * @var string $internalPath
		 */
		list($storage, $internalPath) = self::resolvePath($path);
		if ($storage) {
			$cache = $storage->getCache($internalPath);
			$cache->remove($internalPath);
			$cache->correctFolderSize($internalPath);
			self::correctFolder($path, time());
		}
	}

	/**
	 * preform a rename update
	 *
	 * @param string $from the relative path of the source file
	 * @param string $to the relative path of the target file
	 */
	static public function renameUpdate($from, $to) {
		/**
		 * @var \OC\Files\Storage\Storage $storageFrom
		 * @var \OC\Files\Storage\Storage $storageTo
		 * @var string $internalFrom
		 * @var string $internalTo
		 */
		list($storageFrom, $internalFrom) = self::resolvePath($from);
		list($storageTo, $internalTo) = self::resolvePath($to);
		if ($storageFrom && $storageTo) {
			if ($storageFrom === $storageTo) {
				$cache = $storageFrom->getCache($internalFrom);
				$cache->move($internalFrom, $internalTo);
				$cache->correctFolderSize($internalFrom);
				$cache->correctFolderSize($internalTo);
				self::correctFolder($from, time());
				self::correctFolder($to, time());
			} else {
				self::deleteUpdate($from);
				self::writeUpdate($to);
			}
		}
	}

	/**
	 * Update the mtime and ETag of all parent folders
	 *
	 * @param string $path
	 * @param string $time
	 */
	static public function correctFolder($path, $time) {
		if ($path !== '' && $path !== '/') {
			$parent = dirname($path);
			if ($parent === '.' || $parent === '\\') {
				$parent = '';
			}
			/**
			 * @var \OC\Files\Storage\Storage $storage
			 * @var string $internalPath
			 */
			list($storage, $internalPath) = self::resolvePath($parent);
			if ($storage) {
				$cache = $storage->getCache();
				$id = $cache->getId($internalPath);
				if ($id !== -1) {
					$cache->update($id, array('mtime' => $time, 'etag' => $storage->getETag($internalPath)));
					self::correctFolder($parent, $time);
				} else {
					Util::writeLog('core', 'Path not in cache: '.$internalPath, Util::ERROR);
				}
			}
		}
	}

	/**
	 * @param array $params
	 */
	static public function writeHook($params) {
		self::writeUpdate($params['path']);
	}

	/**
	 * @param array $params
	 */
	static public function touchHook($params) {
		$path = $params['path'];
		list($storage, $internalPath) = self::resolvePath($path);
		$cache = $storage->getCache();
		$id = $cache->getId($internalPath);
		if ($id !== -1) {
			$cache->update($id, array('etag' => $storage->getETag($internalPath)));
		}
		self::writeUpdate($path);
	}

	/**
	 * @param array $params
	 */
	static public function renameHook($params) {
		self::renameUpdate($params['oldpath'], $params['newpath']);
	}

	/**
	 * @param array $params
	 */
	static public function deleteHook($params) {
		self::deleteUpdate($params['path']);
	}
}

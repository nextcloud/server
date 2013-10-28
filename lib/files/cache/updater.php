<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

/**
 * listen to filesystem hooks and change the cache accordingly
 */
class Updater {

	/**
	 * resolve a path to a storage and internal path
	 *
	 * @param string $path
	 * @return array consisting of the storage and the internal path
	 */
	static public function resolvePath($path) {
		$uidOwner = \OC\Files\Filesystem::getOwner($path);
		$info = \OC\Files\Filesystem::getFileInfo($path);
		$view = new \OC\Files\View('/' . $uidOwner);
		return $view->resolvePath($info['path']);
	}

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

			/**
			 * @var \OC\Files\Storage\Storage $storage
			 * @var string $internalPath
			 */
			list($storage, $internalPath) = self::resolvePath(dirname($path));
			$owner = \OC\Files\Filesystem::getOwner($path);
			$view = new \OC\Files\View($owner);


			$cache = $storage->getCache();
			$id = $cache->getId($internalPath);

			while ($id !== -1) {
				error_log("while loop: " . $id);
				$cache->update($id, array('mtime' => $time, 'etag' => $storage->getETag($internalPath)));
				//$id = $cache->getParentId($internalPath);
				$internalPath = dirname($internalPath);
				//if ($internalPath === '.') {
				//	$internalPath = '';
				//}
				list($storage, $internalPath) = $view->resolvePath($internalPath);
				$cache = $storage->getCache();
				$id = $cache->getId($internalPath);

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

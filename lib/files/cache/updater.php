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
		$view = \OC\Files\Filesystem::getView();
		return $view->resolvePath($path);
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

	/**
	* Update the mtime and ETag of all parent folders
	*
	* @param string $path
	* @param string $time
	*/
	static public function correctFolder($path, $time) {
		if ($path !== '' && $path !== '/') {
			$parent = dirname($path);
			if ($parent === '.') {
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
	static public function renameHook($params) {
		self::deleteUpdate($params['oldpath']);
		self::writeUpdate($params['newpath']);
	}

	/**
	 * @param array $params
	 */
	static public function deleteHook($params) {
		self::deleteUpdate($params['path']);
	}
}

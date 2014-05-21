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
	 * @param string $path the relative path
	 * @return array an array consisting of the storage and the internal path
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
			$data = $scanner->scan($internalPath, Scanner::SCAN_SHALLOW);
			$cache->correctFolderSize($internalPath, $data);
			self::correctFolder($path, $storage->filemtime($internalPath));
			self::correctParentStorageMtime($storage, $internalPath);
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
			$parent = dirname($internalPath);
			if ($parent === '.') {
				$parent = '';
			}
			$cache = $storage->getCache($internalPath);
			$cache->remove($internalPath);
			$cache->correctFolderSize($parent);
			self::correctFolder($path, time());
			self::correctParentStorageMtime($storage, $internalPath);
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
		// if it's a moved mountpoint we dont need to do anything
		if ($internalFrom === '') {
			return;
		}
		list($storageTo, $internalTo) = self::resolvePath($to);
		if ($storageFrom && $storageTo) {
			if ($storageFrom === $storageTo) {
				$cache = $storageFrom->getCache($internalFrom);
				$cache->move($internalFrom, $internalTo);
				if (pathinfo($internalFrom, PATHINFO_EXTENSION) !== pathinfo($internalTo, PATHINFO_EXTENSION)) {
					// redetect mime type change
					$mimeType = $storageTo->getMimeType($internalTo);
					$fileId = $storageTo->getCache()->getId($internalTo);
					$storageTo->getCache()->update($fileId, array('mimetype' => $mimeType));
				}
				$cache->correctFolderSize($internalFrom);
				$cache->correctFolderSize($internalTo);
				self::correctFolder($from, time());
				self::correctFolder($to, time());
				self::correctParentStorageMtime($storageFrom, $internalFrom);
				self::correctParentStorageMtime($storageTo, $internalTo);
			} else {
				self::deleteUpdate($from);
				self::writeUpdate($to);
			}
		}
	}

	/**
	 * get file owner and path
	 * @param string $filename
	 * @return string[] with the oweners uid and the owners path
	 */
	private static function getUidAndFilename($filename) {

		$uid = \OC\Files\Filesystem::getOwner($filename);
		\OC\Files\Filesystem::initMountPoints($uid);

		if ($uid != \OCP\User::getUser()) {
			$info = \OC\Files\Filesystem::getFileInfo($filename);
			if (!$info) {
				return array($uid, '/files/' . $filename);
			}
			$ownerView = new \OC\Files\View('/' . $uid . '/files');
			$filename = $ownerView->getPath($info['fileid']);
		}
		return array($uid, '/files/' . $filename);
	}

	/**
	 * Update the mtime and ETag of all parent folders
	 *
	 * @param string $path
	 * @param string $time
	 */
	static public function correctFolder($path, $time) {
		if ($path !== '' && $path !== '/') {

			list($owner, $realPath) = self::getUidAndFilename(dirname($path));

			/**
			 * @var \OC\Files\Storage\Storage $storage
			 * @var string $internalPath
			 */
			$view = new \OC\Files\View('/' . $owner);

			list($storage, $internalPath) = $view->resolvePath($realPath);
			$cache = $storage->getCache();
			$id = $cache->getId($internalPath);

			while ($id !== -1) {
				$cache->update($id, array('mtime' => $time, 'etag' => $storage->getETag($internalPath)));
				if ($realPath !== '') {
					$realPath = dirname($realPath);
					if ($realPath === DIRECTORY_SEPARATOR) {
						$realPath = "";
					}
					// check storage for parent in case we change the storage in this step
					list($storage, $internalPath) = $view->resolvePath($realPath);
					$cache = $storage->getCache();
					$id = $cache->getId($internalPath);
				} else {
					$id = -1;
				}
			}
		}
	}

	/**
	 * update the storage_mtime of the parent
	 *
	 * @param \OC\Files\Storage\Storage $storage
	 * @param string $internalPath
	 */
	static private function correctParentStorageMtime($storage, $internalPath) {
		$cache = $storage->getCache();
		$parentId = $cache->getParentId($internalPath);
		$parent = dirname($internalPath);
		if ($parentId != -1) {
			$cache->update($parentId, array('storage_mtime' => $storage->filemtime($parent)));
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

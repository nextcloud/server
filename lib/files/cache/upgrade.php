<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

class Upgrade {
	/**
	 * @var Legacy $legacy
	 */
	private $legacy;

	private $numericIds = array();

	private $mimeTypeIds = array();

	/**
	 * @param Legacy $legacy
	 */
	public function __construct($legacy) {
		$this->legacy = $legacy;
	}

	/**
	 * Preform a shallow upgrade
	 *
	 * @param string $path
	 * @param int $mode
	 */
	function upgradePath($path, $mode = Scanner::SCAN_RECURSIVE) {
		if (!$this->legacy->hasItems()) {
			return;
		}
		\OC_Hook::emit('\OC\Files\Cache\Upgrade', 'migrate_path', $path);

		if ($row = $this->legacy->get($path)) {
			$data = $this->getNewData($row);
			$this->insert($data);

			$this->upgradeChilds($data['id'], $mode);
		}
	}

	/**
	 * @param int $id
	 */
	function upgradeChilds($id, $mode = Scanner::SCAN_RECURSIVE) {
		$children = $this->legacy->getChildren($id);
		foreach ($children as $child) {
			$childData = $this->getNewData($child);
			\OC_Hook::emit('\OC\Files\Cache\Upgrade', 'migrate_path', $child['path']);
			$this->insert($childData);
			if ($mode == Scanner::SCAN_RECURSIVE) {
				$this->upgradeChilds($child['id']);
			}
		}
	}

	/**
	 * @param array $data the data for the new cache
	 */
	function insert($data) {
		if (!$this->inCache($data['storage'], $data['path_hash'])) {
			$insertQuery = \OC_DB::prepare('INSERT INTO `*PREFIX*filecache`
					( `fileid`, `storage`, `path`, `path_hash`, `parent`, `name`, `mimetype`, `mimepart`, `size`, `mtime`, `encrypted` )
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

			$insertQuery->execute(array($data['id'], $data['storage'], $data['path'], $data['path_hash'], $data['parent'], $data['name'],
				$data['mimetype'], $data['mimepart'], $data['size'], $data['mtime'], $data['encrypted']));
		}
	}

	/**
	 * @param string $storage
	 * @param string $pathHash
	 * @return bool
	 */
	function inCache($storage, $pathHash) {
		$query = \OC_DB::prepare('SELECT `fileid` FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path_hash` = ?');
		$result = $query->execute(array($storage, $pathHash));
		return (bool)$result->fetchRow();
	}

	/**
	 * get the new data array from the old one
	 *
	 * @param array $data the data from the old cache
	 * @return array
	 */
	function getNewData($data) {
		$newData = $data;
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($data['path']);
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 * @var string $internalPath;
		 */
		$newData['path_hash'] = md5($internalPath);
		$newData['path'] = $internalPath;
		$newData['storage'] = $this->getNumericId($storage);
		$newData['parent'] = ($internalPath === '') ? -1 : $data['parent'];
		$newData['permissions'] = ($data['writable']) ? \OCP\PERMISSION_ALL : \OCP\PERMISSION_READ;
		$newData['storage_object'] = $storage;
		$newData['mimetype'] = $this->getMimetypeId($newData['mimetype'], $storage);
		$newData['mimepart'] = $this->getMimetypeId($newData['mimepart'], $storage);
		return $newData;
	}

	/**
	 * get the numeric storage id
	 *
	 * @param \OC\Files\Storage\Storage $storage
	 * @return int
	 */
	function getNumericId($storage) {
		$storageId = $storage->getId();
		if (!isset($this->numericIds[$storageId])) {
			$cache = $storage->getCache();
			$this->numericIds[$storageId] = $cache->getNumericStorageId();
		}
		return $this->numericIds[$storageId];
	}

	/**
	 * @param string $mimetype
	 * @param \OC\Files\Storage\Storage $storage
	 * @return int
	 */
	function getMimetypeId($mimetype, $storage) {
		if (!isset($this->mimeTypeIds[$mimetype])) {
			$cache = new Cache($storage);
			$this->mimeTypeIds[$mimetype] = $cache->getMimetypeId($mimetype);
		}
		return $this->mimeTypeIds[$mimetype];
	}

	/**
	 * check if a cache upgrade is required for $user
	 *
	 * @param string $user
	 * @return bool
	 */
	static function needUpgrade($user) {
		$cacheVersion = (int)\OCP\Config::getUserValue($user, 'files', 'cache_version', 4);
		return $cacheVersion < 5;
	}

	/**
	 * mark the filecache as upgrade
	 *
	 * @param string $user
	 */
	static function upgradeDone($user) {
		\OCP\Config::setUserValue($user, 'files', 'cache_version', 5);
	}
}

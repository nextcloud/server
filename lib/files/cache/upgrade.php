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
			if ($data) {
				$this->insert($data);
				$this->upgradeChilds($data['id'], $mode);
			}
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
			if ($childData) {
				$this->insert($childData);
				if ($mode == Scanner::SCAN_RECURSIVE) {
					$this->upgradeChilds($child['id']);
				}
			}
		}
	}

	/**
	 * @param array $data the data for the new cache
	 */
	function insert($data) {
		static $insertQuery = null;
		if(is_null($insertQuery)) {
			$insertQuery = \OC_DB::prepare('INSERT INTO `*PREFIX*filecache`
				( `fileid`, `storage`, `path`, `path_hash`, `parent`, `name`, `mimetype`, `mimepart`, `size`, `mtime`, `encrypted`, `etag` )
				VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
		}
		if (!$this->inCache($data['storage'], $data['path_hash'], $data['id'])) {
			$insertQuery->execute(array($data['id'], $data['storage'],
				$data['path'], $data['path_hash'], $data['parent'], $data['name'],
				$data['mimetype'], $data['mimepart'], $data['size'], $data['mtime'], $data['encrypted'], $data['etag']));
		}
	}

	/**
	 * @param string $storage
	 * @param string $pathHash
	 * @param string $id
	 * @return bool
	 */
	function inCache($storage, $pathHash, $id) {
		static $query = null;
		if(is_null($query)) {
			$query = \OC_DB::prepare('SELECT `fileid` FROM `*PREFIX*filecache` WHERE (`storage` = ? AND `path_hash` = ?) OR `fileid` = ?');
		}
		$result = $query->execute(array($storage, $pathHash, $id));
		return (bool)$result->fetchRow();
	}

	/**
	 * get the new data array from the old one
	 *
	 * @param array $data the data from the old cache
	 * Example data array
	 * Array
	 *	(
	 *		[id] => 418
	 *		[path] => /tina/files/picture.jpg		//relative to datadir
	 *		[path_hash] => 66d4547e372888deed80b24fec9b192b
	 *		[parent] => 234
	 *		[name] => picture.jpg
	 *		[user] => tina
	 *		[size] => 1265283
	 *		[ctime] => 1363909709
	 *		[mtime] => 1363909709
	 *		[mimetype] => image/jpeg
	 *		[mimepart] => image
	 *		[encrypted] => 0
	 *		[versioned] => 0
	 *		[writable] => 1
	 *	)
	 *
	 * @return array
	 */
	function getNewData($data) {
		//Make sure there is a path, otherwise we can do nothing.
		if(!isset($data['path'])) {
			return false;
		}
		$newData = $data;
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 * @var string $internalPath;
		 */
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($data['path']);
		if ($storage) {
			$newData['etag'] = $data['etag'];
			$newData['path_hash'] = md5($internalPath);
			$newData['path'] = $internalPath;
			$newData['storage'] = $this->getNumericId($storage);
			$newData['parent'] = ($internalPath === '') ? -1 : $data['parent'];
			$newData['permissions'] = ($data['writable']) ? \OCP\PERMISSION_ALL : \OCP\PERMISSION_READ;
			$newData['storage_object'] = $storage;
			$newData['mimetype'] = $this->getMimetypeId($newData['mimetype'], $storage);
			$newData['mimepart'] = $this->getMimetypeId($newData['mimepart'], $storage);
			return $newData;
		} else {
			\OC_Log::write('core', 'Unable to migrate data from old cache for '.$data['path'].' because the storage was not found', \OC_Log::ERROR);
			return false;
		}
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

	/**
	 * Does a "silent" upgrade, i.e. without an Event-Source as triggered
	 * on User-Login via Ajax. This method is called within the regular
	 * ownCloud upgrade.
	 *
	 * @param string $user a User ID
	 */
	public static function doSilentUpgrade($user) {
		if(!self::needUpgrade($user)) {
			return;
		}
		$legacy = new \OC\Files\Cache\Legacy($user);
		if ($legacy->hasItems()) {
			\OC_DB::beginTransaction();
			$upgrade = new \OC\Files\Cache\Upgrade($legacy);
			$upgrade->upgradePath('/' . $user . '/files');
			\OC_DB::commit();
		}
		\OC\Files\Cache\Upgrade::upgradeDone($user);
	}
}

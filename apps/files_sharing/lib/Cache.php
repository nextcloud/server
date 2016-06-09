<?php
/**
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing;

use OC\Files\Cache\Wrapper\CacheJail;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Storage\IStorage;

/**
 * Metadata cache for shared files
 *
 * don't use this class directly if you need to get metadata, use \OC\Files\Filesystem::getFileInfo instead
 */
class Cache extends CacheJail {
	/**
	 * @var \OC\Files\Storage\Shared
	 */
	private $storage;

	/**
	 * @var IStorage
	 */
	private $sourceStorage;

	/**
	 * @var ICacheEntry
	 */
	private $sourceRootInfo;

	/**
	 * @var \OCP\Files\Cache\ICache
	 */
	private $sourceCache;

	/**
	 * @param \OC\Files\Storage\Shared $storage
	 * @param IStorage $sourceStorage
	 * @param ICacheEntry $sourceRootInfo
	 */
	public function __construct($storage, IStorage $sourceStorage, ICacheEntry $sourceRootInfo) {
		$this->storage = $storage;
		$this->sourceStorage = $sourceStorage;
		$this->sourceRootInfo = $sourceRootInfo;
		$this->sourceCache = $sourceStorage->getCache();
		parent::__construct(
			$this->sourceCache,
			$this->sourceRootInfo->getPath()
		);
	}

	public function getNumericStorageId() {
		if (isset($this->numericId)) {
			return $this->numericId;
		} else {
			return false;
		}
	}

	protected function formatCacheEntry($entry) {
		$path = isset($entry['path']) ? $entry['path'] : '';
		$entry = parent::formatCacheEntry($entry);
		$sharePermissions = $this->storage->getPermissions($path);
		if (isset($entry['permissions'])) {
			$entry['permissions'] &= $sharePermissions;
		} else {
			$entry['permissions'] = $sharePermissions;
		}
		$entry['uid_owner'] = $this->storage->getOwner($path);
		$entry['displayname_owner'] = \OC_User::getDisplayName($entry['uid_owner']);
		if ($path === '') {
			$entry['is_share_mount_point'] = true;
		}
		return $entry;
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		// Not a valid action for Shared Cache
	}
}

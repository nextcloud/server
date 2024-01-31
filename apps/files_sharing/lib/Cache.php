<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Sharing;

use OC\Files\Cache\FailedCache;
use OC\Files\Cache\Wrapper\CacheJail;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Storage\Wrapper\Jail;
use OC\User\DisplayNameCache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\StorageNotAvailableException;
use OCP\Share\IShare;

/**
 * Metadata cache for shared files
 *
 * don't use this class directly if you need to get metadata, use \OC\Files\Filesystem::getFileInfo instead
 */
class Cache extends CacheJail {
	/** @var SharedStorage */
	private $storage;
	private ICacheEntry $sourceRootInfo;
	private bool $rootUnchanged = true;
	private ?string $ownerDisplayName = null;
	private $numericId;
	private DisplayNameCache $displayNameCache;
	private IShare $share;

	/**
	 * @param SharedStorage $storage
	 */
	public function __construct(
		$storage,
		ICacheEntry $sourceRootInfo,
		DisplayNameCache $displayNameCache,
		IShare $share
	) {
		$this->storage = $storage;
		$this->sourceRootInfo = $sourceRootInfo;
		$this->numericId = $sourceRootInfo->getStorageId();
		$this->displayNameCache = $displayNameCache;
		$this->share = $share;

		parent::__construct(
			null,
			''
		);
	}

	protected function getRoot() {
		if ($this->root === '') {
			$absoluteRoot = $this->sourceRootInfo->getPath();

			// the sourceRootInfo path is the absolute path of the folder in the "real" storage
			// in the case where a folder is shared from a Jail we need to ensure that the share Jail
			// has its root set relative to the source Jail
			$currentStorage = $this->storage->getSourceStorage();
			if ($currentStorage->instanceOfStorage(Jail::class)) {
				/** @var Jail $currentStorage */
				$absoluteRoot = $currentStorage->getJailedPath($absoluteRoot);
			}
			$this->root = $absoluteRoot;
		}
		return $this->root;
	}

	protected function getGetUnjailedRoot() {
		return $this->sourceRootInfo->getPath();
	}

	public function getCache() {
		if (is_null($this->cache)) {
			$sourceStorage = $this->storage->getSourceStorage();
			if ($sourceStorage) {
				$this->cache = $sourceStorage->getCache();
			} else {
				// don't set $this->cache here since sourceStorage will be set later
				return new FailedCache();
			}
		}
		return $this->cache;
	}

	public function getNumericStorageId() {
		if (isset($this->numericId)) {
			return $this->numericId;
		} else {
			return -1;
		}
	}

	public function get($file) {
		if ($this->rootUnchanged && ($file === '' || $file === $this->sourceRootInfo->getId())) {
			return $this->formatCacheEntry(clone $this->sourceRootInfo, '');
		}
		return parent::get($file);
	}

	public function update($id, array $data) {
		$this->rootUnchanged = false;
		parent::update($id, $data);
	}

	public function insert($file, array $data) {
		$this->rootUnchanged = false;
		return parent::insert($file, $data);
	}

	public function remove($file) {
		$this->rootUnchanged = false;
		parent::remove($file);
	}

	public function moveFromCache(\OCP\Files\Cache\ICache $sourceCache, $sourcePath, $targetPath) {
		$this->rootUnchanged = false;
		return parent::moveFromCache($sourceCache, $sourcePath, $targetPath);
	}

	protected function formatCacheEntry($entry, $path = null) {
		if (is_null($path)) {
			$path = $entry['path'] ?? '';
			$entry['path'] = $this->getJailedPath($path);
		} else {
			$entry['path'] = $path;
		}

		try {
			if (isset($entry['permissions'])) {
				$entry['permissions'] &= $this->share->getPermissions();
			} else {
				$entry['permissions'] = $this->storage->getPermissions($entry['path']);
			}

			if ($this->share->getNodeId() === $entry['fileid']) {
				$entry['name'] = basename($this->share->getTarget());
			}
		} catch (StorageNotAvailableException $e) {
			// thrown by FailedStorage e.g. when the sharer does not exist anymore
			// (IDE may say the exception is never thrown – false negative)
			$sharePermissions = 0;
		}
		$entry['uid_owner'] = $this->share->getShareOwner();
		$entry['displayname_owner'] = $this->getOwnerDisplayName();
		if ($path === '') {
			$entry['is_share_mount_point'] = true;
		}
		return $entry;
	}

	private function getOwnerDisplayName() {
		if (!$this->ownerDisplayName) {
			$uid = $this->share->getShareOwner();
			$this->ownerDisplayName = $this->displayNameCache->getDisplayName($uid) ?? $uid;
		}
		return $this->ownerDisplayName;
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		// Not a valid action for Shared Cache
	}

	public function getQueryFilterForStorage(): ISearchOperator {
		$storageFilter = \OC\Files\Cache\Cache::getQueryFilterForStorage();

		// Do the normal jail behavior for non files
		if ($this->storage->getItemType() !== 'file') {
			return $this->addJailFilterQuery($storageFilter);
		}

		// for single file shares we don't need to do the LIKE
		return new SearchBinaryOperator(
			ISearchBinaryOperator::OPERATOR_AND,
			[
				$storageFilter,
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', $this->getGetUnjailedRoot()),
			]
		);
	}

	public function getCacheEntryFromSearchResult(ICacheEntry $rawEntry): ?ICacheEntry {
		if ($rawEntry->getStorageId() === $this->getNumericStorageId()) {
			return parent::getCacheEntryFromSearchResult($rawEntry);
		} else {
			return null;
		}
	}
}

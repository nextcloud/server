<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Jagszent <daniel@jagszent.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OC\Files\Cache\Wrapper;

use OC\Files\Cache\Cache;
use OC\Files\Cache\QuerySearchHelper;
use OC\Files\Search\SearchQuery;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Search\ISearchQuery;

/**
 * Jail to a subdirectory of the wrapped cache
 */
class CacheJail extends CacheWrapper {
	/**
	 * @var string
	 */
	protected $root;
	protected $unjailedRoot;

	/**
	 * @param \OCP\Files\Cache\ICache $cache
	 * @param string $root
	 */
	public function __construct($cache, $root) {
		parent::__construct($cache);
		$this->root = $root;
		$this->connection = \OC::$server->getDatabaseConnection();
		$this->mimetypeLoader = \OC::$server->getMimeTypeLoader();

		if ($cache instanceof CacheJail) {
			$this->unjailedRoot = $cache->getSourcePath($root);
		} else {
			$this->unjailedRoot = $root;
		}
		$this->querySearchHelper = new QuerySearchHelper($this->mimetypeLoader);
	}

	protected function getRoot() {
		return $this->root;
	}

	/**
	 * Get the root path with any nested jails resolved
	 *
	 * @return string
	 */
	protected function getGetUnjailedRoot() {
		return $this->unjailedRoot;
	}

	protected function getSourcePath($path) {
		if ($path === '') {
			return $this->getRoot();
		} else {
			return $this->getRoot() . '/' . ltrim($path, '/');
		}
	}

	/**
	 * @param string $path
	 * @param null|string $root
	 * @return null|string the jailed path or null if the path is outside the jail
	 */
	protected function getJailedPath(string $path, string $root = null) {
		if ($root === null) {
			$root = $this->getRoot();
		}
		if ($root === '') {
			return $path;
		}
		$rootLength = strlen($root) + 1;
		if ($path === $root) {
			return '';
		} elseif (substr($path, 0, $rootLength) === $root . '/') {
			return substr($path, $rootLength);
		} else {
			return null;
		}
	}

	/**
	 * @param ICacheEntry $entry
	 * @return ICacheEntry
	 */
	protected function formatCacheEntry($entry) {
		if (isset($entry['path'])) {
			$entry['path'] = $this->getJailedPath($entry['path']);
		}
		return $entry;
	}

	/**
	 * Get the stored metadata of a file or folder
	 *
	 * @param string /int $file
	 * @return ICacheEntry|false
	 */
	public function get($file) {
		if (is_string($file) or $file == '') {
			$file = $this->getSourcePath($file);
		}
		return parent::get($file);
	}

	/**
	 * insert meta data for a new file or folder
	 *
	 * @param string $file
	 * @param array $data
	 *
	 * @return int file id
	 * @throws \RuntimeException
	 */
	public function insert($file, array $data) {
		return $this->getCache()->insert($this->getSourcePath($file), $data);
	}

	/**
	 * update the metadata in the cache
	 *
	 * @param int $id
	 * @param array $data
	 */
	public function update($id, array $data) {
		$this->getCache()->update($id, $data);
	}

	/**
	 * get the file id for a file
	 *
	 * @param string $file
	 * @return int
	 */
	public function getId($file) {
		return $this->getCache()->getId($this->getSourcePath($file));
	}

	/**
	 * get the id of the parent folder of a file
	 *
	 * @param string $file
	 * @return int
	 */
	public function getParentId($file) {
		return $this->getCache()->getParentId($this->getSourcePath($file));
	}

	/**
	 * check if a file is available in the cache
	 *
	 * @param string $file
	 * @return bool
	 */
	public function inCache($file) {
		return $this->getCache()->inCache($this->getSourcePath($file));
	}

	/**
	 * remove a file or folder from the cache
	 *
	 * @param string $file
	 */
	public function remove($file) {
		$this->getCache()->remove($this->getSourcePath($file));
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function move($source, $target) {
		$this->getCache()->move($this->getSourcePath($source), $this->getSourcePath($target));
	}

	/**
	 * Get the storage id and path needed for a move
	 *
	 * @param string $path
	 * @return array [$storageId, $internalPath]
	 */
	protected function getMoveInfo($path) {
		return [$this->getNumericStorageId(), $this->getSourcePath($path)];
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		$this->getCache()->remove($this->getRoot());
	}

	/**
	 * @param string $file
	 *
	 * @return int Cache::NOT_FOUND, Cache::PARTIAL, Cache::SHALLOW or Cache::COMPLETE
	 */
	public function getStatus($file) {
		return $this->getCache()->getStatus($this->getSourcePath($file));
	}

	private function formatSearchResults($results) {
		$finalResult = [];
		foreach ($results as $entry) {
			// Filter not accessible entries (e.g. some groupfolder entries when ACLs are enabled)
			$cacheWrapper = $this;
			while (($cacheWrapper instanceof CacheWrapper) && $entry !== false) {
				if (!($cacheWrapper instanceof CacheJail)) { // We apply the jail at the end
					$entry = $cacheWrapper->formatCacheEntry($entry);
				}
				$cacheWrapper = $cacheWrapper->getCache();
			}
			if ($entry === false) {
				continue;
			}

			// Unjailed the path (remove __groupfolder/<id> prefix)
			$entry['path'] = $this->getJailedPath($entry['path'], $this->getGetUnjailedRoot());
			$finalResult[] = $entry;
		}
		return $finalResult;
	}

	/**
	 * search for files matching $pattern
	 *
	 * @param string $pattern
	 * @return array an array of file data
	 */
	public function search($pattern) {
		if ($this->getGetUnjailedRoot() === '' || $this->getGetUnjailedRoot() === '/') {
			return parent::search($pattern);
		}

		// normalize pattern
		$pattern = $this->normalize($pattern);

		if ($pattern === '%%') {
			return [];
		}

		$query = $this->getQueryBuilder();
		$query->selectFileCache()
			->whereStorageId()
			->andWhere($query->expr()->orX(
				$query->expr()->like('path', $query->createNamedParameter($this->getGetUnjailedRoot() . '/%')),
				$query->expr()->eq('path', $query->createNamedParameter($this->getGetUnjailedRoot()))
			))
			->andWhere($query->expr()->iLike('name', $query->createNamedParameter($pattern)));

		$result = $query->execute();
		$files = $result->fetchAll();
		$result->closeCursor();

		$results = array_map(function (array $data) {
			return self::cacheEntryFromData($data, $this->mimetypeLoader);
		}, $files);
		return $this->formatSearchResults($results);
	}

	/**
	 * search for files by mimetype
	 *
	 * @param string $mimetype
	 * @return array
	 */
	public function searchByMime($mimetype) {
		if ($this->getGetUnjailedRoot() === '' || $this->getGetUnjailedRoot() === '/') {
			return parent::searchByMime($mimetype);
		}

		$mimeId = $this->mimetypeLoader->getId($mimetype);

		$query = $this->getQueryBuilder();
		$query->selectFileCache()
			->whereStorageId()
			->andWhere($query->expr()->orX(
				$query->expr()->like('path', $query->createNamedParameter($this->getGetUnjailedRoot() . '/%')),
				$query->expr()->eq('path', $query->createNamedParameter($this->getGetUnjailedRoot()))
			));

		if (strpos($mimetype, '/')) {
			$query->andWhere($query->expr()->eq('mimetype', $query->createNamedParameter($mimeId, IQueryBuilder::PARAM_INT)));
		} else {
			$query->andWhere($query->expr()->eq('mimepart', $query->createNamedParameter($mimeId, IQueryBuilder::PARAM_INT)));
		}

		$result = $query->execute();
		$files = $result->fetchAll();
		$result->closeCursor();

		$results = array_map(function (array $data) {
			return self::cacheEntryFromData($data, $this->mimetypeLoader);
		}, $files);
		return $this->formatSearchResults($results);
	}

	public function searchQuery(ISearchQuery $searchQuery) {
		if ($this->getGetUnjailedRoot() === '' || $this->getGetUnjailedRoot() === '/') {
			return parent::searchQuery($searchQuery);
		}

		$query = $this->buildSearchQuery($searchQuery);

		$query->andWhere($query->expr()->orX(
			$query->expr()->like('path', $query->createNamedParameter($this->getGetUnjailedRoot() . '/%')),
			$query->expr()->eq('path', $query->createNamedParameter($this->getGetUnjailedRoot()))
		));

		$result = $query->execute();
		$results = $this->searchResultToCacheEntries($result);
		$result->closeCursor();
		return $this->formatSearchResults($results);
	}

	/**
	 * update the folder size and the size of all parent folders
	 *
	 * @param string|boolean $path
	 * @param array $data (optional) meta data of the folder
	 */
	public function correctFolderSize($path, $data = null, $isBackgroundScan = false) {
		if ($this->getCache() instanceof Cache) {
			$this->getCache()->correctFolderSize($this->getSourcePath($path), $data, $isBackgroundScan);
		}
	}

	/**
	 * get the size of a folder and set it in the cache
	 *
	 * @param string $path
	 * @param array $entry (optional) meta data of the folder
	 * @return int
	 */
	public function calculateFolderSize($path, $entry = null) {
		if ($this->getCache() instanceof Cache) {
			return $this->getCache()->calculateFolderSize($this->getSourcePath($path), $entry);
		} else {
			return 0;
		}
	}

	/**
	 * get all file ids on the files on the storage
	 *
	 * @return int[]
	 */
	public function getAll() {
		// not supported
		return [];
	}

	/**
	 * find a folder in the cache which has not been fully scanned
	 *
	 * If multiply incomplete folders are in the cache, the one with the highest id will be returned,
	 * use the one with the highest id gives the best result with the background scanner, since that is most
	 * likely the folder where we stopped scanning previously
	 *
	 * @return string|bool the path of the folder or false when no folder matched
	 */
	public function getIncomplete() {
		// not supported
		return false;
	}

	/**
	 * get the path of a file on this storage by it's id
	 *
	 * @param int $id
	 * @return string|null
	 */
	public function getPathById($id) {
		$path = $this->getCache()->getPathById($id);
		if ($path === null) {
			return null;
		}

		return $this->getJailedPath($path);
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * Note that this should make sure the entries are removed from the source cache
	 *
	 * @param \OCP\Files\Cache\ICache $sourceCache
	 * @param string $sourcePath
	 * @param string $targetPath
	 */
	public function moveFromCache(\OCP\Files\Cache\ICache $sourceCache, $sourcePath, $targetPath) {
		if ($sourceCache === $this) {
			return $this->move($sourcePath, $targetPath);
		}
		return $this->getCache()->moveFromCache($sourceCache, $sourcePath, $this->getSourcePath($targetPath));
	}
}

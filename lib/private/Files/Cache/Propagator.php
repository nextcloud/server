<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
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

namespace OC\Files\Cache;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Cache\IPropagator;
use OCP\IDBConnection;

/**
 * Propagate etags and mtimes within the storage
 */
class Propagator implements IPropagator {
	/**
	 * @var \OC\Files\Storage\Storage
	 */
	protected $storage;

	/**
	 * @var IDBConnection
	 */
	private $connection;

	/**
	 * @param \OC\Files\Storage\Storage $storage
	 * @param IDBConnection $connection
	 */
	public function __construct(\OC\Files\Storage\Storage $storage, IDBConnection $connection) {
		$this->storage = $storage;
		$this->connection = $connection;
	}


	/**
	 * @param string $internalPath
	 * @param int $time
	 * @param int $sizeDifference number of bytes the file has grown
	 */
	public function propagateChange($internalPath, $time, $sizeDifference = 0) {
		$storageId = (int)$this->storage->getStorageCache()->getNumericId();

		$parents = $this->getParents($internalPath);

		$parentHashes = array_map('md5', $parents);
		$etag = uniqid(); // since we give all folders the same etag we don't ask the storage for the etag

		$builder = $this->connection->getQueryBuilder();
		$hashParams = array_map(function ($hash) use ($builder) {
			return $builder->expr()->literal($hash);
		}, $parentHashes);

		$builder->update('filecache')
			->set('mtime', $builder->createFunction('GREATEST(`mtime`, ' . $builder->createNamedParameter($time) . ')'))
			->set('etag', $builder->createNamedParameter($etag, IQueryBuilder::PARAM_STR))
			->where($builder->expr()->eq('storage', $builder->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
			->andWhere($builder->expr()->in('path_hash', $hashParams));

		$builder->execute();

		if ($sizeDifference !== 0) {
			// we need to do size separably so we can ignore entries with uncalculated size
			$builder = $this->connection->getQueryBuilder();
			$builder->update('filecache')
				->set('size', $builder->createFunction('`size` + ' . $builder->createNamedParameter($sizeDifference)))
				->where($builder->expr()->eq('storage', $builder->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
				->andWhere($builder->expr()->in('path_hash', $hashParams))
				->andWhere($builder->expr()->gt('size', $builder->expr()->literal(-1, IQueryBuilder::PARAM_INT)));
		}

		$builder->execute();
	}

	protected function getParents($path) {
		$parts = explode('/', $path);
		$parent = '';
		$parents = [];
		foreach ($parts as $part) {
			$parents[] = $parent;
			$parent = trim($parent . '/' . $part, '/');
		}
		return $parents;
	}
}

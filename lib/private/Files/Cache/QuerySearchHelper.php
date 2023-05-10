<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tobias Kaminsky <tobias@kaminsky.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Files\Cache;

use OC\Files\Cache\Wrapper\CacheJail;
use OC\Files\Node\Root;
use OC\Files\Search\QueryOptimizer\QueryOptimizer;
use OC\Files\Search\SearchBinaryOperator;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchQuery;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class QuerySearchHelper {
	/** @var IMimeTypeLoader */
	private $mimetypeLoader;
	/** @var IDBConnection */
	private $connection;
	/** @var SystemConfig */
	private $systemConfig;
	private LoggerInterface $logger;
	/** @var SearchBuilder */
	private $searchBuilder;
	/** @var QueryOptimizer */
	private $queryOptimizer;

	public function __construct(
		IMimeTypeLoader $mimetypeLoader,
		IDBConnection $connection,
		SystemConfig $systemConfig,
		LoggerInterface $logger,
		SearchBuilder $searchBuilder,
		QueryOptimizer $queryOptimizer
	) {
		$this->mimetypeLoader = $mimetypeLoader;
		$this->connection = $connection;
		$this->systemConfig = $systemConfig;
		$this->logger = $logger;
		$this->searchBuilder = $searchBuilder;
		$this->queryOptimizer = $queryOptimizer;
	}

	protected function getQueryBuilder() {
		return new CacheQueryBuilder(
			$this->connection,
			$this->systemConfig,
			$this->logger
		);
	}

	protected function applySearchConstraints(CacheQueryBuilder $query, ISearchQuery $searchQuery, array $caches): void {
		$storageFilters = array_values(array_map(function (ICache $cache) {
			return $cache->getQueryFilterForStorage();
		}, $caches));
		$storageFilter = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, $storageFilters);
		$filter = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [$searchQuery->getSearchOperation(), $storageFilter]);
		$this->queryOptimizer->processOperator($filter);

		$searchExpr = $this->searchBuilder->searchOperatorToDBExpr($query, $filter);
		if ($searchExpr) {
			$query->andWhere($searchExpr);
		}

		$this->searchBuilder->addSearchOrdersToQuery($query, $searchQuery->getOrder());

		if ($searchQuery->getLimit()) {
			$query->setMaxResults($searchQuery->getLimit());
		}
		if ($searchQuery->getOffset()) {
			$query->setFirstResult($searchQuery->getOffset());
		}
	}


	/**
	 * @return array<array-key, array{id: int, name: string, visibility: int, editable: int, ref_file_id: int, number_files: int}>
	 */
	public function findUsedTagsInCaches(ISearchQuery $searchQuery, array $caches): array {
		$query = $this->getQueryBuilder();
		$query->selectTagUsage();

		$this->applySearchConstraints($query, $searchQuery, $caches);

		$result = $query->execute();
		$tags = $result->fetchAll();
		$result->closeCursor();
		return $tags;
	}

	/**
	 * Perform a file system search in multiple caches
	 *
	 * the results will be grouped by the same array keys as the $caches argument to allow
	 * post-processing based on which cache the result came from
	 *
	 * @template T of array-key
	 * @param ISearchQuery $searchQuery
	 * @param array<T, ICache> $caches
	 * @return array<T, ICacheEntry[]>
	 */
	public function searchInCaches(ISearchQuery $searchQuery, array $caches): array {
		// search in multiple caches at once by creating one query in the following format
		// SELECT ... FROM oc_filecache WHERE
		//     <filter expressions from the search query>
		// AND (
		//     <filter expression for storage1> OR
		//     <filter expression for storage2> OR
		//     ...
		// );
		//
		// This gives us all the files matching the search query from all caches
		//
		// while the resulting rows don't have a way to tell what storage they came from (multiple storages/caches can share storage_id)
		// we can just ask every cache if the row belongs to them and give them the cache to do any post processing on the result.

		$builder = $this->getQueryBuilder();

		$query = $builder->selectFileCache('file', false);

		if ($this->searchBuilder->shouldJoinTags($searchQuery->getSearchOperation())) {
			$user = $searchQuery->getUser();
			if ($user === null) {
				throw new \InvalidArgumentException("Searching by tag requires the user to be set in the query");
			}
			$query
				->leftJoin('file', 'vcategory_to_object', 'tagmap', $builder->expr()->eq('file.fileid', 'tagmap.objid'))
				->leftJoin('tagmap', 'vcategory', 'tag', $builder->expr()->andX(
					$builder->expr()->eq('tagmap.type', 'tag.type'),
					$builder->expr()->eq('tagmap.categoryid', 'tag.id'),
					$builder->expr()->eq('tag.type', $builder->createNamedParameter('files')),
					$builder->expr()->eq('tag.uid', $builder->createNamedParameter($user->getUID()))
				))
				->leftJoin('file', 'systemtag_object_mapping', 'systemtagmap', $builder->expr()->andX(
					$builder->expr()->eq('file.fileid', $builder->expr()->castColumn('systemtagmap.objectid', IQueryBuilder::PARAM_INT)),
					$builder->expr()->eq('systemtagmap.objecttype', $builder->createNamedParameter('files'))
				))
				->leftJoin('systemtagmap', 'systemtag', 'systemtag', $builder->expr()->andX(
					$builder->expr()->eq('systemtag.id', 'systemtagmap.systemtagid'),
					$builder->expr()->eq('systemtag.visibility', $builder->createNamedParameter(true))
				));
		}

		$this->applySearchConstraints($query, $searchQuery, $caches);

		$result = $query->execute();
		$files = $result->fetchAll();

		$rawEntries = array_map(function (array $data) {
			return Cache::cacheEntryFromData($data, $this->mimetypeLoader);
		}, $files);

		$result->closeCursor();

		// loop through all caches for each result to see if the result matches that storage
		// results are grouped by the same array keys as the caches argument to allow the caller to distinguish the source of the results
		$results = array_fill_keys(array_keys($caches), []);
		foreach ($rawEntries as $rawEntry) {
			foreach ($caches as $cacheKey => $cache) {
				$entry = $cache->getCacheEntryFromSearchResult($rawEntry);
				if ($entry) {
					$results[$cacheKey][] = $entry;
				}
			}
		}
		return $results;
	}

	/**
	 * @return array{array<string, ICache>, array<string, IMountPoint>}
	 */
	public function getCachesAndMountPointsForSearch(Root $root, string $path, bool $limitToHome = false): array {
		$rootLength = strlen($path);
		$mount = $root->getMount($path);
		$storage = $mount->getStorage();
		$internalPath = $mount->getInternalPath($path);

		if ($internalPath !== '') {
			// a temporary CacheJail is used to handle filtering down the results to within this folder
			$caches = ['' => new CacheJail($storage->getCache(''), $internalPath)];
		} else {
			$caches = ['' => $storage->getCache('')];
		}
		$mountByMountPoint = ['' => $mount];

		if (!$limitToHome) {
			/** @var IMountPoint[] $mounts */
			$mounts = $root->getMountsIn($path);
			foreach ($mounts as $mount) {
				$storage = $mount->getStorage();
				if ($storage) {
					$relativeMountPoint = ltrim(substr($mount->getMountPoint(), $rootLength), '/');
					$caches[$relativeMountPoint] = $storage->getCache('');
					$mountByMountPoint[$relativeMountPoint] = $mount;
				}
			}
		}

		return [$caches, $mountByMountPoint];
	}
}

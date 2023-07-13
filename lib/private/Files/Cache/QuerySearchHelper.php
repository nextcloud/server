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
use OC\Files\Search\QueryOptimizer\QueryOptimizer;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchQuery;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
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
	private IGroupManager $groupManager;

	public function __construct(
		IMimeTypeLoader $mimetypeLoader,
		IDBConnection $connection,
		SystemConfig $systemConfig,
		LoggerInterface $logger,
		SearchBuilder $searchBuilder,
		QueryOptimizer $queryOptimizer,
		IGroupManager $groupManager,
	) {
		$this->mimetypeLoader = $mimetypeLoader;
		$this->connection = $connection;
		$this->systemConfig = $systemConfig;
		$this->logger = $logger;
		$this->searchBuilder = $searchBuilder;
		$this->queryOptimizer = $queryOptimizer;
		$this->groupManager = $groupManager;
	}

	protected function getQueryBuilder() {
		return new CacheQueryBuilder(
			$this->connection,
			$this->systemConfig,
			$this->logger
		);
	}

	private function isCompareEqual(ISearchOperator $operator, string $fieldName): bool {
		return
			$operator instanceof ISearchComparison &&
			$operator->getType() === ISearchComparison::COMPARE_EQUAL &&
			$operator->getField() === $fieldName;
	}

	private function checkStorageAndPathFilter(ISearchOperator $operator, array &$storageToPathsMap, array &$storageOtherFilters): void {
		if ($operator instanceof ISearchBinaryOperator && $operator->getType() === ISearchBinaryOperator::OPERATOR_AND && count($operator->getArguments()) == 2) {
			$a = $operator->getArguments()[0];
			$b = $operator->getArguments()[1];
			if ($this->isCompareEqual($a, "storage") && $this->isCompareEqual($b, "path")) {
				$storage = $a->getValue();
				$path = $b->getValue();
				\OC::$server->getLogger()->debug("QuerySearchHelper::checkStorageAndPathFilter: storage=" . $storage . " " . "path=" . $path);
				$storageToPathsMap[$storage][] = $path;
				return;
			}
		}
		$storageOtherFilters[] = $operator;
	}

	private function optimizeStorageFilters(array $storageFilters): array {
		//
		// Optimize the storage filters query, when there are many shared files.
		//
		// Originally for each shared file the following section is added to the SQL WHERE clause:
		//
		//   (`storage` = <storage-id>) AND (`path` = <file-path>)
		//
		// When many files are shared between the same two users, the storage part of the filter is repeated many times.
		//
		// Here we want to refactor the query to have a single filter for each storage
		// and provide all `path_hash` values for the same storage in the IN clause:
		//
		//   (`storage` = <storage-id>) AND (`path_hash` IN (<path-hash-1>, <path-hash-2>, ...))

		// Pick up single file shares to prepare more efficient query
		$storageToPathsMap = [];
		$storageOtherFilters = [];
		foreach ($storageFilters as $storageFilter) {
			$this->checkStorageAndPathFilter($storageFilter, $storageToPathsMap, $storageOtherFilters);
		}

		// Create filters for single file shares
		$singleFileFilters = [];
		foreach ($storageToPathsMap as $storage => $paths) {
			\OC::$server->getLogger()->debug("QuerySearchHelper::optimizeStorageFilters: storage=" . $storage . " " . "paths=" . implode(", ", $paths));
			$singleFileFilters[] = new SearchBinaryOperator(
				ISearchBinaryOperator::OPERATOR_AND,
				[
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', $storage),
					new SearchComparison(ISearchComparison::COMPARE_IN, 'path_hash', array_map(fn ($path) => md5($path), $paths))
				]
			);
		}

		return array_merge($storageOtherFilters, $singleFileFilters);
	}

	protected function applySearchConstraints(CacheQueryBuilder $query, ISearchQuery $searchQuery, array $caches): void {
		$storageFilters = array_values(array_map(function (ICache $cache) {
			return $cache->getQueryFilterForStorage();
		}, $caches));
		$optimizedStorageFilters = $this->optimizeStorageFilters($storageFilters);
		$storageFilter = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, $optimizedStorageFilters);
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

	protected function equipQueryForSystemTags(CacheQueryBuilder $query, IUser $user): void {
		$query->leftJoin('file', 'systemtag_object_mapping', 'systemtagmap', $query->expr()->andX(
			$query->expr()->eq('file.fileid', $query->expr()->castColumn('systemtagmap.objectid', IQueryBuilder::PARAM_INT)),
			$query->expr()->eq('systemtagmap.objecttype', $query->createNamedParameter('files'))
		));
		$on = $query->expr()->andX($query->expr()->eq('systemtag.id', 'systemtagmap.systemtagid'));
		if (!$this->groupManager->isAdmin($user->getUID())) {
			$on->add($query->expr()->eq('systemtag.visibility', $query->createNamedParameter(true)));
		}
		$query->leftJoin('systemtagmap', 'systemtag', 'systemtag', $on);
	}

	protected function equipQueryForDavTags(CacheQueryBuilder $query, IUser $user): void {
		$query
			->leftJoin('file', 'vcategory_to_object', 'tagmap', $query->expr()->eq('file.fileid', 'tagmap.objid'))
			->leftJoin('tagmap', 'vcategory', 'tag', $query->expr()->andX(
				$query->expr()->eq('tagmap.type', 'tag.type'),
				$query->expr()->eq('tagmap.categoryid', 'tag.id'),
				$query->expr()->eq('tag.type', $query->createNamedParameter('files')),
				$query->expr()->eq('tag.uid', $query->createNamedParameter($user->getUID()))
			));
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

		$requestedFields = $this->searchBuilder->extractRequestedFields($searchQuery->getSearchOperation());
		if (in_array('systemtag', $requestedFields)) {
			$this->equipQueryForSystemTags($query, $this->requireUser($searchQuery));
		}
		if (in_array('tagname', $requestedFields) || in_array('favorite', $requestedFields)) {
			$this->equipQueryForDavTags($query, $this->requireUser($searchQuery));
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

	protected function requireUser(ISearchQuery $searchQuery): IUser {
		$user = $searchQuery->getUser();
		if ($user === null) {
			throw new \InvalidArgumentException("This search operation requires the user to be set in the query");
		}
		return $user;
	}

	/**
	 * @return list{0?: array<array-key, ICache>, 1?: array<array-key, IMountPoint>}
	 */
	public function getCachesAndMountPointsForSearch(IRootFolder $root, string $path, bool $limitToHome = false): array {
		$rootLength = strlen($path);
		$mount = $root->getMount($path);
		$storage = $mount->getStorage();
		if ($storage === null) {
			return [];
		}
		$internalPath = $mount->getInternalPath($path);

		if ($internalPath !== '') {
			// a temporary CacheJail is used to handle filtering down the results to within this folder
			/** @var ICache[] $caches */
			$caches = ['' => new CacheJail($storage->getCache(''), $internalPath)];
		} else {
			/** @var ICache[] $caches */
			$caches = ['' => $storage->getCache('')];
		}
		/** @var IMountPoint[] $mountByMountPoint */
		$mountByMountPoint = ['' => $mount];

		if (!$limitToHome) {
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

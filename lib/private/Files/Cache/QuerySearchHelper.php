<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Cache;

use OC\DB\FilesSearchSnapshot;
use OC\Files\Cache\Wrapper\CacheJail;
use OC\Files\Search\FilesSearchQuery;
use OC\Files\Search\QueryOptimizer\QueryOptimizer;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchQuery;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchOrder;
use OCP\Files\Search\ISearchQuery;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FilesMetadata\IMetadataQuery;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;

class QuerySearchHelper {
	public function __construct(
		private readonly IMimeTypeLoader $mimetypeLoader,
		private readonly IDBConnection $connection,
		private readonly SearchBuilder $searchBuilder,
		private readonly QueryOptimizer $queryOptimizer,
		private readonly IGroupManager $groupManager,
		private readonly IFilesMetadataManager $filesMetadataManager,
		private readonly ?FilesSearchSnapshot $filesSearchSnapshot = null,
	) {
	}

	private function withConnection(IDBConnection $connection): self {
		return new self(
			$this->mimetypeLoader,
			$connection,
			$this->searchBuilder,
			$this->queryOptimizer,
			$this->groupManager,
			$this->filesMetadataManager,
			null,
		);
	}

	protected function getQueryBuilder(): CacheQueryBuilder {
		return new CacheQueryBuilder(
			$this->connection->getQueryBuilder(),
			$this->filesMetadataManager,
		);
	}

	/**
	 * @param CacheQueryBuilder $query
	 * @param ISearchQuery $searchQuery
	 * @param array $caches
	 * @param IMetadataQuery|null $metadataQuery
	 */
	protected function applySearchConstraints(
		CacheQueryBuilder $query,
		ISearchQuery $searchQuery,
		array $caches,
		?IMetadataQuery $metadataQuery = null,
	): void {
		$storageFilters = array_values(array_map(function (ICache $cache) {
			return $cache->getQueryFilterForStorage();
		}, $caches));
		$storageFilter = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, $storageFilters);
		$filter = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [$searchQuery->getSearchOperation(), $storageFilter]);
		$this->queryOptimizer->processOperator($filter);

		$searchExpr = $this->searchBuilder->searchOperatorToDBExpr($query, $filter, $metadataQuery);
		if ($searchExpr) {
			$query->andWhere($searchExpr);
		}

		$this->searchBuilder->addSearchOrdersToQuery($query, $searchQuery->getOrder(), $metadataQuery);

		if ($searchQuery->getLimit()) {
			$query->setMaxResults($searchQuery->getLimit());
		}
		if ($searchQuery->getOffset()) {
			$query->setFirstResult($searchQuery->getOffset());
		}
	}

	/**
	 * @return list<array{id: int, name: string, visibility: int, editable: int, ref_file_id: int, number_files: int}>
	 */
	public function findUsedTagsInCaches(ISearchQuery $searchQuery, array $caches): array {
		$query = $this->getQueryBuilder();
		$query->selectTagUsage();

		$this->applySearchConstraints($query, $searchQuery, $caches);

		$result = $query->executeQuery();
		/** @var list<array{id: int, name: string, visibility: int, editable: int, ref_file_id: int, number_files: int}> $tags */
		$tags = $result->fetchAllAssociative();
		$result->closeCursor();
		return $tags;
	}

	protected function equipQueryForSystemTags(CacheQueryBuilder $query, IUser $user): void {
		$query->leftJoin('file', 'systemtag_object_mapping', 'systemtagmap', $query->expr()->andX(
			$query->expr()->eq('file.fileid', $query->expr()->castColumn('systemtagmap.objectid', IQueryBuilder::PARAM_INT)),
			$query->expr()->eq('systemtagmap.objecttype', $query->createNamedParameter('files')),
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
				$query->expr()->eq('tagmap.categoryid', 'tag.id'),
				$query->expr()->eq('tag.type', $query->createNamedParameter('files')),
				$query->expr()->eq('tag.uid', $query->createNamedParameter($user->getUID())),
			));
	}

	protected function equipQueryForMounts(CacheQueryBuilder $query, IUser $user): void {
		$query
			->leftJoin('file', 'mounts', 'm', $query->expr()->andX(
				$query->expr()->eq('m.root_id', 'file.fileid'),
				$query->expr()->eq('m.user_id', $query->createNamedParameter($user->getUID())),
			));
	}

	protected function equipQueryForShares(CacheQueryBuilder $query): void {
		$query->join('file', 'share', 's', $query->expr()->eq('file.fileid', 's.file_source'));
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
		$context = $this->filesSearchContext($searchQuery, $caches);
		$prefix = $context === null ? null : $this->rankPrefix($searchQuery);
		if ($context !== null && $prefix !== null && $this->filesSearchSnapshot !== null) {
			$results = $this->filesSearchSnapshot->run(
				$this->connection,
				function (IDBConnection $connection, callable $assertActive, callable $verifyOneToOne, callable $recentNameIds) use ($searchQuery, $caches, $context, $prefix): array {
					$helper = $this->withConnection($connection);
					$recentStorage = count($context[1]) === 1 ? $context[1][0] : null;
					$namePattern = $context[0]->getValue();
					if (is_int($recentStorage) && $recentStorage > 0 && is_string($namePattern)) {
						$recentRows = $recentNameIds($recentStorage, $namePattern, $prefix, false);
						$assertActive();
						if (is_array($recentRows) && count($recentRows) === $prefix && $verifyOneToOne()) {
							$recentIds = $helper->candidateIds($recentRows, $prefix);
							$restrictedQuery = $helper->queryWithIds($searchQuery, $recentIds);
							$probe = $helper->idProbeBuilder($restrictedQuery, $caches);
							$assertActive();
							$probeResult = $probe->executeQuery();
							try {
								$probeRows = $probeResult->fetchAllAssociative();
							} finally {
								$probeResult->closeCursor();
							}
							$assertActive();
							if (count($probeRows) > $searchQuery->getLimit()) {
								throw new \UnexpectedValueException('Files search recent probe bound violated');
							}
							if (count($probeRows) === $searchQuery->getLimit()) {
								return $helper->searchInCaches($restrictedQuery, $caches);
							}
							$widenedRows = $recentNameIds($recentStorage, $namePattern, $prefix, true);
							$assertActive();
							if (is_array($widenedRows)) {
								$widenedIds = $helper->candidateIds($widenedRows, 2048);
								if ($widenedIds !== []) {
									$widenedQuery = $helper->queryWithIds($searchQuery, $widenedIds);
									$widenedProbe = $helper->idProbeBuilder($widenedQuery, $caches);
									$assertActive();
									$widenedResult = $widenedProbe->executeQuery();
									try {
										$widenedProbeRows = $widenedResult->fetchAllAssociative();
									} finally {
										$widenedResult->closeCursor();
									}
									$assertActive();
									if (count($widenedProbeRows) > $searchQuery->getLimit()) {
										throw new \UnexpectedValueException('Files search widened probe bound violated');
									}
									if (count($widenedProbeRows) === $searchQuery->getLimit()) {
										return $helper->searchInCaches($widenedQuery, $caches);
									}
								}
							}
						}
					}
					$builder = $helper->getQueryBuilder();
					$builder->selectFileCache('file', false);
					$metadataQuery = $builder->selectMetadata();
					$coarseOperation = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
						$context[0],
						new SearchComparison(ISearchComparison::COMPARE_IN, 'storage', $context[1]),
					]);
					$searchExpr = $helper->searchBuilder->searchOperatorToDBExpr($builder, $coarseOperation, $metadataQuery);
					if ($searchExpr) {
						$builder->andWhere($searchExpr);
					}
					$helper->searchBuilder->addSearchOrdersToQuery($builder, $searchQuery->getOrder(), $metadataQuery);
					$builder->setMaxResults($prefix);
					$builder->select('file.fileid');
					$assertActive();
					$result = $builder->executeQuery();
					try {
						$rows = $result->fetchAllAssociative();
					} finally {
						$result->closeCursor();
					}
					$assertActive();
					if (count($rows) > $prefix) {
						throw new \UnexpectedValueException('Files search candidate bound violated');
					}
					if (!$verifyOneToOne()) {
						return $helper->searchInCaches($searchQuery, $caches);
					}
					$ids = $helper->candidateIds($rows, $prefix);
					if ($ids === []) {
						return array_fill_keys(array_keys($caches), []);
					}
					$restrictedQuery = $helper->queryWithIds($searchQuery, $ids);
					if (count($ids) === $prefix) {
						$probe = $helper->idProbeBuilder($restrictedQuery, $caches);
						$assertActive();
						$probeResult = $probe->executeQuery();
						try {
							$probeRows = $probeResult->fetchAllAssociative();
						} finally {
							$probeResult->closeCursor();
						}
						$assertActive();
						if (count($probeRows) > $searchQuery->getLimit()) {
							throw new \UnexpectedValueException('Files search probe bound violated');
						}
						if (count($probeRows) !== $searchQuery->getLimit()) {
							return $helper->searchInCaches($searchQuery, $caches);
						}
					}
					$assertActive();
					return $helper->searchInCaches($restrictedQuery, $caches);
				},
			);
			if ($results !== null) {
				return $results;
			}
		}

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

		$searchQuery = $this->preProcessQuery($searchQuery);

		$builder = $this->getQueryBuilder();

		$requestedFields = array_merge(
			$this->searchBuilder->extractRequestedFields($searchQuery->getSearchOperation()),
			array_map(fn ($order) => $order->getField(), $searchQuery->getOrder()),
			$searchQuery->getSelectFields(),
		);

		$joinExtendedCache = in_array('metadata_etag', $requestedFields, true)
			|| in_array('creation_time', $requestedFields, true)
			|| in_array('upload_time', $requestedFields, true)
			|| in_array('last_activity', $requestedFields, true);

		$query = $builder->selectFileCache('file', $joinExtendedCache);

		if (in_array('systemtag', $requestedFields, true)) {
			$this->equipQueryForSystemTags($query, $this->requireUser($searchQuery));
		}
		if (in_array('tagname', $requestedFields, true) || in_array('favorite', $requestedFields, true)) {
			$this->equipQueryForDavTags($query, $this->requireUser($searchQuery));
		}
		if (in_array('owner', $requestedFields, true) || in_array('share_with', $requestedFields, true) || in_array('share_type', $requestedFields, true)) {
			$this->equipQueryForShares($query);
		}
		if (in_array('mount_point_name', $requestedFields, true)) {
			$this->equipQueryForMounts($query, $this->requireUser($searchQuery));
		}

		$metadataQuery = $query->selectMetadata();

		$this->applySearchConstraints($query, $searchQuery, $caches, $metadataQuery);

		$result = $query->executeQuery();
		$files = $result->fetchAllAssociative();

		$rawEntries = array_map(function (array $data) use ($metadataQuery) {
			$data['metadata'] = $metadataQuery->extractMetadata($data)->asArray();
			return Cache::cacheEntryFromData($data, $this->mimetypeLoader);
		}, $files);

		$result->closeCursor();

		// a result can only belong to the caches of its own storage, so only those are asked if the result matches
		// results are grouped by the same array keys as the caches argument to allow the caller to distinguish the source of the results
		$cachesByStorage = [];
		foreach ($caches as $cacheKey => $cache) {
			$cachesByStorage[$cache->getNumericStorageId()][$cacheKey] = $cache;
		}
		$results = array_fill_keys(array_keys($caches), []);
		foreach ($rawEntries as $rawEntry) {
			foreach ($cachesByStorage[$rawEntry->getStorageId()] ?? [] as $cacheKey => $cache) {
				$entry = $cache->getCacheEntryFromSearchResult($rawEntry);
				if ($entry) {
					$results[$cacheKey][] = $entry;
				}
			}
		}
		return $results;
	}

	/** @return array{0: SearchComparison, 1: list<int>}|null */
	private function filesSearchContext(ISearchQuery $query, array $caches): ?array {
		if ($this->connection->getShardDefinition('filecache') !== null
			|| !$query instanceof FilesSearchQuery || count($caches) !== 1 || $query->getSelectFields() !== []
			|| $query->getLimit() < 1 || $query->getLimit() > 100 || $query->getOffset() < 0) {
			return null;
		}
		$order = $query->getOrder();
		if (count($order) !== 2 || $order[0]->getField() !== 'mtime' || $order[0]->getDirection() !== ISearchOrder::DIRECTION_DESCENDING
			|| $order[1]->getField() !== 'fileid' || $order[1]->getDirection() !== ISearchOrder::DIRECTION_DESCENDING
			|| $order[0]->getExtra() !== '' || $order[1]->getExtra() !== '') {
			return null;
		}
		$operation = $query->getSearchOperation();
		if ($operation instanceof SearchBinaryOperator && $operation->getType() === ISearchBinaryOperator::OPERATOR_AND
			&& count($operation->getArguments()) === 1) {
			$operation = $operation->getArguments()[0];
		}
		if (!$operation instanceof SearchComparison || $operation->getField() !== 'name'
			|| $operation->getType() !== ISearchComparison::COMPARE_LIKE || $operation->getExtra() !== ''
			|| !is_string($operation->getValue())) {
			return null;
		}
		$cache = reset($caches);
		if (!$cache instanceof CacheJail || $cache->getGetUnjailedRoot() !== 'files') {
			return null;
		}
		$storages = $this->storageSuperset($cache->getQueryFilterForStorage());
		return $storages === null || $storages === [] ? null : [$operation, $storages];
	}

	private function rankPrefix(ISearchQuery $query): ?int {
		$limit = $query->getLimit();
		$offset = $query->getOffset();
		if ($limit < 1 || $offset < 0 || $limit > 1024 || $offset > 1024 - $limit) {
			return null;
		}
		return $offset + $limit;
	}

	/** @param list<array<string, mixed>> $rows
	 * @return list<int> */
	private function candidateIds(array $rows, int $bound): array {
		if (count($rows) > $bound) {
			throw new \UnexpectedValueException('Files search candidate bound violated');
		}
		$ids = [];
		foreach ($rows as $row) {
			$value = $row['fileid'] ?? null;
			if ((!is_int($value) && !is_string($value)) || !preg_match('/^[1-9][0-9]*$/D', (string)$value)) {
				throw new \UnexpectedValueException('Files search candidate fileid is invalid');
			}
			$id = filter_var($value, FILTER_VALIDATE_INT);
			if ($id === false || isset($ids[$id])) {
				throw new \UnexpectedValueException('Files search candidate fileid is invalid');
			}
			$ids[$id] = $id;
		}
		return array_values($ids);
	}

	/** @param list<int> $ids */
	private function queryWithIds(ISearchQuery $query, array $ids): SearchQuery {
		return new SearchQuery(
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
				$query->getSearchOperation(),
				new SearchComparison(ISearchComparison::COMPARE_IN, 'fileid', $ids),
			]),
			$query->getLimit(),
			$query->getOffset(),
			$query->getOrder(),
			$query->getUser(),
			$query->limitToHome(),
			$query->getSelectFields(),
		);
	}

	private function idProbeBuilder(ISearchQuery $query, array $caches): CacheQueryBuilder {
		$builder = $this->getQueryBuilder();
		$builder->selectFileCache('file', false);
		$metadataQuery = $builder->selectMetadata();
		$this->applySearchConstraints($builder, $query, $caches, $metadataQuery);
		$builder->select('file.fileid');
		return $builder;
	}

	/** @return list<int>|null */
	private function storageSuperset(ISearchOperator $operator): ?array {
		if ($operator instanceof SearchComparison && $operator->getField() === 'storage' && $operator->getExtra() === '') {
			$values = match ($operator->getType()) {
				ISearchComparison::COMPARE_EQUAL => [$operator->getValue()],
				ISearchComparison::COMPARE_IN => $operator->getValue(),
				default => null,
			};
			if (!is_array($values) || $values === [] || count($values) > 16) {
				return null;
			}
			foreach ($values as $id) {
				if (!is_int($id) || $id < 1) {
					return null;
				}
			}
			return array_values(array_unique($values));
		}
		if (!$operator instanceof SearchBinaryOperator || $operator->getArguments() === []) {
			return null;
		}
		$type = $operator->getType();
		if ($type !== ISearchBinaryOperator::OPERATOR_AND && $type !== ISearchBinaryOperator::OPERATOR_OR) {
			return null;
		}
		$sets = array_map($this->storageSuperset(...), $operator->getArguments());
		if ($type === ISearchBinaryOperator::OPERATOR_OR && in_array(null, $sets, true)) {
			return null;
		}
		$sets = array_values(array_filter($sets, static fn ($set) => $set !== null));
		if ($sets === []) {
			return null;
		}
		$result = array_shift($sets);
		foreach ($sets as $set) {
			$result = $type === ISearchBinaryOperator::OPERATOR_AND ? array_intersect($result, $set) : array_merge($result, $set);
		}
		$result = array_values(array_unique($result));
		return count($result) > 16 ? null : $result;
	}

	protected function requireUser(ISearchQuery $searchQuery): IUser {
		$user = $searchQuery->getUser();
		if ($user === null) {
			throw new \InvalidArgumentException('This search operation requires the user to be set in the query');
		}
		return $user;
	}

	/**
	 * @return array{0?: array<array-key, ICache>, 1?: array<array-key, IMountPoint>}
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
			$caches = ['' => new CacheJail($storage->getCache(), $internalPath)];
		} else {
			/** @var ICache[] $caches */
			$caches = ['' => $storage->getCache()];
		}
		/** @var IMountPoint[] $mountByMountPoint */
		$mountByMountPoint = ['' => $mount];

		if (!$limitToHome) {
			$mounts = $root->getMountsIn($path);
			foreach ($mounts as $mount) {
				$storage = $mount->getStorage();
				if ($storage) {
					$relativeMountPoint = ltrim(substr($mount->getMountPoint(), $rootLength), '/');
					$caches[$relativeMountPoint] = $storage->getCache();
					$mountByMountPoint[$relativeMountPoint] = $mount;
				}
			}
		}

		return [$caches, $mountByMountPoint];
	}

	private function preProcessQuery(ISearchQuery $searchQuery): ISearchQuery {
		// when sharding is enabled, we can't join on the mounts table
		// so instead we need to fetch the matching mount root ids and filter on those
		if ($this->connection->getShardDefinition('filecache') !== null) {
			$operation = $this->replaceMountNameWithRootIds($searchQuery->getSearchOperation());
			return new SearchQuery(
				$operation,
				$searchQuery->getLimit(),
				$searchQuery->getOffset(),
				$searchQuery->getOrder(),
				$searchQuery->getUser(),
				$searchQuery->limitToHome(),
				$searchQuery->getSelectFields(),
			);
		} else {
			return $searchQuery;
		}
	}

	private function replaceMountNameWithRootIds(ISearchOperator $searchOperator): ISearchOperator {
		if ($searchOperator instanceof ISearchBinaryOperator) {
			return new SearchBinaryOperator(
				$searchOperator->getType(),
				array_map($this->replaceMountNameWithRootIds(...), $searchOperator->getArguments())
			);
		} elseif ($searchOperator instanceof ISearchComparison && $searchOperator->getField() === 'mount_point_name') {
			if (!in_array($searchOperator->getType(), [
				ISearchComparison::COMPARE_LIKE,
				ISearchComparison::COMPARE_EQUAL,
				ISearchComparison::COMPARE_IN,
			], true)) {
				throw new \InvalidArgumentException('Filtering mount name with ' . $searchOperator->getType() . ' is not supported');
			}

			$query = $this->connection->getQueryBuilder();
			$query->select('root_id')
				->from('mounts', 'm')
				->where($this->searchBuilder->searchOperatorToDBExpr($query, $searchOperator));
			$rootIds = $query->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
			return new SearchComparison(ISearchComparison::COMPARE_IN, 'fileid', $rootIds);
		} else {
			return $searchOperator;
		}
	}
}

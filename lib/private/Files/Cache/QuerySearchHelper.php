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

use OC\Files\Search\SearchBinaryOperator;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchOrder;
use OCP\Files\Search\ISearchQuery;
use OCP\IDBConnection;
use OCP\ILogger;

/**
 * Tools for transforming search queries into database queries
 */
class QuerySearchHelper {
	protected static $searchOperatorMap = [
		ISearchComparison::COMPARE_LIKE => 'iLike',
		ISearchComparison::COMPARE_EQUAL => 'eq',
		ISearchComparison::COMPARE_GREATER_THAN => 'gt',
		ISearchComparison::COMPARE_GREATER_THAN_EQUAL => 'gte',
		ISearchComparison::COMPARE_LESS_THAN => 'lt',
		ISearchComparison::COMPARE_LESS_THAN_EQUAL => 'lte',
	];

	protected static $searchOperatorNegativeMap = [
		ISearchComparison::COMPARE_LIKE => 'notLike',
		ISearchComparison::COMPARE_EQUAL => 'neq',
		ISearchComparison::COMPARE_GREATER_THAN => 'lte',
		ISearchComparison::COMPARE_GREATER_THAN_EQUAL => 'lt',
		ISearchComparison::COMPARE_LESS_THAN => 'gte',
		ISearchComparison::COMPARE_LESS_THAN_EQUAL => 'lt',
	];

	public const TAG_FAVORITE = '_$!<Favorite>!$_';

	/** @var IMimeTypeLoader */
	private $mimetypeLoader;
	/** @var IDBConnection */
	private $connection;
	/** @var SystemConfig */
	private $systemConfig;
	/** @var ILogger */
	private $logger;

	public function __construct(
		IMimeTypeLoader $mimetypeLoader,
		IDBConnection $connection,
		SystemConfig $systemConfig,
		ILogger $logger
	) {
		$this->mimetypeLoader = $mimetypeLoader;
		$this->connection = $connection;
		$this->systemConfig = $systemConfig;
		$this->logger = $logger;
	}

	/**
	 * Whether or not the tag tables should be joined to complete the search
	 *
	 * @param ISearchOperator $operator
	 * @return boolean
	 */
	public function shouldJoinTags(ISearchOperator $operator) {
		if ($operator instanceof ISearchBinaryOperator) {
			return array_reduce($operator->getArguments(), function ($shouldJoin, ISearchOperator $operator) {
				return $shouldJoin || $this->shouldJoinTags($operator);
			}, false);
		} elseif ($operator instanceof ISearchComparison) {
			return $operator->getField() === 'tagname' || $operator->getField() === 'favorite';
		}
		return false;
	}

	/**
	 * @param IQueryBuilder $builder
	 * @param ISearchOperator $operator
	 */
	public function searchOperatorArrayToDBExprArray(IQueryBuilder $builder, array $operators) {
		return array_filter(array_map(function ($operator) use ($builder) {
			return $this->searchOperatorToDBExpr($builder, $operator);
		}, $operators));
	}

	public function searchOperatorToDBExpr(IQueryBuilder $builder, ISearchOperator $operator) {
		$expr = $builder->expr();
		if ($operator instanceof ISearchBinaryOperator) {
			if (count($operator->getArguments()) === 0) {
				return null;
			}

			switch ($operator->getType()) {
				case ISearchBinaryOperator::OPERATOR_NOT:
					$negativeOperator = $operator->getArguments()[0];
					if ($negativeOperator instanceof ISearchComparison) {
						return $this->searchComparisonToDBExpr($builder, $negativeOperator, self::$searchOperatorNegativeMap);
					} else {
						throw new \InvalidArgumentException('Binary operators inside "not" is not supported');
					}
				// no break
				case ISearchBinaryOperator::OPERATOR_AND:
					return call_user_func_array([$expr, 'andX'], $this->searchOperatorArrayToDBExprArray($builder, $operator->getArguments()));
				case ISearchBinaryOperator::OPERATOR_OR:
					return call_user_func_array([$expr, 'orX'], $this->searchOperatorArrayToDBExprArray($builder, $operator->getArguments()));
				default:
					throw new \InvalidArgumentException('Invalid operator type: ' . $operator->getType());
			}
		} elseif ($operator instanceof ISearchComparison) {
			return $this->searchComparisonToDBExpr($builder, $operator, self::$searchOperatorMap);
		} else {
			throw new \InvalidArgumentException('Invalid operator type: ' . get_class($operator));
		}
	}

	private function searchComparisonToDBExpr(IQueryBuilder $builder, ISearchComparison $comparison, array $operatorMap) {
		$this->validateComparison($comparison);

		[$field, $value, $type] = $this->getOperatorFieldAndValue($comparison);
		if (isset($operatorMap[$type])) {
			$queryOperator = $operatorMap[$type];
			return $builder->expr()->$queryOperator($field, $this->getParameterForValue($builder, $value));
		} else {
			throw new \InvalidArgumentException('Invalid operator type: ' . $comparison->getType());
		}
	}

	private function getOperatorFieldAndValue(ISearchComparison $operator) {
		$field = $operator->getField();
		$value = $operator->getValue();
		$type = $operator->getType();
		if ($field === 'mimetype') {
			if ($operator->getType() === ISearchComparison::COMPARE_EQUAL) {
				$value = (int)$this->mimetypeLoader->getId($value);
			} elseif ($operator->getType() === ISearchComparison::COMPARE_LIKE) {
				// transform "mimetype='foo/%'" to "mimepart='foo'"
				if (preg_match('|(.+)/%|', $value, $matches)) {
					$field = 'mimepart';
					$value = (int)$this->mimetypeLoader->getId($matches[1]);
					$type = ISearchComparison::COMPARE_EQUAL;
				} elseif (strpos($value, '%') !== false) {
					throw new \InvalidArgumentException('Unsupported query value for mimetype: ' . $value . ', only values in the format "mime/type" or "mime/%" are supported');
				} else {
					$field = 'mimetype';
					$value = (int)$this->mimetypeLoader->getId($value);
					$type = ISearchComparison::COMPARE_EQUAL;
				}
			}
		} elseif ($field === 'favorite') {
			$field = 'tag.category';
			$value = self::TAG_FAVORITE;
		} elseif ($field === 'tagname') {
			$field = 'tag.category';
		} elseif ($field === 'fileid') {
			$field = 'file.fileid';
		} elseif ($field === 'path' && $type === ISearchComparison::COMPARE_EQUAL) {
			$field = 'path_hash';
			$value = md5((string)$value);
		}
		return [$field, $value, $type];
	}

	private function validateComparison(ISearchComparison $operator) {
		$types = [
			'mimetype' => 'string',
			'mtime' => 'integer',
			'name' => 'string',
			'path' => 'string',
			'size' => 'integer',
			'tagname' => 'string',
			'favorite' => 'boolean',
			'fileid' => 'integer',
			'storage' => 'integer',
		];
		$comparisons = [
			'mimetype' => ['eq', 'like'],
			'mtime' => ['eq', 'gt', 'lt', 'gte', 'lte'],
			'name' => ['eq', 'like'],
			'path' => ['eq', 'like'],
			'size' => ['eq', 'gt', 'lt', 'gte', 'lte'],
			'tagname' => ['eq', 'like'],
			'favorite' => ['eq'],
			'fileid' => ['eq'],
			'storage' => ['eq'],
		];

		if (!isset($types[$operator->getField()])) {
			throw new \InvalidArgumentException('Unsupported comparison field ' . $operator->getField());
		}
		$type = $types[$operator->getField()];
		if (gettype($operator->getValue()) !== $type) {
			throw new \InvalidArgumentException('Invalid type for field ' . $operator->getField());
		}
		if (!in_array($operator->getType(), $comparisons[$operator->getField()])) {
			throw new \InvalidArgumentException('Unsupported comparison for field  ' . $operator->getField() . ': ' . $operator->getType());
		}
	}

	private function getParameterForValue(IQueryBuilder $builder, $value) {
		if ($value instanceof \DateTime) {
			$value = $value->getTimestamp();
		}
		if (is_numeric($value)) {
			$type = IQueryBuilder::PARAM_INT;
		} else {
			$type = IQueryBuilder::PARAM_STR;
		}
		return $builder->createNamedParameter($value, $type);
	}

	/**
	 * @param IQueryBuilder $query
	 * @param ISearchOrder[] $orders
	 */
	public function addSearchOrdersToQuery(IQueryBuilder $query, array $orders) {
		foreach ($orders as $order) {
			$query->addOrderBy($order->getField(), $order->getDirection());
		}
	}

	protected function getQueryBuilder() {
		return new CacheQueryBuilder(
			$this->connection,
			$this->systemConfig,
			$this->logger
		);
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

		$query = $builder->selectFileCache('file');

		if ($this->shouldJoinTags($searchQuery->getSearchOperation())) {
			$user = $searchQuery->getUser();
			if ($user === null) {
				throw new \InvalidArgumentException("Searching by tag requires the user to be set in the query");
			}
			$query
				->innerJoin('file', 'vcategory_to_object', 'tagmap', $builder->expr()->eq('file.fileid', 'tagmap.objid'))
				->innerJoin('tagmap', 'vcategory', 'tag', $builder->expr()->andX(
					$builder->expr()->eq('tagmap.type', 'tag.type'),
					$builder->expr()->eq('tagmap.categoryid', 'tag.id')
				))
				->andWhere($builder->expr()->eq('tag.type', $builder->createNamedParameter('files')))
				->andWhere($builder->expr()->eq('tag.uid', $builder->createNamedParameter($user->getUID())));
		}

		$searchExpr = $this->searchOperatorToDBExpr($builder, $searchQuery->getSearchOperation());
		if ($searchExpr) {
			$query->andWhere($searchExpr);
		}

		$storageFilters = array_values(array_map(function (ICache $cache) {
			return $cache->getQueryFilterForStorage();
		}, $caches));
		$query->andWhere($this->searchOperatorToDBExpr($builder, new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, $storageFilters)));

		$this->addSearchOrdersToQuery($query, $searchQuery->getOrder());

		if ($searchQuery->getLimit()) {
			$query->setMaxResults($searchQuery->getLimit());
		}
		if ($searchQuery->getOffset()) {
			$query->setFirstResult($searchQuery->getOffset());
		}

		$result = $query->execute();
		$files = $result->fetchAll();

		$rawEntries = array_map(function (array $data) {
			return Cache::cacheEntryFromData($data, $this->mimetypeLoader);
		}, $files);

		$result->closeCursor();

		// loop trough all caches for each result to see if the result matches that storage
		// results are grouped by the same array keys as the caches argument to allow the caller to distringuish the source of the results
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
}

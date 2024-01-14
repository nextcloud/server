<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Maxence Lange <maxence@artificial-owl.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\Cache;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchOrder;
use OCP\FilesMetadata\IMetadataQuery;

/**
 * Tools for transforming search queries into database queries
 */
class SearchBuilder {
	protected static $searchOperatorMap = [
		ISearchComparison::COMPARE_LIKE => 'iLike',
		ISearchComparison::COMPARE_LIKE_CASE_SENSITIVE => 'like',
		ISearchComparison::COMPARE_EQUAL => 'eq',
		ISearchComparison::COMPARE_GREATER_THAN => 'gt',
		ISearchComparison::COMPARE_GREATER_THAN_EQUAL => 'gte',
		ISearchComparison::COMPARE_LESS_THAN => 'lt',
		ISearchComparison::COMPARE_LESS_THAN_EQUAL => 'lte',
		ISearchComparison::COMPARE_DEFINED => 'isNotNull',
	];

	protected static $searchOperatorNegativeMap = [
		ISearchComparison::COMPARE_LIKE => 'notLike',
		ISearchComparison::COMPARE_LIKE_CASE_SENSITIVE => 'notLike',
		ISearchComparison::COMPARE_EQUAL => 'neq',
		ISearchComparison::COMPARE_GREATER_THAN => 'lte',
		ISearchComparison::COMPARE_GREATER_THAN_EQUAL => 'lt',
		ISearchComparison::COMPARE_LESS_THAN => 'gte',
		ISearchComparison::COMPARE_LESS_THAN_EQUAL => 'gt',
		ISearchComparison::COMPARE_DEFINED => 'isNull',
	];

	public const TAG_FAVORITE = '_$!<Favorite>!$_';

	/** @var IMimeTypeLoader */
	private $mimetypeLoader;

	public function __construct(
		IMimeTypeLoader $mimetypeLoader
	) {
		$this->mimetypeLoader = $mimetypeLoader;
	}

	/**
	 * @return string[]
	 */
	public function extractRequestedFields(ISearchOperator $operator): array {
		if ($operator instanceof ISearchBinaryOperator) {
			return array_reduce($operator->getArguments(), function (array $fields, ISearchOperator $operator) {
				return array_unique(array_merge($fields, $this->extractRequestedFields($operator)));
			}, []);
		} elseif ($operator instanceof ISearchComparison && !$operator->getExtra()) {
			return [$operator->getField()];
		}
		return [];
	}

	/**
	 * @param IQueryBuilder $builder
	 * @param ISearchOperator[] $operators
	 */
	public function searchOperatorArrayToDBExprArray(
		IQueryBuilder $builder,
		array $operators,
		?IMetadataQuery $metadataQuery = null
	) {
		return array_filter(array_map(function ($operator) use ($builder, $metadataQuery) {
			return $this->searchOperatorToDBExpr($builder, $operator, $metadataQuery);
		}, $operators));
	}

	public function searchOperatorToDBExpr(
		IQueryBuilder $builder,
		ISearchOperator $operator,
		?IMetadataQuery $metadataQuery = null
	) {
		$expr = $builder->expr();

		if ($operator instanceof ISearchBinaryOperator) {
			if (count($operator->getArguments()) === 0) {
				return null;
			}

			switch ($operator->getType()) {
				case ISearchBinaryOperator::OPERATOR_NOT:
					$negativeOperator = $operator->getArguments()[0];
					if ($negativeOperator instanceof ISearchComparison) {
						return $this->searchComparisonToDBExpr($builder, $negativeOperator, self::$searchOperatorNegativeMap, $metadataQuery);
					} else {
						throw new \InvalidArgumentException('Binary operators inside "not" is not supported');
					}
					// no break
				case ISearchBinaryOperator::OPERATOR_AND:
					return call_user_func_array([$expr, 'andX'], $this->searchOperatorArrayToDBExprArray($builder, $operator->getArguments(), $metadataQuery));
				case ISearchBinaryOperator::OPERATOR_OR:
					return call_user_func_array([$expr, 'orX'], $this->searchOperatorArrayToDBExprArray($builder, $operator->getArguments(), $metadataQuery));
				default:
					throw new \InvalidArgumentException('Invalid operator type: ' . $operator->getType());
			}
		} elseif ($operator instanceof ISearchComparison) {
			return $this->searchComparisonToDBExpr($builder, $operator, self::$searchOperatorMap, $metadataQuery);
		} else {
			throw new \InvalidArgumentException('Invalid operator type: ' . get_class($operator));
		}
	}

	private function searchComparisonToDBExpr(
		IQueryBuilder $builder,
		ISearchComparison $comparison,
		array $operatorMap,
		?IMetadataQuery $metadataQuery = null
	) {
		if ($comparison->getExtra()) {
			[$field, $value, $type] = $this->getExtraOperatorField($comparison, $metadataQuery);
		} else {
			[$field, $value, $type] = $this->getOperatorFieldAndValue($comparison);
		}

		if (isset($operatorMap[$type])) {
			$queryOperator = $operatorMap[$type];
			return $builder->expr()->$queryOperator($field, $this->getParameterForValue($builder, $value));
		} else {
			throw new \InvalidArgumentException('Invalid operator type: ' . $comparison->getType());
		}
	}

	private function getOperatorFieldAndValue(ISearchComparison $operator) {
		$this->validateComparison($operator);

		$field = $operator->getField();
		$value = $operator->getValue();
		$type = $operator->getType();

		if ($field === 'mimetype') {
			$value = (string)$value;
			if ($operator->getType() === ISearchComparison::COMPARE_EQUAL) {
				$value = (int)$this->mimetypeLoader->getId($value);
			} elseif ($operator->getType() === ISearchComparison::COMPARE_LIKE) {
				// transform "mimetype='foo/%'" to "mimepart='foo'"
				if (preg_match('|(.+)/%|', $value, $matches)) {
					$field = 'mimepart';
					$value = (int)$this->mimetypeLoader->getId($matches[1]);
					$type = ISearchComparison::COMPARE_EQUAL;
				} elseif (str_contains($value, '%')) {
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
		} elseif ($field === 'name') {
			$field = 'file.name';
		} elseif ($field === 'tagname') {
			$field = 'tag.category';
		} elseif ($field === 'systemtag') {
			$field = 'systemtag.name';
		} elseif ($field === 'fileid') {
			$field = 'file.fileid';
		} elseif ($field === 'path' && $type === ISearchComparison::COMPARE_EQUAL && $operator->getQueryHint(ISearchComparison::HINT_PATH_EQ_HASH, true)) {
			$field = 'path_hash';
			$value = md5((string)$value);
		} elseif ($field === 'owner') {
			$field = 'uid_owner';
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
			'systemtag' => 'string',
			'favorite' => 'boolean',
			'fileid' => 'integer',
			'storage' => 'integer',
			'share_with' => 'string',
			'share_type' => 'integer',
			'owner' => 'string',
		];
		$comparisons = [
			'mimetype' => ['eq', 'like'],
			'mtime' => ['eq', 'gt', 'lt', 'gte', 'lte'],
			'name' => ['eq', 'like', 'clike'],
			'path' => ['eq', 'like', 'clike'],
			'size' => ['eq', 'gt', 'lt', 'gte', 'lte'],
			'tagname' => ['eq', 'like'],
			'systemtag' => ['eq', 'like'],
			'favorite' => ['eq'],
			'fileid' => ['eq'],
			'storage' => ['eq'],
			'share_with' => ['eq'],
			'share_type' => ['eq'],
			'owner' => ['eq'],
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


	private function getExtraOperatorField(ISearchComparison $operator, IMetadataQuery $metadataQuery): array {
		$field = $operator->getField();
		$value = $operator->getValue();
		$type = $operator->getType();

		switch($operator->getExtra()) {
			case IMetadataQuery::EXTRA:
				$metadataQuery->joinIndex($field); // join index table if not joined yet
				$field = $metadataQuery->getMetadataValueField($field);
				break;
			default:
				throw new \InvalidArgumentException('Invalid extra type: ' . $operator->getExtra());
		}

		return [$field, $value, $type];
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
	 * @param IMetadataQuery|null $metadataQuery
	 */
	public function addSearchOrdersToQuery(IQueryBuilder $query, array $orders, ?IMetadataQuery $metadataQuery = null): void {
		foreach ($orders as $order) {
			$field = $order->getField();
			switch ($order->getExtra()) {
				case IMetadataQuery::EXTRA:
					$metadataQuery->joinIndex($field); // join index table if not joined yet
					$field = $metadataQuery->getMetadataValueField($order->getField());
					break;

				default:
					if ($field === 'fileid') {
						$field = 'file.fileid';
					}

					// Mysql really likes to pick an index for sorting if it can't fully satisfy the where
					// filter with an index, since search queries pretty much never are fully filtered by index
					// mysql often picks an index for sorting instead of the much more useful index for filtering.
					//
					// By changing the order by to an expression, mysql isn't smart enough to see that it could still
					// use the index, so it instead picks an index for the filtering
					if ($field === 'mtime') {
						$field = $query->func()->add($field, $query->createNamedParameter(0));
					}
			}
			$query->addOrderBy($field, $order->getDirection());
		}
	}
}

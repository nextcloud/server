<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Cache;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchOrder;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FilesMetadata\IMetadataQuery;

/**
 * Tools for transforming search queries into database queries
 *
 * @psalm-import-type ParamSingleValue from ISearchComparison
 * @psalm-import-type ParamValue from ISearchComparison
 */
class SearchBuilder {
	/** @var array<string, string> */
	protected static $searchOperatorMap = [
		ISearchComparison::COMPARE_LIKE => 'iLike',
		ISearchComparison::COMPARE_LIKE_CASE_SENSITIVE => 'like',
		ISearchComparison::COMPARE_EQUAL => 'eq',
		ISearchComparison::COMPARE_GREATER_THAN => 'gt',
		ISearchComparison::COMPARE_GREATER_THAN_EQUAL => 'gte',
		ISearchComparison::COMPARE_LESS_THAN => 'lt',
		ISearchComparison::COMPARE_LESS_THAN_EQUAL => 'lte',
		ISearchComparison::COMPARE_DEFINED => 'isNotNull',
		ISearchComparison::COMPARE_IN => 'in',
	];

	/** @var array<string, string> */
	protected static $searchOperatorNegativeMap = [
		ISearchComparison::COMPARE_LIKE => 'notLike',
		ISearchComparison::COMPARE_LIKE_CASE_SENSITIVE => 'notLike',
		ISearchComparison::COMPARE_EQUAL => 'neq',
		ISearchComparison::COMPARE_GREATER_THAN => 'lte',
		ISearchComparison::COMPARE_GREATER_THAN_EQUAL => 'lt',
		ISearchComparison::COMPARE_LESS_THAN => 'gte',
		ISearchComparison::COMPARE_LESS_THAN_EQUAL => 'gt',
		ISearchComparison::COMPARE_DEFINED => 'isNull',
		ISearchComparison::COMPARE_IN => 'notIn',
	];

	/** @var array<string, string> */
	protected static $fieldTypes = [
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

	/** @var array<string, int|string> */
	protected static $paramTypeMap = [
		'string' => IQueryBuilder::PARAM_STR,
		'integer' => IQueryBuilder::PARAM_INT,
		'boolean' => IQueryBuilder::PARAM_BOOL,
	];

	/** @var array<string, int> */
	protected static $paramArrayTypeMap = [
		'string' => IQueryBuilder::PARAM_STR_ARRAY,
		'integer' => IQueryBuilder::PARAM_INT_ARRAY,
		'boolean' => IQueryBuilder::PARAM_INT_ARRAY,
	];

	public const TAG_FAVORITE = '_$!<Favorite>!$_';

	public function __construct(
		private IMimeTypeLoader $mimetypeLoader,
		private IFilesMetadataManager $filesMetadataManager,
	) {
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
		?IMetadataQuery $metadataQuery = null,
	) {
		return array_filter(array_map(function ($operator) use ($builder, $metadataQuery) {
			return $this->searchOperatorToDBExpr($builder, $operator, $metadataQuery);
		}, $operators));
	}

	public function searchOperatorToDBExpr(
		IQueryBuilder $builder,
		ISearchOperator $operator,
		?IMetadataQuery $metadataQuery = null,
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
		?IMetadataQuery $metadataQuery = null,
	) {
		if ($comparison->getExtra()) {
			[$field, $value, $type, $paramType] = $this->getExtraOperatorField($comparison, $metadataQuery);
		} else {
			[$field, $value, $type, $paramType] = $this->getOperatorFieldAndValue($comparison);
		}

		if (isset($operatorMap[$type])) {
			$queryOperator = $operatorMap[$type];
			return $builder->expr()->$queryOperator($field, $this->getParameterForValue($builder, $value, $paramType));
		} else {
			throw new \InvalidArgumentException('Invalid operator type: ' . $comparison->getType());
		}
	}

	/**
	 * @param ISearchComparison $operator
	 * @return list{string, ParamValue, string, string}
	 */
	private function getOperatorFieldAndValue(ISearchComparison $operator): array {
		$this->validateComparison($operator);
		$field = $operator->getField();
		$value = $operator->getValue();
		$type = $operator->getType();
		$pathEqHash = $operator->getQueryHint(ISearchComparison::HINT_PATH_EQ_HASH, true);
		return $this->getOperatorFieldAndValueInner($field, $value, $type, $pathEqHash);
	}

	/**
	 * @param string $field
	 * @param ParamValue $value
	 * @param string $type
	 * @return list{string, ParamValue, string, string}
	 */
	private function getOperatorFieldAndValueInner(string $field, mixed $value, string $type, bool $pathEqHash): array {
		$paramType = self::$fieldTypes[$field];
		if ($type === ISearchComparison::COMPARE_IN) {
			$resultField = $field;
			$values = [];
			foreach ($value as $arrayValue) {
				/** @var ParamSingleValue $arrayValue */
				[$arrayField, $arrayValue] = $this->getOperatorFieldAndValueInner($field, $arrayValue, ISearchComparison::COMPARE_EQUAL, $pathEqHash);
				$resultField = $arrayField;
				$values[] = $arrayValue;
			}
			return [$resultField, $values, ISearchComparison::COMPARE_IN, $paramType];
		}
		if ($field === 'mimetype') {
			$value = (string)$value;
			if ($type === ISearchComparison::COMPARE_EQUAL) {
				$value = $this->mimetypeLoader->getId($value);
			} elseif ($type === ISearchComparison::COMPARE_LIKE) {
				// transform "mimetype='foo/%'" to "mimepart='foo'"
				if (preg_match('|(.+)/%|', $value, $matches)) {
					$field = 'mimepart';
					$value = $this->mimetypeLoader->getId($matches[1]);
					$type = ISearchComparison::COMPARE_EQUAL;
				} elseif (str_contains($value, '%')) {
					throw new \InvalidArgumentException('Unsupported query value for mimetype: ' . $value . ', only values in the format "mime/type" or "mime/%" are supported');
				} else {
					$field = 'mimetype';
					$value = $this->mimetypeLoader->getId($value);
					$type = ISearchComparison::COMPARE_EQUAL;
				}
			}
		} elseif ($field === 'favorite') {
			$field = 'tag.category';
			$value = self::TAG_FAVORITE;
			$paramType = 'string';
		} elseif ($field === 'name') {
			$field = 'file.name';
		} elseif ($field === 'tagname') {
			$field = 'tag.category';
		} elseif ($field === 'systemtag') {
			$field = 'systemtag.name';
		} elseif ($field === 'fileid') {
			$field = 'file.fileid';
		} elseif ($field === 'path' && $type === ISearchComparison::COMPARE_EQUAL && $pathEqHash) {
			$field = 'path_hash';
			$value = md5((string)$value);
		} elseif ($field === 'owner') {
			$field = 'uid_owner';
		}
		return [$field, $value, $type, $paramType];
	}

	private function validateComparison(ISearchComparison $operator) {
		$comparisons = [
			'mimetype' => ['eq', 'like', 'in'],
			'mtime' => ['eq', 'gt', 'lt', 'gte', 'lte'],
			'name' => ['eq', 'like', 'clike', 'in'],
			'path' => ['eq', 'like', 'clike', 'in'],
			'size' => ['eq', 'gt', 'lt', 'gte', 'lte'],
			'tagname' => ['eq', 'like'],
			'systemtag' => ['eq', 'like'],
			'favorite' => ['eq'],
			'fileid' => ['eq', 'in'],
			'storage' => ['eq', 'in'],
			'share_with' => ['eq'],
			'share_type' => ['eq'],
			'owner' => ['eq'],
		];

		if (!isset(self::$fieldTypes[$operator->getField()])) {
			throw new \InvalidArgumentException('Unsupported comparison field ' . $operator->getField());
		}
		$type = self::$fieldTypes[$operator->getField()];
		if ($operator->getType() === ISearchComparison::COMPARE_IN) {
			if (!is_array($operator->getValue())) {
				throw new \InvalidArgumentException('Invalid type for field ' . $operator->getField());
			}
			foreach ($operator->getValue() as $arrayValue) {
				if (gettype($arrayValue) !== $type) {
					throw new \InvalidArgumentException('Invalid type in array for field ' . $operator->getField());
				}
			}
		} else {
			if (gettype($operator->getValue()) !== $type) {
				throw new \InvalidArgumentException('Invalid type for field ' . $operator->getField());
			}
		}
		if (!in_array($operator->getType(), $comparisons[$operator->getField()])) {
			throw new \InvalidArgumentException('Unsupported comparison for field  ' . $operator->getField() . ': ' . $operator->getType());
		}
	}


	private function getExtraOperatorField(ISearchComparison $operator, IMetadataQuery $metadataQuery): array {
		$field = $operator->getField();
		$value = $operator->getValue();
		$type = $operator->getType();

		$knownMetadata = $this->filesMetadataManager->getKnownMetadata();
		$isIndex = $knownMetadata->isIndex($field);
		$paramType = $knownMetadata->getType($field) === 'int' ? 'integer' : 'string';

		if (!$isIndex) {
			throw new \InvalidArgumentException('Cannot search non indexed metadata key');
		}

		switch ($operator->getExtra()) {
			case IMetadataQuery::EXTRA:
				$metadataQuery->joinIndex($field); // join index table if not joined yet
				$field = $metadataQuery->getMetadataValueField($field);
				break;
			default:
				throw new \InvalidArgumentException('Invalid extra type: ' . $operator->getExtra());
		}

		return [$field, $value, $type, $paramType];
	}

	private function getParameterForValue(IQueryBuilder $builder, $value, string $paramType) {
		if ($value instanceof \DateTime) {
			$value = $value->getTimestamp();
		}
		if (is_array($value)) {
			$type = self::$paramArrayTypeMap[$paramType];
		} else {
			$type = self::$paramTypeMap[$paramType];
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

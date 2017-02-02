<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\Cache;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;

/**
 * Tools for transforming search queries into database queries
 */
class QuerySearchUtil {
	static protected $searchOperatorMap = [
		ISearchComparison::COMPARE_LIKE => 'iLike',
		ISearchComparison::COMPARE_EQUAL => 'eq',
		ISearchComparison::COMPARE_GREATER_THAN => 'gt',
		ISearchComparison::COMPARE_GREATER_THAN_EQUAL => 'gte',
		ISearchComparison::COMPARE_LESS_THAN => 'lt',
		ISearchComparison::COMPARE_LESS_THAN_EQUAL => 'lte'
	];

	static protected $searchOperatorNegativeMap = [
		ISearchComparison::COMPARE_LIKE => 'notLike',
		ISearchComparison::COMPARE_EQUAL => 'neq',
		ISearchComparison::COMPARE_GREATER_THAN => 'lte',
		ISearchComparison::COMPARE_GREATER_THAN_EQUAL => 'lt',
		ISearchComparison::COMPARE_LESS_THAN => 'gte',
		ISearchComparison::COMPARE_LESS_THAN_EQUAL => 'lt'
	];

	/** @var IMimeTypeLoader */
	private $mimetypeLoader;

	/**
	 * QuerySearchUtil constructor.
	 *
	 * @param IMimeTypeLoader $mimetypeLoader
	 */
	public function __construct(IMimeTypeLoader $mimetypeLoader) {
		$this->mimetypeLoader = $mimetypeLoader;
	}

	public function searchOperatorToDBExpr(IQueryBuilder $builder, ISearchOperator $operator) {
		$expr = $builder->expr();
		if ($operator instanceof ISearchBinaryOperator) {
			switch ($operator->getType()) {
				case ISearchBinaryOperator::OPERATOR_NOT:
					$negativeOperator = $operator->getArguments()[0];
					if ($negativeOperator instanceof ISearchComparison) {
						return $this->searchComparisonToDBExpr($builder, $negativeOperator, self::$searchOperatorNegativeMap);
					} else {
						throw new \InvalidArgumentException('Binary operators inside "not" is not supported');
					}
				case ISearchBinaryOperator::OPERATOR_AND:
					return $expr->andX($this->searchOperatorToDBExpr($operator->getArguments()[0], $operator->getArguments()[1]));
				case ISearchBinaryOperator::OPERATOR_OR:
					return $expr->orX($this->searchOperatorToDBExpr($operator->getArguments()[0], $operator->getArguments()[1]));
				default:
					throw new \InvalidArgumentException('Invalid operator type: ' . $operator->getType());
			}
		} else if ($operator instanceof ISearchComparison) {
			return $this->searchComparisonToDBExpr($builder, $operator, self::$searchOperatorMap);
		} else {
			throw new \InvalidArgumentException('Invalid operator type: ' . get_class($operator));
		}
	}

	private function searchComparisonToDBExpr(IQueryBuilder $builder, ISearchComparison $comparison, array $operatorMap) {
		if (!$this->isValidComparison($comparison)) {
			throw new \InvalidArgumentException('Invalid comparison ' . $operator->getType() . ' on field ' . $operator->getField());
		}

		list($field, $value) = $this->getOperatorFieldAndValue($comparison);
		if (isset($operatorMap[$comparison->getType()])) {
			$queryOperator = $operatorMap[$comparison->getType()];
			return $builder->expr()->$queryOperator($field, $this->getParameterForValue($builder, $value));
		} else {
			throw new \InvalidArgumentException('Invalid operator type: ' . $comparison->getType());
		}
	}

	private function getOperatorFieldAndValue(ISearchComparison $operator) {
		$field = $operator->getField();
		$value = $operator->getValue();
		if ($field === 'mimetype') {
			if ($operator->getType() === ISearchComparison::COMPARE_EQUAL) {
				$value = $this->mimetypeLoader->getId($value);
			} else if ($operator->getType() === ISearchComparison::COMPARE_LIKE) {
				// transform "mimetype='foo/%'" to "mimepart='foo'"
				if (preg_match('|(.+)/%|', $value, $matches)) {
					$field = 'mimepart';
					$value = $this->mimetypeLoader->getId($matches[1]);
				}
			}
		}
		return [$field, $value];
	}

	private function isValidComparison(ISearchComparison $operator) {
		$types = [
			'mimetype' => 'string',
			'mtime' => \DateTime::class,
			'name' => 'string',
			'size' => 'integer'
		];
		$comparisons = [
			'mimetype' => ['eq', 'like'],
			'mtime' => ['eq', 'gt', 'lt', 'gte', 'lte'],
			'name' => ['eq', 'like'],
			'size' => ['eq', 'gt', 'lt', 'gte', 'lte']
		];

		if (!isset($types[$operator->getField()])) {
			return false;
		}
		$type = $types[$operator->getField()];
		if (gettype($operator->getValue()) !== $type && !(is_a($operator->getValue(), $type))) {
			return false;
		}
		return in_array($operator->getType(), $comparisons[$operator->getField()]);
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
}

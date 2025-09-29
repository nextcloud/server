<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Search\QueryOptimizer;

use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;

/**
 * transform (field == A OR field == B ...) into field IN (A, B, ...)
 */
class OrEqualsToIn extends ReplacingOptimizerStep {
	public function processOperator(ISearchOperator &$operator): bool {
		if (
			$operator instanceof ISearchBinaryOperator
			&& $operator->getType() === ISearchBinaryOperator::OPERATOR_OR
		) {
			$groups = $this->groupEqualsComparisonsByField($operator->getArguments());
			$newParts = array_map(function (array $group) {
				if (count($group) > 1) {
					// because of the logic from `groupEqualsComparisonsByField` we now that group is all comparisons on the same field
					/** @var ISearchComparison[] $group */
					$field = $group[0]->getField();
					$values = array_map(function (ISearchComparison $comparison) {
						/** @var string|integer|bool|\DateTime $value */
						$value = $comparison->getValue();
						return $value;
					}, $group);
					$in = new SearchComparison(ISearchComparison::COMPARE_IN, $field, $values, $group[0]->getExtra());
					$pathEqHash = array_reduce($group, function ($pathEqHash, ISearchComparison $comparison) {
						return $comparison->getQueryHint(ISearchComparison::HINT_PATH_EQ_HASH, true) && $pathEqHash;
					}, true);
					$in->setQueryHint(ISearchComparison::HINT_PATH_EQ_HASH, $pathEqHash);
					return $in;
				} else {
					return $group[0];
				}
			}, $groups);
			if (count($newParts) === 1) {
				$operator = $newParts[0];
			} else {
				$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, $newParts);
			}
			parent::processOperator($operator);
			return true;
		}
		parent::processOperator($operator);
		return false;
	}

	/**
	 * Non-equals operators are put in a separate group for each
	 *
	 * @param ISearchOperator[] $operators
	 * @return ISearchOperator[][]
	 */
	private function groupEqualsComparisonsByField(array $operators): array {
		$result = [];
		foreach ($operators as $operator) {
			if ($operator instanceof ISearchComparison && $operator->getType() === ISearchComparison::COMPARE_EQUAL) {
				$result[$operator->getField()][] = $operator;
			} else {
				$result[] = [$operator];
			}
		}
		return array_values($result);
	}
}

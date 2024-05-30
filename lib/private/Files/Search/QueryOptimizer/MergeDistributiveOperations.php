<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Search\QueryOptimizer;

use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchOperator;

/**
 * Attempt to transform
 *
 * (A AND B) OR (A AND C) OR (A AND D AND E) into A AND (B OR C OR (D AND E))
 *
 * This is always valid because logical 'AND' and 'OR' are distributive[1].
 *
 * [1]: https://en.wikipedia.org/wiki/Distributive_property
 */
class MergeDistributiveOperations extends ReplacingOptimizerStep {
	public function processOperator(ISearchOperator &$operator): bool {
		if ($operator instanceof SearchBinaryOperator) {
			// either 'AND' or 'OR'
			$topLevelType = $operator->getType();

			// split the arguments into groups that share a first argument
			$groups = $this->groupBinaryOperatorsByChild($operator->getArguments(), 0);
			$outerOperations = array_map(function (array $operators) use ($topLevelType) {
				// no common operations, no need to change anything
				if (count($operators) === 1) {
					return $operators[0];
				}

				// for groups with size >1 we know they are binary operators with at least 1 child
				/** @var ISearchBinaryOperator $firstArgument */
				$firstArgument = $operators[0];

				// we already checked that all arguments have the same type, so this type applies for all, either 'AND' or 'OR'
				$innerType = $firstArgument->getType();

				// the common operation we move out ('A' from the example)
				$extractedLeftHand = $firstArgument->getArguments()[0];

				// for each argument we remove the extracted operation to get the leftovers ('B', 'C' and '(D AND E)' in the example)
				// note that we leave them inside the "inner" binary operation for when the "inner" operation contained more than two parts
				// in the (common) case where the "inner" operation only has 1 item left it will be cleaned up in a follow up step
				$rightHandArguments = array_map(function (ISearchOperator $inner) {
					/** @var ISearchBinaryOperator $inner */
					$arguments = $inner->getArguments();
					array_shift($arguments);
					if (count($arguments) === 1) {
						return $arguments[0];
					}
					return new SearchBinaryOperator($inner->getType(), $arguments);
				}, $operators);

				// combine the extracted operation ('A') with the remaining bit ('(B OR C OR (D AND E))')
				// note that because of how distribution work, we use the "outer" type "inside" and the "inside" type "outside".
				$extractedRightHand = new SearchBinaryOperator($topLevelType, $rightHandArguments);
				return new SearchBinaryOperator(
					$innerType,
					[$extractedLeftHand, $extractedRightHand]
				);
			}, $groups);

			// combine all groups again
			$operator = new SearchBinaryOperator($topLevelType, $outerOperations);
			parent::processOperator($operator);
			return true;
		}
		return parent::processOperator($operator);
	}

	/**
	 * Group a list of binary search operators that have a common argument
	 *
	 * Non-binary operators, or empty binary operators will each get their own 1-sized group
	 *
	 * @param ISearchOperator[] $operators
	 * @return ISearchOperator[][]
	 */
	private function groupBinaryOperatorsByChild(array $operators, int $index = 0): array {
		$result = [];
		foreach ($operators as $operator) {
			if ($operator instanceof ISearchBinaryOperator && count($operator->getArguments()) > 0) {
				/** @var SearchBinaryOperator|SearchComparison $child */
				$child = $operator->getArguments()[$index];
				$childKey = (string)$child;
				$result[$childKey][] = $operator;
			} else {
				$result[] = [$operator];
			}
		}
		return array_values($result);
	}
}

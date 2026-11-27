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
	#[\Override]
	public function processOperator(ISearchOperator &$operator): bool {
		if ($operator instanceof SearchBinaryOperator && $operator->getArguments() !== []) {
			// either 'AND' or 'OR'
			$topLevelType = $operator->getType();

			$argumentCounts = array_map(fn (ISearchOperator $operator) => ($operator instanceof ISearchBinaryOperator) ?count($operator->getArguments()) : 0, $operator->getArguments());
			$maxArgumentCount = max($argumentCounts);

			$bestGrouping = array_map(fn ($argument) => [$argument], $operator->getArguments()); // worst case, no grouping
			$bestGroupingIndex = 0;
			// split the arguments into groups that share a an argument
			// we try all different arguments to find the argument index to find the argument with the least unique fields (and thus the most common fields)
			for ($i = 0; $i < $maxArgumentCount; $i++) {
				$groups = $this->groupBinaryOperatorsByChild($operator->getArguments(), $i);
				if (count($groups) < count($bestGrouping)) {
					$bestGrouping = $groups;
					$bestGroupingIndex = $i;
				}
			}

			$outerOperations = array_map(function (array $operators) use ($topLevelType, $bestGroupingIndex) {
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
				// note that arguments that don't have the specified argument are always placed in single item groups, and thus never reach here
				$extractedCommonOperator = $firstArgument->getArguments()[$bestGroupingIndex];

				// for each argument we remove the extracted operation to get the leftovers ('B', 'C' and '(D AND E)' in the example)
				// note that we leave them inside the "inner" binary operation for when the "inner" operation contained more than two parts
				// in the (common) case where the "inner" operation only has 1 item left it will be cleaned up in a follow up step
				$restArguments = array_map(function (ISearchOperator $inner) use ($bestGroupingIndex) {
					/** @var ISearchBinaryOperator $inner */
					$arguments = $inner->getArguments();
					unset($arguments[$bestGroupingIndex]);
					$arguments = array_values($arguments);

					if (count($arguments) === 1) {
						return $arguments[0];
					}
					return new SearchBinaryOperator($inner->getType(), $arguments);
				}, $operators);

				// combine the extracted operation ('A') with the remaining bit ('(B OR C OR (D AND E))')
				// note that because of how distribution work, we use the "outer" type "inside" and the "inside" type "outside".
				$extractedRightHand = new SearchBinaryOperator($topLevelType, $restArguments);
				return new SearchBinaryOperator(
					$innerType,
					[$extractedCommonOperator, $extractedRightHand]
				);
			}, $bestGrouping);

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
			if ($operator instanceof ISearchBinaryOperator && count($operator->getArguments()) > $index) {
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

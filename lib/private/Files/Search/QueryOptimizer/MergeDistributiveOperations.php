<?php

namespace OC\Files\Search\QueryOptimizer;

use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchOperator;

/**
 * Attempt to transform
 *
 * (A AND B) OR (A AND C) into A AND (B OR C)
 */
class MergeDistributiveOperations extends ReplacingOptimizerStep {
	public function processOperator(ISearchOperator &$operator): bool {
		if (
			$operator instanceof SearchBinaryOperator &&
			$this->isAllSameBinaryOperation($operator->getArguments())
		) {
			$topLevelType = $operator->getType();

			$groups = $this->groupBinaryOperatorsByChild($operator->getArguments(), 0);
			$outerOperations = array_map(function (array $operators) use ($topLevelType) {
				if (count($operators) === 1) {
					return $operators[0];
				}
				/** @var ISearchBinaryOperator $firstArgument */
				$firstArgument = $operators[0];
				$outerType = $firstArgument->getType();
				$extractedLeftHand = $firstArgument->getArguments()[0];

				$rightHandArguments = array_map(function (ISearchOperator $inner) {
					/** @var ISearchBinaryOperator $inner */
					$arguments = $inner->getArguments();
					array_shift($arguments);
					if (count($arguments) === 1) {
						return $arguments[0];
					}
					return new SearchBinaryOperator($inner->getType(), $arguments);
				}, $operators);
				$extractedRightHand = new SearchBinaryOperator($topLevelType, $rightHandArguments);
				return new SearchBinaryOperator(
					$outerType,
					[$extractedLeftHand, $extractedRightHand]
				);
			}, $groups);
			$operator = new SearchBinaryOperator($topLevelType, $outerOperations);
			parent::processOperator($operator);
			return true;
		}
		return parent::processOperator($operator);
	}

	/**
	 * Check that a list of operators is all the same type of (non-empty) binary operators
	 *
	 * @param ISearchOperator[] $operators
	 * @return bool
	 * @psalm-assert-if-true SearchBinaryOperator[] $operators
	 */
	private function isAllSameBinaryOperation(array $operators): bool {
		$operation = null;
		foreach ($operators as $operator) {
			if (!$operator instanceof SearchBinaryOperator) {
				return false;
			}
			if (!$operator->getArguments()) {
				return false;
			}
			if ($operation === null) {
				$operation = $operator->getType();
			} else {
				if ($operation !== $operator->getType()) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Group a list of binary search operators that have a common argument
	 *
	 * @param SearchBinaryOperator[] $operators
	 * @return SearchBinaryOperator[][]
	 */
	private function groupBinaryOperatorsByChild(array $operators, int $index = 0): array {
		$result = [];
		foreach ($operators as $operator) {
			/** @var SearchBinaryOperator|SearchComparison $child */
			$child = $operator->getArguments()[$index];
			;
			$childKey = (string) $child;
			$result[$childKey][] = $operator;
		}
		return array_values($result);
	}
}

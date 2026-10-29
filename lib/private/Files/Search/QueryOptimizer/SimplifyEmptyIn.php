<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Search\QueryOptimizer;

use OC\Files\Search\SearchBinaryOperator;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;

/**
 * An `in` comparison with an empty set matches nothing, and its negation matches everything.
 *
 * Fold these constants into the operators around them: an `and` with a child that matches nothing
 * matches nothing, an `or` with a child that matches everything matches everything, and any other
 * constant child is removed.
 */
class SimplifyEmptyIn extends ReplacingOptimizerStep {
	#[\Override]
	public function processOperator(ISearchOperator &$operator): bool {
		parent::processOperator($operator);

		if (!$operator instanceof SearchBinaryOperator) {
			return false;
		}

		$type = $operator->getType();
		if ($type !== ISearchBinaryOperator::OPERATOR_AND && $type !== ISearchBinaryOperator::OPERATOR_OR) {
			return false;
		}

		// the constant that decides an `and` or `or`, and the one that has no effect on it
		$decisive = $type === ISearchBinaryOperator::OPERATOR_AND ? false : true;
		$remaining = [];
		$neutral = null;
		foreach ($operator->getArguments() as $argument) {
			$constant = $this->constantValue($argument);
			if ($constant === $decisive) {
				$operator = $argument;
				return true;
			}
			if ($constant === null) {
				$remaining[] = $argument;
			} else {
				$neutral = $argument;
			}
		}

		if ($neutral === null) {
			return false;
		}

		if ($remaining === []) {
			$operator = $neutral;
		} elseif (count($remaining) === 1) {
			$operator = $remaining[0];
		} else {
			$operator = new SearchBinaryOperator($type, $remaining);
		}
		return true;
	}

	/**
	 * `false` for an operator that matches nothing, `true` for one that matches everything, and
	 * `null` for any other.
	 */
	private function constantValue(ISearchOperator $operator): ?bool {
		if ($operator instanceof ISearchComparison) {
			return $this->isEmptyIn($operator) ? false : null;
		}

		if (
			$operator instanceof ISearchBinaryOperator
			&& $operator->getType() === ISearchBinaryOperator::OPERATOR_NOT
			&& count($operator->getArguments()) === 1
			&& $operator->getArguments()[0] instanceof ISearchComparison
			&& $this->isEmptyIn($operator->getArguments()[0])
		) {
			return true;
		}

		return null;
	}

	private function isEmptyIn(ISearchComparison $comparison): bool {
		return $comparison->getType() === ISearchComparison::COMPARE_IN && $comparison->getValue() === [];
	}
}

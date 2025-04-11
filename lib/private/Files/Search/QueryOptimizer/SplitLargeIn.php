<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Search\QueryOptimizer;

use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;

/**
 * transform IN (1000+ element) into (IN (1000 elements) OR IN(...))
 */
class SplitLargeIn extends ReplacingOptimizerStep {
	public function processOperator(ISearchOperator &$operator): bool {
		if (
			$operator instanceof ISearchComparison &&
			$operator->getType() === ISearchComparison::COMPARE_IN &&
			count($operator->getValue()) > 1000
		) {
			$chunks = array_chunk($operator->getValue(), 1000);
			$chunkComparisons = array_map(function (array $values) use ($operator) {
				return new SearchComparison(ISearchComparison::COMPARE_IN, $operator->getField(), $values);
			}, $chunks);

			$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, $chunkComparisons);
			return true;
		}
		parent::processOperator($operator);
		return false;
	}
}

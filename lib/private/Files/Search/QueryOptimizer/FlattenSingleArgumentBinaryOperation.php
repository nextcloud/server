<?php

namespace OC\Files\Search\QueryOptimizer;

use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchOperator;

/**
 * replace single argument AND and OR operations with their single argument
 */
class FlattenSingleArgumentBinaryOperation extends ReplacingOptimizerStep {
	public function processOperator(ISearchOperator &$operator): bool {
		parent::processOperator($operator);
		if (
			$operator instanceof ISearchBinaryOperator &&
			count($operator->getArguments()) === 1 &&
			(
				$operator->getType() === ISearchBinaryOperator::OPERATOR_OR ||
				$operator->getType() === ISearchBinaryOperator::OPERATOR_AND
			)
		) {
			$operator = $operator->getArguments()[0];
			return true;
		}
		return false;
	}
}

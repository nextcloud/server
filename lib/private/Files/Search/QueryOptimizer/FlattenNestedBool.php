<?php

namespace OC\Files\Search\QueryOptimizer;

use OC\Files\Search\SearchBinaryOperator;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchOperator;

class FlattenNestedBool extends QueryOptimizerStep {
	public function processOperator(ISearchOperator &$operator) {
		if (
			$operator instanceof SearchBinaryOperator && (
				$operator->getType() === ISearchBinaryOperator::OPERATOR_OR ||
				$operator->getType() === ISearchBinaryOperator::OPERATOR_AND
			)
		) {
			$newArguments = [];
			foreach ($operator->getArguments() as $oldArgument) {
				if ($oldArgument instanceof SearchBinaryOperator && $oldArgument->getType() === $operator->getType()) {
					$newArguments = array_merge($newArguments, $oldArgument->getArguments());
				} else {
					$newArguments[] = $oldArgument;
				}
			}
			$operator->setArguments($newArguments);
		}
		parent::processOperator($operator);
	}
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Search\QueryOptimizer;

use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchOperator;

class QueryOptimizerStep {
	/**
	 * Allow optimizer steps to inspect the entire query before starting processing
	 *
	 * @param ISearchOperator $operator
	 * @return void
	 */
	public function inspectOperator(ISearchOperator $operator): void {
		if ($operator instanceof ISearchBinaryOperator) {
			foreach ($operator->getArguments() as $argument) {
				$this->inspectOperator($argument);
			}
		}
	}

	/**
	 * Allow optimizer steps to modify query operators
	 *
	 * @param ISearchOperator $operator
	 * @return void
	 */
	public function processOperator(ISearchOperator &$operator) {
		if ($operator instanceof ISearchBinaryOperator) {
			foreach ($operator->getArguments() as $argument) {
				$this->processOperator($argument);
			}
		}
	}
}

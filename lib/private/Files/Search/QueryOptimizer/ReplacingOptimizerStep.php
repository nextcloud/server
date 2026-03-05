<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Search\QueryOptimizer;

use OC\Files\Search\SearchBinaryOperator;
use OCP\Files\Search\ISearchOperator;

/**
 * Optimizer step that can replace the $operator altogether instead of just modifying it
 * These steps need some extra logic to properly replace the arguments of binary operators
 */
class ReplacingOptimizerStep extends QueryOptimizerStep {
	/**
	 * Allow optimizer steps to modify query operators
	 *
	 * Returns true if the reference $operator points to a new value
	 */
	public function processOperator(ISearchOperator &$operator): bool {
		if ($operator instanceof SearchBinaryOperator) {
			$modified = false;
			$arguments = $operator->getArguments();
			foreach ($arguments as &$argument) {
				if ($this->processOperator($argument)) {
					$modified = true;
				}
			}
			if ($modified) {
				$operator->setArguments($arguments);
			}
		}
		return false;
	}
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Search\QueryOptimizer;

use OCP\Files\Search\ISearchOperator;

class QueryOptimizer {
	/** @var QueryOptimizerStep[] */
	private $steps = [];

	public function __construct() {
		// note that the order here is relevant
		$this->steps = [
			new PathPrefixOptimizer(),
			new MergeDistributiveOperations(),
			new FlattenSingleArgumentBinaryOperation(),
			new FlattenNestedBool(),
			new OrEqualsToIn(),
			new FlattenNestedBool(),
			new SplitLargeIn(),
		];
	}

	public function processOperator(ISearchOperator &$operator) {
		foreach ($this->steps as $step) {
			$step->inspectOperator($operator);
		}
		foreach ($this->steps as $step) {
			$step->processOperator($operator);
		}
	}
}

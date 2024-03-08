<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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

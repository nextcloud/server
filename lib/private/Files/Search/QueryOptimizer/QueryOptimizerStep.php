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

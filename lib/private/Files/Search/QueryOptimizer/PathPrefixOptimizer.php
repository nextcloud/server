<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Robin Appelman <robin@icewind.nl>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Robin Appelman <robin@icewind.nl>
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

use OC\Files\Search\SearchComparison;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;

class PathPrefixOptimizer extends QueryOptimizerStep {
	private bool $useHashEq = true;

	public function inspectOperator(ISearchOperator $operator): void {
		// normally any `path = "$path"` search filter would be generated as an `path_hash = md5($path)` sql query
		// since the `path_hash` sql column usually provides much faster querying that selecting on the `path` sql column
		//
		// however, if we're already doing a filter on the `path` column in the form of `path LIKE "$prefix/%"`
		// generating a `path = "$prefix"` sql query lets the database handle use the same column for both expressions and potentially use the same index
		//
		// If there is any operator in the query that matches this pattern, we change all `path = "$path"` instances to not the `path_hash` equality,
		// otherwise mariadb has a tendency of ignoring the path_prefix index
		if ($this->useHashEq && $this->isPathPrefixOperator($operator)) {
			$this->useHashEq = false;
		}

		parent::inspectOperator($operator);
	}

	public function processOperator(ISearchOperator &$operator) {
		if (!$this->useHashEq && $operator instanceof ISearchComparison && !$operator->getExtra() && $operator->getField() === 'path' && $operator->getType() === ISearchComparison::COMPARE_EQUAL) {
			$operator->setQueryHint(ISearchComparison::HINT_PATH_EQ_HASH, false);
		}

		parent::processOperator($operator);
	}

	private function isPathPrefixOperator(ISearchOperator $operator): bool {
		if ($operator instanceof ISearchBinaryOperator && $operator->getType() === ISearchBinaryOperator::OPERATOR_OR && count($operator->getArguments()) == 2) {
			$a = $operator->getArguments()[0];
			$b = $operator->getArguments()[1];
			if ($this->operatorPairIsPathPrefix($a, $b) || $this->operatorPairIsPathPrefix($b, $a)) {
				return true;
			}
		}
		return false;
	}

	private function operatorPairIsPathPrefix(ISearchOperator $like, ISearchOperator $equal): bool {
		return (
			$like instanceof ISearchComparison && $equal instanceof ISearchComparison &&
			!$like->getExtra() && !$equal->getExtra() && $like->getField() === 'path' && $equal->getField() === 'path' &&
			$like->getType() === ISearchComparison::COMPARE_LIKE_CASE_SENSITIVE && $equal->getType() === ISearchComparison::COMPARE_EQUAL
			&& $like->getValue() === SearchComparison::escapeLikeParameter($equal->getValue()) . '/%'
		);
	}
}

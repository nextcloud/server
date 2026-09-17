<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Search\QueryOptimizer;

use OC\Files\Search\SearchBinaryOperator;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchOperator;

/**
 * Rewrite `not (a and b)` into `(not a) or (not b)`, and `not (a or b)` into `(not a) and (not b)`,
 * applying De Morgan's laws until every `not` wraps a comparison directly. `not (not a)` is
 * collapsed into `a`.
 *
 * `SearchBuilder` can only turn `not` into SQL when its single argument is a comparison; a `not`
 * left wrapping an `and`/`or` fails with "Binary operators inside 'not' is not supported" however
 * deep inside the tree it occurs. Doing the rewrite here means every caller that builds a query out
 * of `ISearchOperator` gets a working negation of an arbitrary group, instead of each one having to
 * push its own negations down before it ever reaches the file cache.
 */
class PushDownNegation extends ReplacingOptimizerStep {
	#[\Override]
	public function processOperator(ISearchOperator &$operator): bool {
		if (
			$operator instanceof SearchBinaryOperator
			&& $operator->getType() === ISearchBinaryOperator::OPERATOR_NOT
			&& count($operator->getArguments()) === 1
		) {
			$inner = $operator->getArguments()[0];

			if ($inner instanceof SearchBinaryOperator && $inner->getType() === ISearchBinaryOperator::OPERATOR_NOT) {
				$operator = $inner->getArguments()[0];
				$this->processOperator($operator);

				return true;
			}

			if ($inner instanceof SearchBinaryOperator) {
				// the only other binary operator types are 'and' and 'or', which De Morgan swaps
				$flipped = $inner->getType() === ISearchBinaryOperator::OPERATOR_AND
					? ISearchBinaryOperator::OPERATOR_OR
					: ISearchBinaryOperator::OPERATOR_AND;

				$operator = new SearchBinaryOperator($flipped, array_map(
					static fn (ISearchOperator $child): ISearchOperator
						=> new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [$child]),
					$inner->getArguments(),
				));
				$this->processOperator($operator);

				return true;
			}
		}

		parent::processOperator($operator);

		return false;
	}
}

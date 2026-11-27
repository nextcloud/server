<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Search\QueryOptimizer;

use OC\Files\Search\SearchBinaryOperator;
use OCP\Files\Search\ISearchOperator;

/**
 * Repeatedly apply the `MergeDistributiveOperations` optimizer until it runs out of effect
 */
class RepeatMergeDistributiveOperations extends MergeDistributiveOperations {
	#[\Override]
	public function processOperator(ISearchOperator &$operator): bool {
		if ($operator instanceof SearchBinaryOperator) {
			do {
				$argumentCount = count($operator->getArguments());
				parent::processOperator($operator);
			} while (count($operator->getArguments()) < $argumentCount);
			return true;
		}
		return parent::processOperator($operator);
	}
}

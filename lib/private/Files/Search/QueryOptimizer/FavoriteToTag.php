<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Robin Appelman <robin@icewind.nl>
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
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;

class FavoriteToTag extends ReplacingOptimizerStep {
	public const TAG_FAVORITE = '_$!<Favorite>!$_';

	public function processOperator(ISearchOperator &$operator): bool {
		if ($operator instanceof ISearchComparison && $operator->getField() === 'favorite' && $operator->getType() === ISearchComparison::COMPARE_EQUAL) {
			$operator = new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'tagname', self::TAG_FAVORITE);
			return true;
		} else {
			return parent::processOperator($operator);
		}
	}
}

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

class DavTagToFileIds extends ReplacingOptimizerStep {
	public function __construct(
		private IDBConnection $connection,
		private IUser $user,
	) {
	}

	public function processOperator(ISearchOperator &$operator): bool {
		if ($operator instanceof ISearchComparison && $operator->getField() === 'tagname') {
			$operator = new SearchComparison(ISearchComparison::COMPARE_IN, 'fileid', $this->getFileIdsForDavTag($operator));
			return true;
		} else {
			return parent::processOperator($operator);
		}
	}


	private function getFileIdsForDavTag(ISearchComparison $comparison): array {
		$query = $this->connection->getQueryBuilder();

		$query->select('tagmap.objid')
			->from('vcategory_to_object', 'tagmap')
			->leftJoin('tagmap', 'vcategory', 'tag', $query->expr()->andX(
				$query->expr()->eq('tagmap.type', 'tag.type'),
				$query->expr()->eq('tagmap.categoryid', 'tag.id')
			))
			->where($query->expr()->eq('tag.type', $query->createNamedParameter('files')))
			->andWhere($query->expr()->eq('tag.uid', $query->createNamedParameter($this->user->getUID())));
		if ($comparison->getType() === ISearchComparison::COMPARE_EQUAL) {
			$query->andWhere($query->expr()->eq('tag.category', $query->createNamedParameter($comparison->getValue())));
		} elseif ($comparison->getType() === ISearchComparison::COMPARE_LIKE) {
			$query->andWhere($query->expr()->like('tag.category', $query->createNamedParameter($comparison->getValue())));
		} else {
			throw new \InvalidArgumentException('Unsupported comparison for field  ' . $comparison->getField() . ': ' . $comparison->getType());
		}
		return $query->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
	}
}

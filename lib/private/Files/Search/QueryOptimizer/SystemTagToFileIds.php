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

class SystemTagToFileIds extends ReplacingOptimizerStep {
	public function __construct(
		private IDBConnection $connection,
		private IUser $user,
		private IGroupManager $groupManager,
	) {
	}

	public function processOperator(ISearchOperator &$operator): bool {
		if ($operator instanceof ISearchComparison && $operator->getField() === 'systemtag') {
			$operator = new SearchComparison(ISearchComparison::COMPARE_IN, 'fileid', $this->getFileIdsForSystemTag($operator));
			return true;
		} else {
			return parent::processOperator($operator);
		}
	}


	private function getFileIdsForSystemTag(ISearchComparison $comparison): array {
		$query = $this->connection->getQueryBuilder();

		$on = $query->expr()->andX($query->expr()->eq('systemtag.id', 'systemtagmap.systemtagid'));
		if (!$this->groupManager->isAdmin($this->user->getUID())) {
			$on->add($query->expr()->eq('systemtag.visibility', $query->createNamedParameter(true)));
		}

		$query->select('systemtagmap.objectid')
			->from('systemtag_object_mapping', 'systemtagmap')
			->leftJoin('systemtagmap', 'systemtag', 'systemtag', $on)
			->where($query->expr()->eq('systemtagmap.objecttype', $query->createNamedParameter('files')))
			->andWhere($query->expr()->eq('systemtagmap.objecttype', $query->createNamedParameter('files')));
		if ($comparison->getType() === ISearchComparison::COMPARE_EQUAL) {
			$query->andWhere($query->expr()->eq('systemtag.name', $query->createNamedParameter($comparison->getValue())));
		} elseif ($comparison->getType() === ISearchComparison::COMPARE_LIKE) {
			$query->andWhere($query->expr()->like('systemtag.name', $query->createNamedParameter($comparison->getValue())));
		} else {
			throw new \InvalidArgumentException('Unsupported comparison for field  ' . $comparison->getField() . ': ' . $comparison->getType());
		}
		return $query->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
	}
}

<?php

declare(strict_types=1);
/*
 * @copyright 2024 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\DAV\DAV\Sharing;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class SharingMapper {
	public function __construct(private IDBConnection $db) {
	}

	public function getSharesForId(int $resourceId, string $resourceType): array {
		$query = $this->db->getQueryBuilder();
		$result = $query->select(['principaluri', 'access'])
			->from('dav_shares')
			->where($query->expr()->eq('resourceid', $query->createNamedParameter($resourceId, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter($resourceType, IQueryBuilder::PARAM_STR)))
			->andWhere($query->expr()->neq('access', $query->createNamedParameter(Backend::ACCESS_UNSHARED, IQueryBuilder::PARAM_INT)))
			->groupBy(['principaluri', 'access'])
			->executeQuery();

		$rows = $result->fetchAll();
		$result->closeCursor();
		return $rows;
	}

	public function getSharesForIds(array $resourceIds, string $resourceType): array {
		$query = $this->db->getQueryBuilder();
		$result = $query->select(['resourceid', 'principaluri', 'access'])
			->from('dav_shares')
			->where($query->expr()->in('resourceid', $query->createNamedParameter($resourceIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter($resourceType)))
			->andWhere($query->expr()->neq('access', $query->createNamedParameter(Backend::ACCESS_UNSHARED, IQueryBuilder::PARAM_INT)))
			->groupBy(['principaluri', 'access', 'resourceid'])
			->executeQuery();

		$rows = $result->fetchAll();
		$result->closeCursor();
		return $rows;
	}

	public function unshare(int $resourceId, string $resourceType, string $principal): void {
		$query = $this->db->getQueryBuilder();
		$query->insert('dav_shares')
			->values([
				'principaluri' => $query->createNamedParameter($principal),
				'type' => $query->createNamedParameter($resourceType),
				'access' => $query->createNamedParameter(Backend::ACCESS_UNSHARED),
				'resourceid' => $query->createNamedParameter($resourceId)
			]);
		$query->executeStatement();
	}

	public function share(int $resourceId, string $resourceType, int $access, string $principal): void {
		$query = $this->db->getQueryBuilder();
		$query->insert('dav_shares')
			->values([
				'principaluri' => $query->createNamedParameter($principal),
				'type' => $query->createNamedParameter($resourceType),
				'access' => $query->createNamedParameter($access),
				'resourceid' => $query->createNamedParameter($resourceId)
			]);
		$query->executeStatement();
	}

	public function deleteShare(int $resourceId, string $resourceType, string $principal): void {
		$query = $this->db->getQueryBuilder();
		$query->delete('dav_shares');
		$query->where(
			$query->expr()->eq('resourceid', $query->createNamedParameter($resourceId, IQueryBuilder::PARAM_INT)),
			$query->expr()->eq('type', $query->createNamedParameter($resourceType)),
			$query->expr()->eq('principaluri', $query->createNamedParameter($principal))
		);
		$query->executeStatement();

	}

	public function deleteAllShares(int $resourceId, string $resourceType): void {
		$query = $this->db->getQueryBuilder();
		$query->delete('dav_shares')
			->where($query->expr()->eq('resourceid', $query->createNamedParameter($resourceId)))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter($resourceType)))
			->executeStatement();
	}

	public function deleteAllSharesByUser(string $principaluri, string $resourceType): void {
		$query = $this->db->getQueryBuilder();
		$query->delete('dav_shares')
			->where($query->expr()->eq('principaluri', $query->createNamedParameter($principaluri)))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter($resourceType)))
			->executeStatement();
	}
}

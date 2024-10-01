<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\DAV\Sharing;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class SharingMapper {
	public function __construct(
		private IDBConnection $db,
	) {
	}

	protected function getSharesForIdByAccess(int $resourceId, string $resourceType, bool $sharesWithAccess): array {
		$query = $this->db->getQueryBuilder();
		$query->select(['principaluri', 'access'])
			->from('dav_shares')
			->where($query->expr()->eq('resourceid', $query->createNamedParameter($resourceId, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter($resourceType, IQueryBuilder::PARAM_STR)))
			->groupBy(['principaluri', 'access']);

		if ($sharesWithAccess) {
			$query->andWhere($query->expr()->neq('access', $query->createNamedParameter(Backend::ACCESS_UNSHARED, IQueryBuilder::PARAM_INT)));
		} else {
			$query->andWhere($query->expr()->eq('access', $query->createNamedParameter(Backend::ACCESS_UNSHARED, IQueryBuilder::PARAM_INT)));
		}

		$result = $query->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();
		return $rows;
	}

	public function getSharesForId(int $resourceId, string $resourceType): array {
		return $this->getSharesForIdByAccess($resourceId, $resourceType, true);
	}

	public function getUnsharesForId(int $resourceId, string $resourceType): array {
		return $this->getSharesForIdByAccess($resourceId, $resourceType, false);
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

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

	public function shareWithToken(int $resourceId, string $resourceType, int $access, string $principal, string $token): void {
		$query = $this->db->getQueryBuilder();
		$query->insert('dav_shares')
			->values([
				'principaluri' => $query->createNamedParameter($principal),
				'type' => $query->createNamedParameter($resourceType),
				'access' => $query->createNamedParameter($access),
				'resourceid' => $query->createNamedParameter($resourceId),
				'token' => $query->createNamedParameter($token),
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

	public function getSharesByPrincipals(array $principals, string $resourceType): array {
		$query = $this->db->getQueryBuilder();
		$result = $query->select(['id', 'principaluri', 'type', 'access', 'resourceid'])
			->from('dav_shares')
			->where($query->expr()->in('principaluri', $query->createNamedParameter($principals, IQueryBuilder::PARAM_STR_ARRAY), IQueryBuilder::PARAM_STR_ARRAY))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter($resourceType)))
			->orderBy('id')
			->executeQuery();

		$rows = $result->fetchAll();
		$result->closeCursor();

		return $rows;
	}

	public function deleteUnsharesByPrincipal(string $principal, string $resourceType): void {
		$query = $this->db->getQueryBuilder();
		$query->delete('dav_shares')
			->where($query->expr()->eq('principaluri', $query->createNamedParameter($principal)))
			->andWhere($query->expr()->eq('type', $query->createNamedParameter($resourceType)))
			->andWhere($query->expr()->eq('access', $query->createNamedParameter(Backend::ACCESS_UNSHARED, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}

	/**
	 * @return array{principaluri: string}[]
	 * @throws \OCP\DB\Exception
	 */
	public function getPrincipalUrisByPrefix(string $resourceType, string $prefix): array {
		$query = $this->db->getQueryBuilder();
		$result = $query->selectDistinct('principaluri')
			->from('dav_shares')
			->where($query->expr()->like(
				'principaluri',
				$query->createNamedParameter("$prefix/%", IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			))
			->andWhere($query->expr()->eq(
				'type',
				$query->createNamedParameter($resourceType, IQueryBuilder::PARAM_STR)),
				IQueryBuilder::PARAM_STR,
			)
			->executeQuery();

		$rows = $result->fetchAll();
		$result->closeCursor();

		return $rows;
	}

	/**
	 * @psalm-return array{uri: string, principaluri: string}[]
	 * @throws \OCP\DB\Exception
	 */
	public function getSharedCalendarsForRemoteUser(
		string $remoteUserPrincipalUri,
		string $token,
	): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('c.uri', 'c.principaluri')
			->from('dav_shares', 'ds')
			->join('ds', 'calendars', 'c', $qb->expr()->eq(
				'ds.resourceid',
				'c.id',
				IQueryBuilder::PARAM_INT,
			))
			->where($qb->expr()->eq(
				'ds.type',
				$qb->createNamedParameter('calendar', IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			))
			->andWhere($qb->expr()->eq(
				'ds.principaluri',
				$qb->createNamedParameter($remoteUserPrincipalUri, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			))
			->andWhere($qb->expr()->eq(
				'ds.token',
				$qb->createNamedParameter($token, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			));
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();

		return $rows;
	}

	/**
	 * @param string[] $principalUris
	 *
	 * @throws \OCP\DB\Exception
	 */
	public function getSharesByPrincipalsAndResource(
		array $principalUris,
		int $resourceId,
		string $resourceType,
	): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('dav_shares')
			->where($qb->expr()->in(
				'principaluri',
				$qb->createNamedParameter($principalUris, IQueryBuilder::PARAM_STR_ARRAY),
				IQueryBuilder::PARAM_STR_ARRAY,
			))
			->andWhere($qb->expr()->eq(
				'resourceid',
				$qb->createNamedParameter($resourceId, IQueryBuilder::PARAM_INT),
				IQueryBuilder::PARAM_INT,
			))
			->andWhere($qb->expr()->eq(
				'type',
				$qb->createNamedParameter($resourceType, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			));
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();

		return $rows;
	}
}

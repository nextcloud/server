<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<GroupMembership>
 */
class GroupMembershipMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'ldap_group_membership', GroupMembership::class);
	}

	/**
	 * @return string[]
	 */
	public function getKnownGroups(): array {
		$query = $this->db->getQueryBuilder();
		$result = $query->selectDistinct('groupid')
			->from($this->getTableName())
			->executeQuery();

		$groups = array_column($result->fetchAll(), 'groupid');
		$result->closeCursor();
		return $groups;
	}

	/**
	 * @return GroupMembership[]
	 */
	public function findGroupMemberships(string $groupid): array {
		$qb = $this->db->getQueryBuilder();
		$select = $qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('groupid', $qb->createNamedParameter($groupid)));

		return $this->findEntities($select);
	}

	/**
	 * @return GroupMembership[]
	 */
	public function findGroupMembershipsForUser(string $userid): array {
		$qb = $this->db->getQueryBuilder();
		$select = $qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('userid', $qb->createNamedParameter($userid)));

		return $this->findEntities($select);
	}

	public function deleteGroups(array $removedGroups): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where($query->expr()->in('groupid', $query->createParameter('groupids')));

		foreach (array_chunk($removedGroups, 1000) as $removedGroupsChunk) {
			$query->setParameter('groupids', $removedGroupsChunk, IQueryBuilder::PARAM_STR_ARRAY);
			$query->executeStatement();
		}
	}
}

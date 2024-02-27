<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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

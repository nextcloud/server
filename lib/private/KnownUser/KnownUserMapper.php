<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\KnownUser;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @method KnownUser mapRowToEntity(array $row)
 *
 * @template-extends QBMapper<KnownUser>
 */
class KnownUserMapper extends QBMapper {
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'known_users', KnownUser::class);
	}

	/**
	 * @param string $knownTo
	 * @return int Number of deleted entities
	 */
	public function deleteKnownTo(string $knownTo): int {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where($query->expr()->eq('known_to', $query->createNamedParameter($knownTo)));

		return $query->executeStatement();
	}

	/**
	 * @param string $knownUser
	 * @return int Number of deleted entities
	 */
	public function deleteKnownUser(string $knownUser): int {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where($query->expr()->eq('known_user', $query->createNamedParameter($knownUser)));

		return $query->executeStatement();
	}

	/**
	 * Returns all "known users" for the given "known to" user
	 *
	 * @param string $knownTo
	 * @return KnownUser[]
	 */
	public function getKnownUsers(string $knownTo): array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName())
			->where($query->expr()->eq('known_to', $query->createNamedParameter($knownTo)));

		return $this->findEntities($query);
	}

	public function createKnownUserFromRow(array $row): KnownUser {
		return $this->mapRowToEntity([
			'id' => $row['s_id'],
			'known_to' => $row['known_to'],
			'known_user' => $row['known_user'],
		]);
	}
}

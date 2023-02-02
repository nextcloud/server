<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OC\KnownUser;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @method KnownUser mapRowToEntity(array $row)
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

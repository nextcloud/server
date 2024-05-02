<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Authentication\Token;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\Authentication\Token\IToken;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<PublicKeyToken>
 */
class PublicKeyTokenMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'authtoken');
	}

	/**
	 * Invalidate (delete) a given token
	 */
	public function invalidate(string $token) {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
			->where($qb->expr()->eq('token', $qb->createNamedParameter($token)))
			->andWhere($qb->expr()->eq('version', $qb->createNamedParameter(PublicKeyToken::VERSION, IQueryBuilder::PARAM_INT)))
			->execute();
	}

	/**
	 * @param int $olderThan
	 * @param int $remember
	 */
	public function invalidateOld(int $olderThan, int $remember = IToken::DO_NOT_REMEMBER) {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
			->where($qb->expr()->lt('last_activity', $qb->createNamedParameter($olderThan, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('type', $qb->createNamedParameter(IToken::TEMPORARY_TOKEN, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('remember', $qb->createNamedParameter($remember, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('version', $qb->createNamedParameter(PublicKeyToken::VERSION, IQueryBuilder::PARAM_INT)))
			->execute();
	}

	public function invalidateLastUsedBefore(string $uid, int $before): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->lt('last_activity', $qb->createNamedParameter($before, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('version', $qb->createNamedParameter(PublicKeyToken::VERSION, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}

	/**
	 * Get the user UID for the given token
	 *
	 * @throws DoesNotExistException
	 */
	public function getToken(string $token): PublicKeyToken {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq('token', $qb->createNamedParameter($token)))
			->andWhere($qb->expr()->eq('version', $qb->createNamedParameter(PublicKeyToken::VERSION, IQueryBuilder::PARAM_INT)))
			->execute();

		$data = $result->fetch();
		$result->closeCursor();
		if ($data === false) {
			throw new DoesNotExistException('token does not exist');
		}
		return PublicKeyToken::fromRow($data);
	}

	/**
	 * Get the token for $id
	 *
	 * @throws DoesNotExistException
	 */
	public function getTokenById(int $id): PublicKeyToken {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('version', $qb->createNamedParameter(PublicKeyToken::VERSION, IQueryBuilder::PARAM_INT)))
			->execute();

		$data = $result->fetch();
		$result->closeCursor();
		if ($data === false) {
			throw new DoesNotExistException('token does not exist');
		}
		return PublicKeyToken::fromRow($data);
	}

	/**
	 * Get all tokens of a user
	 *
	 * The provider may limit the number of result rows in case of an abuse
	 * where a high number of (session) tokens is generated
	 *
	 * @param string $uid
	 * @return PublicKeyToken[]
	 */
	public function getTokenByUser(string $uid): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('version', $qb->createNamedParameter(PublicKeyToken::VERSION, IQueryBuilder::PARAM_INT)))
			->setMaxResults(1000);
		$result = $qb->execute();
		$data = $result->fetchAll();
		$result->closeCursor();

		$entities = array_map(function ($row) {
			return PublicKeyToken::fromRow($row);
		}, $data);

		return $entities;
	}

	public function getTokenByUserAndId(string $uid, int $id): ?string {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('token')
			->from($this->tableName)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('version', $qb->createNamedParameter(PublicKeyToken::VERSION, IQueryBuilder::PARAM_INT)));
		return $qb->executeQuery()->fetchOne() ?: null;
	}

	/**
	 * delete all auth token which belong to a specific client if the client was deleted
	 *
	 * @param string $name
	 */
	public function deleteByName(string $name) {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
			->where($qb->expr()->eq('name', $qb->createNamedParameter($name), IQueryBuilder::PARAM_STR))
			->andWhere($qb->expr()->eq('version', $qb->createNamedParameter(PublicKeyToken::VERSION, IQueryBuilder::PARAM_INT)));
		$qb->execute();
	}

	public function deleteTempToken(PublicKeyToken $except) {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->tableName)
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($except->getUID())))
			->andWhere($qb->expr()->eq('type', $qb->createNamedParameter(IToken::TEMPORARY_TOKEN)))
			->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($except->getId())))
			->andWhere($qb->expr()->eq('version', $qb->createNamedParameter(PublicKeyToken::VERSION, IQueryBuilder::PARAM_INT)));

		$qb->execute();
	}

	public function hasExpiredTokens(string $uid): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('password_invalid', $qb->createNamedParameter(true), IQueryBuilder::PARAM_BOOL))
			->setMaxResults(1);

		$cursor = $qb->execute();
		$data = $cursor->fetchAll();
		$cursor->closeCursor();

		return count($data) === 1;
	}

	/**
	 * Update the last activity timestamp
	 *
	 * In highly concurrent setups it can happen that two parallel processes
	 * trigger the update at (nearly) the same time. In that special case it's
	 * not necessary to hit the database with two actual updates. Therefore the
	 * target last activity is included in the WHERE clause with a few seconds
	 * of tolerance.
	 *
	 * Example:
	 * - process 1 (P1) reads the token at timestamp 1500
	 * - process 1 (P2) reads the token at timestamp 1501
	 * - activity update interval is 100
	 *
	 * This means
	 *
	 * - P1 will see a last_activity smaller than the current time and update
	 *   the token row
	 * - If P2 reads after P1 had written, it will see 1600 as last activity
	 *   and the comparison on last_activity won't be truthy. This means no rows
	 *   need to be updated a second time
	 * - If P2 reads before P1 had written, it will see 1501 as last activity,
	 *   but the comparison on last_activity will still not be truthy and the
	 *   token row is not updated a second time
	 *
	 * @param IToken $token
	 * @param int $now
	 */
	public function updateActivity(IToken $token, int $now): void {
		$qb = $this->db->getQueryBuilder();
		$update = $qb->update($this->getTableName())
			->set('last_activity', $qb->createNamedParameter($now, IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($token->getId(), IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb->expr()->lt('last_activity', $qb->createNamedParameter($now - 15, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT)
			);
		$update->executeStatement();
	}

	public function updateHashesForUser(string $userId, string $passwordHash): void {
		$qb = $this->db->getQueryBuilder();
		$update = $qb->update($this->getTableName())
			->set('password_hash', $qb->createNamedParameter($passwordHash))
			->where(
				$qb->expr()->eq('uid', $qb->createNamedParameter($userId))
			);
		$update->executeStatement();
	}

	public function getFirstTokenForUser(string $userId): ?PublicKeyToken {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId)))
			->setMaxResults(1)
			->orderBy('id');
		$result = $qb->executeQuery();

		$data = $result->fetch();
		$result->closeCursor();
		if ($data === false) {
			return null;
		}
		return PublicKeyToken::fromRow($data);
	}
}

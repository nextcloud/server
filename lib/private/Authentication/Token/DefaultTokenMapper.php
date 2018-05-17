<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Marcel Waldvogel <marcel.waldvogel@uni-konstanz.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Authentication\Token;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;

class DefaultTokenMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'authtoken');
	}

	/**
	 * Invalidate (delete) a given token
	 *
	 * @param string $token
	 */
	public function invalidate(string $token) {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->delete('authtoken')
			->where($qb->expr()->eq('token', $qb->createParameter('token')))
			->setParameter('token', $token)
			->execute();
	}

	/**
	 * @param int $olderThan
	 * @param int $remember
	 */
	public function invalidateOld(int $olderThan, int $remember = IToken::DO_NOT_REMEMBER) {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->delete('authtoken')
			->where($qb->expr()->lt('last_activity', $qb->createNamedParameter($olderThan, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('type', $qb->createNamedParameter(IToken::TEMPORARY_TOKEN, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('remember', $qb->createNamedParameter($remember, IQueryBuilder::PARAM_INT)))
			->execute();
	}

	/**
	 * Get the user UID for the given token
	 *
	 * @param string $token
	 * @throws DoesNotExistException
	 * @return DefaultToken
	 */
	public function getToken(string $token): DefaultToken {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('*')
			->from('authtoken')
			->where($qb->expr()->eq('token', $qb->createNamedParameter($token)))
			->execute();

		$data = $result->fetch();
		$result->closeCursor();
		if ($data === false) {
			throw new DoesNotExistException('token does not exist');
		}
		return DefaultToken::fromRow($data);
	}

	/**
	 * Get the token for $id
	 *
	 * @param int $id
	 * @throws DoesNotExistException
	 * @return DefaultToken
	 */
	public function getTokenById(int $id): DefaultToken {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('*')
			->from('authtoken')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->execute();

		$data = $result->fetch();
		$result->closeCursor();
		if ($data === false) {
			throw new DoesNotExistException('token does not exist');
		}
		return DefaultToken::fromRow($data);
	}

	/**
	 * Get all tokens of a user
	 *
	 * The provider may limit the number of result rows in case of an abuse
	 * where a high number of (session) tokens is generated
	 *
	 * @param IUser $user
	 * @return DefaultToken[]
	 */
	public function getTokenByUser(IUser $user): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('authtoken')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())))
			->setMaxResults(1000);
		$result = $qb->execute();
		$data = $result->fetchAll();
		$result->closeCursor();

		$entities = array_map(function ($row) {
			return DefaultToken::fromRow($row);
		}, $data);

		return $entities;
	}

	/**
	 * @param IUser $user
	 * @param int $id
	 */
	public function deleteById(IUser $user, int $id) {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->delete('authtoken')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())));
		$qb->execute();
	}

	/**
	 * delete all auth token which belong to a specific client if the client was deleted
	 *
	 * @param string $name
	 */
	public function deleteByName(string $name) {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('authtoken')
			->where($qb->expr()->eq('name', $qb->createNamedParameter($name), IQueryBuilder::PARAM_STR));
		$qb->execute();
	}

}

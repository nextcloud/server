<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\Authentication\ClientLogin;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Mapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class AccessTokenMapper extends Mapper {

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'client_access_tokens');
	}

	/**
	 * @param string $accessToken hashed access token
	 * @throws DoesNotExistException
	 * @return AccessToken
	 */
	public function getToken($accessToken) {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'token', 'uid', 'client_name', 'status', 'created_at')
			->from('client_access_tokens')
			->where($qb->expr()->eq('token', $qb->createNamedParameter($accessToken)));
		$result = $qb->execute();

		$data = $result->fetch();
		$result->closeCursor();

		if ($data === false) {
			throw new DoesNotExistException('access token does not exist');
		}

		return AccessToken::fromRow($data);
	}

}

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

namespace OC\Authentication\Token;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;

class DefaultTokenMapper extends Mapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'authtoken');
	}

	/**
	 * Invalidate (delete) a given token
	 *
	 * @param string $token
	 */
	public function invalidate($token) {
		$sql = 'DELETE FROM `' . $this->getTableName() . '` '
			. 'WHERE `token` = ?';
		return $this->execute($sql, [
				$token
		]);
	}

	/**
	 * @param int $olderThan
	 */
	public function invalidateOld($olderThan) {
		$sql = 'DELETE FROM `' . $this->getTableName() . '` '
			. 'WHERE `last_activity` < ?';
		$this->execute($sql, [
			$olderThan
		]);
	}

	/**
	 * Get the user UID for the given token
	 *
	 * @param string $token
	 * @throws DoesNotExistException
	 * @return DefaultToken
	 */
	public function getToken($token) {
		$sql = 'SELECT `id`, `uid`, `password`, `name`, `token`, `last_activity` '
			. 'FROM `' . $this->getTableName() . '` '
			. 'WHERE `token` = ?';
		return $this->findEntity($sql, [
				$token
		]);
	}

}

<?php
/**
 * @author Jörn Friedrich Dreyer
 * @copyright (c) 2014 Jörn Friedrich Dreyer <jfd@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\ObjectStore;

use OC\User\User;

class HomeObjectStoreStorage extends ObjectStoreStorage implements \OCP\Files\IHomeStorage {

	/**
	 * The home user storage requires a user object to create a unique storage id
	 * @param array $params
	 */
	public function __construct($params) {
		if ( ! isset($params['user']) || ! $params['user'] instanceof User) {
			throw new \Exception('missing user object in parameters');
		}
		$this->user = $params['user'];
		parent::__construct($params);
	}

	public function getId () {
		return 'object::user:' . $this->user->getUID();
	}

	/**
	 * get the owner of a path
	 *
	 * @param string $path The path to get the owner
	 * @return false|string uid
	 */
	public function getOwner($path) {
		if (is_object($this->user)) {
			return $this->user->getUID();
		}
		return false;
	}

	/**
	 * @param string $path, optional
	 * @return \OC\User\User
	 */
	public function getUser($path = null) {
		return $this->user;
	}


}
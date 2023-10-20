<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Files\ObjectStore;

use OCP\Files\IHomeStorage;
use OCP\IUser;

class HomeObjectStoreStorage extends ObjectStoreStorage implements IHomeStorage {
	protected IUser $user;

	/**
	 * The home user storage requires a user object to create a unique storage id
	 * @param array $params
	 */
	public function __construct($params) {
		if (! isset($params['user']) || ! $params['user'] instanceof IUser) {
			throw new \Exception('missing user object in parameters');
		}
		$this->user = $params['user'];
		parent::__construct($params);
	}

	public function getId() {
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

	public function getUser(): IUser {
		return $this->user;
	}
}

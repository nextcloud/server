<?php

/**
 * @copyright Copyright (c) 2016, Robin Appelman <robin@icewind.nl>
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

namespace OC\Lockdown;

use OC\Authentication\Token\IToken;
use OCP\Lockdown\ILockdownManager;

class LockdownManager implements ILockdownManager {
	private $enabled = false;

	/** @var array|null */
	private $scope;

	public function enable() {
		$this->enabled = true;
	}

	public function setToken(IToken $token) {
		$this->scope = $token->getScopeAsArray();
		$this->enable();
	}

	public function canAccessFilesystem() {
		if (!$this->enabled) {
			return true;
		}
		return !$this->scope || $this->scope['filesystem'];
	}
}

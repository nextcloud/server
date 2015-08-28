<?php
/**
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\Files_External\Lib;

use \OCA\Files_External\Service\BackendService;

/**
 * Trait to implement backend and auth mechanism permissions
 *
 * For user type constants, see BackendService::USER_*
 * For permission constants, see BackendService::PERMISSION_*
 */
trait PermissionsTrait {

	/** @var array [user type => permissions] */
	protected $permissions = [
		BackendService::USER_PERSONAL => BackendService::PERMISSION_DEFAULT,
		BackendService::USER_ADMIN => BackendService::PERMISSION_DEFAULT,
	];

	/** @var array [user type => allowed permissions] */
	protected $allowedPermissions = [
		BackendService::USER_PERSONAL => BackendService::PERMISSION_DEFAULT,
		BackendService::USER_ADMIN => BackendService::PERMISSION_DEFAULT,
	];

	/**
	 * @param string $userType
	 * @return int
	 */
	public function getPermissions($userType) {
		if (isset($this->permissions[$userType])) {
			return $this->permissions[$userType];
		}
		return BackendService::PERMISSION_NONE;
	}

	/**
	 * Check if the user type has permission
	 *
	 * @param string $userType
	 * @param int $permission
	 * @return bool
	 */
	public function isPermitted($userType, $permission) {
		if ($this->getPermissions($userType) & $permission) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $userType
	 * @param int $permissions
	 * @return self
	 */
	public function setPermissions($userType, $permissions) {
		$this->permissions[$userType] = $permissions;
		$this->allowedPermissions[$userType] =
			$this->getAllowedPermissions($userType) | $permissions;
		return $this;
	}

	/**
	 * @param string $userType
	 * @param int $permission
	 * @return self
	 */
	public function addPermission($userType, $permission) {
		return $this->setPermissions($userType,
			$this->getPermissions($userType) | $permission
		);
	}

	/**
	 * @param string $userType
	 * @param int $permission
	 * @return self
	 */
	public function removePermission($userType, $permission) {
		return $this->setPermissions($userType,
			$this->getPermissions($userType) & ~$permission
		);
	}

	/**
	 * @param string $userType
	 * @return int
	 */
	public function getAllowedPermissions($userType) {
		if (isset($this->allowedPermissions[$userType])) {
			return $this->allowedPermissions[$userType];
		}
		return BackendService::PERMISSION_NONE;
	}

	/**
	 * Check if the user type has an allowed permission
	 *
	 * @param string $userType
	 * @param int $permission
	 * @return bool
	 */
	public function isAllowedPermitted($userType, $permission) {
		if ($this->getAllowedPermissions($userType) & $permission) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $userType
	 * @param int $permissions
	 * @return self
	 */
	public function setAllowedPermissions($userType, $permissions) {
		$this->allowedPermissions[$userType] = $permissions;
		$this->permissions[$userType] =
			$this->getPermissions($userType) & $permissions;
		return $this;
	}

	/**
	 * @param string $userType
	 * @param int $permission
	 * @return self
	 */
	public function addAllowedPermission($userType, $permission) {
		return $this->setAllowedPermissions($userType,
			$this->getAllowedPermissions($userType) | $permission
		);
	}

	/**
	 * @param string $userType
	 * @param int $permission
	 * @return self
	 */
	public function removeAllowedPermission($userType, $permission) {
		return $this->setAllowedPermissions($userType,
			$this->getAllowedPermissions($userType) & ~$permission
		);
	}

}

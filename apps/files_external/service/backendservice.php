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

namespace OCA\Files_External\Service;

use \OCP\IConfig;

use \OCA\Files_External\Lib\Backend\Backend;
use \OCA\Files_External\Lib\Auth\AuthMechanism;

/**
 * Service class to manage backend definitions
 */
class BackendService {

	/** Visibility constants for VisibilityTrait */
	const VISIBILITY_NONE = 0;
	const VISIBILITY_PERSONAL = 1;
	const VISIBILITY_ADMIN = 2;
	//const VISIBILITY_ALIENS = 4;

	const VISIBILITY_DEFAULT = 3; // PERSONAL | ADMIN

	/** Priority constants for PriorityTrait */
	const PRIORITY_DEFAULT = 100;

	/** @var IConfig */
	protected $config;

	/** @var bool */
	private $userMountingAllowed = true;

	/** @var string[] */
	private $userMountingBackends = [];

	/** @var Backend[] */
	private $backends = [];

	/** @var AuthMechanism[] */
	private $authMechanisms = [];

	/**
	 * @param IConfig $config
	 */
	public function __construct(
		IConfig $config
	) {
		$this->config = $config;

		// Load config values
		if ($this->config->getAppValue('files_external', 'allow_user_mounting', 'yes') !== 'yes') {
			$this->userMountingAllowed = false;
		}
		$this->userMountingBackends = explode(',',
			$this->config->getAppValue('files_external', 'user_mounting_backends', '')
		);
	}

	/**
	 * Register a backend
	 *
	 * @param Backend $backend
	 */
	public function registerBackend(Backend $backend) {
		if (!$this->isAllowedUserBackend($backend)) {
			$backend->removeVisibility(BackendService::VISIBILITY_PERSONAL);
		}
		foreach ($backend->getIdentifierAliases() as $alias) {
			$this->backends[$alias] = $backend;
		}
	}

	/**
	 * @param Backend[] $backends
	 */
	public function registerBackends(array $backends) {
		foreach ($backends as $backend) {
			$this->registerBackend($backend);
		}
	}
	/**
	 * Register an authentication mechanism
	 *
	 * @param AuthMechanism $authMech
	 */
	public function registerAuthMechanism(AuthMechanism $authMech) {
		if (!$this->isAllowedAuthMechanism($authMech)) {
			$authMech->removeVisibility(BackendService::VISIBILITY_PERSONAL);
		}
		foreach ($authMech->getIdentifierAliases() as $alias) {
			$this->authMechanisms[$alias] = $authMech;
		}
	}

	/**
	 * @param AuthMechanism[] $mechanisms
	 */
	public function registerAuthMechanisms(array $mechanisms) {
		foreach ($mechanisms as $mechanism) {
			$this->registerAuthMechanism($mechanism);
		}
	}

	/**
	 * Get all backends
	 *
	 * @return Backend[]
	 */
	public function getBackends() {
		// only return real identifiers, no aliases
		$backends = [];
		foreach ($this->backends as $backend) {
			$backends[$backend->getIdentifier()] = $backend;
		}
		return $backends;
	}

	/**
	 * Get all available backends
	 *
	 * @return Backend[]
	 */
	public function getAvailableBackends() {
		return array_filter($this->getBackends(), function($backend) {
			return !($backend->checkDependencies());
		});
	}

	/**
	 * @param string $identifier
	 * @return Backend|null
	 */
	public function getBackend($identifier) {
		if (isset($this->backends[$identifier])) {
			return $this->backends[$identifier];
		}
		return null;
	}

	/**
	 * Get all authentication mechanisms
	 *
	 * @return AuthMechanism[]
	 */
	public function getAuthMechanisms() {
		// only return real identifiers, no aliases
		$mechanisms = [];
		foreach ($this->authMechanisms as $mechanism) {
			$mechanisms[$mechanism->getIdentifier()] = $mechanism;
		}
		return $mechanisms;
	}

	/**
	 * Get all authentication mechanisms for schemes
	 *
	 * @param string[] $schemes
	 * @return AuthMechanism[]
	 */
	public function getAuthMechanismsByScheme(array $schemes) {
		return array_filter($this->getAuthMechanisms(), function($authMech) use ($schemes) {
			return in_array($authMech->getScheme(), $schemes, true);
		});
	}

	/**
	 * @param string $identifier
	 * @return AuthMechanism|null
	 */
	public function getAuthMechanism($identifier) {
		if (isset($this->authMechanisms[$identifier])) {
			return $this->authMechanisms[$identifier];
		}
		return null;
	}

	/**
	 * @return bool
	 */
	public function isUserMountingAllowed() {
		return $this->userMountingAllowed;
	}

	/**
	 * Check a backend if a user is allowed to mount it
	 *
	 * @param Backend $backend
	 * @return bool
	 */
	protected function isAllowedUserBackend(Backend $backend) {
		if ($this->userMountingAllowed &&
			array_intersect($backend->getIdentifierAliases(), $this->userMountingBackends)
		) {
			return true;
		}
		return false;
	}

	/**
	 * Check an authentication mechanism if a user is allowed to use it
	 *
	 * @param AuthMechanism $authMechanism
	 * @return bool
	 */
	protected function isAllowedAuthMechanism(AuthMechanism $authMechanism) {
		return true; // not implemented
	}
}

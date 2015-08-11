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
		$this->backends[$backend->getClass()] = $backend;
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
	 * Get all backends
	 *
	 * @return Backend[]
	 */
	public function getBackends() {
		return $this->backends;
	}

	/**
	 * Get all available backends
	 *
	 * @return Backend[]
	 */
	public function getAvailableBackends() {
		return array_filter($this->getBackends(), function($backend) {
			return empty($backend->checkDependencies());
		});
	}

	/**
	 * Get backends visible for $visibleFor
	 *
	 * @param int $visibleFor
	 * @return Backend[]
	 */
	public function getBackendsVisibleFor($visibleFor) {
		return array_filter($this->getAvailableBackends(), function($backend) use ($visibleFor) {
			return $backend->isVisibleFor($visibleFor);
		});
	}

	/**
	 * Get backends allowed to be visible for $visibleFor
	 *
	 * @param int $visibleFor
	 * @return Backend[]
	 */
	public function getBackendsAllowedVisibleFor($visibleFor) {
		return array_filter($this->getAvailableBackends(), function($backend) use ($visibleFor) {
			return $backend->isAllowedVisibleFor($visibleFor);
		});
	}

	/**
	 * @param string $class Backend class name
	 * @return Backend|null
	 */
	public function getBackend($class) {
		if (isset($this->backends[$class])) {
			return $this->backends[$class];
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
			in_array($backend->getClass(), $this->userMountingBackends)
		) {
			return true;
		}
		return false;
	}
}

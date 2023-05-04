<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\User_LDAP\Jobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_LDAP;
use OCA\User_LDAP\User_Proxy;

/**
 * Class CleanUp
 *
 * a Background job to clean up deleted users
 *
 * @package OCA\User_LDAP\Jobs;
 */
class CleanUp extends TimedJob {
	/** @var ?int $limit amount of users that should be checked per run */
	protected $limit;

	/** @var int $defaultIntervalMin default interval in minutes */
	protected $defaultIntervalMin = 51;

	/** @var User_LDAP|User_Proxy $userBackend */
	protected $userBackend;

	/** @var \OCP\IConfig $ocConfig */
	protected $ocConfig;

	/** @var \OCP\IDBConnection $db */
	protected $db;

	/** @var Helper $ldapHelper */
	protected $ldapHelper;

	/** @var UserMapping */
	protected $mapping;

	/** @var DeletedUsersIndex */
	protected $dui;

	public function __construct(
		ITimeFactory $timeFactory,
		User_Proxy $userBackend,
		DeletedUsersIndex $dui
	) {
		parent::__construct($timeFactory);
		$minutes = \OC::$server->getConfig()->getSystemValue(
			'ldapUserCleanupInterval', (string)$this->defaultIntervalMin);
		$this->setInterval((int)$minutes * 60);
		$this->userBackend = $userBackend;
		$this->dui = $dui;
	}

	/**
	 * assigns the instances passed to run() to the class properties
	 * @param array $arguments
	 */
	public function setArguments($arguments): void {
		//Dependency Injection is not possible, because the constructor will
		//only get values that are serialized to JSON. I.e. whatever we would
		//pass in app.php we do add here, except something else is passed e.g.
		//in tests.

		if (isset($arguments['helper'])) {
			$this->ldapHelper = $arguments['helper'];
		} else {
			$this->ldapHelper = new Helper(\OC::$server->getConfig(), \OC::$server->getDatabaseConnection());
		}

		if (isset($arguments['ocConfig'])) {
			$this->ocConfig = $arguments['ocConfig'];
		} else {
			$this->ocConfig = \OC::$server->getConfig();
		}

		if (isset($arguments['userBackend'])) {
			$this->userBackend = $arguments['userBackend'];
		}

		if (isset($arguments['db'])) {
			$this->db = $arguments['db'];
		} else {
			$this->db = \OC::$server->getDatabaseConnection();
		}

		if (isset($arguments['mapping'])) {
			$this->mapping = $arguments['mapping'];
		} else {
			$this->mapping = \OCP\Server::get(UserMapping::class);
		}

		if (isset($arguments['deletedUsersIndex'])) {
			$this->dui = $arguments['deletedUsersIndex'];
		}
	}

	/**
	 * makes the background job do its work
	 * @param array $argument
	 */
	public function run($argument): void {
		$this->setArguments($argument);

		if (!$this->isCleanUpAllowed()) {
			return;
		}
		$users = $this->mapping->getList($this->getOffset(), $this->getChunkSize());
		$resetOffset = $this->isOffsetResetNecessary(count($users));
		$this->checkUsers($users);
		$this->setOffset($resetOffset);
	}

	/**
	 * checks whether next run should start at 0 again
	 */
	public function isOffsetResetNecessary(int $resultCount): bool {
		return $resultCount < $this->getChunkSize();
	}

	/**
	 * checks whether cleaning up LDAP users is allowed
	 */
	public function isCleanUpAllowed(): bool {
		try {
			if ($this->ldapHelper->haveDisabledConfigurations()) {
				return false;
			}
		} catch (\Exception $e) {
			return false;
		}

		return $this->isCleanUpEnabled();
	}

	/**
	 * checks whether clean up is enabled by configuration
	 */
	private function isCleanUpEnabled(): bool {
		return (bool)$this->ocConfig->getSystemValue(
			'ldapUserCleanupInterval', (string)$this->defaultIntervalMin);
	}

	/**
	 * checks users whether they are still existing
	 * @param array $users result from getMappedUsers()
	 */
	private function checkUsers(array $users): void {
		foreach ($users as $user) {
			$this->checkUser($user);
		}
	}

	/**
	 * checks whether a user is still existing in LDAP
	 * @param string[] $user
	 */
	private function checkUser(array $user): void {
		if ($this->userBackend->userExistsOnLDAP($user['name'])) {
			//still available, all good

			return;
		}

		$this->dui->markUser($user['name']);
	}

	/**
	 * gets the offset to fetch users from the mappings table
	 */
	private function getOffset(): int {
		return (int)$this->ocConfig->getAppValue('user_ldap', 'cleanUpJobOffset', '0');
	}

	/**
	 * sets the new offset for the next run
	 * @param bool $reset whether the offset should be set to 0
	 */
	public function setOffset(bool $reset = false): void {
		$newOffset = $reset ? 0 :
			$this->getOffset() + $this->getChunkSize();
		$this->ocConfig->setAppValue('user_ldap', 'cleanUpJobOffset', (string)$newOffset);
	}

	/**
	 * returns the chunk size (limit in DB speak)
	 */
	public function getChunkSize(): int {
		if ($this->limit === null) {
			$this->limit = (int)$this->ocConfig->getAppValue('user_ldap', 'cleanUpJobChunkSize', '50');
		}
		return $this->limit;
	}
}

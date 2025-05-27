<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Jobs;

use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_Proxy;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Server;

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
	protected $defaultIntervalMin = 60;

	/** @var IConfig $ocConfig */
	protected $ocConfig;

	/** @var IDBConnection $db */
	protected $db;

	/** @var Helper $ldapHelper */
	protected $ldapHelper;

	/** @var UserMapping */
	protected $mapping;

	public function __construct(
		ITimeFactory $timeFactory,
		protected User_Proxy $userBackend,
		protected DeletedUsersIndex $dui,
	) {
		parent::__construct($timeFactory);
		$minutes = Server::get(IConfig::class)->getSystemValue(
			'ldapUserCleanupInterval', (string)$this->defaultIntervalMin);
		$this->setInterval((int)$minutes * 60);
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
			$this->ldapHelper = Server::get(Helper::class);
		}

		if (isset($arguments['ocConfig'])) {
			$this->ocConfig = $arguments['ocConfig'];
		} else {
			$this->ocConfig = Server::get(IConfig::class);
		}

		if (isset($arguments['userBackend'])) {
			$this->userBackend = $arguments['userBackend'];
		}

		if (isset($arguments['db'])) {
			$this->db = $arguments['db'];
		} else {
			$this->db = Server::get(IDBConnection::class);
		}

		if (isset($arguments['mapping'])) {
			$this->mapping = $arguments['mapping'];
		} else {
			$this->mapping = Server::get(UserMapping::class);
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

<?php
/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\User_LDAP\Jobs;

use \OCA\user_ldap\User_Proxy;
use \OCA\user_ldap\lib\Helper;
use \OCA\user_ldap\lib\LDAP;

/**
 * Class CleanUp
 *
 * a Background job to clean up deleted users
 *
 * @package OCA\user_ldap\lib;
 */
class CleanUp extends \OC\BackgroundJob\TimedJob {
	/**
	 * @var int $limit amount of users that should be checked per run
	 */
	protected $limit = 50;

	/**
	 * @var \OCP\UserInterface $userBackend
	 */
	protected $userBackend;

	/**
	 * @var \OCP\IConfig $ocConfig
	 */
	protected $ocConfig;

	/**
	 * @var \OCP\IDBConnection $db
	 */
	protected $db;

	/**
	 * @var Helper $ldapHelper
	 */
	protected $ldapHelper;

	/**
	 * @var int $defaultIntervalMin default interval in minutes
	 */
	protected $defaultIntervalMin = 51;

	public function __construct() {
		$minutes = \OC::$server->getConfig()->getSystemValue(
			'ldapUserCleanupInterval', strval($this->defaultIntervalMin));
		$this->setInterval(intval($minutes) * 60);
	}

	/**
	 * assigns the instances passed to run() to the class properties
	 * @param array $arguments
	 */
	public function setArguments($arguments) {
		//Dependency Injection is not possible, because the constructor will
		//only get values that are serialized to JSON. I.e. whatever we would
		//pass in app.php we do add here, except something else is passed e.g.
		//in tests.

		if(isset($arguments['helper'])) {
			$this->ldapHelper = $arguments['helper'];
		} else {
			$this->ldapHelper = new Helper();
		}

		if(isset($arguments['userBackend'])) {
			$this->userBackend = $arguments['userBackend'];
		} else {
			$this->userBackend =  new User_Proxy(
				$this->ldapHelper->getServerConfigurationPrefixes(true),
				new LDAP()
			);
		}

		if(isset($arguments['ocConfig'])) {
			$this->ocConfig = $arguments['ocConfig'];
		} else {
			$this->ocConfig = \OC::$server->getConfig();
		}

		if(isset($arguments['db'])) {
			$this->db = $arguments['db'];
		} else {
			$this->db = \OC::$server->getDatabaseConnection();
		}
	}

	/**
	 * makes the background job do its work
	 * @param array $argument
	 */
	public function run($argument) {
		$this->setArguments($argument);

		if(!$this->isCleanUpAllowed()) {
			return;
		}
		$users = $this->getMappedUsers($this->limit, $this->getOffset());
		if(!is_array($users)) {
			//something wrong? Let's start from the beginning next time and
			//abort
			$this->setOffset(true);
			return;
		}
		$resetOffset = $this->isOffsetResetNecessary(count($users));
		$this->checkUsers($users);
		$this->setOffset($resetOffset);
	}

	/**
	 * checks whether next run should start at 0 again
	 * @param int $resultCount
	 * @return bool
	 */
	public function isOffsetResetNecessary($resultCount) {
		return ($resultCount < $this->limit) ? true : false;
	}

	/**
	 * checks whether cleaning up LDAP users is allowed
	 * @return bool
	 */
	public function isCleanUpAllowed() {
		try {
			if($this->ldapHelper->haveDisabledConfigurations()) {
				return false;
			}
		} catch (\Exception $e) {
			return false;
		}

		$enabled = $this->isCleanUpEnabled();

		return $enabled;
	}

	/**
	 * checks whether clean up is enabled by configuration
	 * @return bool
	 */
	private function isCleanUpEnabled() {
		return (bool)$this->ocConfig->getSystemValue(
			'ldapUserCleanupInterval', strval($this->defaultIntervalMin));
	}

	/**
	 * checks users whether they are still existing
	 * @param array $users result from getMappedUsers()
	 */
	private function checkUsers($users) {
		foreach($users as $user) {
			$this->checkUser($user);
		}
	}

	/**
	 * checks whether a user is still existing in LDAP
	 * @param string[] $user
	 */
	private function checkUser($user) {
		if($this->userBackend->userExistsOnLDAP($user['name'])) {
			//still available, all good
			return;
		}

		// TODO FIXME consolidate next line in DeletedUsersIndex
		// (impractical now, because of class dependencies)
		$this->ocConfig->setUserValue($user['name'], 'user_ldap', 'isDeleted', '1');
	}

	/**
	 * returns a batch of users from the mappings table
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function getMappedUsers($limit, $offset) {
		$query = $this->db->prepare('
			SELECT
				`ldap_dn` AS `dn`,
				`owncloud_name` AS `name`,
				`directory_uuid` AS `uuid`
			FROM `*PREFIX*ldap_user_mapping`',
			$limit,
			$offset
		);

		$query->execute();
		return $query->fetchAll();
	}

	/**
	 * gets the offset to fetch users from the mappings table
	 * @return int
	 */
	private function getOffset() {
		return $this->ocConfig->getAppValue('user_ldap', 'cleanUpJobOffset', 0);
	}

	/**
	 * sets the new offset for the next run
	 * @param bool $reset whether the offset should be set to 0
	 */
	public function setOffset($reset = false) {
		$newOffset = $reset ? 0 :
			$this->getOffset() + $this->limit;
		$this->ocConfig->setAppValue('user_ldap', 'cleanUpJobOffset', $newOffset);
	}

	/**
	 * returns the chunk size (limit in DB speak)
	 * @return int
	 */
	public function getChunkSize() {
		return $this->limit;
	}

}

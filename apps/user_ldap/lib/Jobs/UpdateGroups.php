<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\User_LDAP\Jobs;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\FilesystemHelper;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\LogWrapper;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\Manager;

class UpdateGroups extends \OC\BackgroundJob\TimedJob {
	static private $groupsFromDB;

	static private $groupBE;
	static private $connector;

	public function __construct(){
		$this->interval = self::getRefreshInterval();
	}

	/**
	 * @param mixed $argument
	 */
	public function run($argument){
		self::updateGroups();
	}

	static public function updateGroups() {
		\OCP\Util::writeLog('user_ldap', 'Run background job "updateGroups"', \OCP\Util::DEBUG);

		$knownGroups = array_keys(self::getKnownGroups());
		$actualGroups = self::getGroupBE()->getGroups();

		if(empty($actualGroups) && empty($knownGroups)) {
			\OCP\Util::writeLog('user_ldap',
				'bgJ "updateGroups" – groups do not seem to be configured properly, aborting.',
				\OCP\Util::INFO);
			return;
		}

		self::handleKnownGroups(array_intersect($actualGroups, $knownGroups));
		self::handleCreatedGroups(array_diff($actualGroups, $knownGroups));
		self::handleRemovedGroups(array_diff($knownGroups, $actualGroups));

		\OCP\Util::writeLog('user_ldap', 'bgJ "updateGroups" – Finished.', \OCP\Util::DEBUG);
	}

	/**
	 * @return int
	 */
	static private function getRefreshInterval() {
		//defaults to every hour
		return \OCP\Config::getAppValue('user_ldap', 'bgjRefreshInterval', 3600);
	}

	/**
	 * @param string[] $groups
	 */
	static private function handleKnownGroups($groups) {
		\OCP\Util::writeLog('user_ldap', 'bgJ "updateGroups" – Dealing with known Groups.', \OCP\Util::DEBUG);
		$query = \OCP\DB::prepare('
			UPDATE `*PREFIX*ldap_group_members`
			SET `owncloudusers` = ?
			WHERE `owncloudname` = ?
		');
		foreach($groups as $group) {
			//we assume, that self::$groupsFromDB has been retrieved already
			$knownUsers = unserialize(self::$groupsFromDB[$group]['owncloudusers']);
			$actualUsers = self::getGroupBE()->usersInGroup($group);
			$hasChanged = false;
			foreach(array_diff($knownUsers, $actualUsers) as $removedUser) {
				\OCP\Util::emitHook('OC_User', 'post_removeFromGroup', array('uid' => $removedUser, 'gid' => $group));
				\OCP\Util::writeLog('user_ldap',
				'bgJ "updateGroups" – "'.$removedUser.'" removed from "'.$group.'".',
				\OCP\Util::INFO);
				$hasChanged = true;
			}
			foreach(array_diff($actualUsers, $knownUsers) as $addedUser) {
				\OCP\Util::emitHook('OC_User', 'post_addToGroup', array('uid' => $addedUser, 'gid' => $group));
				\OCP\Util::writeLog('user_ldap',
				'bgJ "updateGroups" – "'.$addedUser.'" added to "'.$group.'".',
				\OCP\Util::INFO);
				$hasChanged = true;
			}
			if($hasChanged) {
				$query->execute(array(serialize($actualUsers), $group));
			}
		}
		\OCP\Util::writeLog('user_ldap',
			'bgJ "updateGroups" – FINISHED dealing with known Groups.',
			\OCP\Util::DEBUG);
	}

	/**
	 * @param string[] $createdGroups
	 */
	static private function handleCreatedGroups($createdGroups) {
		\OCP\Util::writeLog('user_ldap', 'bgJ "updateGroups" – dealing with created Groups.', \OCP\Util::DEBUG);
		$query = \OCP\DB::prepare('
			INSERT
			INTO `*PREFIX*ldap_group_members` (`owncloudname`, `owncloudusers`)
			VALUES (?, ?)
		');
		foreach($createdGroups as $createdGroup) {
			\OCP\Util::writeLog('user_ldap',
				'bgJ "updateGroups" – new group "'.$createdGroup.'" found.',
				\OCP\Util::INFO);
			$users = serialize(self::getGroupBE()->usersInGroup($createdGroup));
			$query->execute(array($createdGroup, $users));
		}
		\OCP\Util::writeLog('user_ldap',
			'bgJ "updateGroups" – FINISHED dealing with created Groups.',
			\OCP\Util::DEBUG);
	}

	/**
	 * @param string[] $removedGroups
	 */
	static private function handleRemovedGroups($removedGroups) {
		\OCP\Util::writeLog('user_ldap', 'bgJ "updateGroups" – dealing with removed groups.', \OCP\Util::DEBUG);
		$query = \OCP\DB::prepare('
			DELETE
			FROM `*PREFIX*ldap_group_members`
			WHERE `owncloudname` = ?
		');
		foreach($removedGroups as $removedGroup) {
			\OCP\Util::writeLog('user_ldap',
				'bgJ "updateGroups" – group "'.$removedGroup.'" was removed.',
				\OCP\Util::INFO);
			$query->execute(array($removedGroup));
		}
		\OCP\Util::writeLog('user_ldap',
			'bgJ "updateGroups" – FINISHED dealing with removed groups.',
			\OCP\Util::DEBUG);
	}

	/**
	 * @return \OCA\User_LDAP\Group_LDAP|\OCA\User_LDAP\Group_Proxy
	 */
	static private function getGroupBE() {
		if(!is_null(self::$groupBE)) {
			return self::$groupBE;
		}
		$helper = new Helper();
		$configPrefixes = $helper->getServerConfigurationPrefixes(true);
		$ldapWrapper = new LDAP();
		if(count($configPrefixes) === 1) {
			//avoid the proxy when there is only one LDAP server configured
			$dbc = \OC::$server->getDatabaseConnection();
			$userManager = new Manager(
				\OC::$server->getConfig(),
				new FilesystemHelper(),
				new LogWrapper(),
				\OC::$server->getAvatarManager(),
				new \OCP\Image(),
				$dbc,
				\OC::$server->getUserManager());
			$connector = new Connection($ldapWrapper, $configPrefixes[0]);
			$ldapAccess = new Access($connector, $ldapWrapper, $userManager);
			$groupMapper = new GroupMapping($dbc);
			$userMapper  = new UserMapping($dbc);
			$ldapAccess->setGroupMapper($groupMapper);
			$ldapAccess->setUserMapper($userMapper);
			self::$groupBE = new \OCA\User_LDAP\Group_LDAP($ldapAccess);
		} else {
			self::$groupBE = new \OCA\User_LDAP\Group_Proxy($configPrefixes, $ldapWrapper);
		}

		return self::$groupBE;
	}

	/**
	 * @return array
	 */
	static private function getKnownGroups() {
		if(is_array(self::$groupsFromDB)) {
			return self::$groupsFromDB;
		}
		$query = \OCP\DB::prepare('
			SELECT `owncloudname`, `owncloudusers`
			FROM `*PREFIX*ldap_group_members`
		');
		$result = $query->execute()->fetchAll();
		self::$groupsFromDB = array();
		foreach($result as $dataset) {
			self::$groupsFromDB[$dataset['owncloudname']] = $dataset;
		}

		return self::$groupsFromDB;
	}
}

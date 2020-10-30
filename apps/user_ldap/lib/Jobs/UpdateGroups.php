<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Roger Szabo <roger.szabo@web.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\FilesystemHelper;
use OCA\User_LDAP\GroupPluginManager;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\LogWrapper;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\Manager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\ILogger;
use OCP\IUser;

class UpdateGroups extends \OC\BackgroundJob\TimedJob {
	private static $groupsFromDB;

	private static $groupBE;

	public function __construct() {
		$this->interval = self::getRefreshInterval();
	}

	/**
	 * @param mixed $argument
	 */
	public function run($argument) {
		self::updateGroups();
	}

	public static function updateGroups() {
		\OCP\Util::writeLog('user_ldap', 'Run background job "updateGroups"', ILogger::DEBUG);

		$knownGroups = array_keys(self::getKnownGroups());
		$actualGroups = self::getGroupBE()->getGroups();

		if (empty($actualGroups) && empty($knownGroups)) {
			\OCP\Util::writeLog('user_ldap',
				'bgJ "updateGroups" – groups do not seem to be configured properly, aborting.',
				ILogger::INFO);
			return;
		}

		self::handleKnownGroups(array_intersect($actualGroups, $knownGroups));
		self::handleCreatedGroups(array_diff($actualGroups, $knownGroups));
		self::handleRemovedGroups(array_diff($knownGroups, $actualGroups));

		\OCP\Util::writeLog('user_ldap', 'bgJ "updateGroups" – Finished.', ILogger::DEBUG);
	}

	/**
	 * @return int
	 */
	private static function getRefreshInterval() {
		//defaults to every hour
		return \OC::$server->getConfig()->getAppValue('user_ldap', 'bgjRefreshInterval', 3600);
	}

	/**
	 * @param string[] $groups
	 */
	private static function handleKnownGroups($groups) {
		/** @var IEventDispatcher $dispatcher */
		$dispatcher = \OC::$server->query(IEventDispatcher::class);
		$groupManager = \OC::$server->getGroupManager();
		$userManager = \OC::$server->getUserManager();

		\OCP\Util::writeLog('user_ldap', 'bgJ "updateGroups" – Dealing with known Groups.', ILogger::DEBUG);
		$query = \OC_DB::prepare('
			UPDATE `*PREFIX*ldap_group_members`
			SET `owncloudusers` = ?
			WHERE `owncloudname` = ?
		');
		foreach ($groups as $group) {
			//we assume, that self::$groupsFromDB has been retrieved already
			$knownUsers = unserialize(self::$groupsFromDB[$group]['owncloudusers']);
			$actualUsers = self::getGroupBE()->usersInGroup($group);
			$hasChanged = false;

			$groupObject = $groupManager->get($group);
			foreach (array_diff($knownUsers, $actualUsers) as $removedUser) {
				$userObject = $userManager->get($removedUser);
				if ($userObject instanceof IUser) {
					$dispatcher->dispatchTyped(new UserRemovedEvent($groupObject, $userObject));
				}
				\OCP\Util::writeLog('user_ldap',
				'bgJ "updateGroups" – "'.$removedUser.'" removed from "'.$group.'".',
					ILogger::INFO);
				$hasChanged = true;
			}
			foreach (array_diff($actualUsers, $knownUsers) as $addedUser) {
				$userObject = $userManager->get($addedUser);
				if ($userObject instanceof IUser) {
					$dispatcher->dispatchTyped(new UserAddedEvent($groupObject, $userObject));
				}
				\OCP\Util::writeLog('user_ldap',
				'bgJ "updateGroups" – "'.$addedUser.'" added to "'.$group.'".',
					ILogger::INFO);
				$hasChanged = true;
			}
			if ($hasChanged) {
				$query->execute([serialize($actualUsers), $group]);
			}
		}
		\OCP\Util::writeLog('user_ldap',
			'bgJ "updateGroups" – FINISHED dealing with known Groups.',
			ILogger::DEBUG);
	}

	/**
	 * @param string[] $createdGroups
	 */
	private static function handleCreatedGroups($createdGroups) {
		\OCP\Util::writeLog('user_ldap', 'bgJ "updateGroups" – dealing with created Groups.', ILogger::DEBUG);
		$query = \OC_DB::prepare('
			INSERT
			INTO `*PREFIX*ldap_group_members` (`owncloudname`, `owncloudusers`)
			VALUES (?, ?)
		');
		foreach ($createdGroups as $createdGroup) {
			\OCP\Util::writeLog('user_ldap',
				'bgJ "updateGroups" – new group "'.$createdGroup.'" found.',
				ILogger::INFO);
			$users = serialize(self::getGroupBE()->usersInGroup($createdGroup));
			$query->execute([$createdGroup, $users]);
		}
		\OCP\Util::writeLog('user_ldap',
			'bgJ "updateGroups" – FINISHED dealing with created Groups.',
			ILogger::DEBUG);
	}

	/**
	 * @param string[] $removedGroups
	 */
	private static function handleRemovedGroups($removedGroups) {
		\OCP\Util::writeLog('user_ldap', 'bgJ "updateGroups" – dealing with removed groups.', ILogger::DEBUG);
		$query = \OC_DB::prepare('
			DELETE
			FROM `*PREFIX*ldap_group_members`
			WHERE `owncloudname` = ?
		');
		foreach ($removedGroups as $removedGroup) {
			\OCP\Util::writeLog('user_ldap',
				'bgJ "updateGroups" – group "'.$removedGroup.'" was removed.',
				ILogger::INFO);
			$query->execute([$removedGroup]);
		}
		\OCP\Util::writeLog('user_ldap',
			'bgJ "updateGroups" – FINISHED dealing with removed groups.',
			ILogger::DEBUG);
	}

	/**
	 * @return \OCA\User_LDAP\Group_LDAP|\OCA\User_LDAP\Group_Proxy
	 */
	private static function getGroupBE() {
		if (!is_null(self::$groupBE)) {
			return self::$groupBE;
		}
		$helper = new Helper(\OC::$server->getConfig());
		$configPrefixes = $helper->getServerConfigurationPrefixes(true);
		$ldapWrapper = new LDAP();
		if (count($configPrefixes) === 1) {
			//avoid the proxy when there is only one LDAP server configured
			$dbc = \OC::$server->getDatabaseConnection();
			$userManager = new Manager(
				\OC::$server->getConfig(),
				new FilesystemHelper(),
				new LogWrapper(),
				\OC::$server->getAvatarManager(),
				new \OCP\Image(),
				$dbc,
				\OC::$server->getUserManager(),
				\OC::$server->getNotificationManager());
			$connector = new Connection($ldapWrapper, $configPrefixes[0]);
			$ldapAccess = new Access($connector, $ldapWrapper, $userManager, $helper, \OC::$server->getConfig(), \OC::$server->getUserManager());
			$groupMapper = new GroupMapping($dbc);
			$userMapper  = new UserMapping($dbc);
			$ldapAccess->setGroupMapper($groupMapper);
			$ldapAccess->setUserMapper($userMapper);
			self::$groupBE = new \OCA\User_LDAP\Group_LDAP($ldapAccess, \OC::$server->query(GroupPluginManager::class));
		} else {
			self::$groupBE = new \OCA\User_LDAP\Group_Proxy($configPrefixes, $ldapWrapper, \OC::$server->query(GroupPluginManager::class));
		}

		return self::$groupBE;
	}

	/**
	 * @return array
	 */
	private static function getKnownGroups() {
		if (is_array(self::$groupsFromDB)) {
			return self::$groupsFromDB;
		}
		$query = \OC_DB::prepare('
			SELECT `owncloudname`, `owncloudusers`
			FROM `*PREFIX*ldap_group_members`
		');
		$result = $query->execute()->fetchAll();
		self::$groupsFromDB = [];
		foreach ($result as $dataset) {
			self::$groupsFromDB[$dataset['owncloudname']] = $dataset;
		}

		return self::$groupsFromDB;
	}
}

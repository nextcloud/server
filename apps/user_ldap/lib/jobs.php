<?php

/**
 * ownCloud – LDAP Background Jobs
 *
 * @author Arthur Schiwon
 * @copyright 2012 Arthur Schiwon blizzz@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\user_ldap\lib;

class Jobs {
	static private $groupsFromDB;

	static private $groupBE;
	static private $connector;

	static public function updateGroups() {
		\OCP\Util::writeLog('user_ldap', 'Run background job "updateGroups"', \OCP\Util::DEBUG);
		$lastUpdate = \OCP\Config::getAppValue('user_ldap', 'bgjUpdateGroupsLastRun', 0);
		if((time() - $lastUpdate) < self::getRefreshInterval()) {
			\OCP\Util::writeLog('user_ldap', 'bgJ "updateGroups" – last run too fresh, aborting.', \OCP\Util::DEBUG);
			//komm runter Werner die Maurer geben ein aus
			return;
		}

		$knownGroups = array_keys(self::getKnownGroups());
		$actualGroups = self::getGroupBE()->getGroups();

		if(empty($actualGroups) && empty($knownGroups)) {
			\OCP\Util::writeLog('user_ldap',
				'bgJ "updateGroups" – groups do not seem to be configured properly, aborting.',
				\OCP\Util::INFO);
			\OCP\Config::setAppValue('user_ldap', 'bgjUpdateGroupsLastRun', time());
			return;
		}

		self::handleKnownGroups(array_intersect($actualGroups, $knownGroups));
		self::handleCreatedGroups(array_diff($actualGroups, $knownGroups));
		self::handleRemovedGroups(array_diff($knownGroups, $actualGroups));

		\OCP\Config::setAppValue('user_ldap', 'bgjUpdateGroupsLastRun', time());

		\OCP\Util::writeLog('user_ldap', 'bgJ "updateGroups" – Finished.', \OCP\Util::DEBUG);
	}

	static private function getRefreshInterval() {
		//defaults to every hour
		return \OCP\Config::getAppValue('user_ldap', 'bgjRefreshInterval', 3600);
	}

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
		        \OCP\Util::emitHook('OC_User', 'post_addFromGroup', array('uid' => $addedUser, 'gid' => $group));
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

	static private function getConnector() {
		if(!is_null(self::$connector)) {
			return self::$connector;
		}
		self::$connector = new \OCA\user_ldap\lib\Connection('user_ldap');
		return self::$connector;
	}

	static private function getGroupBE() {
		if(!is_null(self::$groupBE)) {
			return self::$groupBE;
		}
		self::getConnector();
		self::$groupBE = new \OCA\user_ldap\GROUP_LDAP();
		self::$groupBE->setConnector(self::$connector);

		return self::$groupBE;
	}

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

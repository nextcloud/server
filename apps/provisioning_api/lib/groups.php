<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tom Needham <tom@owncloud.com>
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

namespace OCA\Provisioning_API;

use \OC_OCS_Result;
use \OC_SubAdmin;
use OCP\IGroup;
use OCP\IUser;

class Groups{

	/** @var \OCP\IGroupManager */
	private $groupManager;

	/** @var \OCP\IUserSession */
	private $userSession;

	/**
	 * @param \OCP\IGroupManager $groupManager
	 * @param \OCP\IUserSession $userSession
	 */
	public function __construct(\OCP\IGroupManager $groupManager,
								\OCP\IUserSession $userSession) {
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
	}

	/**
	 * returns a list of groups
	 *
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function getGroups($parameters) {
		$search = !empty($_GET['search']) ? $_GET['search'] : '';
		$limit = !empty($_GET['limit']) ? $_GET['limit'] : null;
		$offset = !empty($_GET['offset']) ? $_GET['offset'] : null;

		$groups = $this->groupManager->search($search, $limit, $offset);
		$groups = array_map(function($group) {
			/** @var IGroup $group */
			return $group->getGID();
		}, $groups);

		return new OC_OCS_Result(['groups' => $groups]);
	}

	/**
	 * returns an array of users in the group specified
	 *
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function getGroup($parameters) {
		// Check if user is logged in
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new OC_OCS_Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		// Check the group exists
		if(!$this->groupManager->groupExists($parameters['groupid'])) {
			return new OC_OCS_Result(null, \OCP\API::RESPOND_NOT_FOUND, 'The requested group could not be found');
		}
		// Check subadmin has access to this group
		if($this->groupManager->isAdmin($user->getUID())
		   || in_array($parameters['groupid'], \OC_SubAdmin::getSubAdminsGroups($user->getUID()))){
			$users = $this->groupManager->get($parameters['groupid'])->getUsers();
			$users =  array_map(function($user) {
				/** @var IUser $user */
				return $user->getUID();
			}, $users);
			$users = array_values($users);
			return new OC_OCS_Result(['users' => $users]);
		} else {
			return new OC_OCS_Result(null, \OCP\API::RESPOND_UNAUTHORISED, 'User does not have access to specified group');
		}
	}

	/**
	 * creates a new group
	 *
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function addGroup($parameters) {
		// Validate name
		$groupId = isset($_POST['groupid']) ? $_POST['groupid'] : '';
		if( preg_match( '/[^a-zA-Z0-9 _\.@\-]/', $groupId ) || empty($groupId)){
			\OCP\Util::writeLog('provisioning_api', 'Attempt made to create group using invalid characters.', \OCP\Util::ERROR);
			return new OC_OCS_Result(null, 101, 'Invalid group name');
		}
		// Check if it exists
		if($this->groupManager->groupExists($groupId)){
			return new OC_OCS_Result(null, 102);
		}
		$this->groupManager->createGroup($groupId);
		return new OC_OCS_Result(null, 100);
	}

	/**
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function deleteGroup($parameters) {
		// Check it exists
		if(!$this->groupManager->groupExists($parameters['groupid'])){
			return new OC_OCS_Result(null, 101);
		} else if($parameters['groupid'] === 'admin' || !$this->groupManager->get($parameters['groupid'])->delete()){
			// Cannot delete admin group
			return new OC_OCS_Result(null, 102);
		} else {
			return new OC_OCS_Result(null, 100);
		}
	}

	/**
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function getSubAdminsOfGroup($parameters) {
		$group = $parameters['groupid'];
		// Check group exists
		if(!$this->groupManager->groupExists($group)) {
			return new OC_OCS_Result(null, 101, 'Group does not exist');
		}
		// Go
		if(!$subadmins = OC_Subadmin::getGroupsSubAdmins($group)) {
			return new OC_OCS_Result(null, 102, 'Unknown error occured');
		} else {
			return new OC_OCS_Result($subadmins);
		}
	}

}

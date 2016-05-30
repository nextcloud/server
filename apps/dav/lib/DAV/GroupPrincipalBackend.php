<?php
/**
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
namespace OCA\DAV\DAV;

use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use Sabre\DAV\Exception;
use \Sabre\DAV\PropPatch;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;

class GroupPrincipalBackend implements BackendInterface {

	const PRINCIPAL_PREFIX = 'principals/groups';

	/** @var IGroupManager */
	private $groupManager;

	/**
	 * @param IGroupManager $IGroupManager
	 */
	public function __construct(IGroupManager $IGroupManager) {
		$this->groupManager = $IGroupManager;
	}

	/**
	 * Returns a list of principals based on a prefix.
	 *
	 * This prefix will often contain something like 'principals'. You are only
	 * expected to return principals that are in this base path.
	 *
	 * You are expected to return at least a 'uri' for every user, you can
	 * return any additional properties if you wish so. Common properties are:
	 *   {DAV:}displayname
	 *
	 * @param string $prefixPath
	 * @return string[]
	 */
	public function getPrincipalsByPrefix($prefixPath) {
		$principals = [];

		if ($prefixPath === self::PRINCIPAL_PREFIX) {
			foreach($this->groupManager->search('') as $user) {
				$principals[] = $this->groupToPrincipal($user);
			}
		}

		return $principals;
	}

	/**
	 * Returns a specific principal, specified by it's path.
	 * The returned structure should be the exact same as from
	 * getPrincipalsByPrefix.
	 *
	 * @param string $path
	 * @return array
	 */
	public function getPrincipalByPath($path) {
		$elements = explode('/', $path);
		if ($elements[0] !== 'principals') {
			return null;
		}
		if ($elements[1] !== 'groups') {
			return null;
		}
		$name = $elements[2];
		$group = $this->groupManager->get($name);

		if (!is_null($group)) {
			return $this->groupToPrincipal($group);
		}

		return null;
	}

	/**
	 * Returns the list of members for a group-principal
	 *
	 * @param string $principal
	 * @return string[]
	 * @throws Exception
	 */
	public function getGroupMemberSet($principal) {
		$elements = explode('/', $principal);
		if ($elements[0] !== 'principals') {
			return [];
		}
		if ($elements[1] !== 'groups') {
			return [];
		}
		$name = $elements[2];
		$group = $this->groupManager->get($name);

		if (is_null($group)) {
			return [];
		}

		return array_map(function($user) {
			return $this->userToPrincipal($user);
		}, $group->getUsers());
	}

	/**
	 * Returns the list of groups a principal is a member of
	 *
	 * @param string $principal
	 * @return array
	 * @throws Exception
	 */
	public function getGroupMembership($principal) {
		return [];
	}

	/**
	 * Updates the list of group members for a group principal.
	 *
	 * The principals should be passed as a list of uri's.
	 *
	 * @param string $principal
	 * @param string[] $members
	 * @throws Exception
	 */
	public function setGroupMemberSet($principal, array $members) {
		throw new Exception('Setting members of the group is not supported yet');
	}

	/**
	 * @param string $path
	 * @param PropPatch $propPatch
	 * @return int
	 */
	function updatePrincipal($path, PropPatch $propPatch) {
		return 0;
	}

	/**
	 * @param string $prefixPath
	 * @param array $searchProperties
	 * @param string $test
	 * @return array
	 */
	function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof') {
		return [];
	}

	/**
	 * @param string $uri
	 * @param string $principalPrefix
	 * @return string
	 */
	function findByUri($uri, $principalPrefix) {
		return '';
	}

	/**
	 * @param IGroup $group
	 * @return array
	 */
	protected function groupToPrincipal($group) {
		$groupId = $group->getGID();
		$principal = [
			'uri' => "principals/groups/$groupId",
			'{DAV:}displayname' => $groupId,
		];

		return $principal;
	}

	/**
	 * @param IUser $user
	 * @return array
	 */
	protected function userToPrincipal($user) {
		$principal = [
			'uri' => 'principals/users/' . $user->getUID(),
			'{DAV:}displayname' => $user->getDisplayName(),
		];

		return $principal;
	}
}

<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Connector\Sabre;

use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Sabre\DAV\Exception;
use \Sabre\DAV\PropPatch;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;
use Sabre\HTTP\URLUtil;

class Principal implements BackendInterface {

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var string */
	private $principalPrefix;

	/** @var bool */
	private $hasGroups;

	/**
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param string $principalPrefix
	 */
	public function __construct(IUserManager $userManager,
								IGroupManager $groupManager,
								$principalPrefix = 'principals/users/') {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->principalPrefix = trim($principalPrefix, '/');
		$this->hasGroups = ($principalPrefix === 'principals/users/');
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

		if ($prefixPath === $this->principalPrefix) {
			foreach($this->userManager->search('') as $user) {
				$principals[] = $this->userToPrincipal($user);
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
		list($prefix, $name) = URLUtil::splitPath($path);

		if ($prefix === $this->principalPrefix) {
			$user = $this->userManager->get($name);

			if (!is_null($user)) {
				return $this->userToPrincipal($user);
			}
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
		// TODO: for now the group principal has only one member, the user itself
		$principal = $this->getPrincipalByPath($principal);
		if (!$principal) {
			throw new Exception('Principal not found');
		}

		return [$principal['uri']];
	}

	/**
	 * Returns the list of groups a principal is a member of
	 *
	 * @param string $principal
	 * @param bool $needGroups
	 * @return array
	 * @throws Exception
	 */
	public function getGroupMembership($principal, $needGroups = false) {
		list($prefix, $name) = URLUtil::splitPath($principal);

		if ($prefix === $this->principalPrefix) {
			$user = $this->userManager->get($name);
			if (!$user) {
				throw new Exception('Principal not found');
			}

			if ($this->hasGroups || $needGroups) {
				$groups = $this->groupManager->getUserGroups($user);
				$groups = array_map(function($group) {
					/** @var IGroup $group */
					return 'principals/groups/' . $group->getGID();
				}, $groups);

				return $groups;
			}
		}
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
		if (substr($uri, 0, 7) === 'mailto:') {
			$email = substr($uri, 7);
			$users = $this->userManager->getByEmail($email);
			if (count($users) === 1) {
				return $this->principalPrefix . '/' . $users[0]->getUID();
			}
		}

		return '';
	}

	/**
	 * @param IUser $user
	 * @return array
	 */
	protected function userToPrincipal($user) {
		$userId = $user->getUID();
		$displayName = $user->getDisplayName();
		$principal = [
				'uri' => $this->principalPrefix . '/' . $userId,
				'{DAV:}displayname' => is_null($displayName) ? $userId : $displayName,
		];

		$email = $user->getEMailAddress();
		if (!empty($email)) {
			$principal['{http://sabredav.org/ns}email-address'] = $email;
			return $principal;
		}
		return $principal;
	}

	public function getPrincipalPrefix() {
		return $this->principalPrefix;
	}

}

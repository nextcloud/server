<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Sebastian Döll <sebastian.doell@libasys.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\DAV\Connector\Sabre;

use OCP\IUser;
use OCP\IUserManager;
use OCP\IConfig;
use \Sabre\DAV\PropPatch;
use Sabre\HTTP\URLUtil;

class Principal implements \Sabre\DAVACL\PrincipalBackend\BackendInterface {
	/** @var IConfig */
	private $config;
	/** @var IUserManager */
	private $userManager;

	/**
	 * @param IConfig $config
	 * @param IUserManager $userManager
	 */
	public function __construct(IConfig $config,
								IUserManager $userManager) {
		$this->config = $config;
		$this->userManager = $userManager;
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

		if ($prefixPath === 'principals/users') {
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
		$elements = explode('/', $path);
		if ($elements[0] !== 'principals') {
			return null;
		}
		if ($elements[1] !== 'users') {
			return null;
		}
		$name = $elements[2];
		$user = $this->userManager->get($name);

		if (!is_null($user)) {
			return $this->userToPrincipal($user);
		}

		return null;
	}

	/**
	 * Returns the list of members for a group-principal
	 *
	 * @param string $principal
	 * @return string[]
	 * @throws \Sabre\DAV\Exception
	 */
	public function getGroupMemberSet($principal) {
		// TODO: for now the group principal has only one member, the user itself
		$principal = $this->getPrincipalByPath($principal);
		if (!$principal) {
			throw new \Sabre\DAV\Exception('Principal not found');
		}

		return [$principal['uri']];
	}

	/**
	 * Returns the list of groups a principal is a member of
	 *
	 * @param string $principal
	 * @return array
	 * @throws \Sabre\DAV\Exception
	 */
	public function getGroupMembership($principal) {
		list($prefix, $name) = URLUtil::splitPath($principal);

		$group_membership = array();
		if ($prefix === 'principals/users') {
			$principal = $this->getPrincipalByPath($principal);
			if (!$principal) {
				throw new \Sabre\DAV\Exception('Principal not found');
			}

			// TODO: for now the user principal has only its own groups
			return array(
				'principals/users/'.$name.'/calendar-proxy-read',
				'principals/users/'.$name.'/calendar-proxy-write',
				// The addressbook groups are not supported in Sabre,
				// see http://groups.google.com/group/sabredav-discuss/browse_thread/thread/ef2fa9759d55f8c#msg_5720afc11602e753
				//'principals/'.$name.'/addressbook-proxy-read',
				//'principals/'.$name.'/addressbook-proxy-write',
			);
		}
		return $group_membership;
	}

	/**
	 * Updates the list of group members for a group principal.
	 *
	 * The principals should be passed as a list of uri's.
	 *
	 * @param string $principal
	 * @param string[] $members
	 * @throws \Sabre\DAV\Exception
	 */
	public function setGroupMemberSet($principal, array $members) {
		throw new \Sabre\DAV\Exception('Setting members of the group is not supported yet');
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
	 * @param IUser $user
	 * @return array
	 */
	protected function userToPrincipal($user) {
		$userId = $user->getUID();
		$displayName = $user->getDisplayName();
		$principal = [
				'uri' => "principals/users/$userId",
				'{DAV:}displayname' => is_null($displayName) ? $userId : $displayName,
		];

		$email = $user->getEMailAddress();
		if (!empty($email)) {
			$principal['{http://sabredav.org/ns}email-address'] = $email;
			return $principal;
		}
		return $principal;
	}
}

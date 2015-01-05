<?php
/**
 * Copyright (c) 2011 Jakob Sack mail@jakobsack.de
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Connector\Sabre;

use OCP\IUserManager;
use OCP\IConfig;

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

		if ($prefixPath === 'principals') {
			foreach($this->userManager->search('') as $user) {

				$principal = [
					'uri' => 'principals/' . $user->getUID(),
					'{DAV:}displayname' => $user->getUID(),
				];

				$email = $this->config->getUserValue($user->getUID(), 'settings', 'email');
				if(!empty($email)) {
					$principal['{http://sabredav.org/ns}email-address'] = $email;
				}

				$principals[] = $principal;
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
		list($prefix, $name) = explode('/', $path);
		$user = $this->userManager->get($name);

		if ($prefix === 'principals' && !is_null($user)) {
			$principal = [
				'uri' => 'principals/' . $user->getUID(),
				'{DAV:}displayname' => $user->getUID(),
			];

			$email = $this->config->getUserValue($user->getUID(), 'settings', 'email');
			if($email) {
				$principal['{http://sabredav.org/ns}email-address'] = $email;
			}

			return $principal;
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
		list($prefix, $name) = \Sabre\DAV\URLUtil::splitPath($principal);

		$group_membership = array();
		if ($prefix === 'principals') {
			$principal = $this->getPrincipalByPath($principal);
			if (!$principal) {
				throw new \Sabre\DAV\Exception('Principal not found');
			}

			// TODO: for now the user principal has only its own groups
			return array(
				'principals/'.$name.'/calendar-proxy-read',
				'principals/'.$name.'/calendar-proxy-write',
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
	 * @param array $members
	 * @throws \Sabre\DAV\Exception
	 */
	public function setGroupMemberSet($principal, array $members) {
		throw new \Sabre\DAV\Exception('Setting members of the group is not supported yet');
	}

	/**
	 * @param string $path
	 * @param array $mutations
	 * @return int
	 */
	function updatePrincipal($path, $mutations) {
		return 0;
	}

	/**
	 * @param string $prefixPath
	 * @param array $searchProperties
	 * @return array
	 */
	function searchPrincipals($prefixPath, array $searchProperties) {
		return [];
	}
}

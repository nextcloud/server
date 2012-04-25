<?php
/**
 * Copyright (c) 2011 Jakob Sack mail@jakobsack.de
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Connector_Sabre_Principal implements Sabre_DAVACL_IPrincipalBackend {
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
	 * @return array
	 */
	public function getPrincipalsByPrefix( $prefixPath ) {
		$principals = array();

		if ($prefixPath == 'principals') {
			foreach(OC_User::getUsers() as $user) {
				$user_uri = 'principals/'.$user;
				$principals[] = array(
					'uri' => $user_uri,
					'{DAV:}displayname' => $user,
				);
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
		list($prefix,$name) = explode('/', $path);

		if ($prefix == 'principals' && OC_User::userExists($name)) {
			return array(
				'uri' => 'principals/'.$name,
				'{DAV:}displayname' => $name,
			);
		}

		return null;
	}

	/**
	 * Returns the list of members for a group-principal
	 *
	 * @param string $principal
	 * @return array
	 */
	public function getGroupMemberSet($principal) {
		// TODO: for now the group principal has only one member, the user itself
		list($prefix,$name) = Sabre_DAV_URLUtil::splitPath($principal);

		$principal = $this->getPrincipalByPath($prefix);
		if (!$principal) throw new Sabre_DAV_Exception('Principal not found');

		return array(
			$prefix
		);
	}

	/**
	 * Returns the list of groups a principal is a member of
	 *
	 * @param string $principal
	 * @return array
	 */
	public function getGroupMembership($principal) {
		list($prefix,$name) = Sabre_DAV_URLUtil::splitPath($principal);

		$group_membership = array();
		if ($prefix == 'principals') {
			$principal = $this->getPrincipalByPath($principal);
			if (!$principal) throw new Sabre_DAV_Exception('Principal not found');

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
	 * @return void
	 */
	public function setGroupMemberSet($principal, array $members) {
		throw new Sabre_DAV_Exception('Setting members of the group is not supported yet');
	}
	function updatePrincipal($path, $mutations){return 0;}
	function searchPrincipals($prefixPath, array $searchProperties){return 0;}
}

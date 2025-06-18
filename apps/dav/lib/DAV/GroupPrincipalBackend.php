<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\DAV;

use OCP\Constants;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use Sabre\DAV\Exception;
use Sabre\DAV\PropPatch;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;

class GroupPrincipalBackend implements BackendInterface {
	public const PRINCIPAL_PREFIX = 'principals/groups';

	/**
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 * @param IShareManager $shareManager
	 */
	public function __construct(
		private IGroupManager $groupManager,
		private IUserSession $userSession,
		private IShareManager $shareManager,
		private IConfig $config,
	) {
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
			foreach ($this->groupManager->search('') as $group) {
				if (!$group->hideFromCollaboration()) {
					$principals[] = $this->groupToPrincipal($group);
				}
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
		$elements = explode('/', $path, 3);
		if ($elements[0] !== 'principals') {
			return null;
		}
		if ($elements[1] !== 'groups') {
			return null;
		}
		$name = urldecode($elements[2]);
		$group = $this->groupManager->get($name);

		if ($group !== null && !$group->hideFromCollaboration()) {
			return $this->groupToPrincipal($group);
		}

		return null;
	}

	/**
	 * Returns the list of members for a group-principal
	 *
	 * @param string $principal
	 * @return array
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

		return array_map(function ($user) {
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
	public function updatePrincipal($path, PropPatch $propPatch) {
		return 0;
	}

	/**
	 * @param string $prefixPath
	 * @param array $searchProperties
	 * @param string $test
	 * @return array
	 */
	public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof') {
		$results = [];

		if (\count($searchProperties) === 0) {
			return [];
		}
		if ($prefixPath !== self::PRINCIPAL_PREFIX) {
			return [];
		}
		// If sharing or group sharing is disabled, return the empty array
		if (!$this->groupSharingEnabled()) {
			return [];
		}

		// If sharing is restricted to group members only,
		// return only members that have groups in common
		$restrictGroups = false;
		if ($this->shareManager->shareWithGroupMembersOnly()) {
			$user = $this->userSession->getUser();
			if (!$user) {
				return [];
			}

			$restrictGroups = $this->groupManager->getUserGroupIds($user);
		}

		$searchLimit = $this->config->getSystemValueInt('sharing.maxAutocompleteResults', Constants::SHARING_MAX_AUTOCOMPLETE_RESULTS_DEFAULT);
		if ($searchLimit <= 0) {
			$searchLimit = null;
		}
		foreach ($searchProperties as $prop => $value) {
			switch ($prop) {
				case '{DAV:}displayname':
					$groups = $this->groupManager->search($value, $searchLimit);

					$results[] = array_reduce($groups, function (array $carry, IGroup $group) use ($restrictGroups) {
						if ($group->hideFromCollaboration()) {
							return $carry;
						}

						$gid = $group->getGID();
						// is sharing restricted to groups only?
						if ($restrictGroups !== false) {
							if (!\in_array($gid, $restrictGroups, true)) {
								return $carry;
							}
						}

						$carry[] = self::PRINCIPAL_PREFIX . '/' . urlencode($gid);
						return $carry;
					}, []);
					break;

				case '{urn:ietf:params:xml:ns:caldav}calendar-user-address-set':
					// If you add support for more search properties that qualify as a user-address,
					// please also add them to the array below
					$results[] = $this->searchPrincipals(self::PRINCIPAL_PREFIX, [
					], 'anyof');
					break;

				default:
					$results[] = [];
					break;
			}
		}

		// results is an array of arrays, so this is not the first search result
		// but the results of the first searchProperty
		if (count($results) === 1) {
			return $results[0];
		}

		switch ($test) {
			case 'anyof':
				return array_values(array_unique(array_merge(...$results)));

			case 'allof':
			default:
				return array_values(array_intersect(...$results));
		}
	}

	/**
	 * @param string $uri
	 * @param string $principalPrefix
	 * @return string
	 */
	public function findByUri($uri, $principalPrefix) {
		// If sharing is disabled, return the empty array
		if (!$this->groupSharingEnabled()) {
			return null;
		}

		// If sharing is restricted to group members only,
		// return only members that have groups in common
		$restrictGroups = false;
		if ($this->shareManager->shareWithGroupMembersOnly()) {
			$user = $this->userSession->getUser();
			if (!$user) {
				return null;
			}

			$restrictGroups = $this->groupManager->getUserGroupIds($user);
		}

		if (str_starts_with($uri, 'principal:principals/groups/')) {
			$name = urlencode(substr($uri, 28));
			if ($restrictGroups !== false && !\in_array($name, $restrictGroups, true)) {
				return null;
			}

			return substr($uri, 10);
		}

		return null;
	}

	/**
	 * @param IGroup $group
	 * @return array
	 */
	protected function groupToPrincipal($group) {
		$groupId = $group->getGID();
		// getDisplayName returns UID if none
		$displayName = $group->getDisplayName();

		return [
			'uri' => 'principals/groups/' . urlencode($groupId),
			'{DAV:}displayname' => $displayName,
			'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'GROUP',
		];
	}

	/**
	 * @param IUser $user
	 * @return array
	 */
	protected function userToPrincipal($user) {
		$userId = $user->getUID();
		// getDisplayName returns UID if none
		$displayName = $user->getDisplayName();

		$principal = [
			'uri' => 'principals/users/' . $userId,
			'{DAV:}displayname' => $displayName,
			'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => 'INDIVIDUAL',
		];

		$email = $user->getSystemEMailAddress();
		if (!empty($email)) {
			$principal['{http://sabredav.org/ns}email-address'] = $email;
		}

		return $principal;
	}

	/**
	 * @return bool
	 */
	private function groupSharingEnabled(): bool {
		return $this->shareManager->shareApiEnabled() && $this->shareManager->allowGroupSharing();
	}
}

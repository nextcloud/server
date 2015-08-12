<?php
/**
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\Files_Sharing\API;

use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IAppConfig;
use OCP\IUserSession;
use OCP\IURLGenerator;

class Sharees {

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	/** @var \OCP\Contacts\IManager */
	private $contactsManager;

	/** @var IAppConfig */
	private $appConfig;

	/** @var IUserSession */
	private $userSession;

	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param \OCP\Contacts\IManager $contactsManager
	 * @param IAppConfig $appConfig
	 * @param IUserSession $userSession
	 */
	public function __construct(IGroupManager $groupManager,
								IUserManager $userManager,
								\OCP\Contacts\IManager $contactsManager,
								IAppConfig $appConfig,
								IUserSession $userSession,
								IURLGenerator $urlGenerator) {
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->contactsManager = $contactsManager;
		$this->appConfig = $appConfig;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param string $search
	 * @param bool $shareWithGroupOnly
	 *
	 * @return array possible sharees
	 */
	protected function getUsers($search, $shareWithGroupOnly) {
		$sharees = [];
		
		$users = [];
		if ($shareWithGroupOnly) {
			// Search in all the groups this user is part of
			$userGroups = $this->groupManager->getUserGroupIds($this->userSession->getUser());
			foreach ($userGroups as $userGroup) {
				$users = array_merge($users, $this->groupManager->displayNamesInGroup($userGroup, $search));
			}
			$users = array_unique($users);
		} else {
			// Search in all users
			$users_tmp = $this->userManager->searchDisplayName($search);

			// Put in array that maps uid => displayName
			foreach($users_tmp as $user) {
				$users[$user->getUID()] = $user->getDisplayName();
			}
		}


		foreach ($users as $uid => $displayName) {
			$sharees[] = [
				'label' => $displayName,
				'value' => [
					'shareType' => \OCP\Share::SHARE_TYPE_USER,
					'shareWith' => $uid,
				],
			];
		}

		return $sharees;
	}

	/**
	 * @param string $search
	 * @param bool $shareWithGroupOnly
	 *
	 * @return array possible sharees
	 */
	protected function getGroups($search, $shareWithGroupOnly) {
		$sharees = [];
		$groups = $this->groupManager->search($search);
		$groups = array_map(function (IGroup $group) { return $group->getGID(); }, $groups);

		if (!empty($groups) && $shareWithGroupOnly) {
			// Intersect all the groups that match with the groups this user is a member of
			$userGroups = $this->groupManager->getUserGroups($this->userSession->getUser());
			$userGroups = array_map(function (IGroup $group) { return $group->getGID(); }, $userGroups);
			$groups = array_intersect($groups, $userGroups);
		}

		foreach ($groups as $gid) {
			$sharees[] = [
				'label' => $gid,
				'value' => [
					'shareType' => \OCP\Share::SHARE_TYPE_GROUP,
					'shareWith' => $gid,
				],
			];
		}

		return $sharees;
	}

	/**
	 * @param string $search
	 *
	 * @return array possible sharees
	 */
	protected function getRemote($search) {
		$sharees = [];

		if (substr_count($search, '@') >= 1) {
			$sharees[] = [
				'label' => $search,
				'value' => [
					'shareType' => \OCP\Share::SHARE_TYPE_REMOTE,
					'shareWith' => $search,
				],
			];
		}

		// Search in contacts
		$addressBookContacts = $this->contactsManager->search($search, ['CLOUD', 'FN']);
		foreach ($addressBookContacts as $contact) {
			if (isset($contact['CLOUD'])) {
				foreach ($contact['CLOUD'] as $cloudId) {
					$sharees[] = [
						'label' => $contact['FN'] . ' (' . $cloudId . ')',
						'value' => [
							'shareType' => \OCP\Share::SHARE_TYPE_REMOTE,
							'shareWith' => $cloudId
						]
					];
				}
			}
		}

		return $sharees;
	}

	/**
	 * @param array $params
	 * @return \OC_OCS_Result
	 */
	public function search($params) {
		$search = isset($_GET['search']) ? (string)$_GET['search'] : '';
		$itemType = isset($_GET['itemType']) ? (string)$_GET['itemType'] : null;
		$existingShares = isset($_GET['existingShares']) ? (array)$_GET['existingShares'] : [];
		$shareType = isset($_GET['shareType']) ? intval($_GET['shareType']) : null;
		$page = !empty($_GET['page']) ? intval($_GET['page']) : 1;
		$perPage = !empty($_GET['limit']) ? intval($_GET['limit']) : 200;

		$shareWithGroupOnly = $this->appConfig->getValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';

		return $this->searchSharees($search, $itemType, $existingShares, $shareType, $page, $perPage, $shareWithGroupOnly);
	}

	/**
	 * Testable search function that does not need globals
	 *
	 * @param string $search
	 * @param string $itemType
	 * @param array $existingShares
	 * @param int $shareType
	 * @param int $page
	 * @param int $perPage
	 * @param bool $shareWithGroupOnly
	 * @return \OC_OCS_Result
	 */
	protected function searchSharees($search, $itemType, array $existingShares, $shareType, $page, $perPage, $shareWithGroupOnly) {

		$sharedUsers = $sharedGroups = [];
		if (!empty($existingShares)) {
			if (!empty($existingShares[\OCP\Share::SHARE_TYPE_USER]) &&
				is_array($existingShares[\OCP\Share::SHARE_TYPE_USER])) {
				$sharedUsers = $existingShares[\OCP\Share::SHARE_TYPE_USER];
			}

			if (!empty($existingShares[\OCP\Share::SHARE_TYPE_GROUP]) &&
				is_array($existingShares[\OCP\Share::SHARE_TYPE_GROUP])) {
				$sharedGroups = $existingShares[\OCP\Share::SHARE_TYPE_GROUP];
			}
		}

		// Verify arguments
		if ($itemType === null) {
			return new \OC_OCS_Result(null, 400, 'missing itemType');
		}

		$sharees = [];
		// Get users
		if ($shareType === null || $shareType === \OCP\Share::SHARE_TYPE_USER) {
			$potentialSharees = $this->getUsers($search, $shareWithGroupOnly);
			$sharees = array_merge($sharees, $this->filterSharees($potentialSharees, $sharedUsers));
		}

		// Get groups
		if ($shareType === null || $shareType === \OCP\Share::SHARE_TYPE_GROUP) {
			$potentialSharees = $this->getGroups($search, $shareWithGroupOnly);
			$sharees = array_merge($sharees, $this->filterSharees($potentialSharees, $sharedGroups));
		}

		// Get remote
		if (($shareType === null || $shareType === \OCP\Share::SHARE_TYPE_REMOTE) &&
		    \OCP\Share::getBackend($itemType)->isShareTypeAllowed(\OCP\Share::SHARE_TYPE_REMOTE)) {
			$sharees = array_merge($sharees, $this->getRemote($search));
		}


		// Sort sharees
		$sorter = new \OC\Share\SearchResultSorter($search,
			'label',
			\OC::$server->getLogger());
		usort($sharees, array($sorter, 'sort'));

		//Pagination
		$start = ($page - 1) * $perPage;
		$end = $page * $perPage;
		$total = sizeof($sharees);

		$sharees = array_slice($sharees, $start, $perPage);

		$response = new \OC_OCS_Result($sharees);
		$response->setTotalItems($total);
		$response->setItemsPerPage($perPage);

		if ($total > $end) {
			$params = [
				'search' => $search,
				'itemType' => $itemType,
				'existingShares' => $existingShares,
				'page' => $page + 1,
				'limit' => $perPage,
			];
			if ($shareType !== null) {
				$params['shareType'] = $shareType;
			}
			$url = $this->urlGenerator->getAbsoluteURL('/ocs/v1.php/apps/files_sharing/api/v1/sharees') . '?' . http_build_query($params);
			$response->addHeader('Link', '<' . $url . '> rel="next"');
		}

		return $response;
	}

	/**
	 * Filter out already existing shares from a list of potential sharees
	 *
	 * @param array $potentialSharees
	 * @param array $existingSharees
	 * @return array
	 */
	protected function filterSharees($potentialSharees, $existingSharees) {
		$sharees = array_map(function ($sharee) use ($existingSharees) {
			return in_array($sharee['value']['shareWith'], $existingSharees) ? null : $sharee;
		}, $potentialSharees);

		return array_filter($sharees);
	}
}

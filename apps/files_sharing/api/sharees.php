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
	private function getUsers($search, $shareWithGroupOnly) {
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
	private function getGroups($search, $shareWithGroupOnly) {
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
	private function getRemote($search) {
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

	public function search($params) {
		$search = isset($_GET['search']) ? (string)$_GET['search'] : '';
		$item_type = isset($_GET['item_type']) ? (string)$_GET['item_type'] : null;
		$share_type = isset($_GET['share_type']) ? intval($_GET['share_type']) : null;
		$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
		$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 200;

		// Verify arguments
		if ($item_type === null) {
			return new \OC_OCS_Result(null, 400, 'missing item_type');
		}

		$shareWithGroupOnly = $this->appConfig->getValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes' ? true : false;

		$sharees = [];
		// Get users
		if ($share_type === null || $share_type === \OCP\Share::SHARE_TYPE_USER) {
			$sharees = array_merge($sharees, $this->getUsers($search, $shareWithGroupOnly));
		}

		// Get groups
		if ($share_type === null || $share_type === \OCP\Share::SHARE_TYPE_GROUP) {
			$sharees = array_merge($sharees, $this->getGroups($search, $shareWithGroupOnly));
		}

		// Get remote
		if (($share_type === null || $share_type === \OCP\Share::SHARE_TYPE_REMOTE) &&
		    \OCP\Share::getBackend($item_type)->isShareTypeAllowed(\OCP\Share::SHARE_TYPE_REMOTE)) {
			$sharees = array_merge($sharees, $this->getRemote($search));
		}


		// Sort sharees
		$sorter = new \OC\Share\SearchResultSorter($search,
			'label',
			\OC::$server->getLogger());
		usort($sharees, array($sorter, 'sort'));

		//Pagination
		$start = ($page - 1) * $per_page;
		$end = $page * $per_page;
		$tot = count($sharees);

		$sharees = array_slice($sharees, $start, $per_page);
		$response = new \OC_OCS_Result($sharees);

		// FIXME: Do this?
		$response->setTotalItems($tot);
		$response->setItemsPerPage($per_page);

		// TODO add other link rels
		if ($tot > $end) {
			$url = $this->urlGenerator->getAbsoluteURL('/ocs/v1.php/apps/files_sharing/api/v1/sharees?') .
				'search=' . $search .
				'&item_type=' . $item_type .
				'&share_type=' . $share_type .
				'&page=' . ($page + 1) .
				'&per_page=' . $per_page;
			$response->addHeader('Link', '<' . $url . '> rel="next"');
		}

		return $response;
	}
}

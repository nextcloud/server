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

use OCP\Contacts\IManager;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\IURLGenerator;
use OCP\Share;

class Sharees {

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IManager */
	private $contactsManager;

	/** @var IConfig */
	private $config;

	/** @var IUserSession */
	private $userSession;

	/** @var IRequest */
	private $request;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ILogger */
	private $logger;

	/** @var bool */
	private $shareWithGroupOnly;

	/** @var int */
	protected $offset = 0;

	/** @var int */
	protected $limit = 10;

	/** @var array */
	protected $result = [
		'exact' => [
			'users' => [],
			'groups' => [],
			'remotes' => [],
		],
		'users' => [],
		'groups' => [],
		'remotes' => [],
	];

	protected $reachedEndFor = [];

	/**
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param IManager $contactsManager
	 * @param IConfig $config
	 * @param IUserSession $userSession
	 * @param IURLGenerator $urlGenerator
	 * @param IRequest $request
	 * @param ILogger $logger
	 */
	public function __construct(IGroupManager $groupManager,
								IUserManager $userManager,
								IManager $contactsManager,
								IConfig $config,
								IUserSession $userSession,
								IURLGenerator $urlGenerator,
								IRequest $request,
								ILogger $logger) {
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->contactsManager = $contactsManager;
		$this->config = $config;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
	}

	/**
	 * @param string $search
	 */
	protected function getUsers($search) {
		$this->result['users'] = $this->result['exact']['users'] = $users = [];

		if ($this->shareWithGroupOnly) {
			// Search in all the groups this user is part of
			$userGroups = $this->groupManager->getUserGroupIds($this->userSession->getUser());
			foreach ($userGroups as $userGroup) {
				$users = $this->groupManager->displayNamesInGroup($userGroup, $search, $this->limit, $this->offset);
				foreach ($users as $uid => $userDisplayName) {
					$users[$uid] = $userDisplayName;
				}
			}
		} else {
			// Search in all users
			$usersTmp = $this->userManager->searchDisplayName($search, $this->limit, $this->offset);

			foreach ($usersTmp as $user) {
				$users[$user->getUID()] = $user->getDisplayName();
			}
		}

		if (sizeof($users) < $this->limit) {
			$this->reachedEndFor[] = 'users';
		}

		$foundUserById = false;
		foreach ($users as $uid => $userDisplayName) {
			if ($uid === $search || $userDisplayName === $search) {
				if ($uid === $search) {
					$foundUserById = true;
				}
				$this->result['exact']['users'][] = [
					'shareWith' => $search,
					'label' => $search,
				];
			} else {
				$this->result['users'][] = [
					'shareWith' => $uid,
					'label' => $userDisplayName,
				];
			}
		}

		if ($this->offset === 0 && !$foundUserById) {
			// On page one we try if the search result has a direct hit on the
			// user id and if so, we add that to the exact match list
			$user = $this->userManager->get($search);
			if ($user instanceof IUser) {
				array_push($this->result['exact']['users'], [
					'shareWith' => $user->getUID(),
					'label' => $user->getDisplayName(),
				]);
			}
		}
	}

	/**
	 * @param string $search
	 */
	protected function getGroups($search) {
		$this->result['groups'] = $this->result['exact']['groups'] = [];

		$groups = $this->groupManager->search($search, $this->limit, $this->offset);
		$groups = array_map(function (IGroup $group) { return $group->getGID(); }, $groups);

		if (sizeof($groups) < $this->limit) {
			$this->reachedEndFor[] = 'groups';
		}

		$userGroups =  [];
		if (!empty($groups) && $this->shareWithGroupOnly) {
			// Intersect all the groups that match with the groups this user is a member of
			$userGroups = $this->groupManager->getUserGroups($this->userSession->getUser());
			$userGroups = array_map(function (IGroup $group) { return $group->getGID(); }, $userGroups);
			$groups = array_intersect($groups, $userGroups);
		}

		foreach ($groups as $gid) {
			if ($gid === $search) {
				$this->result['exact']['groups'][] = [
					'shareWith' => $search,
					'label' => $search,
				];
			} else {
				$this->result['groups'][] = [
					'shareWith' => $gid,
					'label' => $gid,
				];
			}
		}

		if ($this->offset === 0 && empty($this->result['exact']['groups'])) {
			// On page one we try if the search result has a direct hit on the
			// user id and if so, we add that to the exact match list
			$group = $this->groupManager->get($search);
			if ($group instanceof IGroup && (!$this->shareWithGroupOnly || array_intersect([$group], $userGroups))) {
				array_push($this->result['exact']['users'], [
					'shareWith' => $group->getGID(),
					'label' => $group->getGID(),
				]);
			}
		}
	}

	/**
	 * @param string $search
	 * @return array possible sharees
	 */
	protected function getRemote($search) {
		$this->result['remotes'] = [];

		if (substr_count($search, '@') >= 1 && $this->offset === 0) {
			$this->result['exact']['remotes'][] = [
				'shareWith' => $search,
				'label' => $search,
			];
		}

		// Search in contacts
		//@todo Pagination missing
		$addressBookContacts = $this->contactsManager->search($search, ['CLOUD', 'FN']);
		foreach ($addressBookContacts as $contact) {
			if (isset($contact['CLOUD'])) {
				foreach ($contact['CLOUD'] as $cloudId) {
					if ($contact['FN'] === $search || $cloudId === $search) {
						$this->result['exact']['remotes'][] = [
							'shareWith' => $cloudId,
							'label' => $contact['FN'],
						];
					} else {
						$this->result['remotes'][] = [
							'shareWith' => $cloudId,
							'label' => $contact['FN'],
						];
					}
				}
			}
		}

		$this->reachedEndFor[] = 'remotes';
	}

	/**
	 * @return \OC_OCS_Result
	 */
	public function search() {
		$search = isset($_GET['search']) ? (string) $_GET['search'] : '';
		$itemType = isset($_GET['itemType']) ? (string) $_GET['itemType'] : null;
		$shareIds = isset($_GET['existingShares']) ? (array) $_GET['existingShares'] : [];
		$page = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
		$perPage = !empty($_GET['limit']) ? max(1, (int) $_GET['limit']) : 200;

		$shareTypes = [
			Share::SHARE_TYPE_USER,
			Share::SHARE_TYPE_GROUP,
			Share::SHARE_TYPE_REMOTE,
		];
		if (isset($_GET['shareType']) && is_array($_GET['shareType'])) {
			$shareTypes = array_intersect($shareTypes, $_GET['shareType']);
			sort($shareTypes);

		} else if (isset($_GET['shareType']) && is_numeric($_GET['shareType'])) {
			$shareTypes = array_intersect($shareTypes, [(int) $_GET['shareType']]);
			sort($shareTypes);
		}

		if (in_array(Share::SHARE_TYPE_REMOTE, $shareTypes) && !$this->isRemoteSharingAllowed($itemType)) {
			// Remove remote shares from type array, because it is not allowed.
			$shareTypes = array_diff($shareTypes, [Share::SHARE_TYPE_REMOTE]);
		}

		$this->shareWithGroupOnly = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';
		$this->limit = (int) $perPage;
		$this->offset = $perPage * ($page - 1);

		return $this->searchSharees($search, $itemType, $shareIds, $shareTypes, $page, $perPage);
	}

	/**
	 * Method to get out the static call for better testing
	 *
	 * @param string $itemType
	 * @return bool
	 */
	protected function isRemoteSharingAllowed($itemType) {
		try {
			$backend = Share::getBackend($itemType);
			return $backend->isShareTypeAllowed(Share::SHARE_TYPE_REMOTE);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Testable search function that does not need globals
	 *
	 * @param string $search
	 * @param string $itemType
	 * @param array $shareIds
	 * @param array $shareTypes
	 * @param int $page
	 * @param int $perPage
	 * @return \OC_OCS_Result
	 */
	protected function searchSharees($search, $itemType, array $shareIds, array $shareTypes, $page, $perPage) {
		// Verify arguments
		if ($itemType === null) {
			return new \OC_OCS_Result(null, 400, 'missing itemType');
		}

		// Get users
		if (in_array(Share::SHARE_TYPE_USER, $shareTypes)) {
			$this->getUsers($search);
		}

		// Get groups
		if (in_array(Share::SHARE_TYPE_GROUP, $shareTypes)) {
			$this->getGroups($search);
		}

		// Get remote
		if (in_array(Share::SHARE_TYPE_REMOTE, $shareTypes)) {
			$this->getRemote($search);
		}

		$response = new \OC_OCS_Result($this->result);
		$response->setItemsPerPage($perPage);

		if (sizeof($this->reachedEndFor) < 3) {
			$response->addHeader('Link', $this->getPaginationLink($page, [
				'search' => $search,
				'itemType' => $itemType,
				'existingShares' => $shareIds,
				'shareType' => $shareTypes,
				'limit' => $perPage,
			]));
		}

		return $response;
	}

	/**
	 * Generates a bunch of pagination links for the current page
	 *
	 * @param int $page Current page
	 * @param array $params Parameters for the URL
	 * @return string
	 */
	protected function getPaginationLink($page, array $params) {
		if ($this->isV2()) {
			$url = $this->urlGenerator->getAbsoluteURL('/ocs/v2.php/apps/files_sharing/api/v1/sharees') . '?';
		} else {
			$url = $this->urlGenerator->getAbsoluteURL('/ocs/v1.php/apps/files_sharing/api/v1/sharees') . '?';
		}
		$params['page'] = $page + 1;
		$link = '<' . $url . http_build_query($params) . '>; rel="next"';

		return $link;
	}

	/**
	 * @return bool
	 */
	protected function isV2() {
		return $this->request->getScriptName() === '/ocs/v2.php';
	}
}

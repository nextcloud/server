<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\Files_Sharing\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCSController;
use OCP\Contacts\IManager;
use OCP\Http\Client\IClientService;
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

class ShareesAPIController extends OCSController {

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IUserManager */
	protected $userManager;

	/** @var IManager */
	protected $contactsManager;

	/** @var IConfig */
	protected $config;

	/** @var IUserSession */
	protected $userSession;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var ILogger */
	protected $logger;

	/** @var \OCP\Share\IManager */
	protected $shareManager;

	/** @var IClientService */
	protected $clientService;

	/** @var bool */
	protected $shareWithGroupOnly = false;

	/** @var bool */
	protected $shareeEnumeration = true;

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
			'emails' => [],
		],
		'users' => [],
		'groups' => [],
		'remotes' => [],
		'emails' => [],
		'lookup' => [],
	];

	protected $reachedEndFor = [];

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param IManager $contactsManager
	 * @param IConfig $config
	 * @param IUserSession $userSession
	 * @param IURLGenerator $urlGenerator
	 * @param ILogger $logger
	 * @param \OCP\Share\IManager $shareManager
	 * @param IClientService $clientService
	 */
	public function __construct($appName,
								IRequest $request,
								IGroupManager $groupManager,
								IUserManager $userManager,
								IManager $contactsManager,
								IConfig $config,
								IUserSession $userSession,
								IURLGenerator $urlGenerator,
								ILogger $logger,
								\OCP\Share\IManager $shareManager,
								IClientService $clientService) {
		parent::__construct($appName, $request);

		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->contactsManager = $contactsManager;
		$this->config = $config;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->shareManager = $shareManager;
		$this->clientService = $clientService;
	}

	/**
	 * @param string $search
	 */
	protected function getUsers($search) {
		$this->result['users'] = $this->result['exact']['users'] = $users = [];

		$userGroups = [];
		if ($this->shareWithGroupOnly) {
			// Search in all the groups this user is part of
			$userGroups = $this->groupManager->getUserGroupIds($this->userSession->getUser());
			foreach ($userGroups as $userGroup) {
				$usersTmp = $this->groupManager->displayNamesInGroup($userGroup, $search, $this->limit, $this->offset);
				foreach ($usersTmp as $uid => $userDisplayName) {
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

		if (!$this->shareeEnumeration || sizeof($users) < $this->limit) {
			$this->reachedEndFor[] = 'users';
		}

		$foundUserById = false;
		foreach ($users as $uid => $userDisplayName) {
			if (strtolower($uid) === strtolower($search) || strtolower($userDisplayName) === strtolower($search)) {
				if (strtolower($uid) === strtolower($search)) {
					$foundUserById = true;
				}
				$this->result['exact']['users'][] = [
					'label' => $userDisplayName,
					'value' => [
						'shareType' => Share::SHARE_TYPE_USER,
						'shareWith' => $uid,
					],
				];
			} else {
				$this->result['users'][] = [
					'label' => $userDisplayName,
					'value' => [
						'shareType' => Share::SHARE_TYPE_USER,
						'shareWith' => $uid,
					],
				];
			}
		}

		if ($this->offset === 0 && !$foundUserById) {
			// On page one we try if the search result has a direct hit on the
			// user id and if so, we add that to the exact match list
			$user = $this->userManager->get($search);
			if ($user instanceof IUser) {
				$addUser = true;

				if ($this->shareWithGroupOnly) {
					// Only add, if we have a common group
					$commonGroups = array_intersect($userGroups, $this->groupManager->getUserGroupIds($user));
					$addUser = !empty($commonGroups);
				}

				if ($addUser) {
					array_push($this->result['exact']['users'], [
						'label' => $user->getDisplayName(),
						'value' => [
							'shareType' => Share::SHARE_TYPE_USER,
							'shareWith' => $user->getUID(),
						],
					]);
				}
			}
		}

		if (!$this->shareeEnumeration) {
			$this->result['users'] = [];
		}
	}

	/**
	 * @param string $search
	 */
	protected function getGroups($search) {
		$this->result['groups'] = $this->result['exact']['groups'] = [];

		$groups = $this->groupManager->search($search, $this->limit, $this->offset);
		$groups = array_map(function (IGroup $group) { return $group->getGID(); }, $groups);

		if (!$this->shareeEnumeration || sizeof($groups) < $this->limit) {
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
			if (strtolower($gid) === strtolower($search)) {
				$this->result['exact']['groups'][] = [
					'label' => $gid,
					'value' => [
						'shareType' => Share::SHARE_TYPE_GROUP,
						'shareWith' => $gid,
					],
				];
			} else {
				$this->result['groups'][] = [
					'label' => $gid,
					'value' => [
						'shareType' => Share::SHARE_TYPE_GROUP,
						'shareWith' => $gid,
					],
				];
			}
		}

		if ($this->offset === 0 && empty($this->result['exact']['groups'])) {
			// On page one we try if the search result has a direct hit on the
			// user id and if so, we add that to the exact match list
			$group = $this->groupManager->get($search);
			if ($group instanceof IGroup && (!$this->shareWithGroupOnly || in_array($group->getGID(), $userGroups))) {
				array_push($this->result['exact']['groups'], [
					'label' => $group->getGID(),
					'value' => [
						'shareType' => Share::SHARE_TYPE_GROUP,
						'shareWith' => $group->getGID(),
					],
				]);
			}
		}

		if (!$this->shareeEnumeration) {
			$this->result['groups'] = [];
		}
	}

	/**
	 * @param string $search
	 * @return array
	 */
	protected function getRemote($search) {
		$result = ['results' => [], 'exact' => []];

		// Search in contacts
		//@todo Pagination missing
		$addressBookContacts = $this->contactsManager->search($search, ['CLOUD', 'FN']);
		$result['exactIdMatch'] = false;
		foreach ($addressBookContacts as $contact) {
			if (isset($contact['isLocalSystemBook'])) {
				continue;
			}
			if (isset($contact['CLOUD'])) {
				$cloudIds = $contact['CLOUD'];
				if (!is_array($cloudIds)) {
					$cloudIds = [$cloudIds];
				}
				foreach ($cloudIds as $cloudId) {
					list(, $serverUrl) = $this->splitUserRemote($cloudId);
					if (strtolower($contact['FN']) === strtolower($search) || strtolower($cloudId) === strtolower($search)) {
						if (strtolower($cloudId) === strtolower($search)) {
							$result['exactIdMatch'] = true;
						}
						$result['exact'][] = [
							'label' => $contact['FN'] . " ($cloudId)",
							'value' => [
								'shareType' => Share::SHARE_TYPE_REMOTE,
								'shareWith' => $cloudId,
								'server' => $serverUrl,
							],
						];
					} else {
						$result['results'][] = [
							'label' => $contact['FN'] . " ($cloudId)",
							'value' => [
								'shareType' => Share::SHARE_TYPE_REMOTE,
								'shareWith' => $cloudId,
								'server' => $serverUrl,
							],
						];
					}
				}
			}
		}

		if (!$this->shareeEnumeration) {
			$result['results'] = [];
		}

		if (!$result['exactIdMatch'] && substr_count($search, '@') >= 1 && $this->offset === 0) {
			$result['exact'][] = [
				'label' => $search,
				'value' => [
					'shareType' => Share::SHARE_TYPE_REMOTE,
					'shareWith' => $search,
				],
			];
		}

		$this->reachedEndFor[] = 'remotes';

		return $result;
	}

	/**
	 * split user and remote from federated cloud id
	 *
	 * @param string $address federated share address
	 * @return array [user, remoteURL]
	 * @throws \Exception
	 */
	public function splitUserRemote($address) {
		if (strpos($address, '@') === false) {
			throw new \Exception('Invalid Federated Cloud ID');
		}

		// Find the first character that is not allowed in user names
		$id = str_replace('\\', '/', $address);
		$posSlash = strpos($id, '/');
		$posColon = strpos($id, ':');

		if ($posSlash === false && $posColon === false) {
			$invalidPos = strlen($id);
		} else if ($posSlash === false) {
			$invalidPos = $posColon;
		} else if ($posColon === false) {
			$invalidPos = $posSlash;
		} else {
			$invalidPos = min($posSlash, $posColon);
		}

		// Find the last @ before $invalidPos
		$pos = $lastAtPos = 0;
		while ($lastAtPos !== false && $lastAtPos <= $invalidPos) {
			$pos = $lastAtPos;
			$lastAtPos = strpos($id, '@', $pos + 1);
		}

		if ($pos !== false) {
			$user = substr($id, 0, $pos);
			$remote = substr($id, $pos + 1);
			$remote = $this->fixRemoteURL($remote);
			if (!empty($user) && !empty($remote)) {
				return array($user, $remote);
			}
		}

		throw new \Exception('Invalid Federated Cloud ID');
	}

	/**
	 * Strips away a potential file names and trailing slashes:
	 * - http://localhost
	 * - http://localhost/
	 * - http://localhost/index.php
	 * - http://localhost/index.php/s/{shareToken}
	 *
	 * all return: http://localhost
	 *
	 * @param string $remote
	 * @return string
	 */
	protected function fixRemoteURL($remote) {
		$remote = str_replace('\\', '/', $remote);
		if ($fileNamePosition = strpos($remote, '/index.php')) {
			$remote = substr($remote, 0, $fileNamePosition);
		}
		$remote = rtrim($remote, '/');

		return $remote;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $search
	 * @param string $itemType
	 * @param int $page
	 * @param int $perPage
	 * @param int|int[] $shareType
	 * @param bool $lookup
	 * @return Http\DataResponse
	 * @throws OCSBadRequestException
	 */
	public function search($search = '', $itemType = null, $page = 1, $perPage = 200, $shareType = null, $lookup = true) {
		if ($perPage <= 0) {
			throw new OCSBadRequestException('Invalid perPage argument');
		}
		if ($page <= 0) {
			throw new OCSBadRequestException('Invalid page');
		}

		$shareTypes = [
			Share::SHARE_TYPE_USER,
		];

		if ($itemType === 'file' || $itemType === 'folder') {
			if ($this->shareManager->allowGroupSharing()) {
				$shareTypes[] = Share::SHARE_TYPE_GROUP;
			}

			if ($this->isRemoteSharingAllowed($itemType)) {
				$shareTypes[] = Share::SHARE_TYPE_REMOTE;
			}

			if ($this->shareManager->shareProviderExists(Share::SHARE_TYPE_EMAIL)) {
				$shareTypes[] = Share::SHARE_TYPE_EMAIL;
			}
		} else {
			$shareTypes[] = Share::SHARE_TYPE_GROUP;
			$shareTypes[] = Share::SHARE_TYPE_EMAIL;
		}

		if (isset($_GET['shareType']) && is_array($_GET['shareType'])) {
			$shareTypes = array_intersect($shareTypes, $_GET['shareType']);
			sort($shareTypes);
		} else if (is_numeric($shareType)) {
			$shareTypes = array_intersect($shareTypes, [(int) $shareType]);
			sort($shareTypes);
		}

		$this->shareWithGroupOnly = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';
		$this->shareeEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		$this->limit = (int) $perPage;
		$this->offset = $perPage * ($page - 1);

		return $this->searchSharees($search, $itemType, $shareTypes, $page, $perPage, $lookup);
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
	 * @param array $shareTypes
	 * @param int $page
	 * @param int $perPage
	 * @param bool $lookup
	 * @return Http\DataResponse
	 * @throws OCSBadRequestException
	 */
	protected function searchSharees($search, $itemType, array $shareTypes, $page, $perPage, $lookup) {
		// Verify arguments
		if ($itemType === null) {
			throw new OCSBadRequestException('Missing itemType');
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
		$remoteResults = ['results' => [], 'exact' => [], 'exactIdMatch' => false];
		if (in_array(Share::SHARE_TYPE_REMOTE, $shareTypes)) {
			$remoteResults = $this->getRemote($search);
		}

		// Get emails
		$mailResults = ['results' => [], 'exact' => [], 'exactIdMatch' => false];
		if (in_array(Share::SHARE_TYPE_EMAIL, $shareTypes)) {
			$mailResults = $this->getEmail($search);
		}

		// Get from lookup server
		if ($lookup) {
			$this->getLookup($search);
		}

		// if we have a exact match, either for the federated cloud id or for the
		// email address we only return the exact match. It is highly unlikely
		// that the exact same email address and federated cloud id exists
		if ($mailResults['exactIdMatch'] && !$remoteResults['exactIdMatch']) {
			$this->result['emails'] = $mailResults['results'];
			$this->result['exact']['emails'] = $mailResults['exact'];
		} else if (!$mailResults['exactIdMatch'] && $remoteResults['exactIdMatch']) {
			$this->result['remotes'] = $remoteResults['results'];
			$this->result['exact']['remotes'] = $remoteResults['exact'];
		} else {
			$this->result['remotes'] = $remoteResults['results'];
			$this->result['exact']['remotes'] = $remoteResults['exact'];
			$this->result['emails'] = $mailResults['results'];
			$this->result['exact']['emails'] = $mailResults['exact'];
		}

		$response = new Http\DataResponse($this->result);

		if (sizeof($this->reachedEndFor) < 3) {
			$response->addHeader('Link', $this->getPaginationLink($page, [
				'search' => $search,
				'itemType' => $itemType,
				'shareType' => $shareTypes,
				'perPage' => $perPage,
			]));
		}

		return $response;
	}

	/**
	 * @param string $search
	 * @return array
	 */
	protected function getEmail($search) {
		$result = ['results' => [], 'exact' => []];

		// Search in contacts
		//@todo Pagination missing
		$addressBookContacts = $this->contactsManager->search($search, ['EMAIL', 'FN']);
		$result['exactIdMatch'] = false;
		foreach ($addressBookContacts as $contact) {
			if (isset($contact['isLocalSystemBook'])) {
				continue;
			}
			if (isset($contact['EMAIL'])) {
				$emailAddresses = $contact['EMAIL'];
				if (!is_array($emailAddresses)) {
					$emailAddresses = [$emailAddresses];
				}
				foreach ($emailAddresses as $emailAddress) {
					if (strtolower($contact['FN']) === strtolower($search) || strtolower($emailAddress) === strtolower($search)) {
						if (strtolower($emailAddress) === strtolower($search)) {
							$result['exactIdMatch'] = true;
						}
						$result['exact'][] = [
							'label' => $contact['FN'] . " ($emailAddress)",
							'value' => [
								'shareType' => Share::SHARE_TYPE_EMAIL,
								'shareWith' => $emailAddress,
							],
						];
					} else {
						$result['results'][] = [
							'label' => $contact['FN'] . " ($emailAddress)",
							'value' => [
								'shareType' => Share::SHARE_TYPE_EMAIL,
								'shareWith' => $emailAddress,
							],
						];
					}
				}
			}
		}

		if (!$this->shareeEnumeration) {
			$result['results'] = [];
		}

		if (!$result['exactIdMatch'] && filter_var($search, FILTER_VALIDATE_EMAIL)) {
			$result['exact'][] = [
				'label' => $search,
				'value' => [
					'shareType' => Share::SHARE_TYPE_EMAIL,
					'shareWith' => $search,
				],
			];
		}

		$this->reachedEndFor[] = 'emails';

		return $result;
	}

	protected function getLookup($search) {
		$isEnabled = $this->config->getAppValue('files_sharing', 'lookupServerEnabled', 'no');
		$result = [];

		if($isEnabled === 'yes') {
			try {
				$client = $this->clientService->newClient();
				$response = $client->get(
					'https://lookup.nextcloud.com/users?search=' . urlencode($search),
					[
						'timeout' => 10,
						'connect_timeout' => 3,
					]
				);

				$body = json_decode($response->getBody(), true);

				$result = [];
				foreach ($body as $lookup) {
					$result[] = [
						'label' => $lookup['federationId'],
						'value' => [
							'shareType' => Share::SHARE_TYPE_REMOTE,
							'shareWith' => $lookup['federationId'],
						],
						'extra' => $lookup,
					];
				}
			} catch (\Exception $e) {}
		}

		$this->result['lookup'] = $result;
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

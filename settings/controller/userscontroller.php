<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Settings\Controller;

use OC\AppFramework\Http;
use OC\User\User;
use \OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

/**
 * @package OC\Settings\Controller
 */
class UsersController extends Controller {
	/** @var IL10N */
	private $l10n;
	/** @var IUserSession */
	private $userSession;
	/** @var bool */
	private $isAdmin;
	/** @var IUserManager */
	private $userManager;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IConfig */
	private $config;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 * @param IConfig $config
	 * @param bool $isAdmin
	 * @param IL10N $l10n
	 */
	public function __construct($appName,
								IRequest $request,
								IUserManager $userManager,
								IGroupManager $groupManager,
								IUserSession $userSession,
								IConfig $config,
								$isAdmin,
								IL10N $l10n) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->config = $config;
		$this->isAdmin = $isAdmin;
		$this->l10n = $l10n;
	}

	/**
	 * @param IUser $user
	 * @param array $userGroups
	 * @return array
	 */
	private function formatUserForIndex(IUser $user, array $userGroups = null) {
		return array(
			'name' => $user->getUID(),
			'displayname' => $user->getDisplayName(),
			'groups' => (empty($userGroups)) ? $this->groupManager->getUserGroupIds($user) : $userGroups,
			'subadmin' => \OC_SubAdmin::getSubAdminsGroups($user->getUID()),
			'quota' => $this->config->getUserValue($user->getUID(), 'files', 'quota', 'default'),
			'storageLocation' => $user->getHome(),
			'lastLogin' => $user->getLastLogin(),
			'backend' => $user->getBackendClassName()
		);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $offset
	 * @param int $limit
	 * @param string $gid
	 * @param string $pattern
	 * @return DataResponse
	 *
	 * TODO: Tidy up and write unit tests - code is mainly static method calls
	 */
	public function index($offset = 0, $limit = 10, $gid = '', $pattern = '') {
		// FIXME: The JS sends the group '_everyone' instead of no GID for the "all users" group.
		if($gid === '_everyone') {
			$gid = '';
		}
		$users = array();
		if ($this->isAdmin) {
			if($gid !== '') {
				$batch = $this->groupManager->displayNamesInGroup($gid, $pattern, $limit, $offset);
			} else {
				// FIXME: Remove static method call
				$batch = \OC_User::getDisplayNames($pattern, $limit, $offset);
			}

			foreach ($batch as $uid => $displayname) {
				$users[] = $this->formatUserForIndex($this->userManager->get($uid));
			}
		} else {
			$groups = \OC_SubAdmin::getSubAdminsGroups($this->userSession->getUser()->getUID());
			if($gid !== '' && in_array($gid, $groups)) {
				$groups = array($gid);
			} elseif($gid !== '') {
				//don't you try to investigate loops you must not know about
				$groups = array();
			}
			$batch = \OC_Group::usersInGroups($groups, $pattern, $limit, $offset);
			foreach ($batch as $uid) {
				$user = $this->userManager->get($uid);

				// Only add the groups, this user is a subadmin of
				$userGroups = array_intersect($this->groupManager->getUserGroupIds($user),
					\OC_SubAdmin::getSubAdminsGroups($this->userSession->getUser()->getUID()));
				$users[] = $this->formatUserForIndex($user, $userGroups);
			}
		}

		return new DataResponse($users);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $username
	 * @param string $password
	 * @param array $groups
	 * @return DataResponse
	 *
	 * TODO: Tidy up and write unit tests - code is mainly static method calls
	 */
	public function create($username, $password, array $groups) {

		if (!$this->isAdmin) {
			if (!empty($groups)) {
				foreach ($groups as $key => $group) {
					if (!\OC_SubAdmin::isGroupAccessible($this->userSession->getUser()->getUID(), $group)) {
						unset($groups[$key]);
					}
				}
			}
			if (empty($groups)) {
				$groups = \OC_SubAdmin::getSubAdminsGroups($this->userSession->getUser()->getUID());
			}
		}

		try {
			$user = $this->userManager->createUser($username, $password);
		} catch (\Exception $exception) {
			return new DataResponse(
				array(
					'message' => (string)$this->l10n->t('Unable to create user.')
				),
				Http::STATUS_FORBIDDEN
			);
		}

		if($user instanceof User) {
			foreach( $groups as $groupName ) {
				$group = $this->groupManager->get($groupName);

				if(empty($group)) {
					$group = $this->groupManager->createGroup($groupName);
				}
				$group->addUser($user);
			}
		}

		return new DataResponse(
			array(
				'username' => $username,
				'groups' => $this->groupManager->getUserGroupIds($user),
				'storageLocation' => $user->getHome()
			),
			Http::STATUS_CREATED
		);

	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $id
	 * @return DataResponse
	 *
	 * TODO: Tidy up and write unit tests - code is mainly static method calls
	 */
	public function destroy($id) {
		if($this->userSession->getUser()->getUID() === $id) {
			return new DataResponse(
				array(
					'status' => 'error',
					'data' => array(
						'message' => (string)$this->l10n->t('Unable to delete user.')
					)
				),
				Http::STATUS_FORBIDDEN
			);
		}

		// FIXME: Remove this static function call at some point…
		if(!$this->isAdmin && !\OC_SubAdmin::isUserAccessible($this->userSession->getUser()->getUID(), $id)) {
			return new DataResponse(
				array(
					'status' => 'error',
					'data' => array(
						'message' => (string)$this->l10n->t('Authentication error')
					)
				),
				Http::STATUS_FORBIDDEN
			);
		}

		$user = $this->userManager->get($id);
		if($user) {
			if($user->delete()) {
				return new DataResponse(
					array(
						'status' => 'success',
						'data' => array(
							'username' => $id
						)
					),
					Http::STATUS_NO_CONTENT
				);
			}
		}

		return new DataResponse(
			array(
				'status' => 'error',
				'data' => array(
					'message' => (string)$this->l10n->t('Unable to delete user.')
				)
			),
			Http::STATUS_FORBIDDEN
		);
	}

}

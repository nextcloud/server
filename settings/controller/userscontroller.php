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
use OC\User\Manager;
use OC\User\User;
use \OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
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
	/** @var ILogger */
	private $log;
	/** @var \OC_Defaults */
	private $defaults;
	/** @var \OC_Mail */
	private $mail;
	/** @var string */
	private $fromMailAddress;
	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 * @param IConfig $config
	 * @param bool $isAdmin
	 * @param IL10N $l10n
	 * @param ILogger $log
	 * @param \OC_Defaults $defaults
	 * @param \OC_Mail $mail
	 * @param string $fromMailAddress
	 */
	public function __construct($appName,
								IRequest $request,
								IUserManager $userManager,
								IGroupManager $groupManager,
								IUserSession $userSession,
								IConfig $config,
								$isAdmin,
								IL10N $l10n,
								ILogger $log,
								\OC_Defaults $defaults,
								\OC_Mail $mail,
								$fromMailAddress,
								IURLGenerator $urlGenerator) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->config = $config;
		$this->isAdmin = $isAdmin;
		$this->l10n = $l10n;
		$this->log = $log;
		$this->defaults = $defaults;
		$this->mail = $mail;
		$this->fromMailAddress = $fromMailAddress;
		$this->urlGenerator = $urlGenerator;
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
			'backend' => $user->getBackendClassName(),
			'email' => $this->config->getUserValue($user->getUID(), 'settings', 'email', '')
		);
	}

	/**
	 * @param array $userIDs Array with schema [$uid => $displayName]
	 * @return IUser[]
	 */
	private function getUsersForUID(array $userIDs) {
		$users = [];
		foreach ($userIDs as $uid => $displayName) {
			$users[] = $this->userManager->get($uid);
		}
		return $users;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $offset
	 * @param int $limit
	 * @param string $gid GID to filter for
	 * @param string $pattern Pattern to search for in the username
	 * @param string $backend Backend to filter for (class-name)
	 * @return DataResponse
	 *
	 * TODO: Tidy up and write unit tests - code is mainly static method calls
	 */
	public function index($offset = 0, $limit = 10, $gid = '', $pattern = '', $backend = '') {
		// FIXME: The JS sends the group '_everyone' instead of no GID for the "all users" group.
		if($gid === '_everyone') {
			$gid = '';
		}

		// Remove backends
		if(!empty($backend)) {
			$activeBackends = $this->userManager->getBackends();
			$this->userManager->clearBackends();
			foreach($activeBackends as $singleActiveBackend) {
				if($backend === get_class($singleActiveBackend)) {
					$this->userManager->registerBackend($singleActiveBackend);
					break;
				}
			}
		}

		$users = array();
		if ($this->isAdmin) {

			if($gid !== '') {
				$batch = $this->getUsersForUID($this->groupManager->displayNamesInGroup($gid, $pattern, $limit, $offset));
			} else {
				$batch = $this->userManager->search('', $limit, $offset);
			}

			foreach ($batch as $user) {
				$users[] = $this->formatUserForIndex($user);
			}

		} else {
			// Set the $gid parameter to an empty value if the subadmin has no rights to access a specific group
			if($gid !== '' && !in_array($gid, \OC_SubAdmin::getSubAdminsGroups($this->userSession->getUser()->getUID()))) {
				$gid = '';
			}

			$batch = $this->getUsersForUID($this->groupManager->displayNamesInGroup($gid, $pattern, $limit, $offset));
			foreach ($batch as $user) {
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
	 * @param string $email
	 * @return DataResponse
	 *
	 * TODO: Tidy up and write unit tests - code is mainly static method calls
	 */
	public function create($username, $password, array $groups=array(), $email='') {

		if($email !== '' && !$this->mail->validateAddress($email)) {
			return new DataResponse(
				array(
					'message' => (string)$this->l10n->t('Invalid mail address')
				),
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		// TODO FIXME get rid of the static calls to OC_Subadmin
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
			if($groups !== null) {
				foreach( $groups as $groupName ) {
					$group = $this->groupManager->get($groupName);

					if(empty($group)) {
						$group = $this->groupManager->createGroup($groupName);
					}
					$group->addUser($user);
				}
			}
			/**
			 * Send new user mail only if a mail is set
			 */
			if($email !== '') {
				$this->config->setUserValue($username, 'settings', 'email', $email);

				// data for the mail template
				$mailData = array(
					'username' => $username,
					'url' => $this->urlGenerator->getAbsoluteURL('/')
				);

				$mail = new TemplateResponse('settings', 'email.new_user', $mailData, 'blank');
				$mailContent = $mail->render();

				$mail = new TemplateResponse('settings', 'email.new_user_plain_text', $mailData, 'blank');
				$plainTextMailContent = $mail->render();

				$subject = $this->l10n->t('Your %s account was created', [$this->defaults->getName()]);

				try {
					$this->mail->send(
						$email,
						$username,
						$subject,
						$mailContent,
						$this->fromMailAddress,
						$this->defaults->getName(),
						1,
						$plainTextMailContent);
				} catch(\Exception $e) {
					$this->log->error("Can't send new user mail to $email: " . $e->getMessage(), array('app' => 'settings'));
				}
			}
			// fetch users groups
			$userGroups = $this->groupManager->getUserGroupIds($user);

			return new DataResponse(
				$this->formatUserForIndex($user, $userGroups),
				Http::STATUS_CREATED
			);
		}

		return new DataResponse(
			array(
				'message' => (string)$this->l10n->t('Unable to create user.')
			),
			Http::STATUS_FORBIDDEN
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

	/**
	 * Set the mail address of a user
	 *
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 *
	 * @param string $id
	 * @param string $mailAddress
	 * @return DataResponse
	 *
	 * TODO: Tidy up and write unit tests - code is mainly static method calls
	 */
	public function setMailAddress($id, $mailAddress) {
		// FIXME: Remove this static function call at some point…
		if($this->userSession->getUser()->getUID() !== $id
			&& !$this->isAdmin
			&& !\OC_SubAdmin::isUserAccessible($this->userSession->getUser()->getUID(), $id)) {
			return new DataResponse(
				array(
					'status' => 'error',
					'data' => array(
						'message' => (string)$this->l10n->t('Forbidden')
					)
				),
				Http::STATUS_FORBIDDEN
			);
		}

		if($mailAddress !== '' && !$this->mail->validateAddress($mailAddress)) {
			return new DataResponse(
				array(
					'status' => 'error',
					'data' => array(
						'message' => (string)$this->l10n->t('Invalid mail address')
					)
				),
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		$user = $this->userManager->get($id);
		if(!$user){
			return new DataResponse(
				array(
					'status' => 'error',
					'data' => array(
						'message' => (string)$this->l10n->t('Invalid user')
					)
				),
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		// this is the only permission a backend provides and is also used
		// for the permission of setting a email address
		if(!$user->canChangeDisplayName()){
			return new DataResponse(
				array(
					'status' => 'error',
					'data' => array(
						'message' => (string)$this->l10n->t('Unable to change mail address')
					)
				),
				Http::STATUS_FORBIDDEN
			);
		}

		$this->config->setUserValue($id, 'settings', 'email', $mailAddress);

		return new DataResponse(
			array(
				'status' => 'success',
				'data' => array(
					'username' => $id,
					'mailAddress' => $mailAddress,
					'message' => (string)$this->l10n->t('Email saved')
				)
			),
			Http::STATUS_OK
		);
	}

}

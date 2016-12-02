<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OC\Settings\Controller;

use OC\Accounts\AccountManager;
use OC\AppFramework\Http;
use OC\ForbiddenException;
use OC\User\User;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
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
use OCP\Mail\IMailer;
use OCP\IAvatarManager;
use Punic\Exception;

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
	/** @var IMailer */
	private $mailer;
	/** @var string */
	private $fromMailAddress;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var bool contains the state of the encryption app */
	private $isEncryptionAppEnabled;
	/** @var bool contains the state of the admin recovery setting */
	private $isRestoreEnabled = false;
	/** @var IAvatarManager */
	private $avatarManager;
	/** @var AccountManager */
	private $accountManager;

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
	 * @param IMailer $mailer
	 * @param string $fromMailAddress
	 * @param IURLGenerator $urlGenerator
	 * @param IAppManager $appManager
	 * @param IAvatarManager $avatarManager
	 * @param AccountManager $accountManager
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
								IMailer $mailer,
								$fromMailAddress,
								IURLGenerator $urlGenerator,
								IAppManager $appManager,
								IAvatarManager $avatarManager,
								AccountManager $accountManager
) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->config = $config;
		$this->isAdmin = $isAdmin;
		$this->l10n = $l10n;
		$this->log = $log;
		$this->defaults = $defaults;
		$this->mailer = $mailer;
		$this->fromMailAddress = $fromMailAddress;
		$this->urlGenerator = $urlGenerator;
		$this->avatarManager = $avatarManager;
		$this->accountManager = $accountManager;

		// check for encryption state - TODO see formatUserForIndex
		$this->isEncryptionAppEnabled = $appManager->isEnabledForUser('encryption');
		if($this->isEncryptionAppEnabled) {
			// putting this directly in empty is possible in PHP 5.5+
			$result = $config->getAppValue('encryption', 'recoveryAdminEnabled', 0);
			$this->isRestoreEnabled = !empty($result);
		}
	}

	/**
	 * @param IUser $user
	 * @param array $userGroups
	 * @return array
	 */
	private function formatUserForIndex(IUser $user, array $userGroups = null) {

		// TODO: eliminate this encryption specific code below and somehow
		// hook in additional user info from other apps

		// recovery isn't possible if admin or user has it disabled and encryption
		// is enabled - so we eliminate the else paths in the conditional tree
		// below
		$restorePossible = false;

		if ($this->isEncryptionAppEnabled) {
			if ($this->isRestoreEnabled) {
				// check for the users recovery setting
				$recoveryMode = $this->config->getUserValue($user->getUID(), 'encryption', 'recoveryEnabled', '0');
				// method call inside empty is possible with PHP 5.5+
				$recoveryModeEnabled = !empty($recoveryMode);
				if ($recoveryModeEnabled) {
					// user also has recovery mode enabled
					$restorePossible = true;
				}
			}
		} else {
			// recovery is possible if encryption is disabled (plain files are
			// available)
			$restorePossible = true;
		}

		$subAdminGroups = $this->groupManager->getSubAdmin()->getSubAdminsGroups($user);
		foreach($subAdminGroups as $key => $subAdminGroup) {
			$subAdminGroups[$key] = $subAdminGroup->getGID();
		}

		$displayName = $user->getEMailAddress();
		if (is_null($displayName)) {
			$displayName = '';
		}

		$avatarAvailable = false;
		if ($this->config->getSystemValue('enable_avatars', true) === true) {
			try {
				$avatarAvailable = $this->avatarManager->getAvatar($user->getUID())->exists();
			} catch (\Exception $e) {
				//No avatar yet
			}
		}

		return [
			'name' => $user->getUID(),
			'displayname' => $user->getDisplayName(),
			'groups' => (empty($userGroups)) ? $this->groupManager->getUserGroupIds($user) : $userGroups,
			'subadmin' => $subAdminGroups,
			'quota' => $user->getQuota(),
			'storageLocation' => $user->getHome(),
			'lastLogin' => $user->getLastLogin() * 1000,
			'backend' => $user->getBackendClassName(),
			'email' => $displayName,
			'isRestoreDisabled' => !$restorePossible,
			'isAvatarAvailable' => $avatarAvailable,
		];
	}

	/**
	 * @param array $userIDs Array with schema [$uid => $displayName]
	 * @return IUser[]
	 */
	private function getUsersForUID(array $userIDs) {
		$users = [];
		foreach ($userIDs as $uid => $displayName) {
			$users[$uid] = $this->userManager->get($uid);
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

		$users = [];
		if ($this->isAdmin) {

			if($gid !== '') {
				$batch = $this->getUsersForUID($this->groupManager->displayNamesInGroup($gid, $pattern, $limit, $offset));
			} else {
				$batch = $this->userManager->search($pattern, $limit, $offset);
			}

			foreach ($batch as $user) {
				$users[] = $this->formatUserForIndex($user);
			}

		} else {
			$subAdminOfGroups = $this->groupManager->getSubAdmin()->getSubAdminsGroups($this->userSession->getUser());
			// New class returns IGroup[] so convert back
			$gids = [];
			foreach ($subAdminOfGroups as $group) {
				$gids[] = $group->getGID();
			}
			$subAdminOfGroups = $gids;

			// Set the $gid parameter to an empty value if the subadmin has no rights to access a specific group
			if($gid !== '' && !in_array($gid, $subAdminOfGroups)) {
				$gid = '';
			}

			// Batch all groups the user is subadmin of when a group is specified
			$batch = [];
			if($gid === '') {
				foreach($subAdminOfGroups as $group) {
					$groupUsers = $this->groupManager->displayNamesInGroup($group, $pattern, $limit, $offset);

					foreach($groupUsers as $uid => $displayName) {
						$batch[$uid] = $displayName;
					}
				}
			} else {
				$batch = $this->groupManager->displayNamesInGroup($gid, $pattern, $limit, $offset);
			}
			$batch = $this->getUsersForUID($batch);

			foreach ($batch as $user) {
				// Only add the groups, this user is a subadmin of
				$userGroups = array_values(array_intersect(
					$this->groupManager->getUserGroupIds($user),
					$subAdminOfGroups
				));
				$users[] = $this->formatUserForIndex($user, $userGroups);
			}
		}

		return new DataResponse($users);
	}

	/**
	 * @NoAdminRequired
	 * @PasswordConfirmationRequired
	 *
	 * @param string $username
	 * @param string $password
	 * @param array $groups
	 * @param string $email
	 * @return DataResponse
	 */
	public function create($username, $password, array $groups=array(), $email='') {
		if($email !== '' && !$this->mailer->validateMailAddress($email)) {
			return new DataResponse(
				array(
					'message' => (string)$this->l10n->t('Invalid mail address')
				),
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		$currentUser = $this->userSession->getUser();

		if (!$this->isAdmin) {
			if (!empty($groups)) {
				foreach ($groups as $key => $group) {
					$groupObject = $this->groupManager->get($group);
					if($groupObject === null) {
						unset($groups[$key]);
						continue;
					}

					if (!$this->groupManager->getSubAdmin()->isSubAdminofGroup($currentUser, $groupObject)) {
						unset($groups[$key]);
					}
				}
			}

			if (empty($groups)) {
				$groups = $this->groupManager->getSubAdmin()->getSubAdminsGroups($currentUser);
				// New class returns IGroup[] so convert back
				$gids = [];
				foreach ($groups as $group) {
					$gids[] = $group->getGID();
				}
				$groups = $gids;
			}
		}

		if ($this->userManager->userExists($username)) {
			return new DataResponse(
				array(
					'message' => (string)$this->l10n->t('A user with that name already exists.')
				),
				Http::STATUS_CONFLICT
			);
		}

		try {
			$user = $this->userManager->createUser($username, $password);
		} catch (\Exception $exception) {
			$message = $exception->getMessage();
			if (!$message) {
				$message = $this->l10n->t('Unable to create user.');
			}
			return new DataResponse(
				array(
					'message' => (string) $message,
				),
				Http::STATUS_FORBIDDEN
			);
		}

		if($user instanceof User) {
			if($groups !== null) {
				foreach($groups as $groupName) {
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
				$user->setEMailAddress($email);

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
					$message = $this->mailer->createMessage();
					$message->setTo([$email => $username]);
					$message->setSubject($subject);
					$message->setHtmlBody($mailContent);
					$message->setPlainBody($plainTextMailContent);
					$message->setFrom([$this->fromMailAddress => $this->defaults->getName()]);
					$this->mailer->send($message);
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
	 * @PasswordConfirmationRequired
	 *
	 * @param string $id
	 * @return DataResponse
	 */
	public function destroy($id) {
		$userId = $this->userSession->getUser()->getUID();
		$user = $this->userManager->get($id);

		if($userId === $id) {
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

		if(!$this->isAdmin && !$this->groupManager->getSubAdmin()->isUserAccessible($this->userSession->getUser(), $user)) {
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
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 * @PasswordConfirmationRequired
	 *
	 * @param string $avatarScope
	 * @param string $displayname
	 * @param string $displaynameScope
	 * @param string $phone
	 * @param string $phoneScope
	 * @param string $email
	 * @param string $emailScope
	 * @param string $website
	 * @param string $websiteScope
	 * @param string $address
	 * @param string $addressScope
	 * @param string $twitter
	 * @param string $twitterScope
	 * @return DataResponse
	 */
	public function setUserSettings($avatarScope,
									$displayname,
									$displaynameScope,
									$phone,
									$phoneScope,
									$email,
									$emailScope,
									$website,
									$websiteScope,
									$address,
									$addressScope,
									$twitter,
									$twitterScope
	) {

		if(!empty($email) && !$this->mailer->validateMailAddress($email)) {
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

		$data = [
			AccountManager::PROPERTY_AVATAR =>  ['scope' => $avatarScope],
			AccountManager::PROPERTY_DISPLAYNAME => ['value' => $displayname, 'scope' => $displaynameScope],
			AccountManager::PROPERTY_EMAIL=> ['value' => $email, 'scope' => $emailScope],
			AccountManager::PROPERTY_WEBSITE => ['value' => $website, 'scope' => $websiteScope],
			AccountManager::PROPERTY_ADDRESS => ['value' => $address, 'scope' => $addressScope],
			AccountManager::PROPERTY_PHONE => ['value' => $phone, 'scope' => $phoneScope],
			AccountManager::PROPERTY_TWITTER => ['value' => $twitter, 'scope' => $twitterScope]
		];

		$user = $this->userSession->getUser();

		try {
			$this->saveUserSettings($user, $data);
			return new DataResponse(
				array(
					'status' => 'success',
					'data' => array(
						'userId' => $user->getUID(),
						'avatarScope' => $avatarScope,
						'displayname' => $displayname,
						'displaynameScope' => $displaynameScope,
						'email' => $email,
						'emailScope' => $emailScope,
						'website' => $website,
						'websiteScope' => $websiteScope,
						'address' => $address,
						'addressScope' => $addressScope,
						'message' => (string)$this->l10n->t('Settings saved')
					)
				),
				Http::STATUS_OK
			);
		} catch (ForbiddenException $e) {
			return new DataResponse([
				'status' => 'error',
				'data' => [
					'message' => $e->getMessage()
				],
			]);
		}

	}


	/**
	 * update account manager with new user data
	 *
	 * @param IUser $user
	 * @param array $data
	 * @throws ForbiddenException
	 */
	protected function saveUserSettings(IUser $user, $data) {

		// keep the user back-end up-to-date with the latest display name and email
		// address
		$oldDisplayName = $user->getDisplayName();
		$oldDisplayName = is_null($oldDisplayName) ? '' : $oldDisplayName;
		if (isset($data[AccountManager::PROPERTY_DISPLAYNAME]['value'])
			&& $oldDisplayName !== $data[AccountManager::PROPERTY_DISPLAYNAME]['value']
		) {
			$result = $user->setDisplayName($data[AccountManager::PROPERTY_DISPLAYNAME]['value']);
			if ($result === false) {
				throw new ForbiddenException($this->l10n->t('Unable to change full name'));
			}
		}

		$oldEmailAddress = $user->getEMailAddress();
		$oldEmailAddress = is_null($oldEmailAddress) ? '' : $oldEmailAddress;
		if (isset($data[AccountManager::PROPERTY_EMAIL]['value'])
			&& $oldEmailAddress !== $data[AccountManager::PROPERTY_EMAIL]['value']
		) {
			// this is the only permission a backend provides and is also used
			// for the permission of setting a email address
			if (!$user->canChangeDisplayName()) {
				throw new ForbiddenException($this->l10n->t('Unable to change email address'));
			}
			$user->setEMailAddress($data[AccountManager::PROPERTY_EMAIL]['value']);
		}

		$this->accountManager->updateUser($user, $data);
	}

	/**
	 * Count all unique users visible for the current admin/subadmin.
	 *
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function stats() {
		$userCount = 0;
		if ($this->isAdmin) {
			$countByBackend = $this->userManager->countUsers();

			if (!empty($countByBackend)) {
				foreach ($countByBackend as $count) {
					$userCount += $count;
				}
			}
		} else {
			$groups = $this->groupManager->getSubAdmin()->getSubAdminsGroups($this->userSession->getUser());

			$uniqueUsers = [];
			foreach ($groups as $group) {
				foreach($group->getUsers() as $uid => $displayName) {
					$uniqueUsers[$uid] = true;
				}
			}

			$userCount = count($uniqueUsers);
		}

		return new DataResponse(
			[
				'totalUsers' => $userCount
			]
		);
	}


	/**
	 * Set the displayName of a user
	 *
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 * @PasswordConfirmationRequired
	 * @todo merge into saveUserSettings
	 *
	 * @param string $username
	 * @param string $displayName
	 * @return DataResponse
	 */
	public function setDisplayName($username, $displayName) {
		$currentUser = $this->userSession->getUser();
		$user = $this->userManager->get($username);

		if ($user === null ||
			!$user->canChangeDisplayName() ||
			(
				!$this->groupManager->isAdmin($currentUser->getUID()) &&
				!$this->groupManager->getSubAdmin()->isUserAccessible($currentUser, $user) &&
				$currentUser->getUID() !== $username

			)
		) {
			return new DataResponse([
				'status' => 'error',
				'data' => [
					'message' => $this->l10n->t('Authentication error'),
				],
			]);
		}

		$userData = $this->accountManager->getUser($user);
		$userData[AccountManager::PROPERTY_DISPLAYNAME]['value'] = $displayName;


		try {
			$this->saveUserSettings($user, $userData);
			return new DataResponse([
				'status' => 'success',
				'data' => [
					'message' => $this->l10n->t('Your full name has been changed.'),
					'username' => $username,
					'displayName' => $displayName,
				],
			]);
		} catch (ForbiddenException $e) {
			return new DataResponse([
				'status' => 'error',
				'data' => [
					'message' => $e->getMessage(),
					'displayName' => $user->getDisplayName(),
				],
			]);
		}
	}

	/**
	 * Set the mail address of a user
	 *
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 * @PasswordConfirmationRequired
	 *
	 * @param string $id
	 * @param string $mailAddress
	 * @return DataResponse
	 */
	public function setEMailAddress($id, $mailAddress) {
		$user = $this->userManager->get($id);
		if (!$this->isAdmin
			&& !$this->groupManager->getSubAdmin()->isUserAccessible($this->userSession->getUser(), $user)
		) {
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

		if($mailAddress !== '' && !$this->mailer->validateMailAddress($mailAddress)) {
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

		if (!$user) {
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
		if (!$user->canChangeDisplayName()) {
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

		$userData = $this->accountManager->getUser($user);
		$userData[AccountManager::PROPERTY_EMAIL]['value'] = $mailAddress;

		try {
			$this->saveUserSettings($user, $userData);
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
		} catch (ForbiddenException $e) {
			return new DataResponse([
				'status' => 'error',
				'data' => [
					'message' => $e->getMessage()
				],
			]);
		}
	}

}

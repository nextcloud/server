<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author GretaD <gretadoci@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
// FIXME: disabled for now to be able to inject IGroupManager and also use
// getSubAdmin()

namespace OCA\Settings\Controller;

use InvalidArgumentException;
use OC\AppFramework\Http;
use OC\Encryption\Exceptions\ModuleDoesNotExistsException;
use OC\ForbiddenException;
use OC\Group\Manager as GroupManager;
use OC\KnownUser\KnownUserService;
use OC\L10N\Factory;
use OC\Security\IdentityProof\Manager;
use OC\User\Manager as UserManager;
use OCA\Settings\BackgroundJobs\VerifyUserData;
use OCA\Settings\Events\BeforeTemplateRenderedEvent;
use OCA\User_LDAP\User_Proxy;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\BackgroundJob\IJobList;
use OCP\Encryption\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use function in_array;

class UsersController extends Controller {
	/** @var UserManager */
	private $userManager;
	/** @var GroupManager */
	private $groupManager;
	/** @var IUserSession */
	private $userSession;
	/** @var IConfig */
	private $config;
	/** @var bool */
	private $isAdmin;
	/** @var IL10N */
	private $l10n;
	/** @var IMailer */
	private $mailer;
	/** @var Factory */
	private $l10nFactory;
	/** @var IAppManager */
	private $appManager;
	/** @var IAccountManager */
	private $accountManager;
	/** @var Manager */
	private $keyManager;
	/** @var IJobList */
	private $jobList;
	/** @var IManager */
	private $encryptionManager;
	/** @var KnownUserService */
	private $knownUserService;
	/** @var IEventDispatcher */
	private $dispatcher;


	public function __construct(
		string $appName,
		IRequest $request,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IUserSession $userSession,
		IConfig $config,
		bool $isAdmin,
		IL10N $l10n,
		IMailer $mailer,
		IFactory $l10nFactory,
		IAppManager $appManager,
		IAccountManager $accountManager,
		Manager $keyManager,
		IJobList $jobList,
		IManager $encryptionManager,
		KnownUserService $knownUserService,
		IEventDispatcher $dispatcher
	) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->config = $config;
		$this->isAdmin = $isAdmin;
		$this->l10n = $l10n;
		$this->mailer = $mailer;
		$this->l10nFactory = $l10nFactory;
		$this->appManager = $appManager;
		$this->accountManager = $accountManager;
		$this->keyManager = $keyManager;
		$this->jobList = $jobList;
		$this->encryptionManager = $encryptionManager;
		$this->knownUserService = $knownUserService;
		$this->dispatcher = $dispatcher;
	}


	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * Display users list template
	 *
	 * @return TemplateResponse
	 */
	public function usersListByGroup(): TemplateResponse {
		return $this->usersList();
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * Display users list template
	 *
	 * @return TemplateResponse
	 */
	public function usersList(): TemplateResponse {
		$user = $this->userSession->getUser();
		$uid = $user->getUID();

		\OC::$server->getNavigationManager()->setActiveEntry('core_users');

		/* SORT OPTION: SORT_USERCOUNT or SORT_GROUPNAME */
		$sortGroupsBy = \OC\Group\MetaData::SORT_USERCOUNT;
		$isLDAPUsed = false;
		if ($this->config->getSystemValue('sort_groups_by_name', false)) {
			$sortGroupsBy = \OC\Group\MetaData::SORT_GROUPNAME;
		} else {
			if ($this->appManager->isEnabledForUser('user_ldap')) {
				$isLDAPUsed =
					$this->groupManager->isBackendUsed('\OCA\User_LDAP\Group_Proxy');
				if ($isLDAPUsed) {
					// LDAP user count can be slow, so we sort by group name here
					$sortGroupsBy = \OC\Group\MetaData::SORT_GROUPNAME;
				}
			}
		}

		$canChangePassword = $this->canAdminChangeUserPasswords();

		/* GROUPS */
		$groupsInfo = new \OC\Group\MetaData(
			$uid,
			$this->isAdmin,
			$this->groupManager,
			$this->userSession
		);

		$groupsInfo->setSorting($sortGroupsBy);
		[$adminGroup, $groups] = $groupsInfo->get();

		if (!$isLDAPUsed && $this->appManager->isEnabledForUser('user_ldap')) {
			$isLDAPUsed = (bool)array_reduce($this->userManager->getBackends(), function ($ldapFound, $backend) {
				return $ldapFound || $backend instanceof User_Proxy;
			});
		}

		$disabledUsers = -1;
		$userCount = 0;

		if (!$isLDAPUsed) {
			if ($this->isAdmin) {
				$disabledUsers = $this->userManager->countDisabledUsers();
				$userCount = array_reduce($this->userManager->countUsers(), function ($v, $w) {
					return $v + (int)$w;
				}, 0);
			} else {
				// User is subadmin !
				// Map group list to names to retrieve the countDisabledUsersOfGroups
				$userGroups = $this->groupManager->getUserGroups($user);
				$groupsNames = [];

				foreach ($groups as $key => $group) {
					// $userCount += (int)$group['usercount'];
					array_push($groupsNames, $group['name']);
					// we prevent subadmins from looking up themselves
					// so we lower the count of the groups he belongs to
					if (array_key_exists($group['id'], $userGroups)) {
						$groups[$key]['usercount']--;
						$userCount -= 1; // we also lower from one the total count
					}
				}
				$userCount += $this->userManager->countUsersOfGroups($groupsInfo->getGroups());
				$disabledUsers = $this->userManager->countDisabledUsersOfGroups($groupsNames);
			}

			$userCount -= $disabledUsers;
		}

		$disabledUsersGroup = [
			'id' => 'disabled',
			'name' => 'Disabled users',
			'usercount' => $disabledUsers
		];

		/* QUOTAS PRESETS */
		$quotaPreset = $this->parseQuotaPreset($this->config->getAppValue('files', 'quota_preset', '1 GB, 5 GB, 10 GB'));
		$allowUnlimitedQuota = $this->config->getAppValue('files', 'allow_unlimited_quota', '1') === '1';
		if (!$allowUnlimitedQuota && count($quotaPreset) > 0) {
			$defaultQuota = $this->config->getAppValue('files', 'default_quota', $quotaPreset[0]);
		} else {
			$defaultQuota = $this->config->getAppValue('files', 'default_quota', 'none');
		}

		$event = new BeforeTemplateRenderedEvent();
		$this->dispatcher->dispatch('OC\Settings\Users::loadAdditionalScripts', $event);
		$this->dispatcher->dispatchTyped($event);

		/* LANGUAGES */
		$languages = $this->l10nFactory->getLanguages();

		/* FINAL DATA */
		$serverData = [];
		// groups
		$serverData['groups'] = array_merge_recursive($adminGroup, [$disabledUsersGroup], $groups);
		// Various data
		$serverData['isAdmin'] = $this->isAdmin;
		$serverData['sortGroups'] = $sortGroupsBy;
		$serverData['quotaPreset'] = $quotaPreset;
		$serverData['allowUnlimitedQuota'] = $allowUnlimitedQuota;
		$serverData['userCount'] = $userCount;
		$serverData['languages'] = $languages;
		$serverData['defaultLanguage'] = $this->config->getSystemValue('default_language', 'en');
		$serverData['forceLanguage'] = $this->config->getSystemValue('force_language', false);
		// Settings
		$serverData['defaultQuota'] = $defaultQuota;
		$serverData['canChangePassword'] = $canChangePassword;
		$serverData['newUserGenerateUserID'] = $this->config->getAppValue('core', 'newUser.generateUserID', 'no') === 'yes';
		$serverData['newUserRequireEmail'] = $this->config->getAppValue('core', 'newUser.requireEmail', 'no') === 'yes';
		$serverData['newUserSendEmail'] = $this->config->getAppValue('core', 'newUser.sendEmail', 'yes') === 'yes';

		return new TemplateResponse('settings', 'settings-vue', ['serverData' => $serverData, 'pageTitle' => $this->l10n->t('Users')]);
	}

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return JSONResponse
	 */
	public function setPreference(string $key, string $value): JSONResponse {
		$allowed = ['newUser.sendEmail'];
		if (!in_array($key, $allowed, true)) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->config->setAppValue('core', $key, $value);

		return new JSONResponse([]);
	}

	/**
	 * Parse the app value for quota_present
	 *
	 * @param string $quotaPreset
	 * @return array
	 */
	protected function parseQuotaPreset(string $quotaPreset): array {
		// 1 GB, 5 GB, 10 GB => [1 GB, 5 GB, 10 GB]
		$presets = array_filter(array_map('trim', explode(',', $quotaPreset)));
		// Drop default and none, Make array indexes numerically
		return array_values(array_diff($presets, ['default', 'none']));
	}

	/**
	 * check if the admin can change the users password
	 *
	 * The admin can change the passwords if:
	 *
	 *   - no encryption module is loaded and encryption is disabled
	 *   - encryption module is loaded but it doesn't require per user keys
	 *
	 * The admin can not change the passwords if:
	 *
	 *   - an encryption module is loaded and it uses per-user keys
	 *   - encryption is enabled but no encryption modules are loaded
	 *
	 * @return bool
	 */
	protected function canAdminChangeUserPasswords(): bool {
		$isEncryptionEnabled = $this->encryptionManager->isEnabled();
		try {
			$noUserSpecificEncryptionKeys = !$this->encryptionManager->getEncryptionModule()->needDetailedAccessList();
			$isEncryptionModuleLoaded = true;
		} catch (ModuleDoesNotExistsException $e) {
			$noUserSpecificEncryptionKeys = true;
			$isEncryptionModuleLoaded = false;
		}
		$canChangePassword = ($isEncryptionModuleLoaded && $noUserSpecificEncryptionKeys)
			|| (!$isEncryptionModuleLoaded && !$isEncryptionEnabled);

		return $canChangePassword;
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @PasswordConfirmationRequired
	 *
	 * @param string|null $avatarScope
	 * @param string|null $displayname
	 * @param string|null $displaynameScope
	 * @param string|null $phone
	 * @param string|null $phoneScope
	 * @param string|null $email
	 * @param string|null $emailScope
	 * @param string|null $website
	 * @param string|null $websiteScope
	 * @param string|null $address
	 * @param string|null $addressScope
	 * @param string|null $twitter
	 * @param string|null $twitterScope
	 * @param string|null $fediverse
	 * @param string|null $fediverseScope
	 *
	 * @return DataResponse
	 */
	public function setUserSettings(?string $avatarScope = null,
									?string $displayname = null,
									?string $displaynameScope = null,
									?string $phone = null,
									?string $phoneScope = null,
									?string $email = null,
									?string $emailScope = null,
									?string $website = null,
									?string $websiteScope = null,
									?string $address = null,
									?string $addressScope = null,
									?string $twitter = null,
									?string $twitterScope = null,
									?string $fediverse = null,
									?string $fediverseScope = null
	) {
		$user = $this->userSession->getUser();
		if (!$user instanceof IUser) {
			return new DataResponse(
				[
					'status' => 'error',
					'data' => [
						'message' => $this->l10n->t('Invalid user')
					]
				],
				Http::STATUS_UNAUTHORIZED
			);
		}

		$email = !is_null($email) ? strtolower($email) : $email;
		if (!empty($email) && !$this->mailer->validateMailAddress($email)) {
			return new DataResponse(
				[
					'status' => 'error',
					'data' => [
						'message' => $this->l10n->t('Invalid mail address')
					]
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		$userAccount = $this->accountManager->getAccount($user);
		$oldPhoneValue = $userAccount->getProperty(IAccountManager::PROPERTY_PHONE)->getValue();

		$updatable = [
			IAccountManager::PROPERTY_AVATAR => ['value' => null, 'scope' => $avatarScope],
			IAccountManager::PROPERTY_DISPLAYNAME => ['value' => $displayname, 'scope' => $displaynameScope],
			IAccountManager::PROPERTY_EMAIL => ['value' => $email, 'scope' => $emailScope],
			IAccountManager::PROPERTY_WEBSITE => ['value' => $website, 'scope' => $websiteScope],
			IAccountManager::PROPERTY_ADDRESS => ['value' => $address, 'scope' => $addressScope],
			IAccountManager::PROPERTY_PHONE => ['value' => $phone, 'scope' => $phoneScope],
			IAccountManager::PROPERTY_TWITTER => ['value' => $twitter, 'scope' => $twitterScope],
			IAccountManager::PROPERTY_FEDIVERSE => ['value' => $fediverse, 'scope' => $fediverseScope],
		];
		$allowUserToChangeDisplayName = $this->config->getSystemValueBool('allow_user_to_change_display_name', true);
		foreach ($updatable as $property => $data) {
			if ($allowUserToChangeDisplayName === false
				&& in_array($property, [IAccountManager::PROPERTY_DISPLAYNAME, IAccountManager::PROPERTY_EMAIL], true)) {
				continue;
			}
			$property = $userAccount->getProperty($property);
			if (null !== $data['value']) {
				$property->setValue($data['value']);
			}
			if (null !== $data['scope']) {
				$property->setScope($data['scope']);
			}
		}

		try {
			$this->saveUserSettings($userAccount);
			if ($oldPhoneValue !== $userAccount->getProperty(IAccountManager::PROPERTY_PHONE)->getValue()) {
				$this->knownUserService->deleteByContactUserId($user->getUID());
			}
			return new DataResponse(
				[
					'status' => 'success',
					'data' => [
						'userId' => $user->getUID(),
						'avatarScope' => $userAccount->getProperty(IAccountManager::PROPERTY_AVATAR)->getScope(),
						'displayname' => $userAccount->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
						'displaynameScope' => $userAccount->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getScope(),
						'phone' => $userAccount->getProperty(IAccountManager::PROPERTY_PHONE)->getValue(),
						'phoneScope' => $userAccount->getProperty(IAccountManager::PROPERTY_PHONE)->getScope(),
						'email' => $userAccount->getProperty(IAccountManager::PROPERTY_EMAIL)->getValue(),
						'emailScope' => $userAccount->getProperty(IAccountManager::PROPERTY_EMAIL)->getScope(),
						'website' => $userAccount->getProperty(IAccountManager::PROPERTY_WEBSITE)->getValue(),
						'websiteScope' => $userAccount->getProperty(IAccountManager::PROPERTY_WEBSITE)->getScope(),
						'address' => $userAccount->getProperty(IAccountManager::PROPERTY_ADDRESS)->getValue(),
						'addressScope' => $userAccount->getProperty(IAccountManager::PROPERTY_ADDRESS)->getScope(),
						'twitter' => $userAccount->getProperty(IAccountManager::PROPERTY_TWITTER)->getValue(),
						'twitterScope' => $userAccount->getProperty(IAccountManager::PROPERTY_TWITTER)->getScope(),
						'fediverse' => $userAccount->getProperty(IAccountManager::PROPERTY_FEDIVERSE)->getValue(),
						'fediverseScope' => $userAccount->getProperty(IAccountManager::PROPERTY_FEDIVERSE)->getScope(),
						'message' => $this->l10n->t('Settings saved'),
					],
				],
				Http::STATUS_OK
			);
		} catch (ForbiddenException | InvalidArgumentException | PropertyDoesNotExistException $e) {
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
	 * @throws ForbiddenException
	 * @throws InvalidArgumentException
	 */
	protected function saveUserSettings(IAccount $userAccount): void {
		// keep the user back-end up-to-date with the latest display name and email
		// address
		$oldDisplayName = $userAccount->getUser()->getDisplayName();
		if ($oldDisplayName !== $userAccount->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue()) {
			$result = $userAccount->getUser()->setDisplayName($userAccount->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue());
			if ($result === false) {
				throw new ForbiddenException($this->l10n->t('Unable to change full name'));
			}
		}

		$oldEmailAddress = $userAccount->getUser()->getSystemEMailAddress();
		$oldEmailAddress = strtolower((string)$oldEmailAddress);
		if ($oldEmailAddress !== strtolower($userAccount->getProperty(IAccountManager::PROPERTY_EMAIL)->getValue())) {
			// this is the only permission a backend provides and is also used
			// for the permission of setting a email address
			if (!$userAccount->getUser()->canChangeDisplayName()) {
				throw new ForbiddenException($this->l10n->t('Unable to change email address'));
			}
			$userAccount->getUser()->setSystemEMailAddress($userAccount->getProperty(IAccountManager::PROPERTY_EMAIL)->getValue());
		}

		try {
			$this->accountManager->updateAccount($userAccount);
		} catch (InvalidArgumentException $e) {
			if ($e->getMessage() === IAccountManager::PROPERTY_PHONE) {
				throw new InvalidArgumentException($this->l10n->t('Unable to set invalid phone number'));
			}
			if ($e->getMessage() === IAccountManager::PROPERTY_WEBSITE) {
				throw new InvalidArgumentException($this->l10n->t('Unable to set invalid website'));
			}
			throw new InvalidArgumentException($this->l10n->t('Some account data was invalid'));
		}
	}

	/**
	 * Set the mail address of a user
	 *
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @PasswordConfirmationRequired
	 *
	 * @param string $account
	 * @param bool $onlyVerificationCode only return verification code without updating the data
	 * @return DataResponse
	 */
	public function getVerificationCode(string $account, bool $onlyVerificationCode): DataResponse {
		$user = $this->userSession->getUser();

		if ($user === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$userAccount = $this->accountManager->getAccount($user);
		$cloudId = $user->getCloudId();
		$message = 'Use my Federated Cloud ID to share with me: ' . $cloudId;
		$signature = $this->signMessage($user, $message);

		$code = $message . ' ' . $signature;
		$codeMd5 = $message . ' ' . md5($signature);

		switch ($account) {
			case 'verify-twitter':
				$msg = $this->l10n->t('In order to verify your Twitter account, post the following tweet on Twitter (please make sure to post it without any line breaks):');
				$code = $codeMd5;
				$type = IAccountManager::PROPERTY_TWITTER;
				break;
			case 'verify-website':
				$msg = $this->l10n->t('In order to verify your Website, store the following content in your web-root at \'.well-known/CloudIdVerificationCode.txt\' (please make sure that the complete text is in one line):');
				$type = IAccountManager::PROPERTY_WEBSITE;
				break;
			default:
				return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$userProperty = $userAccount->getProperty($type);
		$userProperty
			->setVerified(IAccountManager::VERIFICATION_IN_PROGRESS)
			->setVerificationData($signature);

		if ($onlyVerificationCode === false) {
			$this->accountManager->updateAccount($userAccount);

			$this->jobList->add(VerifyUserData::class,
				[
					'verificationCode' => $code,
					'data' => $userProperty->getValue(),
					'type' => $type,
					'uid' => $user->getUID(),
					'try' => 0,
					'lastRun' => $this->getCurrentTime()
				]
			);
		}

		return new DataResponse(['msg' => $msg, 'code' => $code]);
	}

	/**
	 * get current timestamp
	 *
	 * @return int
	 */
	protected function getCurrentTime(): int {
		return time();
	}

	/**
	 * sign message with users private key
	 *
	 * @param IUser $user
	 * @param string $message
	 *
	 * @return string base64 encoded signature
	 */
	protected function signMessage(IUser $user, string $message): string {
		$privateKey = $this->keyManager->getKey($user)->getPrivate();
		openssl_sign(json_encode($message), $signature, $privateKey, OPENSSL_ALGO_SHA512);
		return base64_encode($signature);
	}
}

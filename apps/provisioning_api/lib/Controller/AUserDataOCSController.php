<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Provisioning_API\Controller;

use OC\Group\Manager as GroupManager;
use OC\User\Backend;
use OC\User\NoUserException;
use OCA\Provisioning_API\ResponseDefinitions;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Group\ISubAdmin;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\User\Backend\ISetDisplayNameBackend;
use OCP\User\Backend\ISetPasswordBackend;
use OCP\Util;

/**
 * @psalm-import-type Provisioning_APIUserDetails from ResponseDefinitions
 * @psalm-import-type Provisioning_APIUserDetailsQuota from ResponseDefinitions
 */
abstract class AUserDataOCSController extends OCSController {
	public const SCOPE_SUFFIX = 'Scope';

	public const USER_FIELD_DISPLAYNAME = 'display';
	public const USER_FIELD_LANGUAGE = 'language';
	public const USER_FIELD_LOCALE = 'locale';
	public const USER_FIELD_TIMEZONE = 'timezone';
	public const USER_FIELD_FIRST_DAY_OF_WEEK = 'first_day_of_week';
	public const USER_FIELD_PASSWORD = 'password';
	public const USER_FIELD_QUOTA = 'quota';
	public const USER_FIELD_MANAGER = 'manager';
	public const USER_FIELD_NOTIFICATION_EMAIL = 'notify_email';

	public function __construct(
		string $appName,
		IRequest $request,
		protected IUserManager $userManager,
		protected IConfig $config,
		protected GroupManager $groupManager,
		protected IUserSession $userSession,
		protected IAccountManager $accountManager,
		protected ISubAdmin $subAdminManager,
		protected IFactory $l10nFactory,
		protected IRootFolder $rootFolder,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * creates a array with all user data
	 *
	 * @param string $userId
	 * @param bool $includeScopes
	 * @return Provisioning_APIUserDetails|null
	 * @throws NotFoundException
	 * @throws OCSException
	 * @throws OCSNotFoundException
	 */
	protected function getUserData(string $userId, bool $includeScopes = false): ?array {
		$currentLoggedInUser = $this->userSession->getUser();
		assert($currentLoggedInUser !== null, 'No user logged in');

		$data = [];

		// Check if the target user exists
		$targetUserObject = $this->userManager->get($userId);
		if ($targetUserObject === null) {
			throw new OCSNotFoundException('User does not exist');
		}

		$isAdmin = $this->groupManager->isAdmin($currentLoggedInUser->getUID());
		$isDelegatedAdmin = $this->groupManager->isDelegatedAdmin($currentLoggedInUser->getUID());
		if ($isAdmin
			|| $isDelegatedAdmin
			|| $this->groupManager->getSubAdmin()->isUserAccessible($currentLoggedInUser, $targetUserObject)) {
			$data['enabled'] = $this->config->getUserValue($targetUserObject->getUID(), 'core', 'enabled', 'true') === 'true';
		} else {
			// Check they are looking up themselves
			if ($currentLoggedInUser->getUID() !== $targetUserObject->getUID()) {
				return null;
			}
		}

		// Get groups data
		$userAccount = $this->accountManager->getAccount($targetUserObject);
		$groups = $this->groupManager->getUserGroups($targetUserObject);
		$gids = [];
		foreach ($groups as $group) {
			$gids[] = $group->getGID();
		}

		if ($isAdmin || $isDelegatedAdmin) {
			try {
				# might be thrown by LDAP due to handling of users disappears
				# from the external source (reasons unknown to us)
				# cf. https://github.com/nextcloud/server/issues/12991
				$data['storageLocation'] = $targetUserObject->getHome();
			} catch (NoUserException $e) {
				throw new OCSNotFoundException($e->getMessage(), $e);
			}
		}

		// Find the data
		$data['id'] = $targetUserObject->getUID();
		$data['firstLoginTimestamp'] = $targetUserObject->getFirstLogin();
		$data['lastLoginTimestamp'] = $targetUserObject->getLastLogin();
		$data['lastLogin'] = $targetUserObject->getLastLogin() * 1000;
		$data['backend'] = $targetUserObject->getBackendClassName();
		$data['subadmin'] = $this->getUserSubAdminGroupsData($targetUserObject->getUID());
		$data[self::USER_FIELD_QUOTA] = $this->fillStorageInfo($targetUserObject);
		$managers = $this->getManagers($targetUserObject);
		$data[self::USER_FIELD_MANAGER] = empty($managers) ? '' : $managers[0];

		try {
			if ($includeScopes) {
				$data[IAccountManager::PROPERTY_AVATAR . self::SCOPE_SUFFIX] = $userAccount->getProperty(IAccountManager::PROPERTY_AVATAR)->getScope();
			}

			$data[IAccountManager::PROPERTY_EMAIL] = $targetUserObject->getSystemEMailAddress();
			if ($includeScopes) {
				$data[IAccountManager::PROPERTY_EMAIL . self::SCOPE_SUFFIX] = $userAccount->getProperty(IAccountManager::PROPERTY_EMAIL)->getScope();
			}

			$additionalEmails = $additionalEmailScopes = [];
			$emailCollection = $userAccount->getPropertyCollection(IAccountManager::COLLECTION_EMAIL);
			foreach ($emailCollection->getProperties() as $property) {
				$email = mb_strtolower(trim($property->getValue()));
				$additionalEmails[] = $email;
				if ($includeScopes) {
					$additionalEmailScopes[] = $property->getScope();
				}
			}
			$data[IAccountManager::COLLECTION_EMAIL] = $additionalEmails;
			if ($includeScopes) {
				$data[IAccountManager::COLLECTION_EMAIL . self::SCOPE_SUFFIX] = $additionalEmailScopes;
			}

			$data[IAccountManager::PROPERTY_DISPLAYNAME] = $targetUserObject->getDisplayName();
			$data[IAccountManager::PROPERTY_DISPLAYNAME_LEGACY] = $data[IAccountManager::PROPERTY_DISPLAYNAME];
			if ($includeScopes) {
				$data[IAccountManager::PROPERTY_DISPLAYNAME . self::SCOPE_SUFFIX] = $userAccount->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getScope();
			}

			foreach ([
				IAccountManager::PROPERTY_PHONE,
				IAccountManager::PROPERTY_ADDRESS,
				IAccountManager::PROPERTY_WEBSITE,
				IAccountManager::PROPERTY_TWITTER,
				IAccountManager::PROPERTY_BLUESKY,
				IAccountManager::PROPERTY_FEDIVERSE,
				IAccountManager::PROPERTY_ORGANISATION,
				IAccountManager::PROPERTY_ROLE,
				IAccountManager::PROPERTY_HEADLINE,
				IAccountManager::PROPERTY_BIOGRAPHY,
				IAccountManager::PROPERTY_PROFILE_ENABLED,
				IAccountManager::PROPERTY_PRONOUNS,
			] as $propertyName) {
				$property = $userAccount->getProperty($propertyName);
				$data[$propertyName] = $property->getValue();
				if ($includeScopes) {
					$data[$propertyName . self::SCOPE_SUFFIX] = $property->getScope();
				}
			}
		} catch (PropertyDoesNotExistException $e) {
			// hard coded properties should exist
			throw new OCSException($e->getMessage(), Http::STATUS_INTERNAL_SERVER_ERROR, $e);
		}

		$data['groups'] = $gids;
		$data[self::USER_FIELD_LANGUAGE] = $this->l10nFactory->getUserLanguage($targetUserObject);
		$data[self::USER_FIELD_LOCALE] = $this->config->getUserValue($targetUserObject->getUID(), 'core', 'locale');
		$data[self::USER_FIELD_TIMEZONE] = $this->config->getUserValue($targetUserObject->getUID(), 'core', 'timezone');
		$data[self::USER_FIELD_NOTIFICATION_EMAIL] = $targetUserObject->getPrimaryEMailAddress();

		$backend = $targetUserObject->getBackend();
		$data['backendCapabilities'] = [
			'setDisplayName' => $backend instanceof ISetDisplayNameBackend || $backend->implementsActions(Backend::SET_DISPLAYNAME),
			'setPassword' => $backend instanceof ISetPasswordBackend || $backend->implementsActions(Backend::SET_PASSWORD),
		];

		return $data;
	}

	/**
	 * @return string[]
	 */
	protected function getManagers(IUser $user): array {
		$currentLoggedInUser = $this->userSession->getUser();

		$managerUids = $user->getManagerUids();
		if ($this->groupManager->isAdmin($currentLoggedInUser->getUID()) || $this->groupManager->isDelegatedAdmin($currentLoggedInUser->getUID())) {
			return $managerUids;
		}

		if ($this->subAdminManager->isSubAdmin($currentLoggedInUser)) {
			$accessibleManagerUids = array_values(array_filter(
				$managerUids,
				function (string $managerUid) use ($currentLoggedInUser) {
					$manager = $this->userManager->get($managerUid);
					if (!($manager instanceof IUser)) {
						return false;
					}
					return $this->subAdminManager->isUserAccessible($currentLoggedInUser, $manager);
				},
			));
			return $accessibleManagerUids;
		}

		return [];
	}

	/**
	 * Get the groups a user is a subadmin of
	 *
	 * @param string $userId
	 * @return list<string>
	 * @throws OCSException
	 */
	protected function getUserSubAdminGroupsData(string $userId): array {
		$user = $this->userManager->get($userId);
		// Check if the user exists
		if ($user === null) {
			throw new OCSNotFoundException('User does not exist');
		}

		// Get the subadmin groups
		$subAdminGroups = $this->groupManager->getSubAdmin()->getSubAdminsGroups($user);
		$groups = [];
		foreach ($subAdminGroups as $key => $group) {
			$groups[] = $group->getGID();
		}

		return $groups;
	}

	/**
	 * @param IUser $user
	 * @return Provisioning_APIUserDetailsQuota
	 * @throws OCSException
	 */
	protected function fillStorageInfo(IUser $user): array {
		$includeExternal = $this->config->getSystemValueBool('quota_include_external_storage');
		$userId = $user->getUID();

		$quota = $user->getQuota();
		if ($quota === 'none') {
			$quota = FileInfo::SPACE_UNLIMITED;
		} else {
			$quota = Util::computerFileSize($quota);
			if ($quota === false) {
				$quota = FileInfo::SPACE_UNLIMITED;
			}
		}

		try {
			if ($includeExternal) {
				\OC_Util::tearDownFS();
				\OC_Util::setupFS($user->getUID());
				$storage = \OC_Helper::getStorageInfo('/', null, true, false);
				$data = [
					'free' => $storage['free'],
					'used' => $storage['used'],
					'total' => $storage['total'],
					'relative' => $storage['relative'],
					self::USER_FIELD_QUOTA => $storage['quota'],
				];
			} else {
				$userFileInfo = $this->rootFolder->getUserFolder($userId)->getStorage()->getCache()->get('');
				$used = $userFileInfo->getSize();

				if ($quota > 0) {
					// prevent division by zero or error codes (negative values)
					$relative = round(($used / $quota) * 10000) / 100;
					$free = $quota - $used;
					$total = $quota;
				} else {
					$relative = 0;
					$free = FileInfo::SPACE_UNLIMITED;
					$total = FileInfo::SPACE_UNLIMITED;
				}

				$data = [
					'free' => $free,
					'used' => $used,
					'total' => $total,
					'relative' => $relative,
					self::USER_FIELD_QUOTA => $quota,
				];
			}
		} catch (NotFoundException $ex) {
			$data = [
				self::USER_FIELD_QUOTA => $quota >= 0 ? $quota : 'none',
				'used' => 0
			];
		} catch (\Exception $e) {
			Server::get(\Psr\Log\LoggerInterface::class)->error(
				'Could not load storage info for {user}',
				[
					'app' => 'provisioning_api',
					'user' => $userId,
					'exception' => $e,
				]
			);
			return [];
		}
		return $data;
	}
}

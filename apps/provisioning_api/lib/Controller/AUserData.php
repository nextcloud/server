<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Provisioning_API\Controller;

use OC\Group\Manager;
use OC\User\Backend;
use OC\User\NoUserException;
use OC_Helper;
use OCA\Provisioning_API\ResponseDefinitions;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\User\Backend\ISetDisplayNameBackend;
use OCP\User\Backend\ISetPasswordBackend;

/**
 * @psalm-import-type Provisioning_APIUserDetails from ResponseDefinitions
 * @psalm-import-type Provisioning_APIUserDetailsQuota from ResponseDefinitions
 */
abstract class AUserData extends OCSController {
	public const SCOPE_SUFFIX = 'Scope';

	public const USER_FIELD_DISPLAYNAME = 'display';
	public const USER_FIELD_LANGUAGE = 'language';
	public const USER_FIELD_LOCALE = 'locale';
	public const USER_FIELD_PASSWORD = 'password';
	public const USER_FIELD_QUOTA = 'quota';
	public const USER_FIELD_MANAGER = 'manager';
	public const USER_FIELD_NOTIFICATION_EMAIL = 'notify_email';

	/** @var IUserManager */
	protected $userManager;
	/** @var IConfig */
	protected $config;
	/** @var Manager */
	protected $groupManager;
	/** @var IUserSession */
	protected $userSession;
	/** @var IAccountManager */
	protected $accountManager;
	/** @var IFactory */
	protected $l10nFactory;

	public function __construct(string $appName,
		IRequest $request,
		IUserManager $userManager,
		IConfig $config,
		IGroupManager $groupManager,
		IUserSession $userSession,
		IAccountManager $accountManager,
		IFactory $l10nFactory) {
		parent::__construct($appName, $request);

		$this->userManager = $userManager;
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->accountManager = $accountManager;
		$this->l10nFactory = $l10nFactory;
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
		if ($isAdmin
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

		if ($isAdmin) {
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
		$data['lastLogin'] = $targetUserObject->getLastLogin() * 1000;
		$data['backend'] = $targetUserObject->getBackendClassName();
		$data['subadmin'] = $this->getUserSubAdminGroupsData($targetUserObject->getUID());
		$data[self::USER_FIELD_QUOTA] = $this->fillStorageInfo($targetUserObject->getUID());
		$managerUids = $targetUserObject->getManagerUids();
		$data[self::USER_FIELD_MANAGER] = empty($managerUids) ? '' : $managerUids[0];

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
				$additionalEmails[] = $property->getValue();
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
				IAccountManager::PROPERTY_FEDIVERSE,
				IAccountManager::PROPERTY_ORGANISATION,
				IAccountManager::PROPERTY_ROLE,
				IAccountManager::PROPERTY_HEADLINE,
				IAccountManager::PROPERTY_BIOGRAPHY,
				IAccountManager::PROPERTY_PROFILE_ENABLED,
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
		$data[self::USER_FIELD_NOTIFICATION_EMAIL] = $targetUserObject->getPrimaryEMailAddress();

		$backend = $targetUserObject->getBackend();
		$data['backendCapabilities'] = [
			'setDisplayName' => $backend instanceof ISetDisplayNameBackend || $backend->implementsActions(Backend::SET_DISPLAYNAME),
			'setPassword' => $backend instanceof ISetPasswordBackend || $backend->implementsActions(Backend::SET_PASSWORD),
		];

		return $data;
	}

	/**
	 * Get the groups a user is a subadmin of
	 *
	 * @param string $userId
	 * @return string[]
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
	 * @param string $userId
	 * @return Provisioning_APIUserDetailsQuota
	 * @throws OCSException
	 */
	protected function fillStorageInfo(string $userId): array {
		try {
			\OC_Util::tearDownFS();
			\OC_Util::setupFS($userId);
			$storage = OC_Helper::getStorageInfo('/', null, true, false);
			$data = [
				'free' => $storage['free'],
				'used' => $storage['used'],
				'total' => $storage['total'],
				'relative' => $storage['relative'],
				self::USER_FIELD_QUOTA => $storage['quota'],
			];
		} catch (NotFoundException $ex) {
			// User fs is not setup yet
			$user = $this->userManager->get($userId);
			if ($user === null) {
				throw new OCSException('User does not exist', 101);
			}
			$quota = $user->getQuota();
			if ($quota !== 'none') {
				$quota = OC_Helper::computerFileSize($quota);
			}
			$data = [
				self::USER_FIELD_QUOTA => $quota !== false ? $quota : 'none',
				'used' => 0
			];
		} catch (\Exception $e) {
			\OC::$server->get(\Psr\Log\LoggerInterface::class)->error(
				"Could not load storage info for {user}",
				[
					'app' => 'provisioning_api',
					'user' => $userId,
					'exception' => $e,
				]
			);
			/* In case the Exception left things in a bad state */
			\OC_Util::tearDownFS();
			return [];
		}
		return $data;
	}
}

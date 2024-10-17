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
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author michag86 <micha_g@arcor.de>
 * @author Mikael Hammarin <mikael@try2.se>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sujith Haridasan <sujith.h@gmail.com>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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

namespace OCA\Provisioning_API\Controller;

use InvalidArgumentException;
use OC\Authentication\Token\RemoteWipe;
use OC\KnownUser\KnownUserService;
use OC\User\Backend;
use OCA\Provisioning_API\ResponseDefinitions;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\HintException;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IPhoneNumberUtil;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\ISecureRandom;
use OCP\User\Backend\ISetDisplayNameBackend;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type Provisioning_APIUserDetails from ResponseDefinitions
 */
class UsersController extends AUserData {
	/** @var IURLGenerator */
	protected $urlGenerator;
	/** @var LoggerInterface */
	private $logger;
	/** @var IFactory */
	protected $l10nFactory;
	/** @var NewUserMailHelper */
	private $newUserMailHelper;
	/** @var ISecureRandom */
	private $secureRandom;
	/** @var RemoteWipe */
	private $remoteWipe;
	/** @var KnownUserService */
	private $knownUserService;
	/** @var IEventDispatcher */
	private $eventDispatcher;

	public function __construct(
		string $appName,
		IRequest $request,
		IUserManager $userManager,
		IConfig $config,
		IGroupManager $groupManager,
		IUserSession $userSession,
		IAccountManager $accountManager,
		IURLGenerator $urlGenerator,
		LoggerInterface $logger,
		IFactory $l10nFactory,
		NewUserMailHelper $newUserMailHelper,
		ISecureRandom $secureRandom,
		RemoteWipe $remoteWipe,
		KnownUserService $knownUserService,
		IEventDispatcher $eventDispatcher,
		private IPhoneNumberUtil $phoneNumberUtil,
	) {
		parent::__construct(
			$appName,
			$request,
			$userManager,
			$config,
			$groupManager,
			$userSession,
			$accountManager,
			$l10nFactory
		);

		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->l10nFactory = $l10nFactory;
		$this->newUserMailHelper = $newUserMailHelper;
		$this->secureRandom = $secureRandom;
		$this->remoteWipe = $remoteWipe;
		$this->knownUserService = $knownUserService;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get a list of users
	 *
	 * @param string $search Text to search for
	 * @param int|null $limit Limit the amount of groups returned
	 * @param int $offset Offset for searching for groups
	 * @return DataResponse<Http::STATUS_OK, array{users: string[]}, array{}>
	 *
	 * 200: Users returned
	 */
	public function getUsers(string $search = '', int $limit = null, int $offset = 0): DataResponse {
		$user = $this->userSession->getUser();
		$users = [];

		// Admin? Or SubAdmin?
		$uid = $user->getUID();
		$subAdminManager = $this->groupManager->getSubAdmin();
		if ($this->groupManager->isAdmin($uid)) {
			$users = $this->userManager->search($search, $limit, $offset);
		} elseif ($subAdminManager->isSubAdmin($user)) {
			$subAdminOfGroups = $subAdminManager->getSubAdminsGroups($user);
			foreach ($subAdminOfGroups as $key => $group) {
				$subAdminOfGroups[$key] = $group->getGID();
			}

			$users = [];
			foreach ($subAdminOfGroups as $group) {
				$users = array_merge($users, $this->groupManager->displayNamesInGroup($group, $search, $limit, $offset));
			}
		}

		/** @var string[] $users */
		$users = array_keys($users);

		return new DataResponse([
			'users' => $users
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get a list of users and their details
	 *
	 * @param string $search Text to search for
	 * @param int|null $limit Limit the amount of groups returned
	 * @param int $offset Offset for searching for groups
	 * @return DataResponse<Http::STATUS_OK, array{users: array<string, Provisioning_APIUserDetails|array{id: string}>}, array{}>
	 *
	 * 200: Users details returned
	 */
	public function getUsersDetails(string $search = '', int $limit = null, int $offset = 0): DataResponse {
		$currentUser = $this->userSession->getUser();
		$users = [];

		// Admin? Or SubAdmin?
		$uid = $currentUser->getUID();
		$subAdminManager = $this->groupManager->getSubAdmin();
		if ($this->groupManager->isAdmin($uid)) {
			$users = $this->userManager->search($search, $limit, $offset);
			$users = array_keys($users);
		} elseif ($subAdminManager->isSubAdmin($currentUser)) {
			$subAdminOfGroups = $subAdminManager->getSubAdminsGroups($currentUser);
			foreach ($subAdminOfGroups as $key => $group) {
				$subAdminOfGroups[$key] = $group->getGID();
			}

			$users = [];
			foreach ($subAdminOfGroups as $group) {
				$users[] = array_keys($this->groupManager->displayNamesInGroup($group, $search, $limit, $offset));
			}
			$users = array_merge(...$users);
		}

		$usersDetails = [];
		foreach ($users as $userId) {
			$userId = (string) $userId;
			try {
				$userData = $this->getUserData($userId);
			} catch (OCSNotFoundException $e) {
				// We still want to return all other accounts, but this one was removed from the backends
				// yet they are still in our database. Might be a LDAP remnant.
				$userData = null;
				$this->logger->warning('Found one enabled account that is removed from its backend, but still exists in Nextcloud database', ['accountId' => $userId]);
			}
			// Do not insert empty entry
			if ($userData !== null) {
				$usersDetails[$userId] = $userData;
			} else {
				// Logged user does not have permissions to see this user
				// only showing its id
				$usersDetails[$userId] = ['id' => $userId];
			}
		}

		return new DataResponse([
			'users' => $usersDetails
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get the list of disabled users and their details
	 *
	 * @param string $search Text to search for
	 * @param ?int $limit Limit the amount of users returned
	 * @param int $offset Offset
	 * @return DataResponse<Http::STATUS_OK, array{users: array<string, Provisioning_APIUserDetails|array{id: string}>}, array{}>
	 *
	 * 200: Disabled users details returned
	 */
	public function getDisabledUsersDetails(string $search = '', ?int $limit = null, int $offset = 0): DataResponse {
		$currentUser = $this->userSession->getUser();
		if ($currentUser === null) {
			return new DataResponse(['users' => []]);
		}
		if ($limit !== null && $limit < 0) {
			throw new InvalidArgumentException("Invalid limit value: $limit");
		}
		if ($offset < 0) {
			throw new InvalidArgumentException("Invalid offset value: $offset");
		}

		$users = [];

		// Admin? Or SubAdmin?
		$uid = $currentUser->getUID();
		$subAdminManager = $this->groupManager->getSubAdmin();
		if ($this->groupManager->isAdmin($uid)) {
			$users = $this->userManager->getDisabledUsers($limit, $offset, $search);
			$users = array_map(fn (IUser $user): string => $user->getUID(), $users);
		} elseif ($subAdminManager->isSubAdmin($currentUser)) {
			$subAdminOfGroups = $subAdminManager->getSubAdminsGroups($currentUser);

			$users = [];
			/* We have to handle offset ourselve for correctness */
			$tempLimit = ($limit === null ? null : $limit + $offset);
			foreach ($subAdminOfGroups as $group) {
				$users = array_unique(array_merge(
					$users,
					array_map(
						fn (IUser $user): string => $user->getUID(),
						array_filter(
							$group->searchUsers($search),
							fn (IUser $user): bool => !$user->isEnabled()
						)
					)
				));
				if (($tempLimit !== null) && (count($users) >= $tempLimit)) {
					break;
				}
			}
			$users = array_slice($users, $offset, $limit);
		}

		$usersDetails = [];
		foreach ($users as $userId) {
			try {
				$userData = $this->getUserData($userId);
			} catch (OCSNotFoundException $e) {
				// We still want to return all other accounts, but this one was removed from the backends
				// yet they are still in our database. Might be a LDAP remnant.
				$userData = null;
				$this->logger->warning('Found one disabled account that was removed from its backend, but still exists in Nextcloud database', ['accountId' => $userId]);
			}
			// Do not insert empty entry
			if ($userData !== null) {
				$usersDetails[$userId] = $userData;
			} else {
				// Currently logged in user does not have permissions to see this user
				// only showing its id
				$usersDetails[$userId] = ['id' => $userId];
			}
		}

		return new DataResponse([
			'users' => $usersDetails
		]);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * Search users by their phone numbers
	 *
	 * @param string $location Location of the phone number (for country code)
	 * @param array<string, string[]> $search Phone numbers to search for
	 * @return DataResponse<Http::STATUS_OK, array<string, string>, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array<empty>, array{}>
	 *
	 * 200: Users returned
	 * 400: Invalid location
	 */
	public function searchByPhoneNumbers(string $location, array $search): DataResponse {
		if ($this->phoneNumberUtil->getCountryCodeForRegion($location) === null) {
			// Not a valid region code
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		/** @var IUser $user */
		$user = $this->userSession->getUser();
		$knownTo = $user->getUID();
		$defaultPhoneRegion = $this->config->getSystemValueString('default_phone_region');

		$normalizedNumberToKey = [];
		foreach ($search as $key => $phoneNumbers) {
			foreach ($phoneNumbers as $phone) {
				$normalizedNumber = $this->phoneNumberUtil->convertToStandardFormat($phone, $location);
				if ($normalizedNumber !== null) {
					$normalizedNumberToKey[$normalizedNumber] = (string) $key;
				}

				if ($defaultPhoneRegion !== '' && $defaultPhoneRegion !== $location && str_starts_with($phone, '0')) {
					// If the number has a leading zero (no country code),
					// we also check the default phone region of the instance,
					// when it's different to the user's given region.
					$normalizedNumber = $this->phoneNumberUtil->convertToStandardFormat($phone, $defaultPhoneRegion);
					if ($normalizedNumber !== null) {
						$normalizedNumberToKey[$normalizedNumber] = (string) $key;
					}
				}
			}
		}

		$phoneNumbers = array_keys($normalizedNumberToKey);

		if (empty($phoneNumbers)) {
			return new DataResponse();
		}

		// Cleanup all previous entries and only allow new matches
		$this->knownUserService->deleteKnownTo($knownTo);

		$userMatches = $this->accountManager->searchUsers(IAccountManager::PROPERTY_PHONE, $phoneNumbers);

		if (empty($userMatches)) {
			return new DataResponse();
		}

		$cloudUrl = rtrim($this->urlGenerator->getAbsoluteURL('/'), '/');
		if (strpos($cloudUrl, 'http://') === 0) {
			$cloudUrl = substr($cloudUrl, strlen('http://'));
		} elseif (strpos($cloudUrl, 'https://') === 0) {
			$cloudUrl = substr($cloudUrl, strlen('https://'));
		}

		$matches = [];
		foreach ($userMatches as $phone => $userId) {
			// Not using the ICloudIdManager as that would run a search for each contact to find the display name in the address book
			$matches[$normalizedNumberToKey[$phone]] = $userId . '@' . $cloudUrl;
			$this->knownUserService->storeIsKnownToUser($knownTo, $userId);
		}

		return new DataResponse($matches);
	}

	/**
	 * @throws OCSException
	 */
	private function createNewUserId(): string {
		$attempts = 0;
		do {
			$uidCandidate = $this->secureRandom->generate(10, ISecureRandom::CHAR_HUMAN_READABLE);
			if (!$this->userManager->userExists($uidCandidate)) {
				return $uidCandidate;
			}
			$attempts++;
		} while ($attempts < 10);
		throw new OCSException('Could not create non-existing user id', 111);
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoAdminRequired
	 *
	 * Create a new user
	 *
	 * @param string $userid ID of the user
	 * @param string $password Password of the user
	 * @param string $displayName Display name of the user
	 * @param string $email Email of the user
	 * @param string[] $groups Groups of the user
	 * @param string[] $subadmin Groups where the user is subadmin
	 * @param string $quota Quota of the user
	 * @param string $language Language of the user
	 * @param ?string $manager Manager of the user
	 * @return DataResponse<Http::STATUS_OK, array{id: string}, array{}>
	 * @throws OCSException
	 * @throws OCSForbiddenException Missing permissions to make user subadmin
	 *
	 * 200: User added successfully
	 */
	public function addUser(
		string $userid,
		string $password = '',
		string $displayName = '',
		string $email = '',
		array $groups = [],
		array $subadmin = [],
		string $quota = '',
		string $language = '',
		?string $manager = null,
	): DataResponse {
		$user = $this->userSession->getUser();
		$isAdmin = $this->groupManager->isAdmin($user->getUID());
		$subAdminManager = $this->groupManager->getSubAdmin();

		if (empty($userid) && $this->config->getAppValue('core', 'newUser.generateUserID', 'no') === 'yes') {
			$userid = $this->createNewUserId();
		}

		if ($this->userManager->userExists($userid)) {
			$this->logger->error('Failed addUser attempt: User already exists.', ['app' => 'ocs_api']);
			throw new OCSException($this->l10nFactory->get('provisioning_api')->t('User already exists'), 102);
		}

		if ($groups !== []) {
			foreach ($groups as $group) {
				if (!$this->groupManager->groupExists($group)) {
					throw new OCSException('group ' . $group . ' does not exist', 104);
				}
				if (!$isAdmin && !$subAdminManager->isSubAdminOfGroup($user, $this->groupManager->get($group))) {
					throw new OCSException('insufficient privileges for group ' . $group, 105);
				}
			}
		} else {
			if (!$isAdmin) {
				throw new OCSException('no group specified (required for subadmins)', 106);
			}
		}

		$subadminGroups = [];
		if ($subadmin !== []) {
			foreach ($subadmin as $groupid) {
				$group = $this->groupManager->get($groupid);
				// Check if group exists
				if ($group === null) {
					throw new OCSException('Subadmin group does not exist', 102);
				}
				// Check if trying to make subadmin of admin group
				if ($group->getGID() === 'admin') {
					throw new OCSException('Cannot create subadmins for admin group', 103);
				}
				// Check if has permission to promote subadmins
				if (!$subAdminManager->isSubAdminOfGroup($user, $group) && !$isAdmin) {
					throw new OCSForbiddenException('No permissions to promote subadmins');
				}
				$subadminGroups[] = $group;
			}
		}

		$generatePasswordResetToken = false;
		if (strlen($password) > IUserManager::MAX_PASSWORD_LENGTH) {
			throw new OCSException('Invalid password value', 101);
		}
		if ($password === '') {
			if ($email === '') {
				throw new OCSException('To send a password link to the user an email address is required.', 108);
			}

			$passwordEvent = new GenerateSecurePasswordEvent();
			$this->eventDispatcher->dispatchTyped($passwordEvent);

			$password = $passwordEvent->getPassword();
			if ($password === null) {
				// Fallback: ensure to pass password_policy in any case
				$password = $this->secureRandom->generate(10)
					. $this->secureRandom->generate(1, ISecureRandom::CHAR_UPPER)
					. $this->secureRandom->generate(1, ISecureRandom::CHAR_LOWER)
					. $this->secureRandom->generate(1, ISecureRandom::CHAR_DIGITS)
					. $this->secureRandom->generate(1, ISecureRandom::CHAR_SYMBOLS);
			}
			$generatePasswordResetToken = true;
		}

		if ($email === '' && $this->config->getAppValue('core', 'newUser.requireEmail', 'no') === 'yes') {
			throw new OCSException('Required email address was not provided', 110);
		}

		try {
			$newUser = $this->userManager->createUser($userid, $password);
			$this->logger->info('Successful addUser call with userid: ' . $userid, ['app' => 'ocs_api']);

			foreach ($groups as $group) {
				$this->groupManager->get($group)->addUser($newUser);
				$this->logger->info('Added userid ' . $userid . ' to group ' . $group, ['app' => 'ocs_api']);
			}
			foreach ($subadminGroups as $group) {
				$subAdminManager->createSubAdmin($newUser, $group);
			}

			if ($displayName !== '') {
				try {
					$this->editUser($userid, self::USER_FIELD_DISPLAYNAME, $displayName);
				} catch (OCSException $e) {
					if ($newUser instanceof IUser) {
						$newUser->delete();
					}
					throw $e;
				}
			}

			if ($quota !== '') {
				$this->editUser($userid, self::USER_FIELD_QUOTA, $quota);
			}

			if ($language !== '') {
				$this->editUser($userid, self::USER_FIELD_LANGUAGE, $language);
			}

			/**
			 * null -> nothing sent
			 * '' -> unset manager
			 * else -> set manager
			 */
			if ($manager !== null) {
				$this->editUser($userid, self::USER_FIELD_MANAGER, $manager);
			}

			// Send new user mail only if a mail is set
			if ($email !== '') {
				$newUser->setEMailAddress($email);
				if ($this->config->getAppValue('core', 'newUser.sendEmail', 'yes') === 'yes') {
					try {
						$emailTemplate = $this->newUserMailHelper->generateTemplate($newUser, $generatePasswordResetToken);
						$this->newUserMailHelper->sendMail($newUser, $emailTemplate);
					} catch (\Exception $e) {
						// Mail could be failing hard or just be plain not configured
						// Logging error as it is the hardest of the two
						$this->logger->error(
							"Unable to send the invitation mail to $email",
							[
								'app' => 'ocs_api',
								'exception' => $e,
							]
						);
					}
				}
			}

			return new DataResponse(['id' => $userid]);
		} catch (HintException $e) {
			$this->logger->warning(
				'Failed addUser attempt with hint exception.',
				[
					'app' => 'ocs_api',
					'exception' => $e,
				]
			);
			throw new OCSException($e->getHint(), 107);
		} catch (OCSException $e) {
			$this->logger->warning(
				'Failed addUser attempt with ocs exception.',
				[
					'app' => 'ocs_api',
					'exception' => $e,
				]
			);
			throw $e;
		} catch (InvalidArgumentException $e) {
			$this->logger->error(
				'Failed addUser attempt with invalid argument exception.',
				[
					'app' => 'ocs_api',
					'exception' => $e,
				]
			);
			throw new OCSException($e->getMessage(), 101);
		} catch (\Exception $e) {
			$this->logger->error(
				'Failed addUser attempt with exception.',
				[
					'app' => 'ocs_api',
					'exception' => $e
				]
			);
			throw new OCSException('Bad request', 101);
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * Get the details of a user
	 *
	 * @param string $userId ID of the user
	 * @return DataResponse<Http::STATUS_OK, Provisioning_APIUserDetails, array{}>
	 * @throws OCSException
	 *
	 * 200: User returned
	 */
	public function getUser(string $userId): DataResponse {
		$includeScopes = false;
		$currentUser = $this->userSession->getUser();
		if ($currentUser && $currentUser->getUID() === $userId) {
			$includeScopes = true;
		}

		$data = $this->getUserData($userId, $includeScopes);
		// getUserData returns null if not enough permissions
		if ($data === null) {
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}
		return new DataResponse($data);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * Get the details of the current user
	 *
	 * @return DataResponse<Http::STATUS_OK, Provisioning_APIUserDetails, array{}>
	 * @throws OCSException
	 *
	 * 200: Current user returned
	 */
	public function getCurrentUser(): DataResponse {
		$user = $this->userSession->getUser();
		if ($user) {
			/** @var Provisioning_APIUserDetails $data */
			$data = $this->getUserData($user->getUID(), true);
			return new DataResponse($data);
		}

		throw new OCSException('', OCSController::RESPOND_UNAUTHORISED);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * Get a list of fields that are editable for the current user
	 *
	 * @return DataResponse<Http::STATUS_OK, string[], array{}>
	 * @throws OCSException
	 *
	 * 200: Editable fields returned
	 */
	public function getEditableFields(): DataResponse {
		$currentLoggedInUser = $this->userSession->getUser();
		if (!$currentLoggedInUser instanceof IUser) {
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}

		return $this->getEditableFieldsForUser($currentLoggedInUser->getUID());
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * Get a list of fields that are editable for a user
	 *
	 * @param string $userId ID of the user
	 * @return DataResponse<Http::STATUS_OK, string[], array{}>
	 * @throws OCSException
	 *
	 * 200: Editable fields for user returned
	 */
	public function getEditableFieldsForUser(string $userId): DataResponse {
		$currentLoggedInUser = $this->userSession->getUser();
		if (!$currentLoggedInUser instanceof IUser) {
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}

		$permittedFields = [];

		if ($userId !== $currentLoggedInUser->getUID()) {
			$targetUser = $this->userManager->get($userId);
			if (!$targetUser instanceof IUser) {
				throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
			}

			$subAdminManager = $this->groupManager->getSubAdmin();
			if (
				!$this->groupManager->isAdmin($currentLoggedInUser->getUID())
				&& !$subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser)
			) {
				throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
			}
		} else {
			$targetUser = $currentLoggedInUser;
		}

		// Editing self (display, email)
		if ($this->config->getSystemValue('allow_user_to_change_display_name', true) !== false) {
			if (
				$targetUser->getBackend() instanceof ISetDisplayNameBackend
				|| $targetUser->getBackend()->implementsActions(Backend::SET_DISPLAYNAME)
			) {
				$permittedFields[] = IAccountManager::PROPERTY_DISPLAYNAME;
			}
			$permittedFields[] = IAccountManager::PROPERTY_EMAIL;
		}

		$permittedFields[] = IAccountManager::COLLECTION_EMAIL;
		$permittedFields[] = IAccountManager::PROPERTY_PHONE;
		$permittedFields[] = IAccountManager::PROPERTY_ADDRESS;
		$permittedFields[] = IAccountManager::PROPERTY_WEBSITE;
		$permittedFields[] = IAccountManager::PROPERTY_TWITTER;
		$permittedFields[] = IAccountManager::PROPERTY_FEDIVERSE;
		$permittedFields[] = IAccountManager::PROPERTY_ORGANISATION;
		$permittedFields[] = IAccountManager::PROPERTY_ROLE;
		$permittedFields[] = IAccountManager::PROPERTY_HEADLINE;
		$permittedFields[] = IAccountManager::PROPERTY_BIOGRAPHY;
		$permittedFields[] = IAccountManager::PROPERTY_PROFILE_ENABLED;

		return new DataResponse($permittedFields);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @PasswordConfirmationRequired
	 * @UserRateThrottle(limit=5, period=60)
	 *
	 * Update multiple values of the user's details
	 *
	 * @param string $userId ID of the user
	 * @param string $collectionName Collection to update
	 * @param string $key Key that will be updated
	 * @param string $value New value for the key
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: User values edited successfully
	 */
	public function editUserMultiValue(
		string $userId,
		string $collectionName,
		string $key,
		string $value
	): DataResponse {
		$currentLoggedInUser = $this->userSession->getUser();
		if ($currentLoggedInUser === null) {
			throw new OCSException('', OCSController::RESPOND_UNAUTHORISED);
		}

		$targetUser = $this->userManager->get($userId);
		if ($targetUser === null) {
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}

		$subAdminManager = $this->groupManager->getSubAdmin();
		$isAdminOrSubadmin = $this->groupManager->isAdmin($currentLoggedInUser->getUID())
			|| $subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser);

		$permittedFields = [];
		if ($targetUser->getUID() === $currentLoggedInUser->getUID()) {
			// Editing self (display, email)
			$permittedFields[] = IAccountManager::COLLECTION_EMAIL;
			$permittedFields[] = IAccountManager::COLLECTION_EMAIL . self::SCOPE_SUFFIX;
		} else {
			// Check if admin / subadmin
			if ($isAdminOrSubadmin) {
				// They have permissions over the user
				$permittedFields[] = IAccountManager::COLLECTION_EMAIL;
			} else {
				// No rights
				throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
			}
		}

		// Check if permitted to edit this field
		if (!in_array($collectionName, $permittedFields)) {
			throw new OCSException('', 103);
		}

		switch ($collectionName) {
			case IAccountManager::COLLECTION_EMAIL:
				$userAccount = $this->accountManager->getAccount($targetUser);
				$mailCollection = $userAccount->getPropertyCollection(IAccountManager::COLLECTION_EMAIL);
				$mailCollection->removePropertyByValue($key);
				if ($value !== '') {
					$mailCollection->addPropertyWithDefaults($value);
					$property = $mailCollection->getPropertyByValue($key);
					if ($isAdminOrSubadmin && $property) {
						// admin set mails are auto-verified
						$property->setLocallyVerified(IAccountManager::VERIFIED);
					}
				}
				$this->accountManager->updateAccount($userAccount);
				break;

			case IAccountManager::COLLECTION_EMAIL . self::SCOPE_SUFFIX:
				$userAccount = $this->accountManager->getAccount($targetUser);
				$mailCollection = $userAccount->getPropertyCollection(IAccountManager::COLLECTION_EMAIL);
				$targetProperty = null;
				foreach ($mailCollection->getProperties() as $property) {
					if ($property->getValue() === $key) {
						$targetProperty = $property;
						break;
					}
				}
				if ($targetProperty instanceof IAccountProperty) {
					try {
						$targetProperty->setScope($value);
						$this->accountManager->updateAccount($userAccount);
					} catch (InvalidArgumentException $e) {
						throw new OCSException('', 102);
					}
				} else {
					throw new OCSException('', 102);
				}
				break;

			default:
				throw new OCSException('', 103);
		}
		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @PasswordConfirmationRequired
	 * @UserRateThrottle(limit=50, period=600)
	 *
	 * Update a value of the user's details
	 *
	 * @param string $userId ID of the user
	 * @param string $key Key that will be updated
	 * @param string $value New value for the key
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: User value edited successfully
	 */
	public function editUser(string $userId, string $key, string $value): DataResponse {
		$currentLoggedInUser = $this->userSession->getUser();

		$targetUser = $this->userManager->get($userId);
		if ($targetUser === null) {
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}

		$permittedFields = [];
		if ($targetUser->getUID() === $currentLoggedInUser->getUID()) {
			// Editing self (display, email)
			if ($this->config->getSystemValue('allow_user_to_change_display_name', true) !== false) {
				if (
					$targetUser->getBackend() instanceof ISetDisplayNameBackend
					|| $targetUser->getBackend()->implementsActions(Backend::SET_DISPLAYNAME)
				) {
					$permittedFields[] = self::USER_FIELD_DISPLAYNAME;
					$permittedFields[] = IAccountManager::PROPERTY_DISPLAYNAME;
				}
				$permittedFields[] = IAccountManager::PROPERTY_EMAIL;
			}

			$permittedFields[] = IAccountManager::PROPERTY_DISPLAYNAME . self::SCOPE_SUFFIX;
			$permittedFields[] = IAccountManager::PROPERTY_EMAIL . self::SCOPE_SUFFIX;

			$permittedFields[] = IAccountManager::COLLECTION_EMAIL;

			$permittedFields[] = self::USER_FIELD_PASSWORD;
			$permittedFields[] = self::USER_FIELD_NOTIFICATION_EMAIL;
			if (
				$this->config->getSystemValue('force_language', false) === false ||
				$this->groupManager->isAdmin($currentLoggedInUser->getUID())
			) {
				$permittedFields[] = self::USER_FIELD_LANGUAGE;
			}

			if (
				$this->config->getSystemValue('force_locale', false) === false ||
				$this->groupManager->isAdmin($currentLoggedInUser->getUID())
			) {
				$permittedFields[] = self::USER_FIELD_LOCALE;
			}

			$permittedFields[] = IAccountManager::PROPERTY_PHONE;
			$permittedFields[] = IAccountManager::PROPERTY_ADDRESS;
			$permittedFields[] = IAccountManager::PROPERTY_WEBSITE;
			$permittedFields[] = IAccountManager::PROPERTY_TWITTER;
			$permittedFields[] = IAccountManager::PROPERTY_FEDIVERSE;
			$permittedFields[] = IAccountManager::PROPERTY_ORGANISATION;
			$permittedFields[] = IAccountManager::PROPERTY_ROLE;
			$permittedFields[] = IAccountManager::PROPERTY_HEADLINE;
			$permittedFields[] = IAccountManager::PROPERTY_BIOGRAPHY;
			$permittedFields[] = IAccountManager::PROPERTY_PROFILE_ENABLED;
			$permittedFields[] = IAccountManager::PROPERTY_PHONE . self::SCOPE_SUFFIX;
			$permittedFields[] = IAccountManager::PROPERTY_ADDRESS . self::SCOPE_SUFFIX;
			$permittedFields[] = IAccountManager::PROPERTY_WEBSITE . self::SCOPE_SUFFIX;
			$permittedFields[] = IAccountManager::PROPERTY_TWITTER . self::SCOPE_SUFFIX;
			$permittedFields[] = IAccountManager::PROPERTY_FEDIVERSE . self::SCOPE_SUFFIX;
			$permittedFields[] = IAccountManager::PROPERTY_ORGANISATION . self::SCOPE_SUFFIX;
			$permittedFields[] = IAccountManager::PROPERTY_ROLE . self::SCOPE_SUFFIX;
			$permittedFields[] = IAccountManager::PROPERTY_HEADLINE . self::SCOPE_SUFFIX;
			$permittedFields[] = IAccountManager::PROPERTY_BIOGRAPHY . self::SCOPE_SUFFIX;
			$permittedFields[] = IAccountManager::PROPERTY_PROFILE_ENABLED . self::SCOPE_SUFFIX;

			$permittedFields[] = IAccountManager::PROPERTY_AVATAR . self::SCOPE_SUFFIX;

			// If admin they can edit their own quota and manager
			if ($this->groupManager->isAdmin($currentLoggedInUser->getUID())) {
				$permittedFields[] = self::USER_FIELD_QUOTA;
				$permittedFields[] = self::USER_FIELD_MANAGER;
			}
		} else {
			// Check if admin / subadmin
			$subAdminManager = $this->groupManager->getSubAdmin();
			if (
				$this->groupManager->isAdmin($currentLoggedInUser->getUID())
				|| $subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser)
			) {
				// They have permissions over the user
				if (
					$targetUser->getBackend() instanceof ISetDisplayNameBackend
					|| $targetUser->getBackend()->implementsActions(Backend::SET_DISPLAYNAME)
				) {
					$permittedFields[] = self::USER_FIELD_DISPLAYNAME;
					$permittedFields[] = IAccountManager::PROPERTY_DISPLAYNAME;
				}
				$permittedFields[] = IAccountManager::PROPERTY_EMAIL;
				$permittedFields[] = IAccountManager::COLLECTION_EMAIL;
				$permittedFields[] = self::USER_FIELD_PASSWORD;
				$permittedFields[] = self::USER_FIELD_LANGUAGE;
				$permittedFields[] = self::USER_FIELD_LOCALE;
				$permittedFields[] = IAccountManager::PROPERTY_PHONE;
				$permittedFields[] = IAccountManager::PROPERTY_ADDRESS;
				$permittedFields[] = IAccountManager::PROPERTY_WEBSITE;
				$permittedFields[] = IAccountManager::PROPERTY_TWITTER;
				$permittedFields[] = IAccountManager::PROPERTY_FEDIVERSE;
				$permittedFields[] = IAccountManager::PROPERTY_ORGANISATION;
				$permittedFields[] = IAccountManager::PROPERTY_ROLE;
				$permittedFields[] = IAccountManager::PROPERTY_HEADLINE;
				$permittedFields[] = IAccountManager::PROPERTY_BIOGRAPHY;
				$permittedFields[] = IAccountManager::PROPERTY_PROFILE_ENABLED;
				$permittedFields[] = self::USER_FIELD_QUOTA;
				$permittedFields[] = self::USER_FIELD_NOTIFICATION_EMAIL;
				$permittedFields[] = self::USER_FIELD_MANAGER;
			} else {
				// No rights
				throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
			}
		}
		// Check if permitted to edit this field
		if (!in_array($key, $permittedFields)) {
			throw new OCSException('', 113);
		}
		// Process the edit
		switch ($key) {
			case self::USER_FIELD_DISPLAYNAME:
			case IAccountManager::PROPERTY_DISPLAYNAME:
				try {
					$targetUser->setDisplayName($value);
				} catch (InvalidArgumentException $e) {
					throw new OCSException($e->getMessage(), 101);
				}
				break;
			case self::USER_FIELD_QUOTA:
				$quota = $value;
				if ($quota !== 'none' && $quota !== 'default') {
					if (is_numeric($quota)) {
						$quota = (float) $quota;
					} else {
						$quota = \OCP\Util::computerFileSize($quota);
					}
					if ($quota === false) {
						throw new OCSException('Invalid quota value ' . $value, 102);
					}
					if ($quota === -1) {
						$quota = 'none';
					} else {
						$maxQuota = (int) $this->config->getAppValue('files', 'max_quota', '-1');
						if ($maxQuota !== -1 && $quota > $maxQuota) {
							throw new OCSException('Invalid quota value. ' . $value . ' is exceeding the maximum quota', 102);
						}
						$quota = \OCP\Util::humanFileSize($quota);
					}
				}
				// no else block because quota can be set to 'none' in previous if
				if ($quota === 'none') {
					$allowUnlimitedQuota = $this->config->getAppValue('files', 'allow_unlimited_quota', '1') === '1';
					if (!$allowUnlimitedQuota) {
						throw new OCSException('Unlimited quota is forbidden on this instance', 102);
					}
				}
				$targetUser->setQuota($quota);
				break;
			case self::USER_FIELD_MANAGER:
				$targetUser->setManagerUids([$value]);
				break;
			case self::USER_FIELD_PASSWORD:
				try {
					if (strlen($value) > IUserManager::MAX_PASSWORD_LENGTH) {
						throw new OCSException('Invalid password value', 102);
					}
					if (!$targetUser->canChangePassword()) {
						throw new OCSException('Setting the password is not supported by the users backend', 103);
					}
					$targetUser->setPassword($value);
				} catch (HintException $e) { // password policy error
					throw new OCSException($e->getMessage(), 107);
				}
				break;
			case self::USER_FIELD_LANGUAGE:
				$languagesCodes = $this->l10nFactory->findAvailableLanguages();
				if (!in_array($value, $languagesCodes, true) && $value !== 'en') {
					throw new OCSException('Invalid language', 102);
				}
				$this->config->setUserValue($targetUser->getUID(), 'core', 'lang', $value);
				break;
			case self::USER_FIELD_LOCALE:
				if (!$this->l10nFactory->localeExists($value)) {
					throw new OCSException('Invalid locale', 102);
				}
				$this->config->setUserValue($targetUser->getUID(), 'core', 'locale', $value);
				break;
			case self::USER_FIELD_NOTIFICATION_EMAIL:
				$success = false;
				if ($value === '' || filter_var($value, FILTER_VALIDATE_EMAIL)) {
					try {
						$targetUser->setPrimaryEMailAddress($value);
						$success = true;
					} catch (InvalidArgumentException $e) {
						$this->logger->info(
							'Cannot set primary email, because provided address is not verified',
							[
								'app' => 'provisioning_api',
								'exception' => $e,
							]
						);
					}
				}
				if (!$success) {
					throw new OCSException('', 101);
				}
				break;
			case IAccountManager::PROPERTY_EMAIL:
				if (filter_var($value, FILTER_VALIDATE_EMAIL) || $value === '') {
					$targetUser->setEMailAddress($value);
				} else {
					throw new OCSException('', 101);
				}
				break;
			case IAccountManager::COLLECTION_EMAIL:
				if (filter_var($value, FILTER_VALIDATE_EMAIL) && $value !== $targetUser->getSystemEMailAddress()) {
					$userAccount = $this->accountManager->getAccount($targetUser);
					$mailCollection = $userAccount->getPropertyCollection(IAccountManager::COLLECTION_EMAIL);

					if ($mailCollection->getPropertyByValue($value)) {
						throw new OCSException('', 101);
					}

					$mailCollection->addPropertyWithDefaults($value);
					$this->accountManager->updateAccount($userAccount);
				} else {
					throw new OCSException('', 101);
				}
				break;
			case IAccountManager::PROPERTY_PHONE:
			case IAccountManager::PROPERTY_ADDRESS:
			case IAccountManager::PROPERTY_WEBSITE:
			case IAccountManager::PROPERTY_TWITTER:
			case IAccountManager::PROPERTY_FEDIVERSE:
			case IAccountManager::PROPERTY_ORGANISATION:
			case IAccountManager::PROPERTY_ROLE:
			case IAccountManager::PROPERTY_HEADLINE:
			case IAccountManager::PROPERTY_BIOGRAPHY:
				$userAccount = $this->accountManager->getAccount($targetUser);
				try {
					$userProperty = $userAccount->getProperty($key);
					if ($userProperty->getValue() !== $value) {
						try {
							$userProperty->setValue($value);
							if ($userProperty->getName() === IAccountManager::PROPERTY_PHONE) {
								$this->knownUserService->deleteByContactUserId($targetUser->getUID());
							}
						} catch (InvalidArgumentException $e) {
							throw new OCSException('Invalid ' . $e->getMessage(), 101);
						}
					}
				} catch (PropertyDoesNotExistException $e) {
					$userAccount->setProperty($key, $value, IAccountManager::SCOPE_PRIVATE, IAccountManager::NOT_VERIFIED);
				}
				try {
					$this->accountManager->updateAccount($userAccount);
				} catch (InvalidArgumentException $e) {
					throw new OCSException('Invalid ' . $e->getMessage(), 101);
				}
				break;
			case IAccountManager::PROPERTY_PROFILE_ENABLED:
				$userAccount = $this->accountManager->getAccount($targetUser);
				try {
					$userProperty = $userAccount->getProperty($key);
					if ($userProperty->getValue() !== $value) {
						$userProperty->setValue($value);
					}
				} catch (PropertyDoesNotExistException $e) {
					$userAccount->setProperty($key, $value, IAccountManager::SCOPE_LOCAL, IAccountManager::NOT_VERIFIED);
				}
				$this->accountManager->updateAccount($userAccount);
				break;
			case IAccountManager::PROPERTY_DISPLAYNAME . self::SCOPE_SUFFIX:
			case IAccountManager::PROPERTY_EMAIL . self::SCOPE_SUFFIX:
			case IAccountManager::PROPERTY_PHONE . self::SCOPE_SUFFIX:
			case IAccountManager::PROPERTY_ADDRESS . self::SCOPE_SUFFIX:
			case IAccountManager::PROPERTY_WEBSITE . self::SCOPE_SUFFIX:
			case IAccountManager::PROPERTY_TWITTER . self::SCOPE_SUFFIX:
			case IAccountManager::PROPERTY_FEDIVERSE . self::SCOPE_SUFFIX:
			case IAccountManager::PROPERTY_ORGANISATION . self::SCOPE_SUFFIX:
			case IAccountManager::PROPERTY_ROLE . self::SCOPE_SUFFIX:
			case IAccountManager::PROPERTY_HEADLINE . self::SCOPE_SUFFIX:
			case IAccountManager::PROPERTY_BIOGRAPHY . self::SCOPE_SUFFIX:
			case IAccountManager::PROPERTY_PROFILE_ENABLED . self::SCOPE_SUFFIX:
			case IAccountManager::PROPERTY_AVATAR . self::SCOPE_SUFFIX:
				$propertyName = substr($key, 0, strlen($key) - strlen(self::SCOPE_SUFFIX));
				$userAccount = $this->accountManager->getAccount($targetUser);
				$userProperty = $userAccount->getProperty($propertyName);
				if ($userProperty->getScope() !== $value) {
					try {
						$userProperty->setScope($value);
						$this->accountManager->updateAccount($userAccount);
					} catch (InvalidArgumentException $e) {
						throw new OCSException('Invalid ' . $e->getMessage(), 101);
					}
				}
				break;
			default:
				throw new OCSException('', 113);
		}
		return new DataResponse();
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoAdminRequired
	 *
	 * Wipe all devices of a user
	 *
	 * @param string $userId ID of the user
	 *
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 *
	 * @throws OCSException
	 *
	 * 200: Wiped all user devices successfully
	 */
	public function wipeUserDevices(string $userId): DataResponse {
		/** @var IUser $currentLoggedInUser */
		$currentLoggedInUser = $this->userSession->getUser();

		$targetUser = $this->userManager->get($userId);

		if ($targetUser === null) {
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}

		if ($targetUser->getUID() === $currentLoggedInUser->getUID()) {
			throw new OCSException('', 101);
		}

		// If not permitted
		$subAdminManager = $this->groupManager->getSubAdmin();
		if (!$this->groupManager->isAdmin($currentLoggedInUser->getUID()) && !$subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser)) {
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}

		$this->remoteWipe->markAllTokensForWipe($targetUser);

		return new DataResponse();
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoAdminRequired
	 *
	 * Delete a user
	 *
	 * @param string $userId ID of the user
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: User deleted successfully
	 */
	public function deleteUser(string $userId): DataResponse {
		$currentLoggedInUser = $this->userSession->getUser();

		$targetUser = $this->userManager->get($userId);

		if ($targetUser === null) {
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}

		if ($targetUser->getUID() === $currentLoggedInUser->getUID()) {
			throw new OCSException('', 101);
		}

		// If not permitted
		$subAdminManager = $this->groupManager->getSubAdmin();
		if (!$this->groupManager->isAdmin($currentLoggedInUser->getUID()) && !$subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser)) {
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}

		// Go ahead with the delete
		if ($targetUser->delete()) {
			return new DataResponse();
		} else {
			throw new OCSException('', 101);
		}
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoAdminRequired
	 *
	 * Disable a user
	 *
	 * @param string $userId ID of the user
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: User disabled successfully
	 */
	public function disableUser(string $userId): DataResponse {
		return $this->setEnabled($userId, false);
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoAdminRequired
	 *
	 * Enable a user
	 *
	 * @param string $userId ID of the user
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: User enabled successfully
	 */
	public function enableUser(string $userId): DataResponse {
		return $this->setEnabled($userId, true);
	}

	/**
	 * @param string $userId
	 * @param bool $value
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 */
	private function setEnabled(string $userId, bool $value): DataResponse {
		$currentLoggedInUser = $this->userSession->getUser();

		$targetUser = $this->userManager->get($userId);
		if ($targetUser === null || $targetUser->getUID() === $currentLoggedInUser->getUID()) {
			throw new OCSException('', 101);
		}

		// If not permitted
		$subAdminManager = $this->groupManager->getSubAdmin();
		if (!$this->groupManager->isAdmin($currentLoggedInUser->getUID()) && !$subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser)) {
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}

		// enable/disable the user now
		$targetUser->setEnabled($value);
		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * Get a list of groups the user belongs to
	 *
	 * @param string $userId ID of the user
	 * @return DataResponse<Http::STATUS_OK, array{groups: string[]}, array{}>
	 * @throws OCSException
	 *
	 * 200: Users groups returned
	 */
	public function getUsersGroups(string $userId): DataResponse {
		$loggedInUser = $this->userSession->getUser();

		$targetUser = $this->userManager->get($userId);
		if ($targetUser === null) {
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}

		if ($targetUser->getUID() === $loggedInUser->getUID() || $this->groupManager->isAdmin($loggedInUser->getUID())) {
			// Self lookup or admin lookup
			return new DataResponse([
				'groups' => $this->groupManager->getUserGroupIds($targetUser)
			]);
		} else {
			$subAdminManager = $this->groupManager->getSubAdmin();

			// Looking up someone else
			if ($subAdminManager->isUserAccessible($loggedInUser, $targetUser)) {
				// Return the group that the method caller is subadmin of for the user in question
				/** @var IGroup[] $getSubAdminsGroups */
				$getSubAdminsGroups = $subAdminManager->getSubAdminsGroups($loggedInUser);
				foreach ($getSubAdminsGroups as $key => $group) {
					$getSubAdminsGroups[$key] = $group->getGID();
				}
				/** @var string[] $groups */
				$groups = array_intersect(
					$getSubAdminsGroups,
					$this->groupManager->getUserGroupIds($targetUser)
				);
				return new DataResponse(['groups' => $groups]);
			} else {
				// Not permitted
				throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
			}
		}
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoAdminRequired
	 *
	 * Add a user to a group
	 *
	 * @param string $userId ID of the user
	 * @param string $groupid ID of the group
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: User added to group successfully
	 */
	public function addToGroup(string $userId, string $groupid = ''): DataResponse {
		if ($groupid === '') {
			throw new OCSException('', 101);
		}

		$group = $this->groupManager->get($groupid);
		$targetUser = $this->userManager->get($userId);
		if ($group === null) {
			throw new OCSException('', 102);
		}
		if ($targetUser === null) {
			throw new OCSException('', 103);
		}

		// If they're not an admin, check they are a subadmin of the group in question
		$loggedInUser = $this->userSession->getUser();
		$subAdminManager = $this->groupManager->getSubAdmin();
		if (!$this->groupManager->isAdmin($loggedInUser->getUID()) && !$subAdminManager->isSubAdminOfGroup($loggedInUser, $group)) {
			throw new OCSException('', 104);
		}

		// Add user to group
		$group->addUser($targetUser);
		return new DataResponse();
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoAdminRequired
	 *
	 * Remove a user from a group
	 *
	 * @param string $userId ID of the user
	 * @param string $groupid ID of the group
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: User removed from group successfully
	 */
	public function removeFromGroup(string $userId, string $groupid): DataResponse {
		$loggedInUser = $this->userSession->getUser();

		if ($groupid === null || trim($groupid) === '') {
			throw new OCSException('', 101);
		}

		$group = $this->groupManager->get($groupid);
		if ($group === null) {
			throw new OCSException('', 102);
		}

		$targetUser = $this->userManager->get($userId);
		if ($targetUser === null) {
			throw new OCSException('', 103);
		}

		// If they're not an admin, check they are a subadmin of the group in question
		$subAdminManager = $this->groupManager->getSubAdmin();
		if (!$this->groupManager->isAdmin($loggedInUser->getUID()) && !$subAdminManager->isSubAdminOfGroup($loggedInUser, $group)) {
			throw new OCSException('', 104);
		}

		// Check they aren't removing themselves from 'admin' or their 'subadmin; group
		if ($targetUser->getUID() === $loggedInUser->getUID()) {
			if ($this->groupManager->isAdmin($loggedInUser->getUID())) {
				if ($group->getGID() === 'admin') {
					throw new OCSException('Cannot remove yourself from the admin group', 105);
				}
			} else {
				// Not an admin, so the user must be a subadmin of this group, but that is not allowed.
				throw new OCSException('Cannot remove yourself from this group as you are a SubAdmin', 105);
			}
		} elseif (!$this->groupManager->isAdmin($loggedInUser->getUID())) {
			/** @var IGroup[] $subAdminGroups */
			$subAdminGroups = $subAdminManager->getSubAdminsGroups($loggedInUser);
			$subAdminGroups = array_map(function (IGroup $subAdminGroup) {
				return $subAdminGroup->getGID();
			}, $subAdminGroups);
			$userGroups = $this->groupManager->getUserGroupIds($targetUser);
			$userSubAdminGroups = array_intersect($subAdminGroups, $userGroups);

			if (count($userSubAdminGroups) <= 1) {
				// Subadmin must not be able to remove a user from all their subadmin groups.
				throw new OCSException('Not viable to remove user from the last group you are SubAdmin of', 105);
			}
		}

		// Remove user from group
		$group->removeUser($targetUser);
		return new DataResponse();
	}

	/**
	 * @PasswordConfirmationRequired
	 *
	 * Make a user a subadmin of a group
	 *
	 * @param string $userId ID of the user
	 * @param string $groupid ID of the group
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: User added as group subadmin successfully
	 */
	public function addSubAdmin(string $userId, string $groupid): DataResponse {
		$group = $this->groupManager->get($groupid);
		$user = $this->userManager->get($userId);

		// Check if the user exists
		if ($user === null) {
			throw new OCSException('User does not exist', 101);
		}
		// Check if group exists
		if ($group === null) {
			throw new OCSException('Group does not exist', 102);
		}
		// Check if trying to make subadmin of admin group
		if ($group->getGID() === 'admin') {
			throw new OCSException('Cannot create subadmins for admin group', 103);
		}

		$subAdminManager = $this->groupManager->getSubAdmin();

		// We cannot be subadmin twice
		if ($subAdminManager->isSubAdminOfGroup($user, $group)) {
			return new DataResponse();
		}
		// Go
		$subAdminManager->createSubAdmin($user, $group);
		return new DataResponse();
	}

	/**
	 * @PasswordConfirmationRequired
	 *
	 * Remove a user from the subadmins of a group
	 *
	 * @param string $userId ID of the user
	 * @param string $groupid ID of the group
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: User removed as group subadmin successfully
	 */
	public function removeSubAdmin(string $userId, string $groupid): DataResponse {
		$group = $this->groupManager->get($groupid);
		$user = $this->userManager->get($userId);
		$subAdminManager = $this->groupManager->getSubAdmin();

		// Check if the user exists
		if ($user === null) {
			throw new OCSException('User does not exist', 101);
		}
		// Check if the group exists
		if ($group === null) {
			throw new OCSException('Group does not exist', 101);
		}
		// Check if they are a subadmin of this said group
		if (!$subAdminManager->isSubAdminOfGroup($user, $group)) {
			throw new OCSException('User is not a subadmin of this group', 102);
		}

		// Go
		$subAdminManager->deleteSubAdmin($user, $group);
		return new DataResponse();
	}

	/**
	 * Get the groups a user is a subadmin of
	 *
	 * @param string $userId ID if the user
	 * @return DataResponse<Http::STATUS_OK, string[], array{}>
	 * @throws OCSException
	 *
	 * 200: User subadmin groups returned
	 */
	public function getUserSubAdminGroups(string $userId): DataResponse {
		$groups = $this->getUserSubAdminGroupsData($userId);
		return new DataResponse($groups);
	}

	/**
	 * @NoAdminRequired
	 * @PasswordConfirmationRequired
	 *
	 * Resend the welcome message
	 *
	 * @param string $userId ID if the user
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: Resent welcome message successfully
	 */
	public function resendWelcomeMessage(string $userId): DataResponse {
		$currentLoggedInUser = $this->userSession->getUser();

		$targetUser = $this->userManager->get($userId);
		if ($targetUser === null) {
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}

		// Check if admin / subadmin
		$subAdminManager = $this->groupManager->getSubAdmin();
		if (
			!$subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser)
			&& !$this->groupManager->isAdmin($currentLoggedInUser->getUID())
		) {
			// No rights
			throw new OCSException('', OCSController::RESPOND_NOT_FOUND);
		}

		$email = $targetUser->getEMailAddress();
		if ($email === '' || $email === null) {
			throw new OCSException('Email address not available', 101);
		}

		try {
			$emailTemplate = $this->newUserMailHelper->generateTemplate($targetUser, false);
			$this->newUserMailHelper->sendMail($targetUser, $emailTemplate);
		} catch (\Exception $e) {
			$this->logger->error(
				"Can't send new user mail to $email",
				[
					'app' => 'settings',
					'exception' => $e,
				]
			);
			throw new OCSException('Sending email failed', 102);
		}

		return new DataResponse();
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Share20;

use ArrayIterator;
use OC\Core\AppInfo\ConfigLexicon;
use OC\Files\Mount\MoveableMount;
use OC\KnownUser\KnownUserService;
use OC\Share\Helper;
use OC\Share20\Exception\ProviderException;
use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\SharedStorage;
use OCA\ShareByMail\ShareByMailProvider;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IShareOwnerlessMount;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\HintException;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Security\Events\ValidatePasswordPolicyEvent;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use OCP\Security\PasswordContext;
use OCP\Share;
use OCP\Share\Events\BeforeShareDeletedEvent;
use OCP\Share\Events\ShareAcceptedEvent;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\Events\ShareDeletedFromSelfEvent;
use OCP\Share\Exceptions\AlreadySharedException;
use OCP\Share\Exceptions\GenericShareException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\Exceptions\ShareTokenException;
use OCP\Share\IManager;
use OCP\Share\IPartialShareProvider;
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;
use OCP\Share\IShareProvider;
use OCP\Share\IShareProviderSupportsAccept;
use OCP\Share\IShareProviderSupportsAllSharesInFolder;
use OCP\Share\IShareProviderWithNotification;
use Override;
use Psr\Log\LoggerInterface;

/**
 * This class is the communication hub for all sharing related operations.
 */
class Manager implements IManager {

	private ?IL10N $l;
	private LegacyHooks $legacyHooks;

	public function __construct(
		private LoggerInterface $logger,
		private IConfig $config,
		private ISecureRandom $secureRandom,
		private IHasher $hasher,
		private IMountManager $mountManager,
		private IGroupManager $groupManager,
		private IFactory $l10nFactory,
		private IProviderFactory $factory,
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
		private IEventDispatcher $dispatcher,
		private IUserSession $userSession,
		private KnownUserService $knownUserService,
		private ShareDisableChecker $shareDisableChecker,
		private IDateTimeZone $dateTimeZone,
		private IAppConfig $appConfig,
	) {
		$this->l = $this->l10nFactory->get('lib');
		// The constructor of LegacyHooks registers the listeners of share events
		// do not remove if those are not properly migrated
		$this->legacyHooks = new LegacyHooks($this->dispatcher);
	}

	/**
	 * Convert from a full share id to a tuple (providerId, shareId)
	 *
	 * @return string[]
	 */
	private function splitFullId(string $id): array {
		return explode(':', $id, 2);
	}

	/**
	 * Verify if a password meets all requirements
	 *
	 * @throws HintException
	 */
	protected function verifyPassword(?string $password): void {
		if ($password === null) {
			// No password is set, check if this is allowed.
			if ($this->shareApiLinkEnforcePassword()) {
				throw new \InvalidArgumentException($this->l->t('Passwords are enforced for link and mail shares'));
			}

			return;
		}

		// Let others verify the password
		try {
			$event = new ValidatePasswordPolicyEvent($password, PasswordContext::SHARING);
			$this->dispatcher->dispatchTyped($event);
		} catch (HintException $e) {
			/* Wrap in a 400 bad request error */
			throw new HintException($e->getMessage(), $e->getHint(), 400, $e);
		}
	}

	/**
	 * Check for generic requirements before creating a share
	 *
	 * @param IShare $share
	 * @throws \InvalidArgumentException
	 * @throws GenericShareException
	 *
	 * @suppress PhanUndeclaredClassMethod
	 */
	protected function generalCreateChecks(IShare $share, bool $isUpdate = false): void {
		if ($share->getShareType() === IShare::TYPE_USER) {
			// We expect a valid user as sharedWith for user shares
			if (!$this->userManager->userExists($share->getSharedWith())) {
				throw new \InvalidArgumentException($this->l->t('Share recipient is not a valid user'));
			}
		} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
			// We expect a valid group as sharedWith for group shares
			if (!$this->groupManager->groupExists($share->getSharedWith())) {
				throw new \InvalidArgumentException($this->l->t('Share recipient is not a valid group'));
			}
		} elseif ($share->getShareType() === IShare::TYPE_LINK) {
			// No check for TYPE_EMAIL here as we have a recipient for them
			if ($share->getSharedWith() !== null) {
				throw new \InvalidArgumentException($this->l->t('Share recipient should be empty'));
			}
		} elseif ($share->getShareType() === IShare::TYPE_EMAIL) {
			if ($share->getSharedWith() === null) {
				throw new \InvalidArgumentException($this->l->t('Share recipient should not be empty'));
			}
		} elseif ($share->getShareType() === IShare::TYPE_REMOTE) {
			if ($share->getSharedWith() === null) {
				throw new \InvalidArgumentException($this->l->t('Share recipient should not be empty'));
			}
		} elseif ($share->getShareType() === IShare::TYPE_REMOTE_GROUP) {
			if ($share->getSharedWith() === null) {
				throw new \InvalidArgumentException($this->l->t('Share recipient should not be empty'));
			}
		} elseif ($share->getShareType() === IShare::TYPE_CIRCLE) {
			$circle = \OCA\Circles\Api\v1\Circles::detailsCircle($share->getSharedWith());
			if ($circle === null) {
				throw new \InvalidArgumentException($this->l->t('Share recipient is not a valid circle'));
			}
		} elseif ($share->getShareType() !== IShare::TYPE_ROOM && $share->getShareType() !== IShare::TYPE_DECK) {
			// We cannot handle other types yet
			throw new \InvalidArgumentException($this->l->t('Unknown share type'));
		}

		// Verify the initiator of the share is set
		if ($share->getSharedBy() === null) {
			throw new \InvalidArgumentException($this->l->t('Share initiator must be set'));
		}

		// Cannot share with yourself
		if ($share->getShareType() === IShare::TYPE_USER
			&& $share->getSharedWith() === $share->getSharedBy()) {
			throw new \InvalidArgumentException($this->l->t('Cannot share with yourself'));
		}

		// The path should be set
		if ($share->getNode() === null) {
			throw new \InvalidArgumentException($this->l->t('Shared path must be set'));
		}

		// And it should be a file or a folder
		if (!($share->getNode() instanceof \OCP\Files\File)
			&& !($share->getNode() instanceof \OCP\Files\Folder)) {
			throw new \InvalidArgumentException($this->l->t('Shared path must be either a file or a folder'));
		}

		// And you cannot share your rootfolder
		if ($this->userManager->userExists($share->getSharedBy())) {
			$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());
		} else {
			$userFolder = $this->rootFolder->getUserFolder($share->getShareOwner());
		}
		if ($userFolder->getId() === $share->getNode()->getId()) {
			throw new \InvalidArgumentException($this->l->t('You cannot share your root folder'));
		}

		// Check if we actually have share permissions
		if (!$share->getNode()->isShareable()) {
			throw new GenericShareException($this->l->t('You are not allowed to share %s', [$share->getNode()->getName()]), code: 404);
		}

		// Permissions should be set
		if ($share->getPermissions() === null) {
			throw new \InvalidArgumentException($this->l->t('Valid permissions are required for sharing'));
		}

		// Permissions must be valid
		if ($share->getPermissions() < 0 || $share->getPermissions() > \OCP\Constants::PERMISSION_ALL) {
			throw new \InvalidArgumentException($this->l->t('Valid permissions are required for sharing'));
		}

		// Single file shares should never have delete or create permissions
		if (($share->getNode() instanceof File)
			&& (($share->getPermissions() & (\OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_DELETE)) !== 0)) {
			throw new \InvalidArgumentException($this->l->t('File shares cannot have create or delete permissions'));
		}

		$permissions = 0;
		$nodesForUser = $userFolder->getById($share->getNodeId());
		foreach ($nodesForUser as $node) {
			if ($node->getInternalPath() === '' && !$node->getMountPoint() instanceof MoveableMount) {
				// for the root of non-movable mount, the permissions we see if limited by the mount itself,
				// so we instead use the "raw" permissions from the storage
				$permissions |= $node->getStorage()->getPermissions('');
			} else {
				$permissions |= $node->getPermissions();
			}
		}

		// Check that we do not share with more permissions than we have
		if ($share->getPermissions() & ~$permissions) {
			$path = $userFolder->getRelativePath($share->getNode()->getPath());
			throw new GenericShareException($this->l->t('Cannot increase permissions of %s', [$path]), code: 404);
		}

		// Check that read permissions are always set
		// Link shares are allowed to have no read permissions to allow upload to hidden folders
		$noReadPermissionRequired = $share->getShareType() === IShare::TYPE_LINK
			|| $share->getShareType() === IShare::TYPE_EMAIL;
		if (!$noReadPermissionRequired
			&& ($share->getPermissions() & \OCP\Constants::PERMISSION_READ) === 0) {
			throw new \InvalidArgumentException($this->l->t('Shares need at least read permissions'));
		}

		if ($share->getNode() instanceof \OCP\Files\File) {
			if ($share->getPermissions() & \OCP\Constants::PERMISSION_DELETE) {
				throw new GenericShareException($this->l->t('Files cannot be shared with delete permissions'));
			}
			if ($share->getPermissions() & \OCP\Constants::PERMISSION_CREATE) {
				throw new GenericShareException($this->l->t('Files cannot be shared with create permissions'));
			}
		}
	}

	/**
	 * Validate if the expiration date fits the system settings
	 *
	 * @param IShare $share The share to validate the expiration date of
	 * @return IShare The modified share object
	 * @throws GenericShareException
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	protected function validateExpirationDateInternal(IShare $share): IShare {
		$isRemote = $share->getShareType() === IShare::TYPE_REMOTE || $share->getShareType() === IShare::TYPE_REMOTE_GROUP;

		$expirationDate = $share->getExpirationDate();

		if ($isRemote) {
			$defaultExpireDate = $this->shareApiRemoteDefaultExpireDate();
			$defaultExpireDays = $this->shareApiRemoteDefaultExpireDays();
			$configProp = 'remote_defaultExpDays';
			$isEnforced = $this->shareApiRemoteDefaultExpireDateEnforced();
		} else {
			$defaultExpireDate = $this->shareApiInternalDefaultExpireDate();
			$defaultExpireDays = $this->shareApiInternalDefaultExpireDays();
			$configProp = 'internal_defaultExpDays';
			$isEnforced = $this->shareApiInternalDefaultExpireDateEnforced();
		}

		// If $expirationDate is falsy, noExpirationDate is true and expiration not enforced
		// Then skip expiration date validation as null is accepted
		if (!$share->getNoExpirationDate() || $isEnforced) {
			if ($expirationDate !== null) {
				$expirationDate->setTimezone($this->dateTimeZone->getTimeZone());
				$expirationDate->setTime(0, 0, 0);

				$date = new \DateTime('now', $this->dateTimeZone->getTimeZone());
				$date->setTime(0, 0, 0);
				if ($date >= $expirationDate) {
					throw new GenericShareException($this->l->t('Expiration date is in the past'), code: 404);
				}
			}

			// If expiredate is empty set a default one if there is a default
			$fullId = null;
			try {
				$fullId = $share->getFullId();
			} catch (\UnexpectedValueException $e) {
				// This is a new share
			}

			if ($fullId === null && $expirationDate === null && $defaultExpireDate) {
				$expirationDate = new \DateTime('now', $this->dateTimeZone->getTimeZone());
				$expirationDate->setTime(0, 0, 0);
				$days = (int)$this->config->getAppValue('core', $configProp, (string)$defaultExpireDays);
				if ($days > $defaultExpireDays) {
					$days = $defaultExpireDays;
				}
				$expirationDate->add(new \DateInterval('P' . $days . 'D'));
			}

			// If we enforce the expiration date check that is does not exceed
			if ($isEnforced) {
				if (empty($expirationDate)) {
					throw new \InvalidArgumentException($this->l->t('Expiration date is enforced'));
				}

				$date = new \DateTime('now', $this->dateTimeZone->getTimeZone());
				$date->setTime(0, 0, 0);
				$date->add(new \DateInterval('P' . $defaultExpireDays . 'D'));
				if ($date < $expirationDate) {
					throw new GenericShareException($this->l->n('Cannot set expiration date more than %n day in the future', 'Cannot set expiration date more than %n days in the future', $defaultExpireDays), code: 404);
				}
			}
		}

		$accepted = true;
		$message = '';
		\OCP\Util::emitHook('\OC\Share', 'verifyExpirationDate', [
			'expirationDate' => &$expirationDate,
			'accepted' => &$accepted,
			'message' => &$message,
			'passwordSet' => $share->getPassword() !== null,
		]);

		if (!$accepted) {
			throw new \Exception($message);
		}

		$share->setExpirationDate($expirationDate);

		return $share;
	}

	/**
	 * Validate if the expiration date fits the system settings
	 *
	 * @param IShare $share The share to validate the expiration date of
	 * @return IShare The modified share object
	 * @throws GenericShareException
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	protected function validateExpirationDateLink(IShare $share): IShare {
		$expirationDate = $share->getExpirationDate();
		$isEnforced = $this->shareApiLinkDefaultExpireDateEnforced();

		// If $expirationDate is falsy, noExpirationDate is true and expiration not enforced
		// Then skip expiration date validation as null is accepted
		if (!($share->getNoExpirationDate() && !$isEnforced)) {
			if ($expirationDate !== null) {
				$expirationDate->setTimezone($this->dateTimeZone->getTimeZone());
				$expirationDate->setTime(0, 0, 0);

				$date = new \DateTime('now', $this->dateTimeZone->getTimeZone());
				$date->setTime(0, 0, 0);
				if ($date >= $expirationDate) {
					throw new GenericShareException($this->l->t('Expiration date is in the past'), code: 404);
				}
			}

			// If expiredate is empty set a default one if there is a default
			$fullId = null;
			try {
				$fullId = $share->getFullId();
			} catch (\UnexpectedValueException $e) {
				// This is a new share
			}

			if ($fullId === null && $expirationDate === null && $this->shareApiLinkDefaultExpireDate()) {
				$expirationDate = new \DateTime('now', $this->dateTimeZone->getTimeZone());
				$expirationDate->setTime(0, 0, 0);

				$days = (int)$this->config->getAppValue('core', 'link_defaultExpDays', (string)$this->shareApiLinkDefaultExpireDays());
				if ($days > $this->shareApiLinkDefaultExpireDays()) {
					$days = $this->shareApiLinkDefaultExpireDays();
				}
				$expirationDate->add(new \DateInterval('P' . $days . 'D'));
			}

			// If we enforce the expiration date check that is does not exceed
			if ($isEnforced) {
				if (empty($expirationDate)) {
					throw new \InvalidArgumentException($this->l->t('Expiration date is enforced'));
				}

				$date = new \DateTime('now', $this->dateTimeZone->getTimeZone());
				$date->setTime(0, 0, 0);
				$date->add(new \DateInterval('P' . $this->shareApiLinkDefaultExpireDays() . 'D'));
				if ($date < $expirationDate) {
					throw new GenericShareException(
						$this->l->n('Cannot set expiration date more than %n day in the future', 'Cannot set expiration date more than %n days in the future', $this->shareApiLinkDefaultExpireDays()),
						code: 404,
					);
				}
			}

		}

		$accepted = true;
		$message = '';
		\OCP\Util::emitHook('\OC\Share', 'verifyExpirationDate', [
			'expirationDate' => &$expirationDate,
			'accepted' => &$accepted,
			'message' => &$message,
			'passwordSet' => $share->getPassword() !== null,
		]);

		if (!$accepted) {
			throw new \Exception($message);
		}

		$share->setExpirationDate($expirationDate);

		return $share;
	}

	/**
	 * Check for pre share requirements for user shares
	 *
	 * @throws \Exception
	 */
	protected function userCreateChecks(IShare $share): void {
		// Check if we can share with group members only
		if ($this->shareWithGroupMembersOnly()) {
			$sharedBy = $this->userManager->get($share->getSharedBy());
			$sharedWith = $this->userManager->get($share->getSharedWith());
			// Verify we can share with this user
			$groups = array_intersect(
				$this->groupManager->getUserGroupIds($sharedBy),
				$this->groupManager->getUserGroupIds($sharedWith)
			);

			// optional excluded groups
			$excludedGroups = $this->shareWithGroupMembersOnlyExcludeGroupsList();
			$groups = array_diff($groups, $excludedGroups);

			if (empty($groups)) {
				throw new \Exception($this->l->t('Sharing is only allowed with group members'));
			}
		}

		/*
		 * TODO: Could be costly, fix
		 *
		 * Also this is not what we want in the future.. then we want to squash identical shares.
		 */
		$provider = $this->factory->getProviderForType(IShare::TYPE_USER);
		$existingShares = $provider->getSharesByPath($share->getNode());
		foreach ($existingShares as $existingShare) {
			// Ignore if it is the same share
			try {
				if ($existingShare->getFullId() === $share->getFullId()) {
					continue;
				}
			} catch (\UnexpectedValueException $e) {
				//Shares are not identical
			}

			// Identical share already exists
			if ($existingShare->getSharedWith() === $share->getSharedWith() && $existingShare->getShareType() === $share->getShareType()) {
				throw new AlreadySharedException($this->l->t('Sharing %s failed, because this item is already shared with the account %s', [$share->getNode()->getName(), $share->getSharedWithDisplayName()]), $existingShare);
			}

			// The share is already shared with this user via a group share
			if ($existingShare->getShareType() === IShare::TYPE_GROUP) {
				$group = $this->groupManager->get($existingShare->getSharedWith());
				if (!is_null($group)) {
					$user = $this->userManager->get($share->getSharedWith());

					if ($group->inGroup($user) && $existingShare->getShareOwner() !== $share->getShareOwner()) {
						throw new AlreadySharedException($this->l->t('Sharing %s failed, because this item is already shared with the account %s', [$share->getNode()->getName(), $share->getSharedWithDisplayName()]), $existingShare);
					}
				}
			}
		}
	}

	/**
	 * Check for pre share requirements for group shares
	 *
	 * @throws \Exception
	 */
	protected function groupCreateChecks(IShare $share): void {
		// Verify group shares are allowed
		if (!$this->allowGroupSharing()) {
			throw new \Exception($this->l->t('Group sharing is now allowed'));
		}

		// Verify if the user can share with this group
		if ($this->shareWithGroupMembersOnly()) {
			$sharedBy = $this->userManager->get($share->getSharedBy());
			$sharedWith = $this->groupManager->get($share->getSharedWith());

			// optional excluded groups
			$excludedGroups = $this->shareWithGroupMembersOnlyExcludeGroupsList();
			if (is_null($sharedWith) || in_array($share->getSharedWith(), $excludedGroups) || !$sharedWith->inGroup($sharedBy)) {
				throw new \Exception($this->l->t('Sharing is only allowed within your own groups'));
			}
		}

		/*
		 * TODO: Could be costly, fix
		 *
		 * Also this is not what we want in the future.. then we want to squash identical shares.
		 */
		$provider = $this->factory->getProviderForType(IShare::TYPE_GROUP);
		$existingShares = $provider->getSharesByPath($share->getNode());
		foreach ($existingShares as $existingShare) {
			try {
				if ($existingShare->getFullId() === $share->getFullId()) {
					continue;
				}
			} catch (\UnexpectedValueException $e) {
				//It is a new share so just continue
			}

			if ($existingShare->getSharedWith() === $share->getSharedWith() && $existingShare->getShareType() === $share->getShareType()) {
				throw new AlreadySharedException($this->l->t('Path is already shared with this group'), $existingShare);
			}
		}
	}

	/**
	 * Check for pre share requirements for link shares
	 *
	 * @throws \Exception
	 */
	protected function linkCreateChecks(IShare $share): void {
		// Are link shares allowed?
		if (!$this->shareApiAllowLinks()) {
			throw new \Exception($this->l->t('Link sharing is not allowed'));
		}

		// Check if public upload is allowed
		if ($share->getNodeType() === 'folder' && !$this->shareApiLinkAllowPublicUpload()
			&& ($share->getPermissions() & (\OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_DELETE))) {
			throw new \InvalidArgumentException($this->l->t('Public upload is not allowed'));
		}
	}

	/**
	 * To make sure we don't get invisible link shares we set the parent
	 * of a link if it is a reshare. This is a quick word around
	 * until we can properly display multiple link shares in the UI
	 *
	 * See: https://github.com/owncloud/core/issues/22295
	 *
	 * FIXME: Remove once multiple link shares can be properly displayed
	 */
	protected function setLinkParent(IShare $share): void {
		$storage = $share->getNode()->getStorage();
		if ($storage->instanceOfStorage(SharedStorage::class)) {
			/** @var \OCA\Files_Sharing\SharedStorage $storage */
			$share->setParent((int)$storage->getShareId());
		}
	}

	protected function pathCreateChecks(Node $path): void {
		// Make sure that we do not share a path that contains a shared mountpoint
		if ($path instanceof \OCP\Files\Folder) {
			$mounts = $this->mountManager->findIn($path->getPath());
			foreach ($mounts as $mount) {
				if ($mount->getStorage()->instanceOfStorage('\OCA\Files_Sharing\ISharedStorage')) {
					// Using a flat sharing model ensures the file owner can always see who has access.
					// Allowing parent folder sharing would require tracking inherited access, which adds complexity
					// and hurts performance/scalability.
					// So we forbid sharing a parent folder of a share you received.
					throw new \InvalidArgumentException($this->l->t('You cannot share a folder that contains other shares'));
				}
			}
		}
	}

	/**
	 * Check if the user that is sharing can actually share
	 *
	 * @throws \Exception
	 */
	protected function canShare(IShare $share): void {
		if (!$this->shareApiEnabled()) {
			throw new \Exception($this->l->t('Sharing is disabled'));
		}

		if ($this->sharingDisabledForUser($share->getSharedBy())) {
			throw new \Exception($this->l->t('Sharing is disabled for you'));
		}
	}

	#[Override]
	public function createShare(IShare $share): IShare {
		// TODO: handle link share permissions or check them
		$this->canShare($share);

		$this->generalCreateChecks($share);

		// Verify if there are any issues with the path
		$this->pathCreateChecks($share->getNode());

		/*
		 * On creation of a share the owner is always the owner of the path
		 * Except for mounted federated shares.
		 */
		$storage = $share->getNode()->getStorage();
		if ($storage->instanceOfStorage('OCA\Files_Sharing\External\Storage')) {
			$parent = $share->getNode()->getParent();
			while ($parent->getStorage()->instanceOfStorage('OCA\Files_Sharing\External\Storage')) {
				$parent = $parent->getParent();
			}
			$share->setShareOwner($parent->getOwner()->getUID());
		} else {
			if ($share->getNode()->getOwner()) {
				$share->setShareOwner($share->getNode()->getOwner()->getUID());
			} else {
				$share->setShareOwner($share->getSharedBy());
			}
		}

		try {
			// Verify share type
			if ($share->getShareType() === IShare::TYPE_USER) {
				$this->userCreateChecks($share);

				// Verify the expiration date
				$share = $this->validateExpirationDateInternal($share);
			} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
				$this->groupCreateChecks($share);

				// Verify the expiration date
				$share = $this->validateExpirationDateInternal($share);
			} elseif ($share->getShareType() === IShare::TYPE_REMOTE || $share->getShareType() === IShare::TYPE_REMOTE_GROUP) {
				// Verify the expiration date
				$share = $this->validateExpirationDateInternal($share);
			} elseif ($share->getShareType() === IShare::TYPE_LINK
				|| $share->getShareType() === IShare::TYPE_EMAIL) {
				$this->linkCreateChecks($share);
				$this->setLinkParent($share);

				$token = $this->generateToken();
				// Set the unique token
				$share->setToken($token);

				// Verify the expiration date
				$share = $this->validateExpirationDateLink($share);

				// Verify the password
				$this->verifyPassword($share->getPassword());

				// If a password is set. Hash it!
				if ($share->getShareType() === IShare::TYPE_LINK
					&& $share->getPassword() !== null) {
					$share->setPassword($this->hasher->hash($share->getPassword()));
				}
			}

			// Cannot share with the owner
			if ($share->getShareType() === IShare::TYPE_USER
				&& $share->getSharedWith() === $share->getShareOwner()) {
				throw new \InvalidArgumentException($this->l->t('Cannot share with the share owner'));
			}

			// Generate the target
			$shareFolder = $this->config->getSystemValue('share_folder', '/');
			if ($share->getShareType() === IShare::TYPE_USER) {
				$allowCustomShareFolder = $this->config->getSystemValueBool('sharing.allow_custom_share_folder', true);
				if ($allowCustomShareFolder) {
					$shareFolder = $this->config->getUserValue($share->getSharedWith(), Application::APP_ID, 'share_folder', $shareFolder);
				}
			}

			$target = $shareFolder . '/' . $share->getNode()->getName();
			$target = \OC\Files\Filesystem::normalizePath($target);
			$share->setTarget($target);

			// Pre share event
			$event = new Share\Events\BeforeShareCreatedEvent($share);
			$this->dispatchEvent($event, 'before share created');
			if ($event->isPropagationStopped() && $event->getError()) {
				throw new \Exception($event->getError());
			}

			$oldShare = $share;
			$provider = $this->factory->getProviderForType($share->getShareType());
			$share = $provider->create($share);

			// Reuse the node we already have
			$share->setNode($oldShare->getNode());

			// Reset the target if it is null for the new share
			if ($share->getTarget() === '') {
				$share->setTarget($target);
			}
		} catch (AlreadySharedException $e) {
			// If a share for the same target already exists, dont create a new one,
			// but do trigger the hooks and notifications again
			$oldShare = $share;

			// Reuse the node we already have
			$share = $e->getExistingShare();
			$share->setNode($oldShare->getNode());
		}

		// Post share event
		$this->dispatchEvent(new ShareCreatedEvent($share), 'share created');

		// Send email if needed
		if ($this->config->getSystemValueBool('sharing.enable_share_mail', true)) {
			if ($share->getMailSend()) {
				$provider = $this->factory->getProviderForType($share->getShareType());
				if ($provider instanceof IShareProviderWithNotification) {
					$provider->sendMailNotification($share);
				} else {
					$this->logger->debug('Share notification not sent because the provider does not support it.', ['app' => 'share']);
				}
			} else {
				$this->logger->debug('Share notification not sent because mailsend is false.', ['app' => 'share']);
			}
		} else {
			$this->logger->debug('Share notification not sent because sharing notification emails is disabled.', ['app' => 'share']);
		}

		return $share;
	}

	#[Override]
	public function updateShare(IShare $share, bool $onlyValid = true): IShare {
		$expirationDateUpdated = false;

		$this->canShare($share);

		try {
			$originalShare = $this->getShareById($share->getFullId(), onlyValid: $onlyValid);
		} catch (\UnexpectedValueException $e) {
			throw new \InvalidArgumentException($this->l->t('Share does not have a full ID'));
		}

		// We cannot change the share type!
		if ($share->getShareType() !== $originalShare->getShareType()) {
			throw new \InvalidArgumentException($this->l->t('Cannot change share type'));
		}

		// We can only change the recipient on user shares
		if ($share->getSharedWith() !== $originalShare->getSharedWith()
			&& $share->getShareType() !== IShare::TYPE_USER) {
			throw new \InvalidArgumentException($this->l->t('Can only update recipient on user shares'));
		}

		// Cannot share with the owner
		if ($share->getShareType() === IShare::TYPE_USER
			&& $share->getSharedWith() === $share->getShareOwner()) {
			throw new \InvalidArgumentException($this->l->t('Cannot share with the share owner'));
		}

		$this->generalCreateChecks($share, true);

		if ($share->getShareType() === IShare::TYPE_USER) {
			$this->userCreateChecks($share);

			if ($share->getExpirationDate() != $originalShare->getExpirationDate()) {
				// Verify the expiration date
				$this->validateExpirationDateInternal($share);
				$expirationDateUpdated = true;
			}
		} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
			$this->groupCreateChecks($share);

			if ($share->getExpirationDate() != $originalShare->getExpirationDate()) {
				// Verify the expiration date
				$this->validateExpirationDateInternal($share);
				$expirationDateUpdated = true;
			}
		} elseif ($share->getShareType() === IShare::TYPE_LINK
			|| $share->getShareType() === IShare::TYPE_EMAIL) {
			$this->linkCreateChecks($share);

			// The new password is not set again if it is the same as the old
			// one, unless when switching from sending by Talk to sending by
			// mail.
			$plainTextPassword = $share->getPassword();
			$updatedPassword = $this->updateSharePasswordIfNeeded($share, $originalShare);

			/**
			 * Cannot enable the getSendPasswordByTalk if there is no password set
			 */
			if (empty($plainTextPassword) && $share->getSendPasswordByTalk()) {
				throw new \InvalidArgumentException($this->l->t('Cannot enable sending the password by Talk with an empty password'));
			}

			/**
			 * If we're in a mail share, we need to force a password change
			 * as either the user is not aware of the password or is already (received by mail)
			 * Thus the SendPasswordByTalk feature would not make sense
			 */
			if (!$updatedPassword && $share->getShareType() === IShare::TYPE_EMAIL) {
				if (!$originalShare->getSendPasswordByTalk() && $share->getSendPasswordByTalk()) {
					throw new \InvalidArgumentException($this->l->t('Cannot enable sending the password by Talk without setting a new password'));
				}
				if ($originalShare->getSendPasswordByTalk() && !$share->getSendPasswordByTalk()) {
					throw new \InvalidArgumentException($this->l->t('Cannot disable sending the password by Talk without setting a new password'));
				}
			}

			if ($share->getExpirationDate() != $originalShare->getExpirationDate()) {
				// Verify the expiration date
				$this->validateExpirationDateLink($share);
				$expirationDateUpdated = true;
			}
		} elseif ($share->getShareType() === IShare::TYPE_REMOTE || $share->getShareType() === IShare::TYPE_REMOTE_GROUP) {
			if ($share->getExpirationDate() != $originalShare->getExpirationDate()) {
				// Verify the expiration date
				$this->validateExpirationDateInternal($share);
				$expirationDateUpdated = true;
			}
		}

		$this->pathCreateChecks($share->getNode());

		// Now update the share!
		$provider = $this->factory->getProviderForType($share->getShareType());
		if ($share->getShareType() === IShare::TYPE_EMAIL) {
			/** @var ShareByMailProvider $provider */
			$share = $provider->update($share, $plainTextPassword);
		} else {
			$share = $provider->update($share);
		}

		if ($expirationDateUpdated === true) {
			\OC_Hook::emit(Share::class, 'post_set_expiration_date', [
				'itemType' => $share->getNode() instanceof \OCP\Files\File ? 'file' : 'folder',
				'itemSource' => $share->getNode()->getId(),
				'date' => $share->getExpirationDate(),
				'uidOwner' => $share->getSharedBy(),
			]);
		}

		if ($share->getPassword() !== $originalShare->getPassword()) {
			\OC_Hook::emit(Share::class, 'post_update_password', [
				'itemType' => $share->getNode() instanceof \OCP\Files\File ? 'file' : 'folder',
				'itemSource' => $share->getNode()->getId(),
				'uidOwner' => $share->getSharedBy(),
				'token' => $share->getToken(),
				'disabled' => is_null($share->getPassword()),
			]);
		}

		if ($share->getPermissions() !== $originalShare->getPermissions()) {
			if ($this->userManager->userExists($share->getShareOwner())) {
				$userFolder = $this->rootFolder->getUserFolder($share->getShareOwner());
			} else {
				$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());
			}
			\OC_Hook::emit(Share::class, 'post_update_permissions', [
				'itemType' => $share->getNode() instanceof \OCP\Files\File ? 'file' : 'folder',
				'itemSource' => $share->getNode()->getId(),
				'shareType' => $share->getShareType(),
				'shareWith' => $share->getSharedWith(),
				'uidOwner' => $share->getSharedBy(),
				'permissions' => $share->getPermissions(),
				'attributes' => $share->getAttributes() !== null ? $share->getAttributes()->toArray() : null,
				'path' => $userFolder->getRelativePath($share->getNode()->getPath()),
			]);
		}

		return $share;
	}

	#[Override]
	public function acceptShare(IShare $share, string $recipientId): IShare {
		[$providerId,] = $this->splitFullId($share->getFullId());
		$provider = $this->factory->getProvider($providerId);

		if (!($provider instanceof IShareProviderSupportsAccept)) {
			throw new \InvalidArgumentException($this->l->t('Share provider does not support accepting'));
		}
		/** @var IShareProvider&IShareProviderSupportsAccept $provider */
		$provider->acceptShare($share, $recipientId);

		$event = new ShareAcceptedEvent($share);
		$this->dispatchEvent($event, 'share accepted');

		return $share;
	}

	/**
	 * Updates the password of the given share if it is not the same as the
	 * password of the original share.
	 *
	 * @param IShare $share the share to update its password.
	 * @param IShare $originalShare the original share to compare its
	 *                              password with.
	 * @return bool whether the password was updated or not.
	 */
	private function updateSharePasswordIfNeeded(IShare $share, IShare $originalShare): bool {
		$passwordsAreDifferent = ($share->getPassword() !== $originalShare->getPassword())
			&& (($share->getPassword() !== null && $originalShare->getPassword() === null)
				|| ($share->getPassword() === null && $originalShare->getPassword() !== null)
				|| ($share->getPassword() !== null && $originalShare->getPassword() !== null
					&& !$this->hasher->verify($share->getPassword(), $originalShare->getPassword())));

		// Password updated.
		if ($passwordsAreDifferent) {
			// Verify the password
			$this->verifyPassword($share->getPassword());

			// If a password is set. Hash it!
			if (!empty($share->getPassword())) {
				$share->setPassword($this->hasher->hash($share->getPassword()));
				if ($share->getShareType() === IShare::TYPE_EMAIL) {
					// Shares shared by email have temporary passwords
					$this->setSharePasswordExpirationTime($share);
				}

				return true;
			} else {
				// Empty string and null are seen as NOT password protected
				$share->setPassword(null);
				if ($share->getShareType() === IShare::TYPE_EMAIL) {
					$share->setPasswordExpirationTime(null);
				}
				return true;
			}
		} else {
			// Reset the password to the original one, as it is either the same
			// as the "new" password or a hashed version of it.
			$share->setPassword($originalShare->getPassword());
		}

		return false;
	}

	/**
	 * Set the share's password expiration time
	 */
	private function setSharePasswordExpirationTime(IShare $share): void {
		if (!$this->config->getSystemValueBool('sharing.enable_mail_link_password_expiration', false)) {
			// Sets password expiration date to NULL
			$share->setPasswordExpirationTime();
			return;
		}
		// Sets password expiration date
		$expirationTime = null;
		$now = new \DateTime();
		$expirationInterval = $this->config->getSystemValue('sharing.mail_link_password_expiration_interval', 3600);
		$expirationTime = $now->add(new \DateInterval('PT' . $expirationInterval . 'S'));
		$share->setPasswordExpirationTime($expirationTime);
	}


	/**
	 * Delete all the children of this share
	 *
	 * @param IShare $share
	 * @return list<IShare> List of deleted shares
	 */
	protected function deleteChildren(IShare $share): array {
		$deletedShares = [];

		$provider = $this->factory->getProviderForType($share->getShareType());

		foreach ($provider->getChildren($share) as $child) {
			$this->dispatchEvent(new BeforeShareDeletedEvent($child), 'before share deleted');

			$deletedChildren = $this->deleteChildren($child);
			$deletedShares = array_merge($deletedShares, $deletedChildren);

			$provider->delete($child);
			$this->dispatchEvent(new ShareDeletedEvent($child), 'share deleted');
			$deletedShares[] = $child;
		}

		return $deletedShares;
	}

	/** Promote re-shares into direct shares so that target user keeps access */
	protected function promoteReshares(IShare $share): void {
		try {
			$node = $share->getNode();
		} catch (NotFoundException) {
			/* Skip if node not found */
			return;
		}

		$userIds = [];

		if ($share->getShareType() === IShare::TYPE_USER) {
			$userIds[] = $share->getSharedWith();
		} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
			$group = $this->groupManager->get($share->getSharedWith());
			$users = $group?->getUsers() ?? [];

			foreach ($users as $user) {
				/* Skip share owner */
				if ($user->getUID() === $share->getShareOwner() || $user->getUID() === $share->getSharedBy()) {
					continue;
				}
				$userIds[] = $user->getUID();
			}
		} else {
			/* We only support user and group shares */
			return;
		}

		$reshareRecords = [];
		$shareTypes = [
			IShare::TYPE_GROUP,
			IShare::TYPE_USER,
			IShare::TYPE_LINK,
			IShare::TYPE_REMOTE,
			IShare::TYPE_EMAIL,
		];

		foreach ($userIds as $userId) {
			foreach ($shareTypes as $shareType) {
				try {
					$provider = $this->factory->getProviderForType($shareType);
				} catch (ProviderException $e) {
					continue;
				}

				if ($node instanceof Folder) {
					/* We need to get all shares by this user to get subshares */
					$shares = $provider->getSharesBy($userId, $shareType, null, false, -1, 0);

					foreach ($shares as $share) {
						try {
							$path = $share->getNode()->getPath();
						} catch (NotFoundException) {
							/* Ignore share of non-existing node */
							continue;
						}
						if ($node->getRelativePath($path) !== null) {
							/* If relative path is not null it means the shared node is the same or in a subfolder */
							$reshareRecords[] = $share;
						}
					}
				} else {
					$shares = $provider->getSharesBy($userId, $shareType, $node, false, -1, 0);
					foreach ($shares as $child) {
						$reshareRecords[] = $child;
					}
				}
			}
		}

		foreach ($reshareRecords as $child) {
			try {
				/* Check if the share is still valid (means the resharer still has access to the file through another mean) */
				$this->generalCreateChecks($child);
			} catch (GenericShareException $e) {
				/* The check is invalid, promote it to a direct share from the sharer of parent share */
				$this->logger->debug('Promote reshare because of exception ' . $e->getMessage(), ['exception' => $e, 'fullId' => $child->getFullId()]);
				try {
					$child->setSharedBy($share->getSharedBy());
					$this->updateShare($child);
				} catch (GenericShareException|\InvalidArgumentException $e) {
					$this->logger->warning('Failed to promote reshare because of exception ' . $e->getMessage(), ['exception' => $e, 'fullId' => $child->getFullId()]);
				}
			}
		}
	}

	#[Override]
	public function deleteShare(IShare $share): void {
		try {
			$share->getFullId();
		} catch (\UnexpectedValueException $e) {
			throw new \InvalidArgumentException($this->l->t('Share does not have a full ID'));
		}

		$this->dispatchEvent(new BeforeShareDeletedEvent($share), 'before share deleted');

		// Get all children and delete them as well
		$this->deleteChildren($share);

		// Do the actual delete
		$provider = $this->factory->getProviderForType($share->getShareType());
		$provider->delete($share);

		$this->dispatchEvent(new ShareDeletedEvent($share), 'share deleted');

		// Promote reshares of the deleted share
		$this->promoteReshares($share);
	}

	#[Override]
	public function deleteFromSelf(IShare $share, string $recipientId): void {
		[$providerId,] = $this->splitFullId($share->getFullId());
		$provider = $this->factory->getProvider($providerId);

		$provider->deleteFromSelf($share, $recipientId);
		$event = new ShareDeletedFromSelfEvent($share);
		$this->dispatchEvent($event, 'leave share');
	}

	#[Override]
	public function restoreShare(IShare $share, string $recipientId): IShare {
		[$providerId,] = $this->splitFullId($share->getFullId());
		$provider = $this->factory->getProvider($providerId);

		return $provider->restore($share, $recipientId);
	}

	#[Override]
	public function moveShare(IShare $share, string $recipientId): IShare {
		if ($share->getShareType() === IShare::TYPE_LINK
			|| $share->getShareType() === IShare::TYPE_EMAIL) {
			throw new \InvalidArgumentException($this->l->t('Cannot change target of link share'));
		}

		if ($share->getShareType() === IShare::TYPE_USER && $share->getSharedWith() !== $recipientId) {
			throw new \InvalidArgumentException($this->l->t('Invalid share recipient'));
		}

		if ($share->getShareType() === IShare::TYPE_GROUP) {
			$sharedWith = $this->groupManager->get($share->getSharedWith());
			if (is_null($sharedWith)) {
				throw new \InvalidArgumentException($this->l->t('Group "%s" does not exist', [$share->getSharedWith()]));
			}
			$recipient = $this->userManager->get($recipientId);
			if (!$sharedWith->inGroup($recipient)) {
				throw new \InvalidArgumentException($this->l->t('Invalid share recipient'));
			}
		}

		[$providerId,] = $this->splitFullId($share->getFullId());
		$provider = $this->factory->getProvider($providerId);

		return $provider->move($share, $recipientId);
	}

	#[Override]
	public function getSharesInFolder($userId, Folder $node, bool $reshares = false, bool $shallow = true): array {
		$providers = $this->factory->getAllProviders();
		if (!$shallow) {
			throw new \Exception('non-shallow getSharesInFolder is no longer supported');
		}

		$isOwnerless = $node->getMountPoint() instanceof IShareOwnerlessMount;

		$shares = [];
		foreach ($providers as $provider) {
			if ($isOwnerless) {
				// If the provider does not implement the additional interface,
				// we lack a performant way of querying all shares and therefore ignore the provider.
				if ($provider instanceof IShareProviderSupportsAllSharesInFolder) {
					foreach ($provider->getAllSharesInFolder($node) as $fid => $data) {
						$shares[$fid] ??= [];
						$shares[$fid] = array_merge($shares[$fid], $data);
					}
				}
			} else {
				foreach ($provider->getSharesInFolder($userId, $node, $reshares) as $fid => $data) {
					$shares[$fid] ??= [];
					$shares[$fid] = array_merge($shares[$fid], $data);
				}
			}
		}

		return $shares;
	}

	#[Override]
	public function getSharesBy(string $userId, int $shareType, ?Node $path = null, bool $reshares = false, int $limit = 50, int $offset = 0, bool $onlyValid = true): array {
		if ($path !== null
			&& !($path instanceof \OCP\Files\File)
			&& !($path instanceof \OCP\Files\Folder)) {
			throw new \InvalidArgumentException($this->l->t('Invalid path'));
		}

		try {
			$provider = $this->factory->getProviderForType($shareType);
		} catch (ProviderException $e) {
			return [];
		}

		if ($path?->getMountPoint() instanceof IShareOwnerlessMount) {
			$shares = array_filter($provider->getSharesByPath($path), static fn (IShare $share) => $share->getShareType() === $shareType);
		} else {
			$shares = $provider->getSharesBy($userId, $shareType, $path, $reshares, $limit, $offset);
		}

		/*
		 * Work around so we don't return expired shares but still follow
		 * proper pagination.
		 */

		$shares2 = [];

		while (true) {
			$added = 0;
			foreach ($shares as $share) {
				$added++;
				if ($onlyValid) {
					try {
						$this->checkShare($share, $added);
					} catch (ShareNotFound $e) {
						// Ignore since this basically means the share is deleted
						continue;
					}
				}

				$shares2[] = $share;

				if (count($shares2) === $limit) {
					break;
				}
			}

			// If we did not fetch more shares than the limit then there are no more shares
			if (count($shares) < $limit) {
				break;
			}

			if (count($shares2) === $limit) {
				break;
			}

			// If there was no limit on the select we are done
			if ($limit === -1) {
				break;
			}

			$offset += $added;

			// Fetch again $limit shares
			if ($path?->getMountPoint() instanceof IShareOwnerlessMount) {
				// We already fetched all shares, so end here
				$shares = [];
			} else {
				$shares = $provider->getSharesBy($userId, $shareType, $path, $reshares, $limit, $offset);
			}

			// No more shares means we are done
			if (empty($shares)) {
				break;
			}
		}

		$shares = $shares2;

		return $shares;
	}

	#[Override]
	public function getSharedWith(string $userId, int $shareType, ?Node $node = null, int $limit = 50, int $offset = 0): array {
		try {
			$provider = $this->factory->getProviderForType($shareType);
		} catch (ProviderException $e) {
			return [];
		}

		$shares = $provider->getSharedWith($userId, $shareType, $node, $limit, $offset);

		// remove all shares which are already expired
		foreach ($shares as $key => $share) {
			try {
				$this->checkShare($share);
			} catch (ShareNotFound $e) {
				unset($shares[$key]);
			}
		}

		return $shares;
	}

	/**
	 * @inheritDoc
	 */
	public function getSharedWithByPath(string $userId, int $shareType, string $path, bool $forChildren, int $limit = 50, int $offset = 0): iterable {
		try {
			$provider = $this->factory->getProviderForType($shareType);
		} catch (ProviderException $e) {
			return [];
		}

		if (!$provider instanceof IPartialShareProvider) {
			throw new \RuntimeException(\get_class($provider) . ' must implement IPartialShareProvider');
		}

		$shares = $provider->getSharedWithByPath($userId,
			$shareType,
			$path,
			$forChildren,
			$limit,
			$offset
		);

		if (\is_array($shares)) {
			$shares = new ArrayIterator($shares);
		} elseif (!$shares instanceof \Iterator) {
			$shares = new \IteratorIterator($shares);
		}

		return new \CallbackFilterIterator($shares, function (IShare $share) {
			// remove all shares which are already expired
			try {
				$this->checkShare($share);
				return true;
			} catch (ShareNotFound $e) {
				return false;
			}
		});
	}

	#[Override]
	public function getDeletedSharedWith(string $userId, int $shareType, ?Node $node = null, int $limit = 50, int $offset = 0): array {
		$shares = $this->getSharedWith($userId, $shareType, $node, $limit, $offset);

		// Only get shares deleted shares and where the owner still exists
		return array_filter($shares, fn (IShare $share): bool => $share->getPermissions() === 0
			&& $this->userManager->userExists($share->getShareOwner()));
	}

	#[Override]
	public function getShareById($id, $recipient = null, bool $onlyValid = true): IShare {
		if ($id === null) {
			throw new ShareNotFound();
		}

		[$providerId, $id] = $this->splitFullId($id);

		try {
			$provider = $this->factory->getProvider($providerId);
		} catch (ProviderException $e) {
			throw new ShareNotFound();
		}

		$share = $provider->getShareById($id, $recipient);

		if ($onlyValid) {
			$this->checkShare($share);
		}

		return $share;
	}

	#[Override]
	public function getShareByToken(string $token): IShare {
		// tokens cannot be valid local usernames
		if ($this->userManager->userExists($token)) {
			throw new ShareNotFound();
		}
		$share = null;
		try {
			if ($this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes') {
				$provider = $this->factory->getProviderForType(IShare::TYPE_LINK);
				$share = $provider->getShareByToken($token);
			}
		} catch (ProviderException|ShareNotFound) {
		}


		// If it is not a link share try to fetch a federated share by token
		if ($share === null) {
			try {
				$provider = $this->factory->getProviderForType(IShare::TYPE_REMOTE);
				$share = $provider->getShareByToken($token);
			} catch (ProviderException|ShareNotFound) {
			}
		}

		// If it is not a link share try to fetch a mail share by token
		if ($share === null && $this->shareProviderExists(IShare::TYPE_EMAIL)) {
			try {
				$provider = $this->factory->getProviderForType(IShare::TYPE_EMAIL);
				$share = $provider->getShareByToken($token);
			} catch (ProviderException|ShareNotFound) {
			}
		}

		if ($share === null && $this->shareProviderExists(IShare::TYPE_CIRCLE)) {
			try {
				$provider = $this->factory->getProviderForType(IShare::TYPE_CIRCLE);
				$share = $provider->getShareByToken($token);
			} catch (ProviderException|ShareNotFound) {
			}
		}

		if ($share === null && $this->shareProviderExists(IShare::TYPE_ROOM)) {
			try {
				$provider = $this->factory->getProviderForType(IShare::TYPE_ROOM);
				$share = $provider->getShareByToken($token);
			} catch (ProviderException|ShareNotFound) {
			}
		}

		if ($share === null) {
			throw new ShareNotFound($this->l->t('The requested share does not exist anymore'));
		}

		$this->checkShare($share);

		/*
		 * Reduce the permissions for link or email shares if public upload is not enabled
		 */
		if (($share->getShareType() === IShare::TYPE_LINK || $share->getShareType() === IShare::TYPE_EMAIL)
			&& $share->getNodeType() === 'folder' && !$this->shareApiLinkAllowPublicUpload()) {
			$share->setPermissions($share->getPermissions() & ~(\OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE));
		}

		return $share;
	}

	/**
	 * Check expire date and disabled owner
	 *
	 * @param int &$added If given, will be decremented if the share is deleted
	 * @throws ShareNotFound
	 */
	private function checkShare(IShare $share, int &$added = 1): void {
		if ($share->isExpired()) {
			$this->deleteShare($share);
			// Remove 1 to added, because this share was deleted
			$added--;
			throw new ShareNotFound($this->l->t('The requested share does not exist anymore'));
		}
		if ($this->config->getAppValue('files_sharing', 'hide_disabled_user_shares', 'no') === 'yes') {
			$uids = array_unique([$share->getShareOwner(),$share->getSharedBy()]);
			foreach ($uids as $uid) {
				$user = $this->userManager->get($uid);
				if ($user?->isEnabled() === false) {
					throw new ShareNotFound($this->l->t('The requested share comes from a disabled user'));
				}
			}
		}

		// For link and email shares, verify the share owner can still create such shares
		if ($share->getShareType() === IShare::TYPE_LINK || $share->getShareType() === IShare::TYPE_EMAIL) {
			$shareOwner = $this->userManager->get($share->getShareOwner());
			if ($shareOwner === null) {
				throw new ShareNotFound($this->l->t('The requested share does not exist anymore'));
			}
			if (!$this->userCanCreateLinkShares($shareOwner)) {
				throw new ShareNotFound($this->l->t('The requested share does not exist anymore'));
			}
		}
	}

	#[Override]
	public function checkPassword(IShare $share, ?string $password): bool {

		// if there is no password on the share object / passsword is null, there is nothing to check
		if ($password === null || $share->getPassword() === null) {
			return false;
		}

		// Makes sure password hasn't expired
		$expirationTime = $share->getPasswordExpirationTime();
		if ($expirationTime !== null && $expirationTime < new \DateTime()) {
			return false;
		}

		$newHash = '';
		if (!$this->hasher->verify($password, $share->getPassword(), $newHash)) {
			return false;
		}

		if (!empty($newHash)) {
			$share->setPassword($newHash);
			$provider = $this->factory->getProviderForType($share->getShareType());
			$provider->update($share);
		}

		return true;
	}

	#[Override]
	public function userDeleted(string $uid): void {
		$types = [IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_LINK, IShare::TYPE_REMOTE, IShare::TYPE_EMAIL];

		foreach ($types as $type) {
			try {
				$provider = $this->factory->getProviderForType($type);
			} catch (ProviderException $e) {
				continue;
			}
			$provider->userDeleted($uid, $type);
		}
	}

	#[Override]
	public function groupDeleted(string $gid): void {
		foreach ([IShare::TYPE_GROUP, IShare::TYPE_REMOTE_GROUP] as $type) {
			try {
				$provider = $this->factory->getProviderForType($type);
			} catch (ProviderException $e) {
				continue;
			}
			$provider->groupDeleted($gid);
		}

		$excludedGroups = $this->config->getAppValue('core', 'shareapi_exclude_groups_list', '');
		if ($excludedGroups === '') {
			return;
		}

		$excludedGroups = json_decode($excludedGroups, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return;
		}

		$excludedGroups = array_diff($excludedGroups, [$gid]);
		$this->config->setAppValue('core', 'shareapi_exclude_groups_list', json_encode($excludedGroups));
	}

	#[Override]
	public function userDeletedFromGroup(string $uid, string $gid): void {
		foreach ([IShare::TYPE_GROUP, IShare::TYPE_REMOTE_GROUP] as $type) {
			try {
				$provider = $this->factory->getProviderForType($type);
			} catch (ProviderException $e) {
				continue;
			}
			$provider->userDeletedFromGroup($uid, $gid);
		}
	}

	#[\Override]
	public function getAccessList(\OCP\Files\Node $path, $recursive = true, $currentAccess = false): array {
		$owner = $path->getOwner();

		if ($owner === null) {
			return [];
		}

		$owner = $owner->getUID();

		if ($currentAccess) {
			$al = ['users' => [], 'remote' => [], 'public' => false, 'mail' => []];
		} else {
			$al = ['users' => [], 'remote' => false, 'public' => false, 'mail' => []];
		}
		if (!$this->userManager->userExists($owner)) {
			return $al;
		}

		//Get node for the owner and correct the owner in case of external storage
		$userFolder = $this->rootFolder->getUserFolder($owner);
		if ($path->getId() !== $userFolder->getId() && !$userFolder->isSubNode($path)) {
			$path = $userFolder->getFirstNodeById($path->getId());
			if ($path === null || $path->getOwner() === null) {
				return [];
			}
			$owner = $path->getOwner()->getUID();
		}

		$providers = $this->factory->getAllProviders();

		/** @var Node[] $nodes */
		$nodes = [];


		if ($currentAccess) {
			$ownerPath = $path->getPath();
			$ownerPath = explode('/', $ownerPath, 4);
			if (count($ownerPath) < 4) {
				$ownerPath = '';
			} else {
				$ownerPath = $ownerPath[3];
			}
			$al['users'][$owner] = [
				'node_id' => $path->getId(),
				'node_path' => '/' . $ownerPath,
			];
		} else {
			$al['users'][] = $owner;
		}

		// Collect all the shares
		while ($path->getPath() !== $userFolder->getPath()) {
			$nodes[] = $path;
			if (!$recursive) {
				break;
			}
			$path = $path->getParent();
		}

		foreach ($providers as $provider) {
			$tmp = $provider->getAccessList($nodes, $currentAccess);

			foreach ($tmp as $k => $v) {
				if (isset($al[$k])) {
					if (is_array($al[$k])) {
						if ($currentAccess) {
							$al[$k] += $v;
						} else {
							$al[$k] = array_merge($al[$k], $v);
							$al[$k] = array_unique($al[$k]);
							$al[$k] = array_values($al[$k]);
						}
					} else {
						$al[$k] = $al[$k] || $v;
					}
				} else {
					$al[$k] = $v;
				}
			}
		}

		return $al;
	}

	#[Override]
	public function newShare(): IShare {
		return new \OC\Share20\Share($this->rootFolder, $this->userManager);
	}

	#[Override]
	public function shareApiEnabled(): bool {
		return $this->config->getAppValue('core', 'shareapi_enabled', 'yes') === 'yes';
	}

	#[Override]
	public function shareApiAllowLinks(?IUser $user = null): bool {
		if ($this->config->getAppValue('core', 'shareapi_allow_links', 'yes') !== 'yes') {
			return false;
		}

		$user = $user ?? $this->userSession->getUser();
		if ($user) {
			$excludedGroups = json_decode($this->config->getAppValue('core', 'shareapi_allow_links_exclude_groups', '[]'));
			if ($excludedGroups) {
				$userGroups = $this->groupManager->getUserGroupIds($user);
				return !(bool)array_intersect($excludedGroups, $userGroups);
			}
		}

		return true;
	}

	/**
	 * Check if a specific user can create link shares
	 *
	 * @param IUser $user The user to check
	 * @return bool
	 */
	protected function userCanCreateLinkShares(IUser $user): bool {
		return $this->shareApiAllowLinks($user);
	}

	#[Override]
	public function shareApiLinkEnforcePassword(bool $checkGroupMembership = true): bool {
		$excludedGroups = $this->config->getAppValue('core', 'shareapi_enforce_links_password_excluded_groups', '');
		if ($excludedGroups !== '' && $checkGroupMembership) {
			$excludedGroups = json_decode($excludedGroups);
			$user = $this->userSession->getUser();
			if ($user) {
				$userGroups = $this->groupManager->getUserGroupIds($user);
				if ((bool)array_intersect($excludedGroups, $userGroups)) {
					return false;
				}
			}
		}
		return $this->appConfig->getValueBool('core', ConfigLexicon::SHARE_LINK_PASSWORD_ENFORCED);
	}

	#[Override]
	public function shareApiLinkDefaultExpireDate(): bool {
		return $this->appConfig->getValueBool('core', ConfigLexicon::SHARE_LINK_EXPIRE_DATE_DEFAULT);
	}

	#[Override]
	public function shareApiLinkDefaultExpireDateEnforced(): bool {
		return $this->shareApiLinkDefaultExpireDate()
			&& $this->appConfig->getValueBool('core', ConfigLexicon::SHARE_LINK_EXPIRE_DATE_ENFORCED);
	}

	#[Override]
	public function shareApiLinkDefaultExpireDays(): int {
		return (int)$this->config->getAppValue('core', 'shareapi_expire_after_n_days', '7');
	}

	#[Override]
	public function shareApiInternalDefaultExpireDate(): bool {
		return $this->config->getAppValue('core', 'shareapi_default_internal_expire_date', 'no') === 'yes';
	}

	#[Override]
	public function shareApiRemoteDefaultExpireDate(): bool {
		return $this->config->getAppValue('core', 'shareapi_default_remote_expire_date', 'no') === 'yes';
	}

	#[Override]
	public function shareApiInternalDefaultExpireDateEnforced(): bool {
		return $this->shareApiInternalDefaultExpireDate()
			&& $this->config->getAppValue('core', 'shareapi_enforce_internal_expire_date', 'no') === 'yes';
	}

	#[Override]
	public function shareApiRemoteDefaultExpireDateEnforced(): bool {
		return $this->shareApiRemoteDefaultExpireDate()
			&& $this->config->getAppValue('core', 'shareapi_enforce_remote_expire_date', 'no') === 'yes';
	}

	#[Override]
	public function shareApiInternalDefaultExpireDays(): int {
		return (int)$this->config->getAppValue('core', 'shareapi_internal_expire_after_n_days', '7');
	}

	#[Override]
	public function shareApiRemoteDefaultExpireDays(): int {
		return (int)$this->config->getAppValue('core', 'shareapi_remote_expire_after_n_days', '7');
	}

	#[Override]
	public function shareApiLinkAllowPublicUpload(): bool {
		return $this->config->getAppValue('core', 'shareapi_allow_public_upload', 'yes') === 'yes';
	}

	#[Override]
	public function shareWithGroupMembersOnly(): bool {
		return $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';
	}

	#[Override]
	public function shareWithGroupMembersOnlyExcludeGroupsList(): array {
		if (!$this->shareWithGroupMembersOnly()) {
			return [];
		}
		$excludeGroups = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members_exclude_group_list', '');
		return json_decode($excludeGroups, true) ?? [];
	}

	#[Override]
	public function allowGroupSharing(): bool {
		return $this->config->getAppValue('core', 'shareapi_allow_group_sharing', 'yes') === 'yes';
	}

	#[Override]
	public function allowEnumeration(): bool {
		return $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
	}

	#[Override]
	public function limitEnumerationToGroups(): bool {
		return $this->allowEnumeration()
			&& $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
	}

	#[Override]
	public function limitEnumerationToPhone(): bool {
		return $this->allowEnumeration()
			&& $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_phone', 'no') === 'yes';
	}

	#[Override]
	public function allowEnumerationFullMatch(): bool {
		return $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match', 'yes') === 'yes';
	}

	#[Override]
	public function matchEmail(): bool {
		return $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match_email', 'yes') === 'yes';
	}

	#[Override]
	public function matchUserId(): bool {
		return $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match_user_id', 'yes') === 'yes';
	}

	public function matchDisplayName(): bool {
		return $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match_displayname', 'yes') === 'yes';
	}

	#[Override]
	public function ignoreSecondDisplayName(): bool {
		return $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match_ignore_second_dn', 'no') === 'yes';
	}

	#[Override]
	public function allowCustomTokens(): bool {
		return $this->appConfig->getValueBool('core', ConfigLexicon::SHARE_CUSTOM_TOKEN);
	}

	#[Override]
	public function allowViewWithoutDownload(): bool {
		return $this->appConfig->getValueBool('core', 'shareapi_allow_view_without_download', true);
	}

	#[Override]
	public function currentUserCanEnumerateTargetUser(?IUser $currentUser, IUser $targetUser): bool {
		if ($this->allowEnumerationFullMatch()) {
			return true;
		}

		if (!$this->allowEnumeration()) {
			return false;
		}

		if (!$this->limitEnumerationToPhone() && !$this->limitEnumerationToGroups()) {
			// Enumeration is enabled and not restricted: OK
			return true;
		}

		if (!$currentUser instanceof IUser) {
			// Enumeration restrictions require an account
			return false;
		}

		// Enumeration is limited to phone match
		if ($this->limitEnumerationToPhone() && $this->knownUserService->isKnownToUser($currentUser->getUID(), $targetUser->getUID())) {
			return true;
		}

		// Enumeration is limited to groups
		if ($this->limitEnumerationToGroups()) {
			$currentUserGroupIds = $this->groupManager->getUserGroupIds($currentUser);
			$targetUserGroupIds = $this->groupManager->getUserGroupIds($targetUser);
			if (!empty(array_intersect($currentUserGroupIds, $targetUserGroupIds))) {
				return true;
			}
		}

		return false;
	}

	#[Override]
	public function sharingDisabledForUser(?string $userId): bool {
		return $this->shareDisableChecker->sharingDisabledForUser($userId);
	}

	#[Override]
	public function outgoingServer2ServerSharesAllowed(): bool {
		return $this->config->getAppValue('files_sharing', 'outgoing_server2server_share_enabled', 'yes') === 'yes';
	}

	#[Override]
	public function outgoingServer2ServerGroupSharesAllowed(): bool {
		return $this->config->getAppValue('files_sharing', 'outgoing_server2server_group_share_enabled', 'no') === 'yes';
	}

	#[Override]
	public function shareProviderExists(int $shareType): bool {
		try {
			$this->factory->getProviderForType($shareType);
		} catch (ProviderException $e) {
			return false;
		}

		return true;
	}

	public function registerShareProvider(string $shareProviderClass): void {
		$this->factory->registerProvider($shareProviderClass);
	}

	#[Override]
	public function getAllShares(): iterable {
		$providers = $this->factory->getAllProviders();

		foreach ($providers as $provider) {
			yield from $provider->getAllShares();
		}
	}

	#[Override]
	public function generateToken(): string {
		// Initial token length
		$tokenLength = Helper::getTokenLength();

		do {
			$tokenExists = false;

			for ($i = 0; $i <= 2; $i++) {
				// Generate a new token
				$token = $this->secureRandom->generate(
					$tokenLength,
					ISecureRandom::CHAR_HUMAN_READABLE,
				);

				try {
					// Try to fetch a share with the generated token
					$this->getShareByToken($token);
					$tokenExists = true; // Token exists, we need to try again
				} catch (ShareNotFound $e) {
					// Token is unique, exit the loop
					$tokenExists = false;
					break;
				}
			}

			// If we've reached the maximum attempts and the token still exists, increase the token length
			if ($tokenExists) {
				$tokenLength++;

				// Check if the token length exceeds the maximum allowed length
				if ($tokenLength > \OC\Share\Constants::MAX_TOKEN_LENGTH) {
					throw new ShareTokenException('Unable to generate a unique share token. Maximum token length exceeded.');
				}
			}
		} while ($tokenExists);

		return $token;
	}

	private function dispatchEvent(Event $event, string $name): void {
		try {
			$this->dispatcher->dispatchTyped($event);
		} catch (\Exception $e) {
			$this->logger->error("Error while sending ' . $name . ' event", ['exception' => $e]);
		}
	}

	public function getUsersForShare(IShare $share): iterable {
		$provider = $this->factory->getProviderForType($share->getShareType());
		if ($provider instanceof Share\IShareProviderGetUsers) {
			return $provider->getUsersForShare($share);
		} else {
			return [];
		}
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Share20;

use OC\Files\Mount\MoveableMount;
use OC\KnownUser\KnownUserService;
use OC\Share20\Exception\ProviderException;
use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\SharedStorage;
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
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
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
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;
use OCP\Share\IShareProvider;
use OCP\Share\IShareProviderSupportsAccept;
use OCP\Share\IShareProviderWithNotification;
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
		private IMailer $mailer,
		private IURLGenerator $urlGenerator,
		private \OC_Defaults $defaults,
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
	 * @param string $id
	 * @return string[]
	 */
	private function splitFullId($id) {
		return explode(':', $id, 2);
	}

	/**
	 * Verify if a password meets all requirements
	 *
	 * @param string $password
	 * @throws HintException
	 */
	protected function verifyPassword($password) {
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
	protected function generalCreateChecks(IShare $share, bool $isUpdate = false) {
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
		} elseif ($share->getShareType() === IShare::TYPE_ROOM) {
		} elseif ($share->getShareType() === IShare::TYPE_DECK) {
		} elseif ($share->getShareType() === IShare::TYPE_SCIENCEMESH) {
		} else {
			// We cannot handle other types yet
			throw new \InvalidArgumentException($this->l->t('Unknown share type'));
		}

		// Verify the initiator of the share is set
		if ($share->getSharedBy() === null) {
			throw new \InvalidArgumentException($this->l->t('Share initiator must be set'));
		}

		// Cannot share with yourself
		if ($share->getShareType() === IShare::TYPE_USER &&
			$share->getSharedWith() === $share->getSharedBy()) {
			throw new \InvalidArgumentException($this->l->t('Cannot share with yourself'));
		}

		// The path should be set
		if ($share->getNode() === null) {
			throw new \InvalidArgumentException($this->l->t('Shared path must be set'));
		}

		// And it should be a file or a folder
		if (!($share->getNode() instanceof \OCP\Files\File) &&
			!($share->getNode() instanceof \OCP\Files\Folder)) {
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
		if (!$noReadPermissionRequired &&
			($share->getPermissions() & \OCP\Constants::PERMISSION_READ) === 0) {
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
	protected function validateExpirationDateInternal(IShare $share) {
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
	protected function validateExpirationDateLink(IShare $share) {
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
	 * @param IShare $share
	 * @throws \Exception
	 */
	protected function userCreateChecks(IShare $share) {
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
	 * @param IShare $share
	 * @throws \Exception
	 */
	protected function groupCreateChecks(IShare $share) {
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
	 * @param IShare $share
	 * @throws \Exception
	 */
	protected function linkCreateChecks(IShare $share) {
		// Are link shares allowed?
		if (!$this->shareApiAllowLinks()) {
			throw new \Exception($this->l->t('Link sharing is not allowed'));
		}

		// Check if public upload is allowed
		if ($share->getNodeType() === 'folder' && !$this->shareApiLinkAllowPublicUpload() &&
			($share->getPermissions() & (\OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_DELETE))) {
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
	 *
	 * @param IShare $share
	 */
	protected function setLinkParent(IShare $share) {
		// No sense in checking if the method is not there.
		if (method_exists($share, 'setParent')) {
			$storage = $share->getNode()->getStorage();
			if ($storage->instanceOfStorage(SharedStorage::class)) {
				/** @var \OCA\Files_Sharing\SharedStorage $storage */
				$share->setParent($storage->getShareId());
			}
		}
	}

	/**
	 * @param File|Folder $path
	 */
	protected function pathCreateChecks($path) {
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
	 * @param IShare $share
	 * @throws \Exception
	 */
	protected function canShare(IShare $share) {
		if (!$this->shareApiEnabled()) {
			throw new \Exception($this->l->t('Sharing is disabled'));
		}

		if ($this->sharingDisabledForUser($share->getSharedBy())) {
			throw new \Exception($this->l->t('Sharing is disabled for you'));
		}
	}

	/**
	 * Share a path
	 *
	 * @param IShare $share
	 * @return IShare The share object
	 * @throws \Exception
	 *
	 * TODO: handle link share permissions or check them
	 */
	public function createShare(IShare $share) {
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
			if ($share->getShareType() === IShare::TYPE_USER &&
				$share->getSharedWith() === $share->getShareOwner()) {
				throw new \InvalidArgumentException($this->l->t('Cannot share with the share owner'));
			}

			// Generate the target
			$defaultShareFolder = $this->config->getSystemValue('share_folder', '/');
			$allowCustomShareFolder = $this->config->getSystemValueBool('sharing.allow_custom_share_folder', true);
			if ($allowCustomShareFolder) {
				$shareFolder = $this->config->getUserValue($share->getSharedWith(), Application::APP_ID, 'share_folder', $defaultShareFolder);
			} else {
				$shareFolder = $defaultShareFolder;
			}

			$target = $shareFolder . '/' . $share->getNode()->getName();
			$target = \OC\Files\Filesystem::normalizePath($target);
			$share->setTarget($target);

			// Pre share event
			$event = new Share\Events\BeforeShareCreatedEvent($share);
			$this->dispatcher->dispatchTyped($event);
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
		$this->dispatcher->dispatchTyped(new ShareCreatedEvent($share));

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

	/**
	 * Update a share
	 *
	 * @param IShare $share
	 * @return IShare The share object
	 * @throws \InvalidArgumentException
	 * @throws HintException
	 */
	public function updateShare(IShare $share, bool $onlyValid = true) {
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
		if ($share->getSharedWith() !== $originalShare->getSharedWith() &&
			$share->getShareType() !== IShare::TYPE_USER) {
			throw new \InvalidArgumentException($this->l->t('Can only update recipient on user shares'));
		}

		// Cannot share with the owner
		if ($share->getShareType() === IShare::TYPE_USER &&
			$share->getSharedWith() === $share->getShareOwner()) {
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

	/**
	 * Accept a share.
	 *
	 * @param IShare $share
	 * @param string $recipientId
	 * @return IShare The share object
	 * @throws \InvalidArgumentException Thrown if the provider does not implement `IShareProviderSupportsAccept`
	 * @since 9.0.0
	 */
	public function acceptShare(IShare $share, string $recipientId): IShare {
		[$providerId,] = $this->splitFullId($share->getFullId());
		$provider = $this->factory->getProvider($providerId);

		if (!($provider instanceof IShareProviderSupportsAccept)) {
			throw new \InvalidArgumentException($this->l->t('Share provider does not support accepting'));
		}
		/** @var IShareProvider&IShareProviderSupportsAccept $provider */
		$provider->acceptShare($share, $recipientId);

		$event = new ShareAcceptedEvent($share);
		$this->dispatcher->dispatchTyped($event);

		return $share;
	}

	/**
	 * Updates the password of the given share if it is not the same as the
	 * password of the original share.
	 *
	 * @param IShare $share the share to update its password.
	 * @param IShare $originalShare the original share to compare its
	 *                              password with.
	 * @return boolean whether the password was updated or not.
	 */
	private function updateSharePasswordIfNeeded(IShare $share, IShare $originalShare) {
		$passwordsAreDifferent = ($share->getPassword() !== $originalShare->getPassword()) &&
			(($share->getPassword() !== null && $originalShare->getPassword() === null) ||
				($share->getPassword() === null && $originalShare->getPassword() !== null) ||
				($share->getPassword() !== null && $originalShare->getPassword() !== null &&
					!$this->hasher->verify($share->getPassword(), $originalShare->getPassword())));

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
	 * FIXME: remove once https://github.com/owncloud/core/pull/21660 is in
	 *
	 * @param IShare $share
	 * @return IShare[] List of deleted shares
	 */
	protected function deleteChildren(IShare $share) {
		$deletedShares = [];

		$provider = $this->factory->getProviderForType($share->getShareType());

		foreach ($provider->getChildren($share) as $child) {
			$this->dispatcher->dispatchTyped(new BeforeShareDeletedEvent($child));

			$deletedChildren = $this->deleteChildren($child);
			$deletedShares = array_merge($deletedShares, $deletedChildren);

			$provider->delete($child);
			$this->dispatcher->dispatchTyped(new ShareDeletedEvent($child));
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

	/**
	 * Delete a share
	 *
	 * @param IShare $share
	 * @throws ShareNotFound
	 * @throws \InvalidArgumentException
	 */
	public function deleteShare(IShare $share) {
		try {
			$share->getFullId();
		} catch (\UnexpectedValueException $e) {
			throw new \InvalidArgumentException($this->l->t('Share does not have a full ID'));
		}

		$this->dispatcher->dispatchTyped(new BeforeShareDeletedEvent($share));

		// Get all children and delete them as well
		$this->deleteChildren($share);

		// Do the actual delete
		$provider = $this->factory->getProviderForType($share->getShareType());
		$provider->delete($share);

		$this->dispatcher->dispatchTyped(new ShareDeletedEvent($share));

		// Promote reshares of the deleted share
		$this->promoteReshares($share);
	}


	/**
	 * Unshare a file as the recipient.
	 * This can be different from a regular delete for example when one of
	 * the users in a groups deletes that share. But the provider should
	 * handle this.
	 *
	 * @param IShare $share
	 * @param string $recipientId
	 */
	public function deleteFromSelf(IShare $share, $recipientId) {
		[$providerId,] = $this->splitFullId($share->getFullId());
		$provider = $this->factory->getProvider($providerId);

		$provider->deleteFromSelf($share, $recipientId);
		$event = new ShareDeletedFromSelfEvent($share);
		$this->dispatcher->dispatchTyped($event);
	}

	public function restoreShare(IShare $share, string $recipientId): IShare {
		[$providerId,] = $this->splitFullId($share->getFullId());
		$provider = $this->factory->getProvider($providerId);

		return $provider->restore($share, $recipientId);
	}

	/**
	 * @inheritdoc
	 */
	public function moveShare(IShare $share, $recipientId) {
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

	public function getSharesInFolder($userId, Folder $node, $reshares = false, $shallow = true) {
		$providers = $this->factory->getAllProviders();
		if (!$shallow) {
			throw new \Exception('non-shallow getSharesInFolder is no longer supported');
		}

		$isOwnerless = $node->getMountPoint() instanceof IShareOwnerlessMount;

		$shares = [];
		foreach ($providers as $provider) {
			if ($isOwnerless) {
				foreach ($node->getDirectoryListing() as $childNode) {
					$data = $provider->getSharesByPath($childNode);
					$fid = $childNode->getId();
					$shares[$fid] ??= [];
					$shares[$fid] = array_merge($shares[$fid], $data);
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

	/**
	 * @inheritdoc
	 */
	public function getSharesBy($userId, $shareType, $path = null, $reshares = false, $limit = 50, $offset = 0, bool $onlyValid = true) {
		if ($path !== null &&
			!($path instanceof \OCP\Files\File) &&
			!($path instanceof \OCP\Files\Folder)) {
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
				if ($onlyValid) {
					try {
						$this->checkShare($share);
					} catch (ShareNotFound $e) {
						// Ignore since this basically means the share is deleted
						continue;
					}
				}

				$added++;
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

	/**
	 * @inheritdoc
	 */
	public function getSharedWith($userId, $shareType, $node = null, $limit = 50, $offset = 0) {
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
	 * @inheritdoc
	 */
	public function getDeletedSharedWith($userId, $shareType, $node = null, $limit = 50, $offset = 0) {
		$shares = $this->getSharedWith($userId, $shareType, $node, $limit, $offset);

		// Only get deleted shares
		$shares = array_filter($shares, function (IShare $share) {
			return $share->getPermissions() === 0;
		});

		// Only get shares where the owner still exists
		$shares = array_filter($shares, function (IShare $share) {
			return $this->userManager->userExists($share->getShareOwner());
		});

		return $shares;
	}

	/**
	 * @inheritdoc
	 */
	public function getShareById($id, $recipient = null, bool $onlyValid = true) {
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

	/**
	 * Get all the shares for a given path
	 *
	 * @param \OCP\Files\Node $path
	 * @param int $page
	 * @param int $perPage
	 *
	 * @return Share[]
	 */
	public function getSharesByPath(\OCP\Files\Node $path, $page = 0, $perPage = 50) {
		return [];
	}

	/**
	 * Get the share by token possible with password
	 *
	 * @param string $token
	 * @return IShare
	 *
	 * @throws ShareNotFound
	 */
	public function getShareByToken($token) {
		// tokens cannot be valid local user names
		if ($this->userManager->userExists($token)) {
			throw new ShareNotFound();
		}
		$share = null;
		try {
			if ($this->shareApiAllowLinks()) {
				$provider = $this->factory->getProviderForType(IShare::TYPE_LINK);
				$share = $provider->getShareByToken($token);
			}
		} catch (ProviderException $e) {
		} catch (ShareNotFound $e) {
		}


		// If it is not a link share try to fetch a federated share by token
		if ($share === null) {
			try {
				$provider = $this->factory->getProviderForType(IShare::TYPE_REMOTE);
				$share = $provider->getShareByToken($token);
			} catch (ProviderException $e) {
			} catch (ShareNotFound $e) {
			}
		}

		// If it is not a link share try to fetch a mail share by token
		if ($share === null && $this->shareProviderExists(IShare::TYPE_EMAIL)) {
			try {
				$provider = $this->factory->getProviderForType(IShare::TYPE_EMAIL);
				$share = $provider->getShareByToken($token);
			} catch (ProviderException $e) {
			} catch (ShareNotFound $e) {
			}
		}

		if ($share === null && $this->shareProviderExists(IShare::TYPE_CIRCLE)) {
			try {
				$provider = $this->factory->getProviderForType(IShare::TYPE_CIRCLE);
				$share = $provider->getShareByToken($token);
			} catch (ProviderException $e) {
			} catch (ShareNotFound $e) {
			}
		}

		if ($share === null && $this->shareProviderExists(IShare::TYPE_ROOM)) {
			try {
				$provider = $this->factory->getProviderForType(IShare::TYPE_ROOM);
				$share = $provider->getShareByToken($token);
			} catch (ProviderException $e) {
			} catch (ShareNotFound $e) {
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
	 * @throws ShareNotFound
	 */
	protected function checkShare(IShare $share): void {
		if ($share->isExpired()) {
			$this->deleteShare($share);
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
	}

	/**
	 * Verify the password of a public share
	 *
	 * @param IShare $share
	 * @param ?string $password
	 * @return bool
	 */
	public function checkPassword(IShare $share, $password) {

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

	/**
	 * @inheritdoc
	 */
	public function userDeleted($uid) {
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

	/**
	 * @inheritdoc
	 */
	public function groupDeleted($gid) {
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

	/**
	 * @inheritdoc
	 */
	public function userDeletedFromGroup($uid, $gid) {
		foreach ([IShare::TYPE_GROUP, IShare::TYPE_REMOTE_GROUP] as $type) {
			try {
				$provider = $this->factory->getProviderForType($type);
			} catch (ProviderException $e) {
				continue;
			}
			$provider->userDeletedFromGroup($uid, $gid);
		}
	}

	/**
	 * Get access list to a path. This means
	 * all the users that can access a given path.
	 *
	 * Consider:
	 * -root
	 * |-folder1 (23)
	 *  |-folder2 (32)
	 *   |-fileA (42)
	 *
	 * fileA is shared with user1 and user1@server1 and email1@maildomain1
	 * folder2 is shared with group2 (user4 is a member of group2)
	 * folder1 is shared with user2 (renamed to "folder (1)") and user2@server2
	 *                        and email2@maildomain2
	 *
	 * Then the access list to '/folder1/folder2/fileA' with $currentAccess is:
	 * [
	 *  users  => [
	 *      'user1' => ['node_id' => 42, 'node_path' => '/fileA'],
	 *      'user4' => ['node_id' => 32, 'node_path' => '/folder2'],
	 *      'user2' => ['node_id' => 23, 'node_path' => '/folder (1)'],
	 *  ],
	 *  remote => [
	 *      'user1@server1' => ['node_id' => 42, 'token' => 'SeCr3t'],
	 *      'user2@server2' => ['node_id' => 23, 'token' => 'FooBaR'],
	 *  ],
	 *  public => bool
	 *  mail => [
	 *      'email1@maildomain1' => ['node_id' => 42, 'token' => 'aBcDeFg'],
	 *      'email2@maildomain2' => ['node_id' => 23, 'token' => 'hIjKlMn'],
	 *  ]
	 * ]
	 *
	 * The access list to '/folder1/folder2/fileA' **without** $currentAccess is:
	 * [
	 *  users  => ['user1', 'user2', 'user4'],
	 *  remote => bool,
	 *  public => bool
	 *  mail => ['email1@maildomain1', 'email2@maildomain2']
	 * ]
	 *
	 * This is required for encryption/activity
	 *
	 * @param \OCP\Files\Node $path
	 * @param bool $recursive Should we check all parent folders as well
	 * @param bool $currentAccess Ensure the recipient has access to the file (e.g. did not unshare it)
	 * @return array
	 */
	public function getAccessList(\OCP\Files\Node $path, $recursive = true, $currentAccess = false) {
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

	/**
	 * Create a new share
	 *
	 * @return IShare
	 */
	public function newShare() {
		return new \OC\Share20\Share($this->rootFolder, $this->userManager);
	}

	/**
	 * Is the share API enabled
	 *
	 * @return bool
	 */
	public function shareApiEnabled() {
		return $this->config->getAppValue('core', 'shareapi_enabled', 'yes') === 'yes';
	}

	/**
	 * Is public link sharing enabled
	 *
	 * @return bool
	 */
	public function shareApiAllowLinks() {
		if ($this->config->getAppValue('core', 'shareapi_allow_links', 'yes') !== 'yes') {
			return false;
		}

		$user = $this->userSession->getUser();
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
	 * Is password on public link requires
	 *
	 * @param bool Check group membership exclusion
	 * @return bool
	 */
	public function shareApiLinkEnforcePassword(bool $checkGroupMembership = true) {
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
		return $this->config->getAppValue('core', 'shareapi_enforce_links_password', 'no') === 'yes';
	}

	/**
	 * Is default link expire date enabled
	 *
	 * @return bool
	 */
	public function shareApiLinkDefaultExpireDate() {
		return $this->config->getAppValue('core', 'shareapi_default_expire_date', 'no') === 'yes';
	}

	/**
	 * Is default link expire date enforced
	 *`
	 *
	 * @return bool
	 */
	public function shareApiLinkDefaultExpireDateEnforced() {
		return $this->shareApiLinkDefaultExpireDate() &&
			$this->config->getAppValue('core', 'shareapi_enforce_expire_date', 'no') === 'yes';
	}


	/**
	 * Number of default link expire days
	 *
	 * @return int
	 */
	public function shareApiLinkDefaultExpireDays() {
		return (int)$this->config->getAppValue('core', 'shareapi_expire_after_n_days', '7');
	}

	/**
	 * Is default internal expire date enabled
	 *
	 * @return bool
	 */
	public function shareApiInternalDefaultExpireDate(): bool {
		return $this->config->getAppValue('core', 'shareapi_default_internal_expire_date', 'no') === 'yes';
	}

	/**
	 * Is default remote expire date enabled
	 *
	 * @return bool
	 */
	public function shareApiRemoteDefaultExpireDate(): bool {
		return $this->config->getAppValue('core', 'shareapi_default_remote_expire_date', 'no') === 'yes';
	}

	/**
	 * Is default expire date enforced
	 *
	 * @return bool
	 */
	public function shareApiInternalDefaultExpireDateEnforced(): bool {
		return $this->shareApiInternalDefaultExpireDate() &&
			$this->config->getAppValue('core', 'shareapi_enforce_internal_expire_date', 'no') === 'yes';
	}

	/**
	 * Is default expire date enforced for remote shares
	 *
	 * @return bool
	 */
	public function shareApiRemoteDefaultExpireDateEnforced(): bool {
		return $this->shareApiRemoteDefaultExpireDate() &&
			$this->config->getAppValue('core', 'shareapi_enforce_remote_expire_date', 'no') === 'yes';
	}

	/**
	 * Number of default expire days
	 *
	 * @return int
	 */
	public function shareApiInternalDefaultExpireDays(): int {
		return (int)$this->config->getAppValue('core', 'shareapi_internal_expire_after_n_days', '7');
	}

	/**
	 * Number of default expire days for remote shares
	 *
	 * @return int
	 */
	public function shareApiRemoteDefaultExpireDays(): int {
		return (int)$this->config->getAppValue('core', 'shareapi_remote_expire_after_n_days', '7');
	}

	/**
	 * Allow public upload on link shares
	 *
	 * @return bool
	 */
	public function shareApiLinkAllowPublicUpload() {
		return $this->config->getAppValue('core', 'shareapi_allow_public_upload', 'yes') === 'yes';
	}

	/**
	 * check if user can only share with group members
	 *
	 * @return bool
	 */
	public function shareWithGroupMembersOnly() {
		return $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';
	}

	/**
	 * If shareWithGroupMembersOnly is enabled, return an optional
	 * list of groups that must be excluded from the principle of
	 * belonging to the same group.
	 *
	 * @return array
	 */
	public function shareWithGroupMembersOnlyExcludeGroupsList() {
		if (!$this->shareWithGroupMembersOnly()) {
			return [];
		}
		$excludeGroups = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members_exclude_group_list', '');
		return json_decode($excludeGroups, true) ?? [];
	}

	/**
	 * Check if users can share with groups
	 *
	 * @return bool
	 */
	public function allowGroupSharing() {
		return $this->config->getAppValue('core', 'shareapi_allow_group_sharing', 'yes') === 'yes';
	}

	public function allowEnumeration(): bool {
		return $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
	}

	public function limitEnumerationToGroups(): bool {
		return $this->allowEnumeration() &&
			$this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
	}

	public function limitEnumerationToPhone(): bool {
		return $this->allowEnumeration() &&
			$this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_phone', 'no') === 'yes';
	}

	public function allowEnumerationFullMatch(): bool {
		return $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match', 'yes') === 'yes';
	}

	public function matchEmail(): bool {
		return $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match_email', 'yes') === 'yes';
	}

	public function ignoreSecondDisplayName(): bool {
		return $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_full_match_ignore_second_dn', 'no') === 'yes';
	}

	public function allowCustomTokens(): bool {
		return $this->appConfig->getValueBool('core', 'shareapi_allow_custom_tokens', false);
	}

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

	/**
	 * Check if sharing is disabled for the current user
	 */
	public function sharingDisabledForUser(?string $userId): bool {
		return $this->shareDisableChecker->sharingDisabledForUser($userId);
	}

	/**
	 * @inheritdoc
	 */
	public function outgoingServer2ServerSharesAllowed() {
		return $this->config->getAppValue('files_sharing', 'outgoing_server2server_share_enabled', 'yes') === 'yes';
	}

	/**
	 * @inheritdoc
	 */
	public function outgoingServer2ServerGroupSharesAllowed() {
		return $this->config->getAppValue('files_sharing', 'outgoing_server2server_group_share_enabled', 'no') === 'yes';
	}

	/**
	 * @inheritdoc
	 */
	public function shareProviderExists($shareType) {
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

	public function getAllShares(): iterable {
		$providers = $this->factory->getAllProviders();

		foreach ($providers as $provider) {
			yield from $provider->getAllShares();
		}
	}

	public function generateToken(): string {
		// Initial token length
		$tokenLength = \OC\Share\Helper::getTokenLength();

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
}

<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OC\Share20;

use OC\Cache\CappedMemoryCache;
use OC\Files\Mount\MoveableMount;
use OC\HintException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use OCP\Share\Exceptions\GenericShareException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IProviderFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * This class is the communication hub for all sharing related operations.
 */
class Manager implements IManager {

	/** @var IProviderFactory */
	private $factory;
	/** @var ILogger */
	private $logger;
	/** @var IConfig */
	private $config;
	/** @var ISecureRandom */
	private $secureRandom;
	/** @var IHasher */
	private $hasher;
	/** @var IMountManager */
	private $mountManager;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IL10N */
	private $l;
	/** @var IUserManager */
	private $userManager;
	/** @var IRootFolder */
	private $rootFolder;
	/** @var CappedMemoryCache */
	private $sharingDisabledForUsersCache;
	/** @var EventDispatcher */
	private $eventDispatcher;


	/**
	 * Manager constructor.
	 *
	 * @param ILogger $logger
	 * @param IConfig $config
	 * @param ISecureRandom $secureRandom
	 * @param IHasher $hasher
	 * @param IMountManager $mountManager
	 * @param IGroupManager $groupManager
	 * @param IL10N $l
	 * @param IProviderFactory $factory
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 * @param EventDispatcher $eventDispatcher
	 */
	public function __construct(
			ILogger $logger,
			IConfig $config,
			ISecureRandom $secureRandom,
			IHasher $hasher,
			IMountManager $mountManager,
			IGroupManager $groupManager,
			IL10N $l,
			IProviderFactory $factory,
			IUserManager $userManager,
			IRootFolder $rootFolder,
			EventDispatcher $eventDispatcher
	) {
		$this->logger = $logger;
		$this->config = $config;
		$this->secureRandom = $secureRandom;
		$this->hasher = $hasher;
		$this->mountManager = $mountManager;
		$this->groupManager = $groupManager;
		$this->l = $l;
		$this->factory = $factory;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->eventDispatcher = $eventDispatcher;
		$this->sharingDisabledForUsersCache = new CappedMemoryCache();
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
	 * @throws \Exception
	 */
	protected function verifyPassword($password) {
		if ($password === null) {
			// No password is set, check if this is allowed.
			if ($this->shareApiLinkEnforcePassword()) {
				throw new \InvalidArgumentException('Passwords are enforced for link shares');
			}

			return;
		}

		// Let others verify the password
		try {
			$event = new GenericEvent($password);
			$this->eventDispatcher->dispatch('OCP\PasswordPolicy::validate', $event);
		} catch (HintException $e) {
			throw new \Exception($e->getHint());
		}
	}

	/**
	 * Check for generic requirements before creating a share
	 *
	 * @param \OCP\Share\IShare $share
	 * @throws \InvalidArgumentException
	 * @throws GenericShareException
	 */
	protected function generalCreateChecks(\OCP\Share\IShare $share) {
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			// We expect a valid user as sharedWith for user shares
			if (!$this->userManager->userExists($share->getSharedWith())) {
				throw new \InvalidArgumentException('SharedWith is not a valid user');
			}
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			// We expect a valid group as sharedWith for group shares
			if (!$this->groupManager->groupExists($share->getSharedWith())) {
				throw new \InvalidArgumentException('SharedWith is not a valid group');
			}
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
			if ($share->getSharedWith() !== null) {
				throw new \InvalidArgumentException('SharedWith should be empty');
			}
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_REMOTE) {
			if ($share->getSharedWith() === null) {
				throw new \InvalidArgumentException('SharedWith should not be empty');
			}
		} else {
			// We can't handle other types yet
			throw new \InvalidArgumentException('unkown share type');
		}

		// Verify the initiator of the share is set
		if ($share->getSharedBy() === null) {
			throw new \InvalidArgumentException('SharedBy should be set');
		}

		// Cannot share with yourself
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER &&
			$share->getSharedWith() === $share->getSharedBy()) {
			throw new \InvalidArgumentException('Can\'t share with yourself');
		}

		// The path should be set
		if ($share->getNode() === null) {
			throw new \InvalidArgumentException('Path should be set');
		}

		// And it should be a file or a folder
		if (!($share->getNode() instanceof \OCP\Files\File) &&
				!($share->getNode() instanceof \OCP\Files\Folder)) {
			throw new \InvalidArgumentException('Path should be either a file or a folder');
		}

		// And you can't share your rootfolder
		if ($this->userManager->userExists($share->getSharedBy())) {
			$sharedPath = $this->rootFolder->getUserFolder($share->getSharedBy())->getPath();
		} else {
			$sharedPath = $this->rootFolder->getUserFolder($share->getShareOwner())->getPath();
		}
		if ($sharedPath === $share->getNode()->getPath()) {
			throw new \InvalidArgumentException('You can\'t share your root folder');
		}

		// Check if we actually have share permissions
		if (!$share->getNode()->isShareable()) {
			$message_t = $this->l->t('You are not allowed to share %s', [$share->getNode()->getPath()]);
			throw new GenericShareException($message_t, $message_t, 404);
		}

		// Permissions should be set
		if ($share->getPermissions() === null) {
			throw new \InvalidArgumentException('A share requires permissions');
		}

		/*
		 * Quick fix for #23536
		 * Non moveable mount points do not have update and delete permissions
		 * while we 'most likely' do have that on the storage.
		 */
		$permissions = $share->getNode()->getPermissions();
		$mount = $share->getNode()->getMountPoint();
		if (!($mount instanceof MoveableMount)) {
			$permissions |= \OCP\Constants::PERMISSION_DELETE | \OCP\Constants::PERMISSION_UPDATE;
		}

		// Check that we do not share with more permissions than we have
		if ($share->getPermissions() & ~$permissions) {
			$message_t = $this->l->t('Cannot increase permissions of %s', [$share->getNode()->getPath()]);
			throw new GenericShareException($message_t, $message_t, 404);
		}


		// Check that read permissions are always set
		// Link shares are allowed to have no read permissions to allow upload to hidden folders
		if ($share->getShareType() !== \OCP\Share::SHARE_TYPE_LINK &&
			($share->getPermissions() & \OCP\Constants::PERMISSION_READ) === 0) {
			throw new \InvalidArgumentException('Shares need at least read permissions');
		}

		if ($share->getNode() instanceof \OCP\Files\File) {
			if ($share->getPermissions() & \OCP\Constants::PERMISSION_DELETE) {
				$message_t = $this->l->t('Files can\'t be shared with delete permissions');
				throw new GenericShareException($message_t);
			}
			if ($share->getPermissions() & \OCP\Constants::PERMISSION_CREATE) {
				$message_t = $this->l->t('Files can\'t be shared with create permissions');
				throw new GenericShareException($message_t);
			}
		}
	}

	/**
	 * Validate if the expiration date fits the system settings
	 *
	 * @param \OCP\Share\IShare $share The share to validate the expiration date of
	 * @return \OCP\Share\IShare The modified share object
	 * @throws GenericShareException
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	protected function validateExpirationDate(\OCP\Share\IShare $share) {

		$expirationDate = $share->getExpirationDate();

		if ($expirationDate !== null) {
			//Make sure the expiration date is a date
			$expirationDate->setTime(0, 0, 0);

			$date = new \DateTime();
			$date->setTime(0, 0, 0);
			if ($date >= $expirationDate) {
				$message = $this->l->t('Expiration date is in the past');
				throw new GenericShareException($message, $message, 404);
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
			$expirationDate = new \DateTime();
			$expirationDate->setTime(0,0,0);
			$expirationDate->add(new \DateInterval('P'.$this->shareApiLinkDefaultExpireDays().'D'));
		}

		// If we enforce the expiration date check that is does not exceed
		if ($this->shareApiLinkDefaultExpireDateEnforced()) {
			if ($expirationDate === null) {
				throw new \InvalidArgumentException('Expiration date is enforced');
			}

			$date = new \DateTime();
			$date->setTime(0, 0, 0);
			$date->add(new \DateInterval('P' . $this->shareApiLinkDefaultExpireDays() . 'D'));
			if ($date < $expirationDate) {
				$message = $this->l->t('Cannot set expiration date more than %s days in the future', [$this->shareApiLinkDefaultExpireDays()]);
				throw new GenericShareException($message, $message, 404);
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
	 * @param \OCP\Share\IShare $share
	 * @throws \Exception
	 */
	protected function userCreateChecks(\OCP\Share\IShare $share) {
		// Check if we can share with group members only
		if ($this->shareWithGroupMembersOnly()) {
			$sharedBy = $this->userManager->get($share->getSharedBy());
			$sharedWith = $this->userManager->get($share->getSharedWith());
			// Verify we can share with this user
			$groups = array_intersect(
					$this->groupManager->getUserGroupIds($sharedBy),
					$this->groupManager->getUserGroupIds($sharedWith)
			);
			if (empty($groups)) {
				throw new \Exception('Only sharing with group members is allowed');
			}
		}

		/*
		 * TODO: Could be costly, fix
		 *
		 * Also this is not what we want in the future.. then we want to squash identical shares.
		 */
		$provider = $this->factory->getProviderForType(\OCP\Share::SHARE_TYPE_USER);
		$existingShares = $provider->getSharesByPath($share->getNode());
		foreach($existingShares as $existingShare) {
			// Ignore if it is the same share
			try {
				if ($existingShare->getFullId() === $share->getFullId()) {
					continue;
				}
			} catch (\UnexpectedValueException $e) {
				//Shares are not identical
			}

			// Identical share already existst
			if ($existingShare->getSharedWith() === $share->getSharedWith()) {
				throw new \Exception('Path already shared with this user');
			}

			// The share is already shared with this user via a group share
			if ($existingShare->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
				$group = $this->groupManager->get($existingShare->getSharedWith());
				$user = $this->userManager->get($share->getSharedWith());

				if ($group->inGroup($user) && $existingShare->getShareOwner() !== $share->getShareOwner()) {
					throw new \Exception('Path already shared with this user');
				}
			}
		}
	}

	/**
	 * Check for pre share requirements for group shares
	 *
	 * @param \OCP\Share\IShare $share
	 * @throws \Exception
	 */
	protected function groupCreateChecks(\OCP\Share\IShare $share) {
		// Verify group shares are allowed
		if (!$this->allowGroupSharing()) {
			throw new \Exception('Group sharing is now allowed');
		}

		// Verify if the user can share with this group
		if ($this->shareWithGroupMembersOnly()) {
			$sharedBy = $this->userManager->get($share->getSharedBy());
			$sharedWith = $this->groupManager->get($share->getSharedWith());
			if (!$sharedWith->inGroup($sharedBy)) {
				throw new \Exception('Only sharing within your own groups is allowed');
			}
		}

		/*
		 * TODO: Could be costly, fix
		 *
		 * Also this is not what we want in the future.. then we want to squash identical shares.
		 */
		$provider = $this->factory->getProviderForType(\OCP\Share::SHARE_TYPE_GROUP);
		$existingShares = $provider->getSharesByPath($share->getNode());
		foreach($existingShares as $existingShare) {
			try {
				if ($existingShare->getFullId() === $share->getFullId()) {
					continue;
				}
			} catch (\UnexpectedValueException $e) {
				//It is a new share so just continue
			}

			if ($existingShare->getSharedWith() === $share->getSharedWith()) {
				throw new \Exception('Path already shared with this group');
			}
		}
	}

	/**
	 * Check for pre share requirements for link shares
	 *
	 * @param \OCP\Share\IShare $share
	 * @throws \Exception
	 */
	protected function linkCreateChecks(\OCP\Share\IShare $share) {
		// Are link shares allowed?
		if (!$this->shareApiAllowLinks()) {
			throw new \Exception('Link sharing not allowed');
		}

		// Link shares by definition can't have share permissions
		if ($share->getPermissions() & \OCP\Constants::PERMISSION_SHARE) {
			throw new \InvalidArgumentException('Link shares can\'t have reshare permissions');
		}

		// Check if public upload is allowed
		if (!$this->shareApiLinkAllowPublicUpload() &&
			($share->getPermissions() & (\OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_DELETE))) {
			throw new \InvalidArgumentException('Public upload not allowed');
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
	 * @param \OCP\Share\IShare $share
	 */
	protected function setLinkParent(\OCP\Share\IShare $share) {

		// No sense in checking if the method is not there.
		if (method_exists($share, 'setParent')) {
			$storage = $share->getNode()->getStorage();
			if ($storage->instanceOfStorage('\OCA\Files_Sharing\ISharedStorage')) {
				$share->setParent($storage->getShareId());
			}
		};
	}

	/**
	 * @param File|Folder $path
	 */
	protected function pathCreateChecks($path) {
		// Make sure that we do not share a path that contains a shared mountpoint
		if ($path instanceof \OCP\Files\Folder) {
			$mounts = $this->mountManager->findIn($path->getPath());
			foreach($mounts as $mount) {
				if ($mount->getStorage()->instanceOfStorage('\OCA\Files_Sharing\ISharedStorage')) {
					throw new \InvalidArgumentException('Path contains files shared with you');
				}
			}
		}
	}

	/**
	 * Check if the user that is sharing can actually share
	 *
	 * @param \OCP\Share\IShare $share
	 * @throws \Exception
	 */
	protected function canShare(\OCP\Share\IShare $share) {
		if (!$this->shareApiEnabled()) {
			throw new \Exception('The share API is disabled');
		}

		if ($this->sharingDisabledForUser($share->getSharedBy())) {
			throw new \Exception('You are not allowed to share');
		}
	}

	/**
	 * Share a path
	 *
	 * @param \OCP\Share\IShare $share
	 * @return Share The share object
	 * @throws \Exception
	 *
	 * TODO: handle link share permissions or check them
	 */
	public function createShare(\OCP\Share\IShare $share) {
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
			while($parent->getStorage()->instanceOfStorage('OCA\Files_Sharing\External\Storage')) {
				$parent = $parent->getParent();
			}
			$share->setShareOwner($parent->getOwner()->getUID());
		} else {
			$share->setShareOwner($share->getNode()->getOwner()->getUID());
		}

		//Verify share type
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			$this->userCreateChecks($share);
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$this->groupCreateChecks($share);
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
			$this->linkCreateChecks($share);
			$this->setLinkParent($share);

			/*
			 * For now ignore a set token.
			 */
			$share->setToken(
				$this->secureRandom->generate(
					\OC\Share\Constants::TOKEN_LENGTH,
					\OCP\Security\ISecureRandom::CHAR_LOWER.
					\OCP\Security\ISecureRandom::CHAR_UPPER.
					\OCP\Security\ISecureRandom::CHAR_DIGITS
				)
			);

			//Verify the expiration date
			$this->validateExpirationDate($share);

			//Verify the password
			$this->verifyPassword($share->getPassword());

			// If a password is set. Hash it!
			if ($share->getPassword() !== null) {
				$share->setPassword($this->hasher->hash($share->getPassword()));
			}
		}

		// Cannot share with the owner
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER &&
			$share->getSharedWith() === $share->getShareOwner()) {
			throw new \InvalidArgumentException('Can\'t share with the share owner');
		}

		// Generate the target
		$target = $this->config->getSystemValue('share_folder', '/') .'/'. $share->getNode()->getName();
		$target = \OC\Files\Filesystem::normalizePath($target);
		$share->setTarget($target);

		// Pre share hook
		$run = true;
		$error = '';
		$preHookData = [
			'itemType' => $share->getNode() instanceof \OCP\Files\File ? 'file' : 'folder',
			'itemSource' => $share->getNode()->getId(),
			'shareType' => $share->getShareType(),
			'uidOwner' => $share->getSharedBy(),
			'permissions' => $share->getPermissions(),
			'fileSource' => $share->getNode()->getId(),
			'expiration' => $share->getExpirationDate(),
			'token' => $share->getToken(),
			'itemTarget' => $share->getTarget(),
			'shareWith' => $share->getSharedWith(),
			'run' => &$run,
			'error' => &$error,
		];
		\OC_Hook::emit('OCP\Share', 'pre_shared', $preHookData);

		if ($run === false) {
			throw new \Exception($error);
		}

		$oldShare = $share;
		$provider = $this->factory->getProviderForType($share->getShareType());
		$share = $provider->create($share);
		//reuse the node we already have
		$share->setNode($oldShare->getNode());

		// Post share hook
		$postHookData = [
			'itemType' => $share->getNode() instanceof \OCP\Files\File ? 'file' : 'folder',
			'itemSource' => $share->getNode()->getId(),
			'shareType' => $share->getShareType(),
			'uidOwner' => $share->getSharedBy(),
			'permissions' => $share->getPermissions(),
			'fileSource' => $share->getNode()->getId(),
			'expiration' => $share->getExpirationDate(),
			'token' => $share->getToken(),
			'id' => $share->getId(),
			'shareWith' => $share->getSharedWith(),
			'itemTarget' => $share->getTarget(),
			'fileTarget' => $share->getTarget(),
		];

		\OC_Hook::emit('OCP\Share', 'post_shared', $postHookData);

		return $share;
	}

	/**
	 * Update a share
	 *
	 * @param \OCP\Share\IShare $share
	 * @return \OCP\Share\IShare The share object
	 * @throws \InvalidArgumentException
	 */
	public function updateShare(\OCP\Share\IShare $share) {
		$expirationDateUpdated = false;

		$this->canShare($share);

		try {
			$originalShare = $this->getShareById($share->getFullId());
		} catch (\UnexpectedValueException $e) {
			throw new \InvalidArgumentException('Share does not have a full id');
		}

		// We can't change the share type!
		if ($share->getShareType() !== $originalShare->getShareType()) {
			throw new \InvalidArgumentException('Can\'t change share type');
		}

		// We can only change the recipient on user shares
		if ($share->getSharedWith() !== $originalShare->getSharedWith() &&
		    $share->getShareType() !== \OCP\Share::SHARE_TYPE_USER) {
			throw new \InvalidArgumentException('Can only update recipient on user shares');
		}

		// Cannot share with the owner
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER &&
			$share->getSharedWith() === $share->getShareOwner()) {
			throw new \InvalidArgumentException('Can\'t share with the share owner');
		}

		$this->generalCreateChecks($share);

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			$this->userCreateChecks($share);
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$this->groupCreateChecks($share);
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
			$this->linkCreateChecks($share);

			// Password updated.
			if ($share->getPassword() !== $originalShare->getPassword()) {
				//Verify the password
				$this->verifyPassword($share->getPassword());

				// If a password is set. Hash it!
				if ($share->getPassword() !== null) {
					$share->setPassword($this->hasher->hash($share->getPassword()));
				}
			}

			if ($share->getExpirationDate() != $originalShare->getExpirationDate()) {
				//Verify the expiration date
				$this->validateExpirationDate($share);
				$expirationDateUpdated = true;
			}
		}

		$this->pathCreateChecks($share->getNode());

		// Now update the share!
		$provider = $this->factory->getProviderForType($share->getShareType());
		$share = $provider->update($share);

		if ($expirationDateUpdated === true) {
			\OC_Hook::emit('OCP\Share', 'post_set_expiration_date', [
				'itemType' => $share->getNode() instanceof \OCP\Files\File ? 'file' : 'folder',
				'itemSource' => $share->getNode()->getId(),
				'date' => $share->getExpirationDate(),
				'uidOwner' => $share->getSharedBy(),
			]);
		}

		if ($share->getPassword() !== $originalShare->getPassword()) {
			\OC_Hook::emit('OCP\Share', 'post_update_password', [
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
			\OC_Hook::emit('OCP\Share', 'post_update_permissions', array(
				'itemType' => $share->getNode() instanceof \OCP\Files\File ? 'file' : 'folder',
				'itemSource' => $share->getNode()->getId(),
				'shareType' => $share->getShareType(),
				'shareWith' => $share->getSharedWith(),
				'uidOwner' => $share->getSharedBy(),
				'permissions' => $share->getPermissions(),
				'path' => $userFolder->getRelativePath($share->getNode()->getPath()),
			));
		}

		return $share;
	}

	/**
	 * Delete all the children of this share
	 * FIXME: remove once https://github.com/owncloud/core/pull/21660 is in
	 *
	 * @param \OCP\Share\IShare $share
	 * @return \OCP\Share\IShare[] List of deleted shares
	 */
	protected function deleteChildren(\OCP\Share\IShare $share) {
		$deletedShares = [];

		$provider = $this->factory->getProviderForType($share->getShareType());

		foreach ($provider->getChildren($share) as $child) {
			$deletedChildren = $this->deleteChildren($child);
			$deletedShares = array_merge($deletedShares, $deletedChildren);

			$provider->delete($child);
			$deletedShares[] = $child;
		}

		return $deletedShares;
	}

	/**
	 * Delete a share
	 *
	 * @param \OCP\Share\IShare $share
	 * @throws ShareNotFound
	 * @throws \InvalidArgumentException
	 */
	public function deleteShare(\OCP\Share\IShare $share) {

		try {
			$share->getFullId();
		} catch (\UnexpectedValueException $e) {
			throw new \InvalidArgumentException('Share does not have a full id');
		}

		$formatHookParams = function(\OCP\Share\IShare $share) {
			// Prepare hook
			$shareType = $share->getShareType();
			$sharedWith = '';
			if ($shareType === \OCP\Share::SHARE_TYPE_USER) {
				$sharedWith = $share->getSharedWith();
			} else if ($shareType === \OCP\Share::SHARE_TYPE_GROUP) {
				$sharedWith = $share->getSharedWith();
			} else if ($shareType === \OCP\Share::SHARE_TYPE_REMOTE) {
				$sharedWith = $share->getSharedWith();
			}

			$hookParams = [
				'id'         => $share->getId(),
				'itemType'   => $share->getNodeType(),
				'itemSource' => $share->getNodeId(),
				'shareType'  => $shareType,
				'shareWith'  => $sharedWith,
				'itemparent' => method_exists($share, 'getParent') ? $share->getParent() : '',
				'uidOwner'   => $share->getSharedBy(),
				'fileSource' => $share->getNodeId(),
				'fileTarget' => $share->getTarget()
			];
			return $hookParams;
		};

		$hookParams = $formatHookParams($share);

		// Emit pre-hook
		\OC_Hook::emit('OCP\Share', 'pre_unshare', $hookParams);

		// Get all children and delete them as well
		$deletedShares = $this->deleteChildren($share);

		// Do the actual delete
		$provider = $this->factory->getProviderForType($share->getShareType());
		$provider->delete($share);

		// All the deleted shares caused by this delete
		$deletedShares[] = $share;

		//Format hook info
		$formattedDeletedShares = array_map(function($share) use ($formatHookParams) {
			return $formatHookParams($share);
		}, $deletedShares);

		$hookParams['deletedShares'] = $formattedDeletedShares;

		// Emit post hook
		\OC_Hook::emit('OCP\Share', 'post_unshare', $hookParams);
	}


	/**
	 * Unshare a file as the recipient.
	 * This can be different from a regular delete for example when one of
	 * the users in a groups deletes that share. But the provider should
	 * handle this.
	 *
	 * @param \OCP\Share\IShare $share
	 * @param string $recipientId
	 */
	public function deleteFromSelf(\OCP\Share\IShare $share, $recipientId) {
		list($providerId, ) = $this->splitFullId($share->getFullId());
		$provider = $this->factory->getProvider($providerId);

		$provider->deleteFromSelf($share, $recipientId);
	}

	/**
	 * @inheritdoc
	 */
	public function moveShare(\OCP\Share\IShare $share, $recipientId) {
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
			throw new \InvalidArgumentException('Can\'t change target of link share');
		}

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER && $share->getSharedWith() !== $recipientId) {
			throw new \InvalidArgumentException('Invalid recipient');
		}

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$sharedWith = $this->groupManager->get($share->getSharedWith());
			$recipient = $this->userManager->get($recipientId);
			if (!$sharedWith->inGroup($recipient)) {
				throw new \InvalidArgumentException('Invalid recipient');
			}
		}

		list($providerId, ) = $this->splitFullId($share->getFullId());
		$provider = $this->factory->getProvider($providerId);

		$provider->move($share, $recipientId);
	}

	/**
	 * @inheritdoc
	 */
	public function getSharesBy($userId, $shareType, $path = null, $reshares = false, $limit = 50, $offset = 0) {
		if ($path !== null &&
				!($path instanceof \OCP\Files\File) &&
				!($path instanceof \OCP\Files\Folder)) {
			throw new \InvalidArgumentException('invalid path');
		}

		$provider = $this->factory->getProviderForType($shareType);

		$shares = $provider->getSharesBy($userId, $shareType, $path, $reshares, $limit, $offset);

		/*
		 * Work around so we don't return expired shares but still follow
		 * proper pagination.
		 */
		if ($shareType === \OCP\Share::SHARE_TYPE_LINK) {
			$shares2 = [];
			$today = new \DateTime();

			while(true) {
				$added = 0;
				foreach ($shares as $share) {
					// Check if the share is expired and if so delete it
					if ($share->getExpirationDate() !== null &&
						$share->getExpirationDate() <= $today
					) {
						try {
							$this->deleteShare($share);
						} catch (NotFoundException $e) {
							//Ignore since this basically means the share is deleted
						}
						continue;
					}
					$added++;
					$shares2[] = $share;

					if (count($shares2) === $limit) {
						break;
					}
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
				$shares = $provider->getSharesBy($userId, $shareType, $path, $reshares, $limit, $offset);

				// No more shares means we are done
				if (empty($shares)) {
					break;
				}
			}

			$shares = $shares2;
		}

		return $shares;
	}

	/**
	 * @inheritdoc
	 */
	public function getSharedWith($userId, $shareType, $node = null, $limit = 50, $offset = 0) {
		$provider = $this->factory->getProviderForType($shareType);

		return $provider->getSharedWith($userId, $shareType, $node, $limit, $offset);
	}

	/**
	 * @inheritdoc
	 */
	public function getShareById($id, $recipient = null) {
		if ($id === null) {
			throw new ShareNotFound();
		}

		list($providerId, $id) = $this->splitFullId($id);
		$provider = $this->factory->getProvider($providerId);

		$share = $provider->getShareById($id, $recipient);

		// Validate link shares expiration date
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK &&
			$share->getExpirationDate() !== null &&
			$share->getExpirationDate() <= new \DateTime()) {
			$this->deleteShare($share);
			throw new ShareNotFound();
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
	public function getSharesByPath(\OCP\Files\Node $path, $page=0, $perPage=50) {
	}

	/**
	 * Get the share by token possible with password
	 *
	 * @param string $token
	 * @return Share
	 *
	 * @throws ShareNotFound
	 */
	public function getShareByToken($token) {
		$provider = $this->factory->getProviderForType(\OCP\Share::SHARE_TYPE_LINK);

		try {
			$share = $provider->getShareByToken($token);
		} catch (ShareNotFound $e) {
			$share = null;
		}

		// If it is not a link share try to fetch a federated share by token
		if ($share === null) {
			$provider = $this->factory->getProviderForType(\OCP\Share::SHARE_TYPE_REMOTE);
			$share = $provider->getShareByToken($token);
		}

		if ($share->getExpirationDate() !== null &&
			$share->getExpirationDate() <= new \DateTime()) {
			$this->deleteShare($share);
			throw new ShareNotFound();
		}

		/*
		 * Reduce the permissions for link shares if public upload is not enabled
		 */
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK &&
			!$this->shareApiLinkAllowPublicUpload()) {
			$share->setPermissions($share->getPermissions() & ~(\OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE));
		}

		return $share;
	}

	/**
	 * Verify the password of a public share
	 *
	 * @param \OCP\Share\IShare $share
	 * @param string $password
	 * @return bool
	 */
	public function checkPassword(\OCP\Share\IShare $share, $password) {
		if ($share->getShareType() !== \OCP\Share::SHARE_TYPE_LINK) {
			//TODO maybe exception?
			return false;
		}

		if ($password === null || $share->getPassword() === null) {
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
		$types = [\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_GROUP, \OCP\Share::SHARE_TYPE_LINK, \OCP\Share::SHARE_TYPE_REMOTE];

		foreach ($types as $type) {
			$provider = $this->factory->getProviderForType($type);
			$provider->userDeleted($uid, $type);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function groupDeleted($gid) {
		$provider = $this->factory->getProviderForType(\OCP\Share::SHARE_TYPE_GROUP);
		$provider->groupDeleted($gid);
	}

	/**
	 * @inheritdoc
	 */
	public function userDeletedFromGroup($uid, $gid) {
		$provider = $this->factory->getProviderForType(\OCP\Share::SHARE_TYPE_GROUP);
		$provider->userDeletedFromGroup($uid, $gid);
	}

	/**
	 * Get access list to a path. This means
	 * all the users and groups that can access a given path.
	 *
	 * Consider:
	 * -root
	 * |-folder1
	 *  |-folder2
	 *   |-fileA
	 *
	 * fileA is shared with user1
	 * folder2 is shared with group2
	 * folder1 is shared with user2
	 *
	 * Then the access list will to '/folder1/folder2/fileA' is:
	 * [
	 * 	'users' => ['user1', 'user2'],
	 *  'groups' => ['group2']
	 * ]
	 *
	 * This is required for encryption
	 *
	 * @param \OCP\Files\Node $path
	 */
	public function getAccessList(\OCP\Files\Node $path) {
	}

	/**
	 * Create a new share
	 * @return \OCP\Share\IShare;
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
		return $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
	}

	/**
	 * Is password on public link requires
	 *
	 * @return bool
	 */
	public function shareApiLinkEnforcePassword() {
		return $this->config->getAppValue('core', 'shareapi_enforce_links_password', 'no') === 'yes';
	}

	/**
	 * Is default expire date enabled
	 *
	 * @return bool
	 */
	public function shareApiLinkDefaultExpireDate() {
		return $this->config->getAppValue('core', 'shareapi_default_expire_date', 'no') === 'yes';
	}

	/**
	 * Is default expire date enforced
	 *`
	 * @return bool
	 */
	public function shareApiLinkDefaultExpireDateEnforced() {
		return $this->shareApiLinkDefaultExpireDate() &&
			$this->config->getAppValue('core', 'shareapi_enforce_expire_date', 'no') === 'yes';
	}

	/**
	 * Number of default expire days
	 *shareApiLinkAllowPublicUpload
	 * @return int
	 */
	public function shareApiLinkDefaultExpireDays() {
		return (int)$this->config->getAppValue('core', 'shareapi_expire_after_n_days', '7');
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
	 * @return bool
	 */
	public function shareWithGroupMembersOnly() {
		return $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';
	}

	/**
	 * Check if users can share with groups
	 * @return bool
	 */
	public function allowGroupSharing() {
		return $this->config->getAppValue('core', 'shareapi_allow_group_sharing', 'yes') === 'yes';
	}

	/**
	 * Copied from \OC_Util::isSharingDisabledForUser
	 *
	 * TODO: Deprecate fuction from OC_Util
	 *
	 * @param string $userId
	 * @return bool
	 */
	public function sharingDisabledForUser($userId) {
		if ($userId === null) {
			return false;
		}

		if (isset($this->sharingDisabledForUsersCache[$userId])) {
			return $this->sharingDisabledForUsersCache[$userId];
		}

		if ($this->config->getAppValue('core', 'shareapi_exclude_groups', 'no') === 'yes') {
			$groupsList = $this->config->getAppValue('core', 'shareapi_exclude_groups_list', '');
			$excludedGroups = json_decode($groupsList);
			if (is_null($excludedGroups)) {
				$excludedGroups = explode(',', $groupsList);
				$newValue = json_encode($excludedGroups);
				$this->config->setAppValue('core', 'shareapi_exclude_groups_list', $newValue);
			}
			$user = $this->userManager->get($userId);
			$usersGroups = $this->groupManager->getUserGroupIds($user);
			if (!empty($usersGroups)) {
				$remainingGroups = array_diff($usersGroups, $excludedGroups);
				// if the user is only in groups which are disabled for sharing then
				// sharing is also disabled for the user
				if (empty($remainingGroups)) {
					$this->sharingDisabledForUsersCache[$userId] = true;
					return true;
				}
			}
		}

		$this->sharingDisabledForUsersCache[$userId] = false;
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function outgoingServer2ServerSharesAllowed() {
		return $this->config->getAppValue('files_sharing', 'outgoing_server2server_share_enabled', 'yes') === 'yes';
	}

}

<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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


use OC\Share20\Exception\BackendError;
use OC\Share20\Exception\ProviderException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\Security\ISecureRandom;
use OCP\Security\IHasher;
use OCP\Files\Mount\IMountManager;
use OCP\IGroupManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IUser;

use OC\Share20\Exception\ShareNotFound;
use OC\HintException;

/**
 * This class is the communication hub for all sharing related operations.
 */
class Manager {

	/** @var IProviderFactory */
	private $factory;

	/** @var array */
	private $type2provider;

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
	 */
	public function __construct(
			ILogger $logger,
			IConfig $config,
			ISecureRandom $secureRandom,
			IHasher $hasher,
			IMountManager $mountManager,
			IGroupManager $groupManager,
			IL10N $l,
			IProviderFactory $factory
	) {
		$this->providers = [];
		$this->type2provider = [];

		$this->logger = $logger;
		$this->config = $config;
		$this->secureRandom = $secureRandom;
		$this->hasher = $hasher;
		$this->mountManager = $mountManager;
		$this->groupManager = $groupManager;
		$this->l = $l;
		$this->factory = $factory;
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
		$accepted = true;
		$message = '';
		\OCP\Util::emitHook('\OC\Share', 'verifyPassword', [
				'password' => $password,
				'accepted' => &$accepted,
				'message' => &$message
		]);

		if (!$accepted) {
			throw new \Exception($message);
		}
	}

	/**
	 * Check for generic requirements before creating a share
	 *
	 * @param IShare $share
	 * @throws \Exception
	 */
	protected function generalCreateChecks(IShare $share) {
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			// We expect a valid user as sharedWith for user shares
			if (!($share->getSharedWith() instanceof \OCP\IUser)) {
				throw new \InvalidArgumentException('SharedWith should be an IUser');
			}
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			// We expect a valid group as sharedWith for group shares
			if (!($share->getSharedWith() instanceof \OCP\IGroup)) {
				throw new \InvalidArgumentException('SharedWith should be an IGroup');
			}
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
			if ($share->getSharedWith() !== null) {
				throw new \InvalidArgumentException('SharedWith should be empty');
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
		if ($share->getSharedWith() === $share->getSharedBy()) {
			throw new \InvalidArgumentException('Can\'t share with yourself');
		}

		// The path should be set
		if ($share->getPath() === null) {
			throw new \InvalidArgumentException('Path should be set');
		}

		// And it should be a file or a folder
		if (!($share->getPath() instanceof \OCP\Files\File) &&
				!($share->getPath() instanceof \OCP\Files\Folder)) {
			throw new \InvalidArgumentException('Path should be either a file or a folder');
		}

		// Check if we actually have share permissions
		if (!$share->getPath()->isShareable()) {
			$message_t = $this->l->t('You are not allowed to share %s', [$share->getPath()->getPath()]);
			throw new HintException($message_t, $message_t, 404);
		}

		// Permissions should be set
		if ($share->getPermissions() === null) {
			throw new \InvalidArgumentException('A share requires permissions');
		}

		// Check that we do not share with more permissions than we have
		if ($share->getPermissions() & ~$share->getPath()->getPermissions()) {
			$message_t = $this->l->t('Cannot increase permissions of %s', [$share->getPath()->getPath()]);
			throw new HintException($message_t, $message_t, 404);
		}

		// Check that read permissions are always set
		if (($share->getPermissions() & \OCP\Constants::PERMISSION_READ) === 0) {
			throw new \InvalidArgumentException('Shares need at least read permissions');
		}
	}

	/**
	 * Validate if the expiration date fits the system settings
	 *
	 * @param \DateTime $expirationDate The current expiration date (can be null)
	 * @return \DateTime|null The expiration date or null if $expireDate was null and it is not required
	 * @throws \OC\HintException
	 */
	protected function validateExpirationDate($expirationDate) {

		if ($expirationDate !== null) {
			//Make sure the expiration date is a date
			$expirationDate->setTime(0, 0, 0);

			$date = new \DateTime();
			$date->setTime(0, 0, 0);
			if ($date >= $expirationDate) {
				$message = $this->l->t('Expiration date is in the past');
				throw new \OC\HintException($message, $message, 404);
			}
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
				throw new \OC\HintException($message, $message, 404);
			}

			return $expirationDate;
		}

		// If expiredate is empty set a default one if there is a default
		if ($expirationDate === null && $this->shareApiLinkDefaultExpireDate()) {
			$date = new \DateTime();
			$date->setTime(0,0,0);
			$date->add(new \DateInterval('P'.$this->shareApiLinkDefaultExpireDays().'D'));
			return $date;
		}

		return $expirationDate;
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
			// Verify we can share with this user
			$groups = array_intersect(
					$this->groupManager->getUserGroupIds($share->getSharedBy()),
					$this->groupManager->getUserGroupIds($share->getSharedWith())
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
		$existingShares = $provider->getSharesByPath($share->getPath());
		foreach($existingShares as $existingShare) {
			// Ignore if it is the same share
			if ($existingShare->getFullId() === $share->getFullId()) {
				continue;
			}

			// Identical share already existst
			if ($existingShare->getSharedWith() === $share->getSharedWith()) {
				throw new \Exception('Path already shared with this user');
			}

			// The share is already shared with this user via a group share
			if ($existingShare->getShareType() === \OCP\Share::SHARE_TYPE_GROUP &&
					$existingShare->getSharedWith()->inGroup($share->getSharedWith()) &&
					$existingShare->getShareOwner() !== $share->getShareOwner()) {
				throw new \Exception('Path already shared with this user');
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
		// Verify if the user can share with this group
		if ($this->shareWithGroupMembersOnly()) {
			if (!$share->getSharedWith()->inGroup($share->getSharedBy())) {
				throw new \Exception('Only sharing within your own groups is allowed');
			}
		}

		/*
		 * TODO: Could be costly, fix
		 *
		 * Also this is not what we want in the future.. then we want to squash identical shares.
		 */
		$provider = $this->factory->getProviderForType(\OCP\Share::SHARE_TYPE_GROUP);
		$existingShares = $provider->getSharesByPath($share->getPath());
		foreach($existingShares as $existingShare) {
			if ($existingShare->getFullId() === $share->getFullId()) {
				continue;
			}

			if ($existingShare->getSharedWith() === $share->getSharedWith()) {
				throw new \Exception('Path already shared with this group');
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
			throw new \Exception('Link sharing not allowed');
		}

		// Link shares by definition can't have share permissions
		if ($share->getPermissions() & \OCP\Constants::PERMISSION_SHARE) {
			throw new \InvalidArgumentException('Link shares can\'t have reshare permissions');
		}

		// We don't allow deletion on link shares
		if ($share->getPermissions() & \OCP\Constants::PERMISSION_DELETE) {
			throw new \InvalidArgumentException('Link shares can\'t have delete permissions');
		}

		// Check if public upload is allowed
		if (!$this->shareApiLinkAllowPublicUpload() &&
			($share->getPermissions() & (\OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE))) {
			throw new \InvalidArgumentException('Public upload not allowed');
		}
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
	 * @param IShare $share
	 * @return bool
	 */
	protected function canShare(IShare $share) {
		if (!$this->shareApiEnabled()) {
			return false;
		}

		if ($this->isSharingDisabledForUser($share->getSharedBy())) {
			return false;
		}

		return true;
	}

	/**
	 * Share a path
	 *
	 * @param IShare $share
	 * @return Share The share object
	 * @throws \Exception
	 *
	 * TODO: handle link share permissions or check them
	 */
	public function createShare(IShare $share) {
		if (!$this->canShare($share)) {
			throw new \Exception('The Share API is disabled');
		}

		$this->generalCreateChecks($share);

		//Verify share type
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			$this->userCreateChecks($share);
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$this->groupCreateChecks($share);
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
			$this->linkCreateChecks($share);

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
			$share->setExpirationDate($this->validateExpirationDate($share->getExpirationDate()));

			//Verify the password
			$this->verifyPassword($share->getPassword());

			// If a password is set. Hash it!
			if ($share->getPassword() !== null) {
				$share->setPassword($this->hasher->hash($share->getPassword()));
			}
		}

		// Verify if there are any issues with the path
		$this->pathCreateChecks($share->getPath());

		// On creation of a share the owner is always the owner of the path
		$share->setShareOwner($share->getPath()->getOwner());

		// Cannot share with the owner
		if ($share->getSharedWith() === $share->getShareOwner()) {
			throw new \InvalidArgumentException('Can\'t share with the share owner');
		}

		// Generate the target
		$target = $this->config->getSystemValue('share_folder', '/') .'/'. $share->getPath()->getName();
		$target = \OC\Files\Filesystem::normalizePath($target);
		$share->setTarget($target);

		//Get sharewith for hooks
		$sharedWith = null;
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			$sharedWith = $share->getSharedWith()->getUID();
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$sharedWith = $share->getSharedWith()->getGID();
		} else {
			$sharedWith = $share->getSharedWith();
		}

		// Pre share hook
		$run = true;
		$error = '';
		$preHookData = [
			'itemType' => $share->getPath() instanceof \OCP\Files\File ? 'file' : 'folder',
			'itemSource' => $share->getPath()->getId(),
			'shareType' => $share->getShareType(),
			'uidOwner' => $share->getSharedBy()->getUID(),
			'permissions' => $share->getPermissions(),
			'fileSource' => $share->getPath()->getId(),
			'expiration' => $share->getExpirationDate(),
			'token' => $share->getToken(),
			'itemTarget' => $share->getTarget(),
			'shareWith' => $sharedWith,
			'run' => &$run,
			'error' => &$error,
		];
		\OC_Hook::emit('OCP\Share', 'pre_shared', $preHookData);

		if ($run === false) {
			throw new \Exception($error);
		}

		$provider = $this->factory->getProviderForType($share->getShareType());
		$share = $provider->create($share);
		$share->setProviderId($provider->identifier());

		// Post share hook
		$postHookData = [
			'itemType' => $share->getPath() instanceof \OCP\Files\File ? 'file' : 'folder',
			'itemSource' => $share->getPath()->getId(),
			'shareType' => $share->getShareType(),
			'uidOwner' => $share->getSharedBy()->getUID(),
			'permissions' => $share->getPermissions(),
			'fileSource' => $share->getPath()->getId(),
			'expiration' => $share->getExpirationDate(),
			'token' => $share->getToken(),
			'id' => $share->getId(),
			'shareWith' => $sharedWith,
			'itemTarget' => $share->getTarget(),
			'fileTarget' => $share->getTarget(),
		];

		\OC_Hook::emit('OCP\Share', 'post_shared', $postHookData);

		return $share;
	}

	/**
	 * Update a share
	 *
	 * @param IShare $share
	 * @return IShare The share object
	 */
	public function updateShare(IShare $share) {
		$expirationDateUpdated = false;

		if (!$this->canShare($share)) {
			throw new \Exception('The Share API is disabled');
		}

		$originalShare = $this->getShareById($share->getFullId());

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
		if ($share->getSharedWith() === $share->getShareOwner()) {
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

			if ($share->getExpirationDate() !== $originalShare->getExpirationDate()) {
				//Verify the expiration date
				$share->setExpirationDate($this->validateExpirationDate($share->getExpirationDate()));
				$expirationDateUpdated = true;
			}
		}

		$this->pathCreateChecks($share->getPath());

		// Now update the share!
		$provider = $this->factory->getProviderForType($share->getShareType());
		$share = $provider->update($share);

		if ($expirationDateUpdated === true) {
			\OC_Hook::emit('OCP\Share', 'post_set_expiration_date', [
				'itemType' => $share->getPath() instanceof \OCP\Files\File ? 'file' : 'folder',
				'itemSource' => $share->getPath()->getId(),
				'date' => $share->getExpirationDate(),
				'uidOwner' => $share->getSharedBy()->getUID(),
			]);
		}

		return $share;
	}

	/**
	 * Delete all the children of this share
	 *
	 * @param IShare $share
	 * @return IShare[] List of deleted shares
	 */
	protected function deleteChildren(IShare $share) {
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
	 * @param IShare $share
	 * @throws ShareNotFound
	 * @throws BackendError
	 * @throws ShareNotFound
	 */
	public function deleteShare(IShare $share) {
		// Just to make sure we have all the info
		$share = $this->getShareById($share->getFullId());

		$formatHookParams = function(IShare $share) {
			// Prepare hook
			$shareType = $share->getShareType();
			$sharedWith = '';
			if ($shareType === \OCP\Share::SHARE_TYPE_USER) {
				$sharedWith = $share->getSharedWith()->getUID();
			} else if ($shareType === \OCP\Share::SHARE_TYPE_GROUP) {
				$sharedWith = $share->getSharedWith()->getGID();
			} else if ($shareType === \OCP\Share::SHARE_TYPE_REMOTE) {
				$sharedWith = $share->getSharedWith();
			}

			$hookParams = [
				'id'         => $share->getId(),
				'itemType'   => $share->getPath() instanceof \OCP\Files\File ? 'file' : 'folder',
				'itemSource' => $share->getPath()->getId(),
				'shareType'  => $shareType,
				'shareWith'  => $sharedWith,
				'itemparent' => $share->getParent(),
				'uidOwner'   => $share->getSharedBy()->getUID(),
				'fileSource' => $share->getPath()->getId(),
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
	 * @param IShare $share
	 * @param IUser $recipient
	 */
	public function deleteFromSelf(IShare $share, IUser $recipient) {
		list($providerId, $id) = $this->splitFullId($share->getId());
		$provider = $this->factory->getProvider($providerId);

		$provider->deleteFromSelf($share, $recipient);
	}

	/**
	 * Get shares shared by (initiated) by the provided user.
	 *
	 * @param IUser $user
	 * @param int $shareType
	 * @param \OCP\Files\File|\OCP\Files\Folder $path
	 * @param bool $reshares
	 * @param int $limit The maximum number of returned results, -1 for all results
	 * @param int $offset
	 * @return IShare[]
	 */
	public function getSharesBy(IUser $user, $shareType, $path = null, $reshares = false, $limit = 50, $offset = 0) {
		if ($path !== null &&
				!($path instanceof \OCP\Files\File) &&
				!($path instanceof \OCP\Files\Folder)) {
			throw new \InvalidArgumentException('invalid path');
		}

		$provider = $this->factory->getProviderForType($shareType);

		return $provider->getSharesBy($user, $shareType, $path, $reshares, $limit, $offset);
	}

	/**
	 * Get shares shared with $user.
	 *
	 * @param IUser $user
	 * @param int $shareType
	 * @param int $limit The maximum number of shares returned, -1 for all
	 * @param int $offset
	 * @return IShare[]
	 */
	public function getSharedWith(IUser $user, $shareType, $limit = 50, $offset = 0) {
		$provider = $this->factory->getProviderForType($shareType);

		return $provider->getSharedWith($user, $shareType, $limit, $offset);
	}

	/**
	 * Retrieve a share by the share id
	 *
	 * @param string $id
	 * @return Share
	 *
	 * @throws ShareNotFound
	 */
	public function getShareById($id) {
		if ($id === null) {
			throw new ShareNotFound();
		}

		list($providerId, $id) = $this->splitFullId($id);
		$provider = $this->factory->getProvider($providerId);

		$share = $provider->getShareById($id);
		$share->setProviderId($provider->identifier());

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

		$share = $provider->getShareByToken($token);

		//TODO check if share expired

		return $share;
	}

	/**
	 * Verify the password of a public share
	 *
	 * @param IShare $share
	 * @param string $password
	 * @return bool
	 */
	public function checkPassword(IShare $share, $password) {
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
			//TODO update hash!
		}

		return true;
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
	 * @return IShare;
	 */
	public function newShare() {
		return new \OC\Share20\Share();
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
		return $this->config->getAppValue('core', 'shareapi_enforce_expire_date', 'no') === 'yes';
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
	 * Copied from \OC_Util::isSharingDisabledForUser
	 *
	 * TODO: Deprecate fuction from OC_Util
	 *
	 * @param IUser $user
	 * @return bool
	 */
	public function isSharingDisabledForUser($user) {
		if ($this->config->getAppValue('core', 'shareapi_exclude_groups', 'no') === 'yes') {
			$groupsList = $this->config->getAppValue('core', 'shareapi_exclude_groups_list', '');
			$excludedGroups = json_decode($groupsList);
			if (is_null($excludedGroups)) {
				$excludedGroups = explode(',', $groupsList);
				$newValue = json_encode($excludedGroups);
				$this->config->setAppValue('core', 'shareapi_exclude_groups_list', $newValue);
			}
			$usersGroups = $this->groupManager->getUserGroupIds($user);
			if (!empty($usersGroups)) {
				$remainingGroups = array_diff($usersGroups, $excludedGroups);
				// if the user is only in groups which are disabled for sharing then
				// sharing is also disabled for the user
				if (empty($remainingGroups)) {
					return true;
				}
			}
		}
		return false;
	}

}

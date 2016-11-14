<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
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
namespace OCA\Files_Sharing\API;

use OCP\Files\NotFoundException;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Files\IRootFolder;
use OCP\Lock\LockedException;
use OCP\Share;
use OCP\Share\IManager;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\Exceptions\GenericShareException;
use OCP\Lock\ILockingProvider;

/**
 * Class Share20OCS
 *
 * @package OCA\Files_Sharing\API
 */
class Share20OCS {

	/** @var IManager */
	private $shareManager;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IUserManager */
	private $userManager;
	/** @var IRequest */
	private $request;
	/** @var IRootFolder */
	private $rootFolder;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IUser */
	private $currentUser;
	/** @var IL10N */
	private $l;

	/**
	 * Share20OCS constructor.
	 *
	 * @param IManager $shareManager
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param IRequest $request
	 * @param IRootFolder $rootFolder
	 * @param IURLGenerator $urlGenerator
	 * @param IUser $currentUser
	 */
	public function __construct(
			IManager $shareManager,
			IGroupManager $groupManager,
			IUserManager $userManager,
			IRequest $request,
			IRootFolder $rootFolder,
			IURLGenerator $urlGenerator,
			IUser $currentUser,
			IL10N $l10n
	) {
		$this->shareManager = $shareManager;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->request = $request;
		$this->rootFolder = $rootFolder;
		$this->urlGenerator = $urlGenerator;
		$this->currentUser = $currentUser;
		$this->l = $l10n;
	}

	/**
	 * Convert an IShare to an array for OCS output
	 *
	 * @param \OCP\Share\IShare $share
	 * @return array
	 * @throws NotFoundException In case the node can't be resolved.
	 */
	protected function formatShare(\OCP\Share\IShare $share) {
		$sharedBy = $this->userManager->get($share->getSharedBy());
		$shareOwner = $this->userManager->get($share->getShareOwner());

		$result = [
			'id' => $share->getId(),
			'share_type' => $share->getShareType(),
			'uid_owner' => $share->getSharedBy(),
			'displayname_owner' => $sharedBy !== null ? $sharedBy->getDisplayName() : $share->getSharedBy(),
			'permissions' => $share->getPermissions(),
			'stime' => $share->getShareTime()->getTimestamp(),
			'parent' => null,
			'expiration' => null,
			'token' => null,
			'uid_file_owner' => $share->getShareOwner(),
			'displayname_file_owner' => $shareOwner !== null ? $shareOwner->getDisplayName() : $share->getShareOwner(),
		];

		$userFolder = $this->rootFolder->getUserFolder($this->currentUser->getUID());
		$nodes = $userFolder->getById($share->getNodeId());

		if (empty($nodes)) {
			throw new NotFoundException();
		}

		$node = $nodes[0];

		$result['path'] = $userFolder->getRelativePath($node->getPath());
		if ($node instanceOf \OCP\Files\Folder) {
			$result['item_type'] = 'folder';
		} else {
			$result['item_type'] = 'file';
		}
		$result['mimetype'] = $node->getMimeType();
		$result['storage_id'] = $node->getStorage()->getId();
		$result['storage'] = $node->getStorage()->getCache()->getNumericStorageId();
		$result['item_source'] = $node->getId();
		$result['file_source'] = $node->getId();
		$result['file_parent'] = $node->getParent()->getId();
		$result['file_target'] = $share->getTarget();

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			$sharedWith = $this->userManager->get($share->getSharedWith());
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = $sharedWith !== null ? $sharedWith->getDisplayName() : $share->getSharedWith();
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = $share->getSharedWith();
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {

			$result['share_with'] = $share->getPassword();
			$result['share_with_displayname'] = $share->getPassword();

			$result['token'] = $share->getToken();
			$result['url'] = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', ['token' => $share->getToken()]);

			$expiration = $share->getExpirationDate();
			if ($expiration !== null) {
				$result['expiration'] = $expiration->format('Y-m-d 00:00:00');
			}

		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_REMOTE) {
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = $share->getSharedWith();
			$result['token'] = $share->getToken();
		}

		$result['mail_send'] = $share->getMailSend() ? 1 : 0;

		return $result;
	}

	/**
	 * Get a specific share by id
	 *
	 * @param string $id
	 * @return \OC_OCS_Result
	 */
	public function getShare($id) {
		if (!$this->shareManager->shareApiEnabled()) {
			return new \OC_OCS_Result(null, 404, $this->l->t('Share API is disabled'));
		}

		try {
			$share = $this->getShareById($id);
		} catch (ShareNotFound $e) {
			return new \OC_OCS_Result(null, 404, $this->l->t('Wrong share ID, share doesn\'t exist'));
		}

		if ($this->canAccessShare($share)) {
			try {
				$share = $this->formatShare($share);
				return new \OC_OCS_Result([$share]);
			} catch (NotFoundException $e) {
				//Fall trough
			}
		}

		return new \OC_OCS_Result(null, 404, $this->l->t('Wrong share ID, share doesn\'t exist'));
	}

	/**
	 * Delete a share
	 *
	 * @param string $id
	 * @return \OC_OCS_Result
	 */
	public function deleteShare($id) {
		if (!$this->shareManager->shareApiEnabled()) {
			return new \OC_OCS_Result(null, 404, $this->l->t('Share API is disabled'));
		}

		try {
			$share = $this->getShareById($id);
		} catch (ShareNotFound $e) {
			return new \OC_OCS_Result(null, 404, $this->l->t('Wrong share ID, share doesn\'t exist'));
		}

		try {
			$share->getNode()->lock(ILockingProvider::LOCK_SHARED);
		} catch (LockedException $e) {
			return new \OC_OCS_Result(null, 404, 'could not delete share');
		}

		if (!$this->canAccessShare($share, false)) {
			$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
			return new \OC_OCS_Result(null, 404, $this->l->t('Could not delete share'));
		}

		$this->shareManager->deleteShare($share);

		$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);

		return new \OC_OCS_Result();
	}

	/**
	 * @return \OC_OCS_Result
	 */
	public function createShare() {
		$share = $this->shareManager->newShare();

		if (!$this->shareManager->shareApiEnabled()) {
			return new \OC_OCS_Result(null, 404, $this->l->t('Share API is disabled'));
		}

		// Verify path
		$path = $this->request->getParam('path', null);
		if ($path === null) {
			return new \OC_OCS_Result(null, 404, $this->l->t('Please specify a file or folder path'));
		}

		$userFolder = $this->rootFolder->getUserFolder($this->currentUser->getUID());
		try {
			$path = $userFolder->get($path);
		} catch (NotFoundException $e) {
			return new \OC_OCS_Result(null, 404, $this->l->t('Wrong path, file/folder doesn\'t exist'));
		}

		$share->setNode($path);

		try {
			$share->getNode()->lock(ILockingProvider::LOCK_SHARED);
		} catch (LockedException $e) {
			return new \OC_OCS_Result(null, 404, 'Could not create share');
		}

		// Parse permissions (if available)
		$permissions = $this->request->getParam('permissions', null);
		if ($permissions === null) {
			$permissions = \OCP\Constants::PERMISSION_ALL;
		} else {
			$permissions = (int)$permissions;
		}

		if ($permissions < 0 || $permissions > \OCP\Constants::PERMISSION_ALL) {
			$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
			return new \OC_OCS_Result(null, 404, 'invalid permissions');
		}

		// Shares always require read permissions
		$permissions |= \OCP\Constants::PERMISSION_READ;

		if ($path instanceof \OCP\Files\File) {
			// Single file shares should never have delete or create permissions
			$permissions &= ~\OCP\Constants::PERMISSION_DELETE;
			$permissions &= ~\OCP\Constants::PERMISSION_CREATE;
		}

		/*
		 * Hack for https://github.com/owncloud/core/issues/22587
		 * We check the permissions via webdav. But the permissions of the mount point
		 * do not equal the share permissions. Here we fix that for federated mounts.
		 */
		if ($path->getStorage()->instanceOfStorage('OCA\Files_Sharing\External\Storage')) {
			$permissions &= ~($permissions & ~$path->getPermissions());
		}

		$shareWith = $this->request->getParam('shareWith', null);
		$shareType = (int)$this->request->getParam('shareType', '-1');

		if ($shareType === \OCP\Share::SHARE_TYPE_USER) {
			// Valid user is required to share
			if ($shareWith === null || !$this->userManager->userExists($shareWith)) {
				$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
				return new \OC_OCS_Result(null, 404, $this->l->t('Please specify a valid user'));
			}
			$share->setSharedWith($shareWith);
			$share->setPermissions($permissions);
		} else if ($shareType === \OCP\Share::SHARE_TYPE_GROUP) {
			if (!$this->shareManager->allowGroupSharing()) {
				$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
				return new \OC_OCS_Result(null, 404, $this->l->t('Group sharing is disabled by the administrator'));
			}

			// Valid group is required to share
			if ($shareWith === null || !$this->groupManager->groupExists($shareWith)) {
				$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
				return new \OC_OCS_Result(null, 404, $this->l->t('Please specify a valid group'));
			}
			$share->setSharedWith($shareWith);
			$share->setPermissions($permissions);
		} else if ($shareType === \OCP\Share::SHARE_TYPE_LINK) {
			//Can we even share links?
			if (!$this->shareManager->shareApiAllowLinks()) {
				$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
				return new \OC_OCS_Result(null, 404, $this->l->t('Public link sharing is disabled by the administrator'));
			}

			/*
			 * For now we only allow 1 link share.
			 * Return the existing link share if this is a duplicate
			 */
			$existingShares = $this->shareManager->getSharesBy($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_LINK, $path, false, 1, 0);
			if (!empty($existingShares)) {
				$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
				return new \OC_OCS_Result($this->formatShare($existingShares[0]));
			}

			$publicUpload = $this->request->getParam('publicUpload', null);
			if ($publicUpload === 'true') {
				// Check if public upload is allowed
				if (!$this->shareManager->shareApiLinkAllowPublicUpload()) {
					$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
					return new \OC_OCS_Result(null, 403, $this->l->t('Public upload disabled by the administrator'));
				}

				// Public upload can only be set for folders
				if ($path instanceof \OCP\Files\File) {
					$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
					return new \OC_OCS_Result(null, 404, $this->l->t('Public upload is only possible for publicly shared folders'));
				}

				$share->setPermissions(
					\OCP\Constants::PERMISSION_READ |
					\OCP\Constants::PERMISSION_CREATE |
					\OCP\Constants::PERMISSION_UPDATE |
					\OCP\Constants::PERMISSION_DELETE
				);
			} else {
				$share->setPermissions(\OCP\Constants::PERMISSION_READ);
			}

			// Set password
			$password = $this->request->getParam('password', '');

			if ($password !== '') {
				$share->setPassword($password);
			}

			//Expire date
			$expireDate = $this->request->getParam('expireDate', '');

			if ($expireDate !== '') {
				try {
					$expireDate = $this->parseDate($expireDate);
					$share->setExpirationDate($expireDate);
				} catch (\Exception $e) {
					$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
					return new \OC_OCS_Result(null, 404, $this->l->t('Invalid date, date format must be YYYY-MM-DD'));
				}
			}

		} else if ($shareType === \OCP\Share::SHARE_TYPE_REMOTE) {
			if (!$this->shareManager->outgoingServer2ServerSharesAllowed()) {
				$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
				return new \OC_OCS_Result(null, 403, $this->l->t('Sharing %s failed because the back end does not allow shares from type %s', [$path->getPath(), $shareType]));
			}

			$share->setSharedWith($shareWith);
			$share->setPermissions($permissions);
		} else {
			$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
			return new \OC_OCS_Result(null, 400, $this->l->t('Unknown share type'));
		}

		$share->setShareType($shareType);
		$share->setSharedBy($this->currentUser->getUID());

		try {
			$share = $this->shareManager->createShare($share);
		} catch (GenericShareException $e) {
			$code = $e->getCode() === 0 ? 403 : $e->getCode();
			$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
			return new \OC_OCS_Result(null, $code, $e->getHint());
		}catch (\Exception $e) {
			$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
			return new \OC_OCS_Result(null, 403, $e->getMessage());
		}

		$output = $this->formatShare($share);

		$share->getNode()->unlock(\OCP\Lock\ILockingProvider::LOCK_SHARED);

		return new \OC_OCS_Result($output);
	}

	/**
	 * @param \OCP\Files\File|\OCP\Files\Folder $node
	 * @return \OC_OCS_Result
	 */
	private function getSharedWithMe($node = null) {
		$userShares = $this->shareManager->getSharedWith($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_USER, $node, -1, 0);
		$groupShares = $this->shareManager->getSharedWith($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_GROUP, $node, -1, 0);

		$shares = array_merge($userShares, $groupShares);

		$formatted = [];
		foreach ($shares as $share) {
			if ($this->canAccessShare($share)) {
				try {
					$formatted[] = $this->formatShare($share);
				} catch (NotFoundException $e) {
					// Ignore this share
				}
			}
		}

		return new \OC_OCS_Result($formatted);
	}

	/**
	 * @param \OCP\Files\Folder $folder
	 * @return \OC_OCS_Result
	 */
	private function getSharesInDir($folder) {
		if (!($folder instanceof \OCP\Files\Folder)) {
			return new \OC_OCS_Result(null, 400, $this->l->t('Not a directory'));
		}

		$nodes = $folder->getDirectoryListing();
		/** @var \OCP\Share\IShare[] $shares */
		$shares = [];
		foreach ($nodes as $node) {
			$shares = array_merge($shares, $this->shareManager->getSharesBy($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_USER, $node, false, -1, 0));
			$shares = array_merge($shares, $this->shareManager->getSharesBy($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_GROUP, $node, false, -1, 0));
			$shares = array_merge($shares, $this->shareManager->getSharesBy($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_LINK, $node, false, -1, 0));
			if ($this->shareManager->outgoingServer2ServerSharesAllowed()) {
				$shares = array_merge($shares, $this->shareManager->getSharesBy($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_REMOTE, $node, false, -1, 0));
			}
		}

		$formatted = [];
		foreach ($shares as $share) {
			try {
				$formatted[] = $this->formatShare($share);
			} catch (NotFoundException $e) {
				//Ignore this share
			}
		}

		return new \OC_OCS_Result($formatted);
	}

	/**
	 * The getShares function.
	 *
	 * - Get shares by the current user
	 * - Get shares by the current user and reshares (?reshares=true)
	 * - Get shares with the current user (?shared_with_me=true)
	 * - Get shares for a specific path (?path=...)
	 * - Get all shares in a folder (?subfiles=true&path=..)
	 *
	 * @return \OC_OCS_Result
	 */
	public function getShares() {
		if (!$this->shareManager->shareApiEnabled()) {
			return new \OC_OCS_Result();
		}

		$sharedWithMe = $this->request->getParam('shared_with_me', null);
		$reshares = $this->request->getParam('reshares', null);
		$subfiles = $this->request->getParam('subfiles');
		$path = $this->request->getParam('path', null);

		if ($path !== null) {
			$userFolder = $this->rootFolder->getUserFolder($this->currentUser->getUID());
			try {
				$path = $userFolder->get($path);
				$path->lock(ILockingProvider::LOCK_SHARED);
			} catch (\OCP\Files\NotFoundException $e) {
				return new \OC_OCS_Result(null, 404, $this->l->t('Wrong path, file/folder doesn\'t exist'));
			} catch (LockedException $e) {
				return new \OC_OCS_Result(null, 404, $this->l->t('Could not lock path'));
			}
		}

		if ($sharedWithMe === 'true') {
			$result = $this->getSharedWithMe($path);
			if ($path !== null) {
				$path->unlock(ILockingProvider::LOCK_SHARED);
			}
			return $result;
		}

		if ($subfiles === 'true') {
			$result = $this->getSharesInDir($path);
			if ($path !== null) {
				$path->unlock(ILockingProvider::LOCK_SHARED);
			}
			return $result;
		}

		if ($reshares === 'true') {
			$reshares = true;
		} else {
			$reshares = false;
		}

		// Get all shares
		$userShares = $this->shareManager->getSharesBy($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_USER, $path, $reshares, -1, 0);
		$groupShares = $this->shareManager->getSharesBy($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_GROUP, $path, $reshares, -1, 0);
		$linkShares = $this->shareManager->getSharesBy($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_LINK, $path, $reshares, -1, 0);
		$shares = array_merge($userShares, $groupShares, $linkShares);

		if ($this->shareManager->outgoingServer2ServerSharesAllowed()) {
			$federatedShares = $this->shareManager->getSharesBy($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_REMOTE, $path, $reshares, -1, 0);
			$shares = array_merge($shares, $federatedShares);
		}

		$formatted = [];
		foreach ($shares as $share) {
			try {
				$formatted[] = $this->formatShare($share);
			} catch (NotFoundException $e) {
				//Ignore share
			}
		}

		if ($path !== null) {
			$path->unlock(ILockingProvider::LOCK_SHARED);
		}

		return new \OC_OCS_Result($formatted);
	}

	/**
	 * @param int $id
	 * @return \OC_OCS_Result
	 */
	public function updateShare($id) {
		if (!$this->shareManager->shareApiEnabled()) {
			return new \OC_OCS_Result(null, 404, $this->l->t('Share API is disabled'));
		}

		try {
			$share = $this->getShareById($id);
		} catch (ShareNotFound $e) {
			return new \OC_OCS_Result(null, 404, $this->l->t('Wrong share ID, share doesn\'t exist'));
		}

		$share->getNode()->lock(\OCP\Lock\ILockingProvider::LOCK_SHARED);

		if (!$this->canAccessShare($share, false)) {
			$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
			return new \OC_OCS_Result(null, 404, $this->l->t('Wrong share ID, share doesn\'t exist'));
		}

		$permissions = $this->request->getParam('permissions', null);
		$password = $this->request->getParam('password', null);
		$publicUpload = $this->request->getParam('publicUpload', null);
		$expireDate = $this->request->getParam('expireDate', null);

		/*
		 * expirationdate, password and publicUpload only make sense for link shares
		 */
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
			if ($permissions === null && $password === null && $publicUpload === null && $expireDate === null) {
				$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
				return new \OC_OCS_Result(null, 400, 'Wrong or no update parameter given');
			}

			$newPermissions = null;
			if ($publicUpload === 'true') {
				$newPermissions = \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_DELETE;
			} else if ($publicUpload === 'false') {
				$newPermissions = \OCP\Constants::PERMISSION_READ;
			}

			if ($permissions !== null) {
				$newPermissions = (int)$permissions;
			}

			if ($newPermissions !== null &&
				!in_array($newPermissions, [
					\OCP\Constants::PERMISSION_READ,
					\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE, // legacy
					\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_DELETE, // correct
					\OCP\Constants::PERMISSION_CREATE, // hidden file list
				])
			) {
				$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
				return new \OC_OCS_Result(null, 400, $this->l->t('Can\'t change permissions for public share links'));
			}

			if (
				// legacy
				$newPermissions === (\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE) ||
				// correct
				$newPermissions === (\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_DELETE)
			) {
				if (!$this->shareManager->shareApiLinkAllowPublicUpload()) {
					$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
					return new \OC_OCS_Result(null, 403, $this->l->t('Public upload disabled by the administrator'));
				}

				if (!($share->getNode() instanceof \OCP\Files\Folder)) {
					$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
					return new \OC_OCS_Result(null, 400, $this->l->t('Public upload is only possible for publicly shared folders'));
				}

				// normalize to correct public upload permissions
				$newPermissions = \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_DELETE;
			}

			if ($newPermissions !== null) {
				$share->setPermissions($newPermissions);
				$permissions = $newPermissions;
			}

			if ($expireDate === '') {
				$share->setExpirationDate(null);
			} else if ($expireDate !== null) {
				try {
					$expireDate = $this->parseDate($expireDate);
				} catch (\Exception $e) {
					$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
					return new \OC_OCS_Result(null, 400, $e->getMessage());
				}
				$share->setExpirationDate($expireDate);
			}

			if ($password === '') {
				$share->setPassword(null);
			} else if ($password !== null) {
				$share->setPassword($password);
			}

		} else {
			// For other shares only permissions is valid.
			if ($permissions === null) {
				$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
				return new \OC_OCS_Result(null, 400, $this->l->t('Wrong or no update parameter given'));
			} else {
				$permissions = (int)$permissions;
				$share->setPermissions($permissions);
			}
		}

		if ($permissions !== null && $share->getShareOwner() !== $this->currentUser->getUID()) {
			/* Check if this is an incomming share */
			$incomingShares = $this->shareManager->getSharedWith($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_USER, $share->getNode(), -1, 0);
			$incomingShares = array_merge($incomingShares, $this->shareManager->getSharedWith($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_GROUP, $share->getNode(), -1, 0));

			if (!empty($incomingShares)) {
				$maxPermissions = 0;
				foreach ($incomingShares as $incomingShare) {
					$maxPermissions |= $incomingShare->getPermissions();
				}

				if ($share->getPermissions() & ~$maxPermissions) {
					$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
					return new \OC_OCS_Result(null, 404, $this->l->t('Cannot increase permissions'));
				}
			}
		}


		try {
			$share = $this->shareManager->updateShare($share);
		} catch (\Exception $e) {
			$share->getNode()->unlock(ILockingProvider::LOCK_SHARED);
			return new \OC_OCS_Result(null, 400, $e->getMessage());
		}

		$share->getNode()->unlock(\OCP\Lock\ILockingProvider::LOCK_SHARED);

		return new \OC_OCS_Result($this->formatShare($share));
	}

	/**
	 * @param \OCP\Share\IShare $share
	 * @return bool
	 */
	protected function canAccessShare(\OCP\Share\IShare $share, $checkGroups = true) {
		// A file with permissions 0 can't be accessed by us. So Don't show it
		if ($share->getPermissions() === 0) {
			return false;
		}

		// Owner of the file and the sharer of the file can always get share
		if ($share->getShareOwner() === $this->currentUser->getUID() ||
			$share->getSharedBy() === $this->currentUser->getUID()
		) {
			return true;
		}

		// If the share is shared with you (or a group you are a member of)
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER &&
			$share->getSharedWith() === $this->currentUser->getUID()) {
			return true;
		}

		if ($checkGroups && $share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$sharedWith = $this->groupManager->get($share->getSharedWith());
			if ($sharedWith->inGroup($this->currentUser)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Make sure that the passed date is valid ISO 8601
	 * So YYYY-MM-DD
	 * If not throw an exception
	 *
	 * @param string $expireDate
	 *
	 * @throws \Exception
	 * @return \DateTime
	 */
	private function parseDate($expireDate) {
		try {
			$date = new \DateTime($expireDate);
		} catch (\Exception $e) {
			throw new \Exception('Invalid date. Format must be YYYY-MM-DD');
		}

		if ($date === false) {
			throw new \Exception('Invalid date. Format must be YYYY-MM-DD');
		}

		$date->setTime(0,0,0);

		return $date;
	}

	/**
	 * Since we have multiple providers but the OCS Share API v1 does
	 * not support this we need to check all backends.
	 *
	 * @param string $id
	 * @return \OCP\Share\IShare
	 * @throws ShareNotFound
	 */
	private function getShareById($id) {
		$share = null;

		// First check if it is an internal share.
		try {
			$share = $this->shareManager->getShareById('ocinternal:'.$id);
		} catch (ShareNotFound $e) {
			if (!$this->shareManager->outgoingServer2ServerSharesAllowed()) {
				throw new ShareNotFound();
			}

			$share = $this->shareManager->getShareById('ocFederatedSharing:' . $id);
		}

		return $share;
	}
}

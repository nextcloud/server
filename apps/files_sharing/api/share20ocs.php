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
namespace OCA\Files_Sharing\API;

use OCP\Files\NotFoundException;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Files\IRootFolder;
use OCP\Share;
use OCP\Share\IManager;

use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\Exceptions\GenericShareException;

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
	/** @var IUrlGenerator */
	private $urlGenerator;
	/** @var IUser */
	private $currentUser;

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
			IUser $currentUser
	) {
		$this->shareManager = $shareManager;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->request = $request;
		$this->rootFolder = $rootFolder;
		$this->urlGenerator = $urlGenerator;
		$this->currentUser = $currentUser;
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
			return new \OC_OCS_Result(null, 404, 'Share API is disabled');
		}

		// Try both our default, and our federated provider..
		$share = null;

		// First check if it is an internal share.

		try {
			$share = $this->shareManager->getShareById('ocinternal:'.$id);
		} catch (ShareNotFound $e) {
			// Ignore for now
			//return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		}

		if ($share === null) {
			if (!$this->shareManager->outgoingServer2ServerSharesAllowed()) {
				return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
			}

			try {
				$share = $this->shareManager->getShareById('ocFederatedSharing:' . $id);
			} catch (ShareNotFound $e) {
				return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
			}
		}

		if ($this->canAccessShare($share)) {
			try {
				$share = $this->formatShare($share);
				return new \OC_OCS_Result([$share]);
			} catch (NotFoundException $e) {
				//Fall trough
			}
		}

		return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
	}

	/**
	 * Delete a share
	 *
	 * @param string $id
	 * @return \OC_OCS_Result
	 */
	public function deleteShare($id) {
		if (!$this->shareManager->shareApiEnabled()) {
			return new \OC_OCS_Result(null, 404, 'Share API is disabled');
		}

		// Try both our default and our federated provider
		$share = null;

		try {
			$share = $this->shareManager->getShareById('ocinternal:' . $id);
		} catch (ShareNotFound $e) {
			//Ignore for now
			//return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		}

		// Could not find the share as internal share... maybe it is a federated share
		if ($share === null) {
			if (!$this->shareManager->outgoingServer2ServerSharesAllowed()) {
				return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
			}

			try {
				$share = $this->shareManager->getShareById('ocFederatedSharing:' . $id);
			} catch (ShareNotFound $e) {
				return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
			}
		}

		if (!$this->canAccessShare($share)) {
			return new \OC_OCS_Result(null, 404, 'could not delete share');
		}

		$this->shareManager->deleteShare($share);

		return new \OC_OCS_Result();
	}

	/**
	 * @return \OC_OCS_Result
	 */
	public function createShare() {
		$share = $this->shareManager->newShare();

		if (!$this->shareManager->shareApiEnabled()) {
			return new \OC_OCS_Result(null, 404, 'Share API is disabled');
		}

		// Verify path
		$path = $this->request->getParam('path', null);
		if ($path === null) {
			return new \OC_OCS_Result(null, 404, 'please specify a file or folder path');
		}

		$userFolder = $this->rootFolder->getUserFolder($this->currentUser->getUID());
		try {
			$path = $userFolder->get($path);
		} catch (\OCP\Files\NotFoundException $e) {
			return new \OC_OCS_Result(null, 404, 'wrong path, file/folder doesn\'t exist');
		}

		$share->setNode($path);

		// Parse permissions (if available)
		$permissions = $this->request->getParam('permissions', null);
		if ($permissions === null) {
			$permissions = \OCP\Constants::PERMISSION_ALL;
		} else {
			$permissions = (int)$permissions;
		}

		if ($permissions < 0 || $permissions > \OCP\Constants::PERMISSION_ALL) {
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
				return new \OC_OCS_Result(null, 404, 'please specify a valid user');
			}
			$share->setSharedWith($shareWith);
			$share->setPermissions($permissions);
		} else if ($shareType === \OCP\Share::SHARE_TYPE_GROUP) {
			if (!$this->shareManager->allowGroupSharing()) {
				return new \OC_OCS_Result(null, 404, 'group sharing is disabled by the administrator');
			}

			// Valid group is required to share
			if ($shareWith === null || !$this->groupManager->groupExists($shareWith)) {
				return new \OC_OCS_Result(null, 404, 'please specify a valid group');
			}
			$share->setSharedWith($shareWith);
			$share->setPermissions($permissions);
		} else if ($shareType === \OCP\Share::SHARE_TYPE_LINK) {
			//Can we even share links?
			if (!$this->shareManager->shareApiAllowLinks()) {
				return new \OC_OCS_Result(null, 404, 'public link sharing is disabled by the administrator');
			}

			/*
			 * For now we only allow 1 link share.
			 * Return the existing link share if this is a duplicate
			 */
			$existingShares = $this->shareManager->getSharesBy($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_LINK, $path, false, 1, 0);
			if (!empty($existingShares)) {
				return new \OC_OCS_Result($this->formatShare($existingShares[0]));
			}

			$publicUpload = $this->request->getParam('publicUpload', null);
			if ($publicUpload === 'true') {
				// Check if public upload is allowed
				if (!$this->shareManager->shareApiLinkAllowPublicUpload()) {
					return new \OC_OCS_Result(null, 403, 'public upload disabled by the administrator');
				}

				// Public upload can only be set for folders
				if ($path instanceof \OCP\Files\File) {
					return new \OC_OCS_Result(null, 404, 'public upload is only possible for public shared folders');
				}

				$share->setPermissions(
					\OCP\Constants::PERMISSION_READ |
					\OCP\Constants::PERMISSION_CREATE |
					\OCP\Constants::PERMISSION_UPDATE
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
					return new \OC_OCS_Result(null, 404, 'Invalid Date. Format must be YYYY-MM-DD.');
				}
			}

		} else if ($shareType === \OCP\Share::SHARE_TYPE_REMOTE) {
			if (!$this->shareManager->outgoingServer2ServerSharesAllowed()) {
				return new \OC_OCS_Result(null, 403, 'Sharing '.$path.' failed, because the backend does not allow shares from type '.$shareType);
			}

			$share->setSharedWith($shareWith);
			$share->setPermissions($permissions);
		} else {
			return new \OC_OCS_Result(null, 400, "unknown share type");
		}

		$share->setShareType($shareType);
		$share->setSharedBy($this->currentUser->getUID());

		try {
			$share = $this->shareManager->createShare($share);
		} catch (GenericShareException $e) {
			$code = $e->getCode() === 0 ? 403 : $e->getCode();
			return new \OC_OCS_Result(null, $code, $e->getHint());
		}catch (\Exception $e) {
			return new \OC_OCS_Result(null, 403, $e->getMessage());
		}

		$share = $this->formatShare($share);
		return new \OC_OCS_Result($share);
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
			return new \OC_OCS_Result(null, 400, "not a directory");
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
			} catch (\OCP\Files\NotFoundException $e) {
				return new \OC_OCS_Result(null, 404, 'wrong path, file/folder doesn\'t exist');
			}
		}

		if ($sharedWithMe === 'true') {
			return $this->getSharedWithMe($path);
		}

		if ($subfiles === 'true') {
			return $this->getSharesInDir($path);
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

		return new \OC_OCS_Result($formatted);
	}

	/**
	 * @param int $id
	 * @return \OC_OCS_Result
	 */
	public function updateShare($id) {
		if (!$this->shareManager->shareApiEnabled()) {
			return new \OC_OCS_Result(null, 404, 'Share API is disabled');
		}

		// Try both our default and our federated provider
		$share = null;

		try {
			$share = $this->shareManager->getShareById('ocinternal:' . $id);
		} catch (ShareNotFound $e) {
			//Ignore for now
			//return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		}

		// Could not find the share as internal share... maybe it is a federated share
		if ($share === null) {
			if (!$this->shareManager->outgoingServer2ServerSharesAllowed()) {
				return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
			}

			try {
				$share = $this->shareManager->getShareById('ocFederatedSharing:' . $id);
			} catch (ShareNotFound $e) {
				return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
			}
		}

		if (!$this->canAccessShare($share)) {
			return new \OC_OCS_Result(null, 404, 'wrong share Id, share doesn\'t exist.');
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
				return new \OC_OCS_Result(null, 400, 'Wrong or no update parameter given');
			}

			$newPermissions = null;
			if ($publicUpload === 'true') {
				$newPermissions = \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE;
			} else if ($publicUpload === 'false') {
				$newPermissions = \OCP\Constants::PERMISSION_READ;
			}

			if ($permissions !== null) {
				$newPermissions = (int)$permissions;
			}

			if ($newPermissions !== null &&
				$newPermissions !== \OCP\Constants::PERMISSION_READ &&
				$newPermissions !== (\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE)) {
				return new \OC_OCS_Result(null, 400, 'can\'t change permission for public link share');
			}

			if ($newPermissions === (\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE)) {
				if (!$this->shareManager->shareApiLinkAllowPublicUpload()) {
					return new \OC_OCS_Result(null, 403, 'public upload disabled by the administrator');
				}

				if (!($share->getNode() instanceof \OCP\Files\Folder)) {
					return new \OC_OCS_Result(null, 400, "public upload is only possible for public shared folders");
				}
			}

			if ($newPermissions !== null) {
				$share->setPermissions($newPermissions);
			}

			if ($expireDate === '') {
				$share->setExpirationDate(null);
			} else if ($expireDate !== null) {
				try {
					$expireDate = $this->parseDate($expireDate);
				} catch (\Exception $e) {
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
				return new \OC_OCS_Result(null, 400, 'Wrong or no update parameter given');
			} else {
				$permissions = (int)$permissions;
				$share->setPermissions($permissions);
			}
		}

		if ($permissions !== null) {
			/* Check if this is an incomming share */
			$incomingShares = $this->shareManager->getSharedWith($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_USER, $share->getNode(), -1, 0);
			$incomingShares = array_merge($incomingShares, $this->shareManager->getSharedWith($this->currentUser->getUID(), \OCP\Share::SHARE_TYPE_GROUP, $share->getNode(), -1, 0));

			if (!empty($incomingShares)) {
				$maxPermissions = 0;
				foreach ($incomingShares as $incomingShare) {
					$maxPermissions |= $incomingShare->getPermissions();
				}

				if ($share->getPermissions() & ~$maxPermissions) {
					return new \OC_OCS_Result(null, 404, 'Cannot increase permissions');
				}
			}
		}


		try {
			$share = $this->shareManager->updateShare($share);
		} catch (\Exception $e) {
			return new \OC_OCS_Result(null, 400, $e->getMessage());
		}

		return new \OC_OCS_Result($this->formatShare($share));
	}

	/**
	 * @param \OCP\Share\IShare $share
	 * @return bool
	 */
	protected function canAccessShare(\OCP\Share\IShare $share) {
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

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
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
}

<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

use OC\Share20\IShare;

use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IRequest;
use OCP\Files\Folder;
use OCP\IURLGenerator;
use OCP\IUser;

class Share20OCS {

	/** @var \OC\Share20\Manager */
	private $shareManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IRequest */
	private $request;

	/** @var Folder */
	private $userFolder;

	/** @var IUrlGenerator */
	private $urlGenerator;

	/** @var IUser */
	private $currentUser;

	public function __construct(
			\OC\Share20\Manager $shareManager,
			\OCP\IGroupManager $groupManager,
			\OCP\IUserManager $userManager,
			\OCP\IRequest $request,
			\OCP\Files\Folder $userFolder,
			\OCP\IURLGenerator $urlGenerator,
			\OCP\IUser $currentUser
	) {
		$this->shareManager = $shareManager;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->request = $request;
		$this->userFolder = $userFolder;
		$this->urlGenerator = $urlGenerator;
		$this->currentUser = $currentUser;
	}

	/**
	 * Convert an IShare to an array for OCS output
	 *
	 * @param IShare $share
	 * @return array
	 */
	protected function formatShare($share) {
		$result = [
			'id' => $share->getId(),
			'share_type' => $share->getShareType(),
			'uid_owner' => $share->getSharedBy()->getUID(),
			'displayname_owner' => $share->getSharedBy()->getDisplayName(),
			'permissions' => $share->getPermissions(),
			'stime' => $share->getShareTime(),
			'parent' => $share->getParent(),
			'expiration' => null,
			'token' => null,
		];

		$path = $share->getPath();
		$result['path'] = $this->userFolder->getRelativePath($path->getPath());
		if ($path instanceOf \OCP\Files\Folder) {
			$result['item_type'] = 'folder';
		} else {
			$result['item_type'] = 'file';
		}
		$result['storage_id'] = $path->getStorage()->getId();
		$result['storage'] = \OC\Files\Cache\Storage::getNumericStorageId($path->getStorage()->getId());
		$result['item_source'] = $path->getId();
		$result['file_source'] = $path->getId();
		$result['file_parent'] = $path->getParent()->getId();
		$result['file_target'] = $share->getTarget();

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			$sharedWith = $share->getSharedWith();
			$result['share_with'] = $sharedWith->getUID();
			$result['share_with_displayname'] = $sharedWith->getDisplayName();
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$sharedWith = $share->getSharedWith();
			$result['share_with'] = $sharedWith->getGID();
			$result['share_with_displayname'] = $sharedWith->getGID();
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
		try {
			$share = $this->shareManager->getShareById($id);
		} catch (\OC\Share20\Exception\ShareNotFound $e) {
			return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		}

		if ($this->canAccessShare($share)) {
			$share = $this->formatShare($share);
			return new \OC_OCS_Result($share);
		} else {
			return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		}
	}

	/**
	 * Delete a share
	 *
	 * @param string $id
	 * @return \OC_OCS_Result
	 */
	public function deleteShare($id) {
		try {
			$share = $this->shareManager->getShareById($id);
		} catch (\OC\Share20\Exception\ShareNotFound $e) {
			return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		}

		/*
		 * FIXME
		 * User the old code path for remote shares until we have our remoteshareprovider
		 */
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_REMOTE) {
			\OCA\Files_Sharing\API\Local::deleteShare(['id' => $id]);
		}

		if (!$this->canAccessShare($share)) {
			return new \OC_OCS_Result(null, 404, 'could not delete share');
		}

		try {
			$this->shareManager->deleteShare($share);
		} catch (\OC\Share20\Exception\BackendError $e) {
			return new \OC_OCS_Result(null, 404, 'could not delete share');
		}

		return new \OC_OCS_Result();
	}

	/**
	 * @param IShare $share
	 * @return bool
	 */
	protected function canAccessShare(IShare $share) {
		// Owner of the file and the sharer of the file can always get share
		if ($share->getShareOwner() === $this->currentUser ||
			$share->getSharedBy() === $this->currentUser
		) {
			return true;
		}

		// If the share is shared with you (or a group you are a member of)
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER &&
			$share->getSharedWith() === $this->currentUser) {
			return true;
		}

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP &&
			$share->getSharedWith()->inGroup($this->currentUser)) {
			return true;
		}

		return false;
	}
}

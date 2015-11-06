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
namespace OC\Share20;

use OC\Share20\Exception\ShareNotFound;
use OC\Share20\Exception\BackendError;
use OCP\IUser;

class DefaultShareProvider implements IShareProvider {

	/** @var \OCP\IDBConnection */
	private $dbConn;

	/** @var \OCP\IUserManager */
	private $userManager;

	/** @var \OCP\IGroupManager */
	private $groupManager;

	/** @var \OCP\Files\Folder */
	private $userFolder;

	public function __construct(\OCP\IDBConnection $connection,
								\OCP\IUserManager $userManager,
								\OCP\IGroupManager $groupManager,
								\OCP\Files\Folder $userFolder) {
		$this->dbConn = $connection;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->userFolder = $userFolder;
	}

	/**
	 * Share a path
	 * 
	 * @param Share $share
	 * @return Share The share object
	 */
	public function create(Share $share) {
		throw new \Exception();
	}

	/**
	 * Update a share
	 *
	 * @param Share $share
	 * @return Share The share object
	 */
	public function update(Share $share) {
		throw new \Exception();
	}

	/**
	 * Get all childre of this share
	 *
	 * @param IShare $share
	 * @return IShare[]
	 */
	private function getChildren(IShare $share) {
		$children = [];

		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('parent', $qb->createParameter('parent')))
			->setParameter(':parent', $share->getId());

		$cursor = $qb->execute();
		while($data = $cursor->fetch()) {
			$children[] = $this->createShare($data);
		}
		$cursor->closeCursor();

		return $children;
	}

	/**
	 * Delete all the children of this share
	 *
	 * @param IShare $share
	 */
	protected function deleteChildren(IShare $share) {
		foreach($this->getChildren($share) as $child) {
			$this->delete($child);
		}
	}

	/**
	 * Delete a share
	 *
	 * @param Share $share
	 * @throws BackendError
	 */
	public function delete(IShare $share) {
		$this->deleteChildren($share);

		// Fetch share to make sure it exists
		$share = $this->getShareById($share->getId());

		$shareType = $share->getShareType();
		$sharedWith = '';
		if ($shareType === \OCP\Share::SHARE_TYPE_USER) {
			$sharedWith = $share->getSharedWith()->getUID();
		} else if ($shareType === \OCP\Share::SHARE_TYPE_GROUP) {
			$sharedWith = $share->getSharedWith()->getGID();
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

		\OC_Hook::emit('OCP\Share', 'pre_unshare', $hookParams);

		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('id', $qb->createParameter('id')))
			->setParameter(':id', $share->getId());
	
		try {
			$qb->execute();
		} catch (\Exception $e) {
			throw new BackendError();
		}

		\OC_Hook::emit('OCP\Share', 'post_unshare', $hookParams);
	}

	/**
	 * Get all shares by the given user
	 *
	 * @param IUser $user
	 * @param int $shareType
	 * @param int $offset
	 * @param int $limit
	 * @return Share[]
	 */
	public function getShares(IUser $user, $shareType, $offset, $limit) {
		throw new \Exception();
	}

	/**
	 * Get share by id
	 *
	 * @param int $id
	 * @return IShare
	 * @throws ShareNotFound
	 */
	public function getShareById($id) {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createParameter('id')))
			->setParameter(':id', $id);
		
		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new ShareNotFound();
		}

		$share = $this->createShare($data);

		return $share;
	}

	/**
	 * Get shares for a given path
	 *
	 * @param \OCP\Files\Node $path
	 * @param Share[]
	 */
	public function getSharesByPath(\OCP\IUser $user, \OCP\Files\Node $path) {
		throw new \Exception();
	}

	/**
	 * Get shared with the given user
	 *
	 * @param IUser $user
	 * @param int $shareType
	 * @param Share
	 */
	public function getSharedWithMe(IUser $user, $shareType = null) {
		throw new \Exception();
	}

	/**
	 * Get a share by token and if present verify the password
	 *
	 * @param string $token
	 * @param string $password
	 * @param Share
	 */
	public function getShareByToken($token, $password = null) {
		throw new \Exception();
	}
	
	/**
	 * Create a share object from an database row
	 *
	 * @param mixed[] $data
	 * @return Share
	 */
	private function createShare($data) {
		$share = new Share();
		$share->setId((int)$data['id'])
			->setShareType((int)$data['share_type'])
			->setPermissions((int)$data['permissions'])
			->setTarget($data['file_target'])
			->setShareTime((int)$data['stime'])
			->setMailSend((bool)$data['mail_send']);

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			$share->setSharedWith($this->userManager->get($data['share_with']));
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$share->setSharedWith($this->groupManager->get($data['share_with']));
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
			$share->setPassword($data['share_with']);
			$share->setToken($data['token']);
		} else {
			$share->setSharedWith($data['share_with']);
		}

		$share->setSharedBy($this->userManager->get($data['uid_owner']));

		// TODO: getById can return an array. How to handle this properly??
		$path = $this->userFolder->getById($data['file_source']);
		$path = $path[0];
		$share->setPath($path);

		$owner = $path->getStorage()->getOwner('.');
		if ($owner !== false) {
			$share->setShareOwner($this->userManager->get($owner));
		}

		if ($data['expiration'] !== null) {
			$expiration = \DateTime::createFromFormat('Y-m-d H:i:s', $data['expiration']);
			$share->setExpirationDate($expiration);
		}

		return $share;
	}


}

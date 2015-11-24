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
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\Files\Node;

class DefaultShareProvider implements IShareProvider {

	/** @var IDBConnection */
	private $dbConn;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IRootFolder */
	private $rootFolder;

	/**
	 * DefaultShareProvider constructor.
	 *
	 * @param IDBConnection $connection
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(
			IDBConnection $connection,
			IUserManager $userManager,
			IGroupManager $groupManager,
			IRootFolder $rootFolder) {
		$this->dbConn = $connection;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * Share a path
	 * 
	 * @param IShare $share
	 * @return IShare The share object
	 */
	public function create(IShare $share) {
	}

	/**
	 * Update a share
	 *
	 * @param IShare $share
	 * @return IShare The share object
	 */
	public function update(IShare $share) {
	}

	/**
	 * Get all children of this share
	 *
	 * @param IShare $parent
	 * @return IShare[]
	 */
	public function getChildren(IShare $parent) {
		$children = [];

		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('parent', $qb->createParameter('parent')))
			->setParameter(':parent', $parent->getId())
			->orderBy('id');

		$cursor = $qb->execute();
		while($data = $cursor->fetch()) {
			$children[] = $this->createShare($data);
		}
		$cursor->closeCursor();

		return $children;
	}

	/**
	 * Delete a share
	 *
	 * @param IShare $share
	 * @throws BackendError
	 */
	public function delete(IShare $share) {
		// Fetch share to make sure it exists
		$share = $this->getShareById($share->getId());

		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('id', $qb->createParameter('id')))
			->setParameter(':id', $share->getId());
	
		try {
			$qb->execute();
		} catch (\Exception $e) {
			throw new BackendError();
		}
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
	 * @param \OCP\IUser $user
	 * @param \OCP\Files\Node $path
	 * @return IShare[]
	 */
	public function getSharesByPath(IUser $user, Node $path) {
	}

	/**
	 * Get shared with the given user
	 *
	 * @param IUser $user
	 * @param int $shareType
	 * @param Share
	 */
	public function getSharedWithMe(IUser $user, $shareType = null) {
	}

	/**
	 * Get a share by token and if present verify the password
	 *
	 * @param string $token
	 * @param string $password
	 * @param Share
	 */
	public function getShareByToken($token, $password = null) {
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
		$folder = $this->rootFolder->getUserFolder($share->getSharedBy()->getUID());
		$path = $folder->getById((int)$data['file_source'])[0];

		$owner = $path->getOwner();
		$share->setShareOwner($owner);

		$path = $this->rootFolder->getUserFolder($owner->getUID())->getById((int)$data['file_source'])[0];
		$share->setPath($path);

		if ($data['expiration'] !== null) {
			$expiration = \DateTime::createFromFormat('Y-m-d H:i:s', $data['expiration']);
			$share->setExpirationDate($expiration);
		}

		return $share;
	}

}
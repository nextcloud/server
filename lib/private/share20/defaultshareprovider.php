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

use OC\Share20\Exception\ShareNotFound;
use OC\Share20\Exception\BackendError;
use OCP\IUser;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\Files\Node;

/**
 * Class DefaultShareProvider
 *
 * @package OC\Share20
 */
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
	 * Return the share types this provider handles
	 *
	 * @return int[]
	 */
	public function shareTypes() {
		return [
			\OCP\Share::SHARE_TYPE_USER,
			\OCP\Share::SHARE_TYPE_GROUP,
			\OCP\Share::SHARE_TYPE_LINK,
		];
	}

	/**
	 * Return the identifier of this provider.
	 *
	 * @return string Containing only [a-zA-Z0-9]
	 */
	public function identifier() {
		return 'ocinternal';
	}

	/**
	 * Share a path
	 *
	 * @param IShare $share
	 * @return IShare The share object
	 * @throws ShareNotFound
	 * @throws \Exception
	 */
	public function create(IShare $share) {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share');
		$qb->setValue('share_type', $qb->createNamedParameter($share->getShareType()));

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			//Set the UID of the user we share with
			$qb->setValue('share_with', $qb->createNamedParameter($share->getSharedWith()->getUID()));
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			//Set the GID of the group we share with
			$qb->setValue('share_with', $qb->createNamedParameter($share->getSharedWith()->getGID()));
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
			//Set the token of the share
			$qb->setValue('token', $qb->createNamedParameter($share->getToken()));

			//If a password is set store it
			if ($share->getPassword() !== null) {
				$qb->setValue('share_with', $qb->createNamedParameter($share->getPassword()));
			}

			//If an expiration date is set store it
			if ($share->getExpirationDate() !== null) {
				$qb->setValue('expiration', $qb->createNamedParameter($share->getExpirationDate(), 'datetime'));
			}
		} else {
			throw new \Exception('invalid share type!');
		}

		// Set what is shares
		$qb->setValue('item_type', $qb->createParameter('itemType'));
		if ($share->getPath() instanceof \OCP\Files\File) {
			$qb->setParameter('itemType', 'file');
		} else {
			$qb->setParameter('itemType', 'folder');
		}

		// Set the file id
		$qb->setValue('item_source', $qb->createNamedParameter($share->getPath()->getId()));
		$qb->setValue('file_source', $qb->createNamedParameter($share->getPath()->getId()));

		// set the permissions
		$qb->setValue('permissions', $qb->createNamedParameter($share->getPermissions()));

		// Set who created this share
		$qb->setValue('uid_initiator', $qb->createNamedParameter($share->getSharedBy()->getUID()));

		// Set who is the owner of this file/folder (and this the owner of the share)
		$qb->setValue('uid_owner', $qb->createNamedParameter($share->getShareOwner()->getUID()));

		// Set the file target
		$qb->setValue('file_target', $qb->createNamedParameter($share->getTarget()));

		// Set the time this share was created
		$qb->setValue('stime', $qb->createNamedParameter(time()));

		// insert the data and fetch the id of the share
		$this->dbConn->beginTransaction();
		$qb->execute();
		$id = $this->dbConn->lastInsertId('*PREFIX*share');
		$this->dbConn->commit();

		// Now fetch the inserted share and create a complete share object
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('*PREFIX*share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

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
	 * @param \OCP\Files\Node $path
	 * @return IShare[]
	 */
	public function getSharesByPath(Node $path) {
		$qb = $this->dbConn->getQueryBuilder();

		$cursor = $qb->select('*')
			->from('share')
			->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($path->getId())))
			->andWhere(
				$qb->expr()->orX(
					$qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_USER)),
					$qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_GROUP))
				)
			)->execute();

		$shares = [];
		while($data = $cursor->fetch()) {
			$shares[] = $this->createShare($data);
		}
		$cursor->closeCursor();

		return $shares;
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

		if ($data['uid_initiator'] === null) {
			//OLD SHARE
			$share->setSharedBy($this->userManager->get($data['uid_owner']));
			$folder = $this->rootFolder->getUserFolder($share->getSharedBy()->getUID());
			$path = $folder->getById((int)$data['file_source'])[0];

			$owner = $path->getOwner();
			$share->setShareOwner($owner);
		} else {
			//New share!
			$share->setSharedBy($this->userManager->get($data['uid_initiator']));
			$share->setShareOwner($this->userManager->get($data['uid_owner']));
		}

		$path = $this->rootFolder->getUserFolder($share->getShareOwner()->getUID())->getById((int)$data['file_source'])[0];
		$share->setPath($path);

		if ($data['expiration'] !== null) {
			$expiration = \DateTime::createFromFormat('Y-m-d H:i:s', $data['expiration']);
			$share->setExpirationDate($expiration);
		}

		$share->setProviderId($this->identifier());

		return $share;
	}

}
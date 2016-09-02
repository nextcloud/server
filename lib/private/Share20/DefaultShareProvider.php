<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OCP\Files\File;
use OCP\Share\IShareProvider;
use OC\Share20\Exception\InvalidShare;
use OC\Share20\Exception\ProviderException;
use OCP\Share\Exceptions\ShareNotFound;
use OC\Share20\Exception\BackendError;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IGroup;
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

	// Special share type for user modified group shares
	const SHARE_TYPE_USERGROUP = 2;

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
	 * @param \OCP\Share\IShare $share
	 * @return \OCP\Share\IShare The share object
	 * @throws ShareNotFound
	 * @throws \Exception
	 */
	public function create(\OCP\Share\IShare $share) {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share');
		$qb->setValue('share_type', $qb->createNamedParameter($share->getShareType()));

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			//Set the UID of the user we share with
			$qb->setValue('share_with', $qb->createNamedParameter($share->getSharedWith()));
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			//Set the GID of the group we share with
			$qb->setValue('share_with', $qb->createNamedParameter($share->getSharedWith()));
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

			if (method_exists($share, 'getParent')) {
				$qb->setValue('parent', $qb->createNamedParameter($share->getParent()));
			}
		} else {
			throw new \Exception('invalid share type!');
		}

		// Set what is shares
		$qb->setValue('item_type', $qb->createParameter('itemType'));
		if ($share->getNode() instanceof \OCP\Files\File) {
			$qb->setParameter('itemType', 'file');
		} else {
			$qb->setParameter('itemType', 'folder');
		}

		// Set the file id
		$qb->setValue('item_source', $qb->createNamedParameter($share->getNode()->getId()));
		$qb->setValue('file_source', $qb->createNamedParameter($share->getNode()->getId()));

		// set the permissions
		$qb->setValue('permissions', $qb->createNamedParameter($share->getPermissions()));

		// Set who created this share
		$qb->setValue('uid_initiator', $qb->createNamedParameter($share->getSharedBy()));

		// Set who is the owner of this file/folder (and this the owner of the share)
		$qb->setValue('uid_owner', $qb->createNamedParameter($share->getShareOwner()));

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
			->from('share')
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
	 * @param \OCP\Share\IShare $share
	 * @return \OCP\Share\IShare The share object
	 */
	public function update(\OCP\Share\IShare $share) {
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			/*
			 * We allow updating the recipient on user shares.
			 */
			$qb = $this->dbConn->getQueryBuilder();
			$qb->update('share')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
				->set('share_with', $qb->createNamedParameter($share->getSharedWith()))
				->set('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
				->set('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
				->set('permissions', $qb->createNamedParameter($share->getPermissions()))
				->set('item_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('file_source', $qb->createNamedParameter($share->getNode()->getId()))
				->execute();
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$qb = $this->dbConn->getQueryBuilder();
			$qb->update('share')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
				->set('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
				->set('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
				->set('permissions', $qb->createNamedParameter($share->getPermissions()))
				->set('item_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('file_source', $qb->createNamedParameter($share->getNode()->getId()))
				->execute();

			/*
			 * Update all user defined group shares
			 */
			$qb = $this->dbConn->getQueryBuilder();
			$qb->update('share')
				->where($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())))
				->set('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
				->set('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
				->set('item_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('file_source', $qb->createNamedParameter($share->getNode()->getId()))
				->execute();

			/*
			 * Now update the permissions for all children that have not set it to 0
			 */
			$qb = $this->dbConn->getQueryBuilder();
			$qb->update('share')
				->where($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())))
				->andWhere($qb->expr()->neq('permissions', $qb->createNamedParameter(0)))
				->set('permissions', $qb->createNamedParameter($share->getPermissions()))
				->execute();

		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
			$qb = $this->dbConn->getQueryBuilder();
			$qb->update('share')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
				->set('share_with', $qb->createNamedParameter($share->getPassword()))
				->set('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
				->set('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
				->set('permissions', $qb->createNamedParameter($share->getPermissions()))
				->set('item_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('file_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('token', $qb->createNamedParameter($share->getToken()))
				->set('expiration', $qb->createNamedParameter($share->getExpirationDate(), IQueryBuilder::PARAM_DATE))
				->execute();
		}

		return $share;
	}

	/**
	 * Get all children of this share
	 * FIXME: remove once https://github.com/owncloud/core/pull/21660 is in
	 *
	 * @param \OCP\Share\IShare $parent
	 * @return \OCP\Share\IShare[]
	 */
	public function getChildren(\OCP\Share\IShare $parent) {
		$children = [];

		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('parent', $qb->createNamedParameter($parent->getId())))
			->andWhere(
				$qb->expr()->in(
					'share_type',
					$qb->createNamedParameter([
						\OCP\Share::SHARE_TYPE_USER,
						\OCP\Share::SHARE_TYPE_GROUP,
						\OCP\Share::SHARE_TYPE_LINK,
					], IQueryBuilder::PARAM_INT_ARRAY)
				)
			)
			->andWhere($qb->expr()->orX(
				$qb->expr()->eq('item_type', $qb->createNamedParameter('file')),
				$qb->expr()->eq('item_type', $qb->createNamedParameter('folder'))
			))
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
	 * @param \OCP\Share\IShare $share
	 */
	public function delete(\OCP\Share\IShare $share) {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())));

		/*
		 * If the share is a group share delete all possible
		 * user defined groups shares.
		 */
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$qb->orWhere($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())));
		}

		$qb->execute();
	}

	/**
	 * Unshare a share from the recipient. If this is a group share
	 * this means we need a special entry in the share db.
	 *
	 * @param \OCP\Share\IShare $share
	 * @param string $recipient UserId of recipient
	 * @throws BackendError
	 * @throws ProviderException
	 */
	public function deleteFromSelf(\OCP\Share\IShare $share, $recipient) {
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {

			$group = $this->groupManager->get($share->getSharedWith());
			$user = $this->userManager->get($recipient);

			if (!$group->inGroup($user)) {
				throw new ProviderException('Recipient not in receiving group');
			}

			// Try to fetch user specific share
			$qb = $this->dbConn->getQueryBuilder();
			$stmt = $qb->select('*')
				->from('share')
				->where($qb->expr()->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE_USERGROUP)))
				->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($recipient)))
				->andWhere($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())))
				->andWhere($qb->expr()->orX(
					$qb->expr()->eq('item_type', $qb->createNamedParameter('file')),
					$qb->expr()->eq('item_type', $qb->createNamedParameter('folder'))
				))
				->execute();

			$data = $stmt->fetch();

			/*
			 * Check if there already is a user specific group share.
			 * If there is update it (if required).
			 */
			if ($data === false) {
				$qb = $this->dbConn->getQueryBuilder();

				$type = $share->getNode() instanceof \OCP\Files\File ? 'file' : 'folder';

				//Insert new share
				$qb->insert('share')
					->values([
						'share_type' => $qb->createNamedParameter(self::SHARE_TYPE_USERGROUP),
						'share_with' => $qb->createNamedParameter($recipient),
						'uid_owner' => $qb->createNamedParameter($share->getShareOwner()),
						'uid_initiator' => $qb->createNamedParameter($share->getSharedBy()),
						'parent' => $qb->createNamedParameter($share->getId()),
						'item_type' => $qb->createNamedParameter($type),
						'item_source' => $qb->createNamedParameter($share->getNode()->getId()),
						'file_source' => $qb->createNamedParameter($share->getNode()->getId()),
						'file_target' => $qb->createNamedParameter($share->getTarget()),
						'permissions' => $qb->createNamedParameter(0),
						'stime' => $qb->createNamedParameter($share->getShareTime()->getTimestamp()),
					])->execute();

			} else if ($data['permissions'] !== 0) {

				// Update existing usergroup share
				$qb = $this->dbConn->getQueryBuilder();
				$qb->update('share')
					->set('permissions', $qb->createNamedParameter(0))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($data['id'])))
					->execute();
			}

		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {

			if ($share->getSharedWith() !== $recipient) {
				throw new ProviderException('Recipient does not match');
			}

			// We can just delete user and link shares
			$this->delete($share);
		} else {
			throw new ProviderException('Invalid shareType');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function move(\OCP\Share\IShare $share, $recipient) {
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			// Just update the target
			$qb = $this->dbConn->getQueryBuilder();
			$qb->update('share')
				->set('file_target', $qb->createNamedParameter($share->getTarget()))
				->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
				->execute();

		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {

			// Check if there is a usergroup share
			$qb = $this->dbConn->getQueryBuilder();
			$stmt = $qb->select('id')
				->from('share')
				->where($qb->expr()->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE_USERGROUP)))
				->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($recipient)))
				->andWhere($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())))
				->andWhere($qb->expr()->orX(
					$qb->expr()->eq('item_type', $qb->createNamedParameter('file')),
					$qb->expr()->eq('item_type', $qb->createNamedParameter('folder'))
				))
				->setMaxResults(1)
				->execute();

			$data = $stmt->fetch();
			$stmt->closeCursor();

			if ($data === false) {
				// No usergroup share yet. Create one.
				$qb = $this->dbConn->getQueryBuilder();
				$qb->insert('share')
					->values([
						'share_type' => $qb->createNamedParameter(self::SHARE_TYPE_USERGROUP),
						'share_with' => $qb->createNamedParameter($recipient),
						'uid_owner' => $qb->createNamedParameter($share->getShareOwner()),
						'uid_initiator' => $qb->createNamedParameter($share->getSharedBy()),
						'parent' => $qb->createNamedParameter($share->getId()),
						'item_type' => $qb->createNamedParameter($share->getNode() instanceof File ? 'file' : 'folder'),
						'item_source' => $qb->createNamedParameter($share->getNode()->getId()),
						'file_source' => $qb->createNamedParameter($share->getNode()->getId()),
						'file_target' => $qb->createNamedParameter($share->getTarget()),
						'permissions' => $qb->createNamedParameter($share->getPermissions()),
						'stime' => $qb->createNamedParameter($share->getShareTime()->getTimestamp()),
					])->execute();
			} else {
				// Already a usergroup share. Update it.
				$qb = $this->dbConn->getQueryBuilder();
				$qb->update('share')
					->set('file_target', $qb->createNamedParameter($share->getTarget()))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($data['id'])))
					->execute();
			}
		}

		return $share;
	}

	/**
	 * @inheritdoc
	 */
	public function getSharesBy($userId, $shareType, $node, $reshares, $limit, $offset) {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->andWhere($qb->expr()->orX(
				$qb->expr()->eq('item_type', $qb->createNamedParameter('file')),
				$qb->expr()->eq('item_type', $qb->createNamedParameter('folder'))
			));

		$qb->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter($shareType)));

		/**
		 * Reshares for this user are shares where they are the owner.
		 */
		if ($reshares === false) {
			$qb->andWhere($qb->expr()->eq('uid_initiator', $qb->createNamedParameter($userId)));
		} else {
			$qb->andWhere(
				$qb->expr()->orX(
					$qb->expr()->eq('uid_owner', $qb->createNamedParameter($userId)),
					$qb->expr()->eq('uid_initiator', $qb->createNamedParameter($userId))
				)
			);
		}

		if ($node !== null) {
			$qb->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($node->getId())));
		}

		if ($limit !== -1) {
			$qb->setMaxResults($limit);
		}

		$qb->setFirstResult($offset);
		$qb->orderBy('id');

		$cursor = $qb->execute();
		$shares = [];
		while($data = $cursor->fetch()) {
			$shares[] = $this->createShare($data);
		}
		$cursor->closeCursor();

		return $shares;
	}

	/**
	 * @inheritdoc
	 */
	public function getShareById($id, $recipientId = null) {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere(
				$qb->expr()->in(
					'share_type',
					$qb->createNamedParameter([
						\OCP\Share::SHARE_TYPE_USER,
						\OCP\Share::SHARE_TYPE_GROUP,
						\OCP\Share::SHARE_TYPE_LINK,
					], IQueryBuilder::PARAM_INT_ARRAY)
				)
			)
			->andWhere($qb->expr()->orX(
				$qb->expr()->eq('item_type', $qb->createNamedParameter('file')),
				$qb->expr()->eq('item_type', $qb->createNamedParameter('folder'))
			));
		
		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new ShareNotFound();
		}

		try {
			$share = $this->createShare($data);
		} catch (InvalidShare $e) {
			throw new ShareNotFound();
		}

		// If the recipient is set for a group share resolve to that user
		if ($recipientId !== null && $share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$share = $this->resolveGroupShare($share, $recipientId);
		}

		return $share;
	}

	/**
	 * Get shares for a given path
	 *
	 * @param \OCP\Files\Node $path
	 * @return \OCP\Share\IShare[]
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
			)
			->andWhere($qb->expr()->orX(
				$qb->expr()->eq('item_type', $qb->createNamedParameter('file')),
				$qb->expr()->eq('item_type', $qb->createNamedParameter('folder'))
			))
			->execute();

		$shares = [];
		while($data = $cursor->fetch()) {
			$shares[] = $this->createShare($data);
		}
		$cursor->closeCursor();

		return $shares;
	}

	/**
	 * Returns whether the given database result can be interpreted as
	 * a share with accessible file (not trashed, not deleted)
	 */
	private function isAccessibleResult($data) {
		// exclude shares leading to deleted file entries
		if ($data['fileid'] === null) {
			return false;
		}

		// exclude shares leading to trashbin on home storages
		$pathSections = explode('/', $data['path'], 2);
		// FIXME: would not detect rare md5'd home storage case properly
		if ($pathSections[0] !== 'files' && explode(':', $data['storage_string_id'], 2)[0] === 'home') {
			return false;
		}
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function getSharedWith($userId, $shareType, $node, $limit, $offset) {
		/** @var Share[] $shares */
		$shares = [];

		if ($shareType === \OCP\Share::SHARE_TYPE_USER) {
			//Get shares directly with this user
			$qb = $this->dbConn->getQueryBuilder();
			$qb->select('s.*', 'f.fileid', 'f.path')
				->selectAlias('st.id', 'storage_string_id')
				->from('share', 's')
				->leftJoin('s', 'filecache', 'f', $qb->expr()->eq('s.file_source', 'f.fileid'))
				->leftJoin('f', 'storages', 'st', $qb->expr()->eq('f.storage', 'st.numeric_id'));

			// Order by id
			$qb->orderBy('s.id');

			// Set limit and offset
			if ($limit !== -1) {
				$qb->setMaxResults($limit);
			}
			$qb->setFirstResult($offset);

			$qb->where($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_USER)))
				->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($userId)))
				->andWhere($qb->expr()->orX(
					$qb->expr()->eq('item_type', $qb->createNamedParameter('file')),
					$qb->expr()->eq('item_type', $qb->createNamedParameter('folder'))
				));

			// Filter by node if provided
			if ($node !== null) {
				$qb->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($node->getId())));
			}

			$cursor = $qb->execute();

			while($data = $cursor->fetch()) {
				if ($this->isAccessibleResult($data)) {
					$shares[] = $this->createShare($data);
				}
			}
			$cursor->closeCursor();

		} else if ($shareType === \OCP\Share::SHARE_TYPE_GROUP) {
			$user = $this->userManager->get($userId);
			$allGroups = $this->groupManager->getUserGroups($user);

			/** @var Share[] $shares2 */
			$shares2 = [];

			$start = 0;
			while(true) {
				$groups = array_slice($allGroups, $start, 100);
				$start += 100;

				if ($groups === []) {
					break;
				}

				$qb = $this->dbConn->getQueryBuilder();
				$qb->select('s.*', 'f.fileid', 'f.path')
					->selectAlias('st.id', 'storage_string_id')
					->from('share', 's')
					->leftJoin('s', 'filecache', 'f', $qb->expr()->eq('s.file_source', 'f.fileid'))
					->leftJoin('f', 'storages', 'st', $qb->expr()->eq('f.storage', 'st.numeric_id'))
					->orderBy('s.id')
					->setFirstResult(0);

				if ($limit !== -1) {
					$qb->setMaxResults($limit - count($shares));
				}

				// Filter by node if provided
				if ($node !== null) {
					$qb->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($node->getId())));
				}

				$groups = array_map(function(IGroup $group) { return $group->getGID(); }, $groups);

				$qb->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_GROUP)))
					->andWhere($qb->expr()->in('share_with', $qb->createNamedParameter(
						$groups,
						IQueryBuilder::PARAM_STR_ARRAY
					)))
					->andWhere($qb->expr()->orX(
						$qb->expr()->eq('item_type', $qb->createNamedParameter('file')),
						$qb->expr()->eq('item_type', $qb->createNamedParameter('folder'))
					));

				$cursor = $qb->execute();
				while($data = $cursor->fetch()) {
					if ($offset > 0) {
						$offset--;
						continue;
					}

					if ($this->isAccessibleResult($data)) {
						$shares2[] = $this->createShare($data);
					}
				}
				$cursor->closeCursor();
			}

			/*
 			 * Resolve all group shares to user specific shares
 			 * TODO: Optmize this!
 			 */
			foreach($shares2 as $share) {
				$shares[] = $this->resolveGroupShare($share, $userId);
			}
		} else {
			throw new BackendError('Invalid backend');
		}


		return $shares;
	}

	/**
	 * Get a share by token
	 *
	 * @param string $token
	 * @return \OCP\Share\IShare
	 * @throws ShareNotFound
	 */
	public function getShareByToken($token) {
		$qb = $this->dbConn->getQueryBuilder();

		$cursor = $qb->select('*')
			->from('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_LINK)))
			->andWhere($qb->expr()->eq('token', $qb->createNamedParameter($token)))
			->andWhere($qb->expr()->orX(
				$qb->expr()->eq('item_type', $qb->createNamedParameter('file')),
				$qb->expr()->eq('item_type', $qb->createNamedParameter('folder'))
			))
			->execute();

		$data = $cursor->fetch();

		if ($data === false) {
			throw new ShareNotFound();
		}

		try {
			$share = $this->createShare($data);
		} catch (InvalidShare $e) {
			throw new ShareNotFound();
		}

		return $share;
	}
	
	/**
	 * Create a share object from an database row
	 *
	 * @param mixed[] $data
	 * @return \OCP\Share\IShare
	 * @throws InvalidShare
	 */
	private function createShare($data) {
		$share = new Share($this->rootFolder, $this->userManager);
		$share->setId((int)$data['id'])
			->setShareType((int)$data['share_type'])
			->setPermissions((int)$data['permissions'])
			->setTarget($data['file_target'])
			->setMailSend((bool)$data['mail_send']);

		$shareTime = new \DateTime();
		$shareTime->setTimestamp((int)$data['stime']);
		$share->setShareTime($shareTime);

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
			$share->setSharedWith($data['share_with']);
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_GROUP) {
			$share->setSharedWith($data['share_with']);
		} else if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
			$share->setPassword($data['share_with']);
			$share->setToken($data['token']);
		}

		$share->setSharedBy($data['uid_initiator']);
		$share->setShareOwner($data['uid_owner']);

		$share->setNodeId((int)$data['file_source']);
		$share->setNodeType($data['item_type']);

		if ($data['expiration'] !== null) {
			$expiration = \DateTime::createFromFormat('Y-m-d H:i:s', $data['expiration']);
			$share->setExpirationDate($expiration);
		}

		$share->setProviderId($this->identifier());

		return $share;
	}

	/**
	 * Resolve a group share to a user specific share
	 * Thus if the user moved their group share make sure this is properly reflected here.
	 *
	 * @param \OCP\Share\IShare $share
	 * @param string $userId
	 * @return Share Returns the updated share if one was found else return the original share.
	 */
	private function resolveGroupShare(\OCP\Share\IShare $share, $userId) {
		$qb = $this->dbConn->getQueryBuilder();

		$stmt = $qb->select('*')
			->from('share')
			->where($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())))
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE_USERGROUP)))
			->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->orX(
				$qb->expr()->eq('item_type', $qb->createNamedParameter('file')),
				$qb->expr()->eq('item_type', $qb->createNamedParameter('folder'))
			))
			->setMaxResults(1)
			->execute();

		$data = $stmt->fetch();
		$stmt->closeCursor();

		if ($data !== false) {
			$share->setPermissions((int)$data['permissions']);
			$share->setTarget($data['file_target']);
		}

		return $share;
	}

	/**
	 * A user is deleted from the system
	 * So clean up the relevant shares.
	 *
	 * @param string $uid
	 * @param int $shareType
	 */
	public function userDeleted($uid, $shareType) {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->delete('share');

		if ($shareType === \OCP\Share::SHARE_TYPE_USER) {
			/*
			 * Delete all user shares that are owned by this user
			 * or that are received by this user
			 */

			$qb->where($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_USER)));

			$qb->andWhere(
				$qb->expr()->orX(
					$qb->expr()->eq('uid_owner', $qb->createNamedParameter($uid)),
					$qb->expr()->eq('share_with', $qb->createNamedParameter($uid))
				)
			);
		} else if ($shareType === \OCP\Share::SHARE_TYPE_GROUP) {
			/*
			 * Delete all group shares that are owned by this user
			 * Or special user group shares that are received by this user
			 */
			$qb->where(
				$qb->expr()->andX(
					$qb->expr()->orX(
						$qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_GROUP)),
						$qb->expr()->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE_USERGROUP))
					),
					$qb->expr()->eq('uid_owner', $qb->createNamedParameter($uid))
				)
			);

			$qb->orWhere(
				$qb->expr()->andX(
					$qb->expr()->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE_USERGROUP)),
					$qb->expr()->eq('share_with', $qb->createNamedParameter($uid))
				)
			);
		} else if ($shareType === \OCP\Share::SHARE_TYPE_LINK) {
			/*
			 * Delete all link shares owned by this user.
			 * And all link shares initiated by this user (until #22327 is in)
			 */
			$qb->where($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_LINK)));

			$qb->andWhere(
				$qb->expr()->orX(
					$qb->expr()->eq('uid_owner', $qb->createNamedParameter($uid)),
					$qb->expr()->eq('uid_initiator', $qb->createNamedParameter($uid))
				)
			);
		}

		$qb->execute();
	}

	/**
	 * Delete all shares received by this group. As well as any custom group
	 * shares for group members.
	 *
	 * @param string $gid
	 */
	public function groupDeleted($gid) {
		/*
		 * First delete all custom group shares for group members
		 */
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('id')
			->from('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_GROUP)))
			->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($gid)));

		$cursor = $qb->execute();
		$ids = [];
		while($row = $cursor->fetch()) {
			$ids[] = (int)$row['id'];
		}
		$cursor->closeCursor();

		if (!empty($ids)) {
			$chunks = array_chunk($ids, 100);
			foreach ($chunks as $chunk) {
				$qb->delete('share')
					->where($qb->expr()->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE_USERGROUP)))
					->andWhere($qb->expr()->in('parent', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));
				$qb->execute();
			}
		}

		/*
		 * Now delete all the group shares
		 */
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_GROUP)))
			->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($gid)));
		$qb->execute();
	}

	/**
	 * Delete custom group shares to this group for this user
	 *
	 * @param string $uid
	 * @param string $gid
	 */
	public function userDeletedFromGroup($uid, $gid) {
		/*
		 * Get all group shares
		 */
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('id')
			->from('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_GROUP)))
			->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($gid)));

		$cursor = $qb->execute();
		$ids = [];
		while($row = $cursor->fetch()) {
			$ids[] = (int)$row['id'];
		}
		$cursor->closeCursor();

		if (!empty($ids)) {
			$chunks = array_chunk($ids, 100);
			foreach ($chunks as $chunk) {
				/*
				 * Delete all special shares wit this users for the found group shares
				 */
				$qb->delete('share')
					->where($qb->expr()->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE_USERGROUP)))
					->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($uid)))
					->andWhere($qb->expr()->in('parent', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));
				$qb->execute();
			}
		}
	}
}

<?php

namespace OC\Share20;

use OCP\Share\ILinkShareProvider;
use OC\Share20\Exception\InvalidShare;
use OC\Share20\Exception\ProviderException;
use OCP\Share\Exceptions\ShareNotFound;
use OC\Share20\Exception\BackendError;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IUserManager;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\Files\Node;

class LinkShareProvider implements ILinkShareProvider {

	/** @var IDBConnection */
	private $dbConn;

	/** @var IUserManager */
	private $userManager;

	/** @var IRootFolder */
	private $rootFolder;

	/**
	 * DefaultShareProvider constructor.
	 *
	 * @param IDBConnection $connection
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(
		IDBConnection $connection,
		IUserManager $userManager,
		IRootFolder $rootFolder) {
		$this->dbConn = $connection;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * Return the identifier of this provider.
	 *
	 * @return string Containing only [a-zA-Z0-9]
	 */
	public function identifier() {
		return 'nclink';
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

		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
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
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK) {
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
	 * @param \OCP\Share\IShare $parent
	 * @return \OCP\Share\IShare[]
	 */
	public function getChildren(\OCP\Share\IShare $parent) {
		$children = [];

		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('parent', $qb->createNamedParameter($parent->getId())))
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_LINK)))
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
		$qb->execute();
	}

	/**
	 * Unshare a share from the recipient. If this is a group share
	 * this means we need a special entry in the share db.
	 *
	 * @param \OCP\Share\IShare $share
	 * @param string $recipient UserId of recipient
	 * @throws ProviderException
	 */
	public function deleteFromSelf(\OCP\Share\IShare $share, $recipient) {
		// Not supported on link shares
		throw new ProviderException('Not supported');
	}

	/**
	 * @inheritdoc
	 */
	public function move(\OCP\Share\IShare $share, $recipient) {
		// Does nothing for link shares

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
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_LINK)))
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
			->where($qb->expr()->eq('file_source', $qb->createNamedParameter($path->getId())))
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_LINK)))
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
	 * @inheritdoc
	 */
	public function getSharedWith($userId, $shareType, $node, $limit, $offset) {
		// Link shares are never shared with a user
		return [];
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

		$share->setPassword($data['share_with']);
		$share->setToken($data['token']);

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
	 * A user is deleted from the system
	 * So clean up the relevant shares.
	 *
	 * @param string $uid
	 * @param int $shareType
	 */
	public function userDeleted($uid, $shareType) {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->delete('share');

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

		$qb->execute();
	}

	/**
	 * Delete all shares received by this group. As well as any custom group
	 * shares for group members.
	 *
	 * @param string $gid
	 */
	public function groupDeleted($gid) {
		// Not relevant for link shares
		return;
	}

	/**
	 * Delete custom group shares to this group for this user
	 *
	 * @param string $uid
	 * @param string $gid
	 */
	public function userDeletedFromGroup($uid, $gid) {
		// Not relevant for link shares
		return;
	}
}

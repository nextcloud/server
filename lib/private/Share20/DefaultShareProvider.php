<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Share20;

use OC\Files\Cache\Cache;
use OC\Files\Filesystem;
use OC\Share20\Exception\BackendError;
use OC\Share20\Exception\InvalidShare;
use OC\Share20\Exception\ProviderException;
use OC\User\LazyUser;
use OCA\Files_Sharing\AppInfo\Application;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Defaults;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Server;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IAttributes;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Share\IShareProviderSupportsAccept;
use OCP\Share\IShareProviderSupportsAllSharesInFolder;
use OCP\Share\IShareProviderWithNotification;
use OCP\Util;
use Psr\Log\LoggerInterface;
use function str_starts_with;

/**
 * Class DefaultShareProvider
 *
 * @package OC\Share20
 */
class DefaultShareProvider implements IShareProviderWithNotification, IShareProviderSupportsAccept, IShareProviderSupportsAllSharesInFolder {
	public function __construct(
		private IDBConnection $dbConn,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private IRootFolder $rootFolder,
		private IMailer $mailer,
		private Defaults $defaults,
		private IFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
		private ITimeFactory $timeFactory,
		private LoggerInterface $logger,
		private IManager $shareManager,
		private IConfig $config,
	) {
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

		$expirationDate = $share->getExpirationDate();
		if ($expirationDate !== null) {
			$expirationDate = clone $expirationDate;
			$expirationDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
		}

		if ($share->getShareType() === IShare::TYPE_USER) {
			//Set the UID of the user we share with
			$qb->setValue('share_with', $qb->createNamedParameter($share->getSharedWith()));
			$qb->setValue('accepted', $qb->createNamedParameter(IShare::STATUS_PENDING));

			//If an expiration date is set store it
			if ($expirationDate !== null) {
				$qb->setValue('expiration', $qb->createNamedParameter($expirationDate, 'datetime'));
			}

			$qb->setValue('reminder_sent', $qb->createNamedParameter($share->getReminderSent(), IQueryBuilder::PARAM_BOOL));
		} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
			//Set the GID of the group we share with
			$qb->setValue('share_with', $qb->createNamedParameter($share->getSharedWith()));

			//If an expiration date is set store it
			if ($expirationDate !== null) {
				$qb->setValue('expiration', $qb->createNamedParameter($expirationDate, 'datetime'));
			}
		} elseif ($share->getShareType() === IShare::TYPE_LINK) {
			//set label for public link
			$qb->setValue('label', $qb->createNamedParameter($share->getLabel()));
			//Set the token of the share
			$qb->setValue('token', $qb->createNamedParameter($share->getToken()));

			//If a password is set store it
			if ($share->getPassword() !== null) {
				$qb->setValue('password', $qb->createNamedParameter($share->getPassword()));
			}

			$qb->setValue('password_by_talk', $qb->createNamedParameter($share->getSendPasswordByTalk(), IQueryBuilder::PARAM_BOOL));

			//If an expiration date is set store it
			if ($expirationDate !== null) {
				$qb->setValue('expiration', $qb->createNamedParameter($expirationDate, 'datetime'));
			}

			$qb->setValue('parent', $qb->createNamedParameter($share->getParent()));

			$qb->setValue('hide_download', $qb->createNamedParameter($share->getHideDownload() ? 1 : 0, IQueryBuilder::PARAM_INT));
		} else {
			throw new \Exception('invalid share type!');
		}

		// Set what is shares
		$qb->setValue('item_type', $qb->createParameter('itemType'));
		if ($share->getNode() instanceof File) {
			$qb->setParameter('itemType', 'file');
		} else {
			$qb->setParameter('itemType', 'folder');
		}

		// Set the file id
		$qb->setValue('item_source', $qb->createNamedParameter($share->getNode()->getId()));
		$qb->setValue('file_source', $qb->createNamedParameter($share->getNode()->getId()));

		// set the permissions
		$qb->setValue('permissions', $qb->createNamedParameter($share->getPermissions()));

		// set share attributes
		$shareAttributes = $this->formatShareAttributes(
			$share->getAttributes()
		);
		$qb->setValue('attributes', $qb->createNamedParameter($shareAttributes));

		// Set who created this share
		$qb->setValue('uid_initiator', $qb->createNamedParameter($share->getSharedBy()));

		// Set who is the owner of this file/folder (and this the owner of the share)
		$qb->setValue('uid_owner', $qb->createNamedParameter($share->getShareOwner()));

		// Set the file target
		$qb->setValue('file_target', $qb->createNamedParameter($share->getTarget()));

		if ($share->getNote() !== '') {
			$qb->setValue('note', $qb->createNamedParameter($share->getNote()));
		}

		// Set the time this share was created
		$shareTime = $this->timeFactory->now();
		$qb->setValue('stime', $qb->createNamedParameter($shareTime->getTimestamp()));

		// insert the data and fetch the id of the share
		$qb->executeStatement();

		// Update mandatory data
		$id = $qb->getLastInsertId();
		$share->setId((string)$id);
		$share->setProviderId($this->identifier());

		$share->setShareTime(\DateTime::createFromImmutable($shareTime));

		$mailSendValue = $share->getMailSend();
		$share->setMailSend(($mailSendValue === null) ? true : $mailSendValue);

		return $share;
	}

	/**
	 * Update a share
	 *
	 * @param IShare $share
	 * @return IShare The share object
	 * @throws ShareNotFound
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function update(IShare $share) {
		$originalShare = $this->getShareById($share->getId());

		$shareAttributes = $this->formatShareAttributes($share->getAttributes());

		$expirationDate = $share->getExpirationDate();
		if ($expirationDate !== null) {
			$expirationDate = clone $expirationDate;
			$expirationDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
		}

		if ($share->getShareType() === IShare::TYPE_USER) {
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
				->set('attributes', $qb->createNamedParameter($shareAttributes))
				->set('item_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('file_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('expiration', $qb->createNamedParameter($expirationDate, IQueryBuilder::PARAM_DATETIME_MUTABLE))
				->set('note', $qb->createNamedParameter($share->getNote()))
				->set('accepted', $qb->createNamedParameter($share->getStatus()))
				->set('reminder_sent', $qb->createNamedParameter($share->getReminderSent(), IQueryBuilder::PARAM_BOOL))
				->executeStatement();
		} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
			$qb = $this->dbConn->getQueryBuilder();
			$qb->update('share')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
				->set('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
				->set('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
				->set('permissions', $qb->createNamedParameter($share->getPermissions()))
				->set('attributes', $qb->createNamedParameter($shareAttributes))
				->set('item_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('file_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('expiration', $qb->createNamedParameter($expirationDate, IQueryBuilder::PARAM_DATETIME_MUTABLE))
				->set('note', $qb->createNamedParameter($share->getNote()))
				->executeStatement();

			/*
			 * Update all user defined group shares
			 */
			$qb = $this->dbConn->getQueryBuilder();
			$qb->update('share')
				->where($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())))
				->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)))
				->set('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
				->set('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
				->set('item_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('file_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('expiration', $qb->createNamedParameter($expirationDate, IQueryBuilder::PARAM_DATETIME_MUTABLE))
				->set('note', $qb->createNamedParameter($share->getNote()))
				->executeStatement();

			/*
			 * Now update the permissions for all children that have not set it to 0
			 */
			$qb = $this->dbConn->getQueryBuilder();
			$qb->update('share')
				->where($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())))
				->andWhere($qb->expr()->neq('permissions', $qb->createNamedParameter(0)))
				->set('permissions', $qb->createNamedParameter($share->getPermissions()))
				->set('attributes', $qb->createNamedParameter($shareAttributes))
				->executeStatement();
		} elseif ($share->getShareType() === IShare::TYPE_LINK) {
			$qb = $this->dbConn->getQueryBuilder();
			$qb->update('share')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
				->set('password', $qb->createNamedParameter($share->getPassword()))
				->set('password_by_talk', $qb->createNamedParameter($share->getSendPasswordByTalk(), IQueryBuilder::PARAM_BOOL))
				->set('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
				->set('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
				->set('permissions', $qb->createNamedParameter($share->getPermissions()))
				->set('attributes', $qb->createNamedParameter($shareAttributes))
				->set('item_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('file_source', $qb->createNamedParameter($share->getNode()->getId()))
				->set('token', $qb->createNamedParameter($share->getToken()))
				->set('expiration', $qb->createNamedParameter($expirationDate, IQueryBuilder::PARAM_DATETIME_MUTABLE))
				->set('note', $qb->createNamedParameter($share->getNote()))
				->set('label', $qb->createNamedParameter($share->getLabel()))
				->set('hide_download', $qb->createNamedParameter($share->getHideDownload() ? 1 : 0, IQueryBuilder::PARAM_INT))
				->executeStatement();
		}

		if ($originalShare->getNote() !== $share->getNote() && $share->getNote() !== '') {
			$this->propagateNote($share);
		}


		return $share;
	}

	/**
	 * Accept a share.
	 *
	 * @param IShare $share
	 * @param string $recipient
	 * @return IShare The share object
	 * @since 9.0.0
	 */
	public function acceptShare(IShare $share, string $recipient): IShare {
		if ($share->getShareType() === IShare::TYPE_GROUP) {
			$group = $this->groupManager->get($share->getSharedWith());
			$user = $this->userManager->get($recipient);

			if (is_null($group)) {
				throw new ProviderException('Group "' . $share->getSharedWith() . '" does not exist');
			}

			if (!$group->inGroup($user)) {
				throw new ProviderException('Recipient not in receiving group');
			}

			// Try to fetch user specific share
			$qb = $this->dbConn->getQueryBuilder();
			$stmt = $qb->select('*')
				->from('share')
				->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)))
				->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($recipient)))
				->andWhere($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())))
				->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)))
				->executeQuery();

			$data = $stmt->fetch();
			$stmt->closeCursor();

			/*
			 * Check if there already is a user specific group share.
			 * If there is update it (if required).
			 */
			if ($data === false) {
				$id = $this->createUserSpecificGroupShare($share, $recipient);
			} else {
				$id = $data['id'];
			}
		} elseif ($share->getShareType() === IShare::TYPE_USER) {
			if ($share->getSharedWith() !== $recipient) {
				throw new ProviderException('Recipient does not match');
			}

			$id = $share->getId();
		} else {
			throw new ProviderException('Invalid shareType');
		}

		$qb = $this->dbConn->getQueryBuilder();
		$qb->update('share')
			->set('accepted', $qb->createNamedParameter(IShare::STATUS_ACCEPTED))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeStatement();

		return $share;
	}

	public function getChildren(IShare $parent): array {
		$children = [];

		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('parent', $qb->createNamedParameter($parent->getId())))
			->andWhere(
				$qb->expr()->in(
					'share_type',
					$qb->createNamedParameter([
						IShare::TYPE_USER,
						IShare::TYPE_GROUP,
						IShare::TYPE_LINK,
					], IQueryBuilder::PARAM_INT_ARRAY)
				)
			)
			->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)))
			->orderBy('id');

		$cursor = $qb->executeQuery();
		while ($data = $cursor->fetch()) {
			$children[] = $this->createShare($data);
		}
		$cursor->closeCursor();

		return $children;
	}

	/**
	 * Delete a share
	 *
	 * @param IShare $share
	 */
	public function delete(IShare $share) {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())));

		/*
		 * If the share is a group share delete all possible
		 * user defined groups shares.
		 */
		if ($share->getShareType() === IShare::TYPE_GROUP) {
			$qb->orWhere($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())));
		}

		$qb->executeStatement();
	}

	/**
	 * Unshare a share from the recipient. If this is a group share
	 * this means we need a special entry in the share db.
	 *
	 * @param IShare $share
	 * @param string $recipient UserId of recipient
	 * @throws BackendError
	 * @throws ProviderException
	 */
	public function deleteFromSelf(IShare $share, $recipient) {
		if ($share->getShareType() === IShare::TYPE_GROUP) {
			$group = $this->groupManager->get($share->getSharedWith());
			$user = $this->userManager->get($recipient);

			if (is_null($group)) {
				throw new ProviderException('Group "' . $share->getSharedWith() . '" does not exist');
			}

			if (!$group->inGroup($user)) {
				// nothing left to do
				return;
			}

			// Try to fetch user specific share
			$qb = $this->dbConn->getQueryBuilder();
			$stmt = $qb->select('*')
				->from('share')
				->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)))
				->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($recipient)))
				->andWhere($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())))
				->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)))
				->executeQuery();

			$data = $stmt->fetch();

			/*
			 * Check if there already is a user specific group share.
			 * If there is update it (if required).
			 */
			if ($data === false) {
				$id = $this->createUserSpecificGroupShare($share, $recipient);
				$permissions = $share->getPermissions();
			} else {
				$permissions = $data['permissions'];
				$id = $data['id'];
			}

			if ($permissions !== 0) {
				// Update existing usergroup share
				$qb = $this->dbConn->getQueryBuilder();
				$qb->update('share')
					->set('permissions', $qb->createNamedParameter(0))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
					->executeStatement();
			}
		} elseif ($share->getShareType() === IShare::TYPE_USER) {
			if ($share->getSharedWith() !== $recipient) {
				throw new ProviderException('Recipient does not match');
			}

			// We can just delete user and link shares
			$this->delete($share);
		} else {
			throw new ProviderException('Invalid shareType');
		}
	}

	protected function createUserSpecificGroupShare(IShare $share, string $recipient): int {
		$type = $share->getNodeType();

		$shareFolder = $this->config->getSystemValue('share_folder', '/');
		$allowCustomShareFolder = $this->config->getSystemValueBool('sharing.allow_custom_share_folder', true);
		if ($allowCustomShareFolder) {
			$shareFolder = $this->config->getUserValue($recipient, Application::APP_ID, 'share_folder', $shareFolder);
		}

		$target = $shareFolder . '/' . $share->getNode()->getName();
		$target = Filesystem::normalizePath($target);

		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share')
			->values([
				'share_type' => $qb->createNamedParameter(IShare::TYPE_USERGROUP),
				'share_with' => $qb->createNamedParameter($recipient),
				'uid_owner' => $qb->createNamedParameter($share->getShareOwner()),
				'uid_initiator' => $qb->createNamedParameter($share->getSharedBy()),
				'parent' => $qb->createNamedParameter($share->getId()),
				'item_type' => $qb->createNamedParameter($type),
				'item_source' => $qb->createNamedParameter($share->getNodeId()),
				'file_source' => $qb->createNamedParameter($share->getNodeId()),
				'file_target' => $qb->createNamedParameter($target),
				'permissions' => $qb->createNamedParameter($share->getPermissions()),
				'stime' => $qb->createNamedParameter($share->getShareTime()->getTimestamp()),
			])->executeStatement();

		return $qb->getLastInsertId();
	}

	/**
	 * @inheritdoc
	 *
	 * For now this only works for group shares
	 * If this gets implemented for normal shares we have to extend it
	 */
	public function restore(IShare $share, string $recipient): IShare {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('permissions')
			->from('share')
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($share->getId()))
			);
		$cursor = $qb->executeQuery();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		$originalPermission = $data['permissions'];

		$qb = $this->dbConn->getQueryBuilder();
		$qb->update('share')
			->set('permissions', $qb->createNamedParameter($originalPermission))
			->where(
				$qb->expr()->eq('parent', $qb->createNamedParameter($share->getParent()))
			)->andWhere(
				$qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP))
			)->andWhere(
				$qb->expr()->eq('share_with', $qb->createNamedParameter($recipient))
			);

		$qb->executeStatement();

		return $this->getShareById($share->getId(), $recipient);
	}

	/**
	 * @inheritdoc
	 */
	public function move(IShare $share, $recipient) {
		if ($share->getShareType() === IShare::TYPE_USER) {
			// Just update the target
			$qb = $this->dbConn->getQueryBuilder();
			$qb->update('share')
				->set('file_target', $qb->createNamedParameter($share->getTarget()))
				->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
				->executeStatement();
		} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
			// Check if there is a usergroup share
			$qb = $this->dbConn->getQueryBuilder();
			$stmt = $qb->select('id')
				->from('share')
				->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)))
				->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($recipient)))
				->andWhere($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())))
				->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)))
				->setMaxResults(1)
				->executeQuery();

			$data = $stmt->fetch();
			$stmt->closeCursor();

			$shareAttributes = $this->formatShareAttributes(
				$share->getAttributes()
			);

			if ($data === false) {
				// No usergroup share yet. Create one.
				$qb = $this->dbConn->getQueryBuilder();
				$qb->insert('share')
					->values([
						'share_type' => $qb->createNamedParameter(IShare::TYPE_USERGROUP),
						'share_with' => $qb->createNamedParameter($recipient),
						'uid_owner' => $qb->createNamedParameter($share->getShareOwner()),
						'uid_initiator' => $qb->createNamedParameter($share->getSharedBy()),
						'parent' => $qb->createNamedParameter($share->getId()),
						'item_type' => $qb->createNamedParameter($share->getNodeType()),
						'item_source' => $qb->createNamedParameter($share->getNodeId()),
						'file_source' => $qb->createNamedParameter($share->getNodeId()),
						'file_target' => $qb->createNamedParameter($share->getTarget()),
						'permissions' => $qb->createNamedParameter($share->getPermissions()),
						'attributes' => $qb->createNamedParameter($shareAttributes),
						'stime' => $qb->createNamedParameter($share->getShareTime()->getTimestamp()),
					])->executeStatement();
			} else {
				// Already a usergroup share. Update it.
				$qb = $this->dbConn->getQueryBuilder();
				$qb->update('share')
					->set('file_target', $qb->createNamedParameter($share->getTarget()))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($data['id'])))
					->executeStatement();
			}
		}

		return $share;
	}

	public function getSharesInFolder($userId, Folder $node, $reshares, $shallow = true) {
		if (!$shallow) {
			throw new \Exception('non-shallow getSharesInFolder is no longer supported');
		}

		return $this->getSharesInFolderInternal($userId, $node, $reshares);
	}

	public function getAllSharesInFolder(Folder $node): array {
		return $this->getSharesInFolderInternal(null, $node, null);
	}

	/**
	 * @return array<int, list<IShare>>
	 */
	private function getSharesInFolderInternal(?string $userId, Folder $node, ?bool $reshares): array {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('s.*',
			'f.fileid', 'f.path', 'f.permissions AS f_permissions', 'f.storage', 'f.path_hash',
			'f.parent AS f_parent', 'f.name', 'f.mimetype', 'f.mimepart', 'f.size', 'f.mtime', 'f.storage_mtime',
			'f.encrypted', 'f.unencrypted_size', 'f.etag', 'f.checksum')
			->from('share', 's')
			->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)));

		$qb->andWhere($qb->expr()->in('share_type', $qb->createNamedParameter([IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_LINK], IQueryBuilder::PARAM_INT_ARRAY)));

		if ($userId !== null) {
			/**
			 * Reshares for this user are shares where they are the owner.
			 */
			if ($reshares !== true) {
				$qb->andWhere($qb->expr()->eq('uid_initiator', $qb->createNamedParameter($userId)));
			} else {
				$qb->andWhere(
					$qb->expr()->orX(
						$qb->expr()->eq('uid_owner', $qb->createNamedParameter($userId)),
						$qb->expr()->eq('uid_initiator', $qb->createNamedParameter($userId))
					)
				);
			}
		}

		// todo? maybe get these from the oc_mounts table
		$childMountNodes = array_filter($node->getDirectoryListing(), function (Node $node): bool {
			return $node->getInternalPath() === '';
		});
		$childMountRootIds = array_map(function (Node $node): int {
			return $node->getId();
		}, $childMountNodes);

		$qb->innerJoin('s', 'filecache', 'f', $qb->expr()->eq('s.file_source', 'f.fileid'));
		$qb->andWhere(
			$qb->expr()->orX(
				$qb->expr()->eq('f.parent', $qb->createNamedParameter($node->getId())),
				$qb->expr()->in('f.fileid', $qb->createParameter('chunk'))
			)
		);

		$qb->orderBy('id');

		$shares = [];

		$chunks = array_chunk($childMountRootIds, 1000);

		// Force the request to be run when there is 0 mount.
		if (count($chunks) === 0) {
			$chunks = [[]];
		}

		foreach ($chunks as $chunk) {
			$qb->setParameter('chunk', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$cursor = $qb->executeQuery();
			while ($data = $cursor->fetch()) {
				$shares[$data['fileid']][] = $this->createShare($data);
			}
			$cursor->closeCursor();
		}

		return $shares;
	}

	/**
	 * @inheritdoc
	 */
	public function getSharesBy($userId, $shareType, $node, $reshares, $limit, $offset) {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)));

		$qb->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter($shareType)));

		/**
		 * Reshares for this user are shares where they are the owner.
		 */
		if ($reshares === false) {
			$qb->andWhere($qb->expr()->eq('uid_initiator', $qb->createNamedParameter($userId)));
		} else {
			if ($node === null) {
				$qb->andWhere(
					$qb->expr()->orX(
						$qb->expr()->eq('uid_owner', $qb->createNamedParameter($userId)),
						$qb->expr()->eq('uid_initiator', $qb->createNamedParameter($userId))
					)
				);
			}
		}

		if ($node !== null) {
			$qb->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($node->getId())));
		}

		if ($limit !== -1) {
			$qb->setMaxResults($limit);
		}

		$qb->setFirstResult($offset);
		$qb->orderBy('id');

		$cursor = $qb->executeQuery();
		$shares = [];
		while ($data = $cursor->fetch()) {
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
						IShare::TYPE_USER,
						IShare::TYPE_GROUP,
						IShare::TYPE_LINK,
					], IQueryBuilder::PARAM_INT_ARRAY)
				)
			)
			->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)));

		$cursor = $qb->executeQuery();
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
		if ($recipientId !== null && $share->getShareType() === IShare::TYPE_GROUP) {
			$share = $this->resolveGroupShares([(int)$share->getId() => $share], $recipientId)[0];
		}

		return $share;
	}

	/**
	 * Get shares for a given path
	 *
	 * @param Node $path
	 * @return IShare[]
	 */
	public function getSharesByPath(Node $path) {
		$qb = $this->dbConn->getQueryBuilder();

		$cursor = $qb->select('*')
			->from('share')
			->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($path->getId())))
			->andWhere($qb->expr()->in('share_type', $qb->createNamedParameter([IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_LINK], IQueryBuilder::PARAM_INT_ARRAY)))
			->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)))
			->orderBy('id', 'ASC')
			->executeQuery();

		$shares = [];
		while ($data = $cursor->fetch()) {
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
		if ($data['fileid'] === null || $data['path'] === null) {
			return false;
		}

		// exclude shares leading to trashbin on home storages
		$pathSections = explode('/', $data['path'], 2);
		// FIXME: would not detect rare md5'd home storage case properly
		if ($pathSections[0] !== 'files'
			&& (str_starts_with($data['storage_string_id'], 'home::') || str_starts_with($data['storage_string_id'], 'object::user'))) {
			return false;
		} elseif ($pathSections[0] === '__groupfolders'
			&& str_starts_with($pathSections[1], 'trash/')
		) {
			// exclude shares leading to trashbin on group folders storages
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

		if ($shareType === IShare::TYPE_USER) {
			//Get shares directly with this user
			$qb = $this->dbConn->getQueryBuilder();
			$qb->select('s.*',
				'f.fileid', 'f.path', 'f.permissions AS f_permissions', 'f.storage', 'f.path_hash',
				'f.parent AS f_parent', 'f.name', 'f.mimetype', 'f.mimepart', 'f.size', 'f.mtime', 'f.storage_mtime',
				'f.encrypted', 'f.unencrypted_size', 'f.etag', 'f.checksum'
			)
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

			$qb->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USER)))
				->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($userId)))
				->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)));

			// Filter by node if provided
			if ($node !== null) {
				$qb->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($node->getId())));
			}

			$cursor = $qb->executeQuery();

			while ($data = $cursor->fetch()) {
				if ($data['fileid'] && $data['path'] === null) {
					$data['path'] = (string)$data['path'];
					$data['name'] = (string)$data['name'];
					$data['checksum'] = (string)$data['checksum'];
				}
				if ($this->isAccessibleResult($data)) {
					$shares[] = $this->createShare($data);
				}
			}
			$cursor->closeCursor();
		} elseif ($shareType === IShare::TYPE_GROUP) {
			$user = new LazyUser($userId, $this->userManager);
			$allGroups = $this->groupManager->getUserGroupIds($user);

			/** @var Share[] $shares2 */
			$shares2 = [];

			$start = 0;
			while (true) {
				$groups = array_slice($allGroups, $start, 1000);
				$start += 1000;

				if ($groups === []) {
					break;
				}

				$qb = $this->dbConn->getQueryBuilder();
				$qb->select('s.*',
					'f.fileid', 'f.path', 'f.permissions AS f_permissions', 'f.storage', 'f.path_hash',
					'f.parent AS f_parent', 'f.name', 'f.mimetype', 'f.mimepart', 'f.size', 'f.mtime', 'f.storage_mtime',
					'f.encrypted', 'f.unencrypted_size', 'f.etag', 'f.checksum'
				)
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

				$groups = array_filter($groups);

				$qb->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_GROUP)))
					->andWhere($qb->expr()->in('share_with', $qb->createNamedParameter(
						$groups,
						IQueryBuilder::PARAM_STR_ARRAY
					)))
					->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)));

				$cursor = $qb->executeQuery();
				while ($data = $cursor->fetch()) {
					if ($offset > 0) {
						$offset--;
						continue;
					}

					if ($this->isAccessibleResult($data)) {
						$share = $this->createShare($data);
						$shares2[$share->getId()] = $share;
					}
				}
				$cursor->closeCursor();
			}

			/*
			 * Resolve all group shares to user specific shares
			 */
			$shares = $this->resolveGroupShares($shares2, $userId);
		} else {
			throw new BackendError('Invalid backend');
		}


		return $shares;
	}

	/**
	 * Get a share by token
	 *
	 * @param string $token
	 * @return IShare
	 * @throws ShareNotFound
	 */
	public function getShareByToken($token) {
		$qb = $this->dbConn->getQueryBuilder();

		$cursor = $qb->select('*')
			->from('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_LINK)))
			->andWhere($qb->expr()->eq('token', $qb->createNamedParameter($token)))
			->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)))
			->executeQuery();

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
	 * Create a share object from a database row
	 *
	 * @param mixed[] $data
	 * @return IShare
	 * @throws InvalidShare
	 */
	private function createShare($data) {
		$share = new Share($this->rootFolder, $this->userManager);
		$share->setId($data['id'])
			->setShareType((int)$data['share_type'])
			->setPermissions((int)$data['permissions'])
			->setTarget($data['file_target'])
			->setNote((string)$data['note'])
			->setMailSend((bool)$data['mail_send'])
			->setStatus((int)$data['accepted'])
			->setLabel($data['label'] ?? '');

		$shareTime = new \DateTime();
		$shareTime->setTimestamp((int)$data['stime']);
		$share->setShareTime($shareTime);

		if ($share->getShareType() === IShare::TYPE_USER) {
			$share->setSharedWith($data['share_with']);
			$displayName = $this->userManager->getDisplayName($data['share_with']);
			if ($displayName !== null) {
				$share->setSharedWithDisplayName($displayName);
			}
		} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
			$share->setSharedWith($data['share_with']);
			$group = $this->groupManager->get($data['share_with']);
			if ($group !== null) {
				$share->setSharedWithDisplayName($group->getDisplayName());
			}
		} elseif ($share->getShareType() === IShare::TYPE_LINK) {
			$share->setPassword($data['password']);
			$share->setSendPasswordByTalk((bool)$data['password_by_talk']);
			$share->setToken($data['token']);
		}

		$share = $this->updateShareAttributes($share, $data['attributes']);

		$share->setSharedBy($data['uid_initiator']);
		$share->setShareOwner($data['uid_owner']);

		$share->setNodeId((int)$data['file_source']);
		$share->setNodeType($data['item_type']);

		if ($data['expiration'] !== null) {
			$expiration = \DateTime::createFromFormat('Y-m-d H:i:s', $data['expiration']);
			$share->setExpirationDate($expiration);
		}

		if (isset($data['f_permissions'])) {
			$entryData = $data;
			$entryData['permissions'] = $entryData['f_permissions'];
			$entryData['parent'] = $entryData['f_parent'];
			$share->setNodeCacheEntry(Cache::cacheEntryFromData($entryData,
				Server::get(IMimeTypeLoader::class)));
		}

		$share->setProviderId($this->identifier());
		$share->setHideDownload((int)$data['hide_download'] === 1);
		$share->setReminderSent((bool)$data['reminder_sent']);

		return $share;
	}

	/**
	 * Update the data from group shares with any per-user modifications
	 *
	 * @param array<int, Share> $shareMap shares indexed by share id
	 * @param $userId
	 * @return Share[] The updates shares if no update is found for a share return the original
	 */
	private function resolveGroupShares($shareMap, $userId) {
		$qb = $this->dbConn->getQueryBuilder();
		$query = $qb->select('*')
			->from('share')
			->where($qb->expr()->eq('share_with', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)))
			->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)));

		// this is called with either all group shares or one group share.
		// for all shares it's easier to just only search by share_with,
		// for a single share it's efficient to filter by parent
		if (count($shareMap) === 1) {
			$share = reset($shareMap);
			$query->andWhere($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())));
		}

		$stmt = $query->executeQuery();

		while ($data = $stmt->fetch()) {
			if (array_key_exists($data['parent'], $shareMap)) {
				$shareMap[$data['parent']]->setPermissions((int)$data['permissions']);
				$shareMap[$data['parent']]->setStatus((int)$data['accepted']);
				$shareMap[$data['parent']]->setTarget($data['file_target']);
				$shareMap[$data['parent']]->setParent($data['parent']);
			}
		}

		return array_values($shareMap);
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

		if ($shareType === IShare::TYPE_USER) {
			/*
			 * Delete all user shares that are owned by this user
			 * or that are received by this user
			 */

			$qb->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USER)));

			$qb->andWhere(
				$qb->expr()->orX(
					$qb->expr()->eq('uid_owner', $qb->createNamedParameter($uid)),
					$qb->expr()->eq('share_with', $qb->createNamedParameter($uid))
				)
			);
		} elseif ($shareType === IShare::TYPE_GROUP) {
			/*
			 * Delete all group shares that are owned by this user
			 * Or special user group shares that are received by this user
			 */
			$qb->where(
				$qb->expr()->andX(
					$qb->expr()->in('share_type', $qb->createNamedParameter([IShare::TYPE_GROUP, IShare::TYPE_USERGROUP], IQueryBuilder::PARAM_INT_ARRAY)),
					$qb->expr()->eq('uid_owner', $qb->createNamedParameter($uid))
				)
			);

			$qb->orWhere(
				$qb->expr()->andX(
					$qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)),
					$qb->expr()->eq('share_with', $qb->createNamedParameter($uid))
				)
			);
		} elseif ($shareType === IShare::TYPE_LINK) {
			/*
			 * Delete all link shares owned by this user.
			 * And all link shares initiated by this user (until #22327 is in)
			 */
			$qb->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_LINK)));

			$qb->andWhere(
				$qb->expr()->orX(
					$qb->expr()->eq('uid_owner', $qb->createNamedParameter($uid)),
					$qb->expr()->eq('uid_initiator', $qb->createNamedParameter($uid))
				)
			);
		} else {
			$e = new \InvalidArgumentException('Default share provider tried to delete all shares for type: ' . $shareType);
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return;
		}

		$qb->executeStatement();
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
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_GROUP)))
			->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($gid)));

		$cursor = $qb->executeQuery();
		$ids = [];
		while ($row = $cursor->fetch()) {
			$ids[] = (int)$row['id'];
		}
		$cursor->closeCursor();

		if (!empty($ids)) {
			$chunks = array_chunk($ids, 100);

			$qb = $this->dbConn->getQueryBuilder();
			$qb->delete('share')
				->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)))
				->andWhere($qb->expr()->in('parent', $qb->createParameter('parents')));

			foreach ($chunks as $chunk) {
				$qb->setParameter('parents', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
				$qb->executeStatement();
			}
		}

		/*
		 * Now delete all the group shares
		 */
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_GROUP)))
			->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($gid)));
		$qb->executeStatement();
	}

	/**
	 * Delete custom group shares to this group for this user
	 *
	 * @param string $uid
	 * @param string $gid
	 * @return void
	 */
	public function userDeletedFromGroup($uid, $gid) {
		/*
		 * Get all group shares
		 */
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('id')
			->from('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_GROUP)))
			->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($gid)));

		$cursor = $qb->executeQuery();
		$ids = [];
		while ($row = $cursor->fetch()) {
			$ids[] = (int)$row['id'];
		}
		$cursor->closeCursor();

		if (!empty($ids)) {
			$chunks = array_chunk($ids, 100);

			/*
			 * Delete all special shares with this user for the found group shares
			 */
			$qb = $this->dbConn->getQueryBuilder();
			$qb->delete('share')
				->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)))
				->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($uid)))
				->andWhere($qb->expr()->in('parent', $qb->createParameter('parents')));

			foreach ($chunks as $chunk) {
				$qb->setParameter('parents', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
				$qb->executeStatement();
			}
		}

		if ($this->shareManager->shareWithGroupMembersOnly()) {
			$user = $this->userManager->get($uid);
			if ($user === null) {
				return;
			}
			$userGroups = $this->groupManager->getUserGroupIds($user);
			$userGroups = array_diff($userGroups, $this->shareManager->shareWithGroupMembersOnlyExcludeGroupsList());

			// Delete user shares received by the user from users in the group.
			$userReceivedShares = $this->shareManager->getSharedWith($uid, IShare::TYPE_USER, null, -1);
			foreach ($userReceivedShares as $share) {
				$owner = $this->userManager->get($share->getSharedBy());
				if ($owner === null) {
					continue;
				}
				$ownerGroups = $this->groupManager->getUserGroupIds($owner);
				$mutualGroups = array_intersect($userGroups, $ownerGroups);

				if (count($mutualGroups) === 0) {
					$this->shareManager->deleteShare($share);
				}
			}

			// Delete user shares from the user to users in the group.
			$userEmittedShares = $this->shareManager->getSharesBy($uid, IShare::TYPE_USER, null, true, -1);
			foreach ($userEmittedShares as $share) {
				$recipient = $this->userManager->get($share->getSharedWith());
				if ($recipient === null) {
					continue;
				}
				$recipientGroups = $this->groupManager->getUserGroupIds($recipient);
				$mutualGroups = array_intersect($userGroups, $recipientGroups);

				if (count($mutualGroups) === 0) {
					$this->shareManager->deleteShare($share);
				}
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getAccessList($nodes, $currentAccess) {
		$ids = [];
		foreach ($nodes as $node) {
			$ids[] = $node->getId();
		}

		$qb = $this->dbConn->getQueryBuilder();

		$shareTypes = [IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_LINK];

		if ($currentAccess) {
			$shareTypes[] = IShare::TYPE_USERGROUP;
		}

		$qb->select('id', 'parent', 'share_type', 'share_with', 'file_source', 'file_target', 'permissions')
			->from('share')
			->where(
				$qb->expr()->in('share_type', $qb->createNamedParameter($shareTypes, IQueryBuilder::PARAM_INT_ARRAY))
			)
			->andWhere($qb->expr()->in('file_source', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)))
			->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)));

		// Ensure accepted is true for user and usergroup type
		$qb->andWhere(
			$qb->expr()->orX(
				$qb->expr()->andX(
					$qb->expr()->neq('share_type', $qb->createNamedParameter(IShare::TYPE_USER)),
					$qb->expr()->neq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)),
				),
				$qb->expr()->eq('accepted', $qb->createNamedParameter(IShare::STATUS_ACCEPTED, IQueryBuilder::PARAM_INT)),
			),
		);

		$cursor = $qb->executeQuery();

		$users = [];
		$link = false;
		while ($row = $cursor->fetch()) {
			$type = (int)$row['share_type'];
			if ($type === IShare::TYPE_USER) {
				$uid = $row['share_with'];
				$users[$uid] = $users[$uid] ?? [];
				$users[$uid][$row['id']] = $row;
			} elseif ($type === IShare::TYPE_GROUP) {
				$gid = $row['share_with'];
				$group = $this->groupManager->get($gid);

				if ($group === null) {
					continue;
				}

				$userList = $group->getUsers();
				foreach ($userList as $user) {
					$uid = $user->getUID();
					$users[$uid] = $users[$uid] ?? [];
					$users[$uid][$row['id']] = $row;
				}
			} elseif ($type === IShare::TYPE_LINK) {
				$link = true;
			} elseif ($type === IShare::TYPE_USERGROUP && $currentAccess === true) {
				$uid = $row['share_with'];
				$users[$uid] = $users[$uid] ?? [];
				$users[$uid][$row['id']] = $row;
			}
		}
		$cursor->closeCursor();

		if ($currentAccess === true) {
			$users = array_map([$this, 'filterSharesOfUser'], $users);
			$users = array_filter($users);
		} else {
			$users = array_keys($users);
		}

		return ['users' => $users, 'public' => $link];
	}

	/**
	 * For each user the path with the fewest slashes is returned
	 * @param array $shares
	 * @return array
	 */
	protected function filterSharesOfUser(array $shares) {
		// Group shares when the user has a share exception
		foreach ($shares as $id => $share) {
			$type = (int)$share['share_type'];
			$permissions = (int)$share['permissions'];

			if ($type === IShare::TYPE_USERGROUP) {
				unset($shares[$share['parent']]);

				if ($permissions === 0) {
					unset($shares[$id]);
				}
			}
		}

		$best = [];
		$bestDepth = 0;
		foreach ($shares as $id => $share) {
			$depth = substr_count(($share['file_target'] ?? ''), '/');
			if (empty($best) || $depth < $bestDepth) {
				$bestDepth = $depth;
				$best = [
					'node_id' => $share['file_source'],
					'node_path' => $share['file_target'],
				];
			}
		}

		return $best;
	}

	/**
	 * propagate notes to the recipients
	 *
	 * @param IShare $share
	 * @throws NotFoundException
	 */
	private function propagateNote(IShare $share) {
		if ($share->getShareType() === IShare::TYPE_USER) {
			$user = $this->userManager->get($share->getSharedWith());
			$this->sendNote([$user], $share);
		} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
			$group = $this->groupManager->get($share->getSharedWith());
			$groupMembers = $group->getUsers();
			$this->sendNote($groupMembers, $share);
		}
	}

	public function sendMailNotification(IShare $share): bool {
		try {
			// Check user
			$user = $this->userManager->get($share->getSharedWith());
			if ($user === null) {
				$this->logger->debug('Share notification not sent to ' . $share->getSharedWith() . ' because user could not be found.', ['app' => 'share']);
				return false;
			}

			// Handle user shares
			if ($share->getShareType() === IShare::TYPE_USER) {
				// Check email address
				$emailAddress = $user->getEMailAddress();
				if ($emailAddress === null || $emailAddress === '') {
					$this->logger->debug('Share notification not sent to ' . $share->getSharedWith() . ' because email address is not set.', ['app' => 'share']);
					return false;
				}

				$userLang = $this->l10nFactory->getUserLanguage($user);
				$l = $this->l10nFactory->get('lib', $userLang);
				$this->sendUserShareMail(
					$l,
					$share->getNode()->getName(),
					$this->urlGenerator->linkToRouteAbsolute('files_sharing.Accept.accept', ['shareId' => $share->getFullId()]),
					$share->getSharedBy(),
					$emailAddress,
					$share->getExpirationDate(),
					$share->getNote()
				);
				$this->logger->debug('Sent share notification to ' . $emailAddress . ' for share with ID ' . $share->getId() . '.', ['app' => 'share']);
				return true;
			}
		} catch (\Exception $e) {
			$this->logger->error('Share notification mail could not be sent.', ['exception' => $e]);
		}

		return false;
	}

	/**
	 * Send mail notifications for the user share type
	 *
	 * @param IL10N $l Language of the recipient
	 * @param string $filename file/folder name
	 * @param string $link link to the file/folder
	 * @param string $initiator user ID of share sender
	 * @param string $shareWith email address of share receiver
	 * @param \DateTime|null $expiration
	 * @param string $note
	 * @throws \Exception
	 */
	protected function sendUserShareMail(
		IL10N $l,
		$filename,
		$link,
		$initiator,
		$shareWith,
		?\DateTime $expiration = null,
		$note = '') {
		$initiatorUser = $this->userManager->get($initiator);
		$initiatorDisplayName = ($initiatorUser instanceof IUser) ? $initiatorUser->getDisplayName() : $initiator;

		$message = $this->mailer->createMessage();

		$emailTemplate = $this->mailer->createEMailTemplate('files_sharing.RecipientNotification', [
			'filename' => $filename,
			'link' => $link,
			'initiator' => $initiatorDisplayName,
			'expiration' => $expiration,
			'shareWith' => $shareWith,
		]);

		$emailTemplate->setSubject($l->t('%1$s shared %2$s with you', [$initiatorDisplayName, $filename]));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($l->t('%1$s shared %2$s with you', [$initiatorDisplayName, $filename]), false);

		if ($note !== '') {
			$emailTemplate->addBodyText(htmlspecialchars($note), $note);
		}

		$emailTemplate->addBodyButton(
			$l->t('Open %s', [$filename]),
			$link
		);

		$message->setTo([$shareWith]);

		// The "From" contains the sharers name
		$instanceName = $this->defaults->getName();
		$senderName = $l->t(
			'%1$s via %2$s',
			[
				$initiatorDisplayName,
				$instanceName,
			]
		);
		$message->setFrom([Util::getDefaultEmailAddress('noreply') => $senderName]);

		// The "Reply-To" is set to the sharer if an mail address is configured
		// also the default footer contains a "Do not reply" which needs to be adjusted.
		if ($initiatorUser) {
			$initiatorEmail = $initiatorUser->getEMailAddress();
			if ($initiatorEmail !== null) {
				$message->setReplyTo([$initiatorEmail => $initiatorDisplayName]);
				$emailTemplate->addFooter($instanceName . ($this->defaults->getSlogan() !== '' ? ' - ' . $this->defaults->getSlogan() : ''));
			} else {
				$emailTemplate->addFooter();
			}
		} else {
			$emailTemplate->addFooter();
		}

		$message->useTemplate($emailTemplate);
		$failedRecipients = $this->mailer->send($message);
		if (!empty($failedRecipients)) {
			$this->logger->error('Share notification mail could not be sent to: ' . implode(', ', $failedRecipients));
			return;
		}
	}

	/**
	 * send note by mail
	 *
	 * @param array $recipients
	 * @param IShare $share
	 * @throws NotFoundException
	 */
	private function sendNote(array $recipients, IShare $share) {
		$toListByLanguage = [];

		foreach ($recipients as $recipient) {
			/** @var IUser $recipient */
			$email = $recipient->getEMailAddress();
			if ($email) {
				$language = $this->l10nFactory->getUserLanguage($recipient);
				if (!isset($toListByLanguage[$language])) {
					$toListByLanguage[$language] = [];
				}
				$toListByLanguage[$language][$email] = $recipient->getDisplayName();
			}
		}

		if (empty($toListByLanguage)) {
			return;
		}

		foreach ($toListByLanguage as $l10n => $toList) {
			$filename = $share->getNode()->getName();
			$initiator = $share->getSharedBy();
			$note = $share->getNote();

			$l = $this->l10nFactory->get('lib', $l10n);

			$initiatorUser = $this->userManager->get($initiator);
			$initiatorDisplayName = ($initiatorUser instanceof IUser) ? $initiatorUser->getDisplayName() : $initiator;
			$initiatorEmailAddress = ($initiatorUser instanceof IUser) ? $initiatorUser->getEMailAddress() : null;
			$plainHeading = $l->t('%1$s shared %2$s with you and wants to add:', [$initiatorDisplayName, $filename]);
			$htmlHeading = $l->t('%1$s shared %2$s with you and wants to add', [$initiatorDisplayName, $filename]);
			$message = $this->mailer->createMessage();

			$emailTemplate = $this->mailer->createEMailTemplate('defaultShareProvider.sendNote');

			$emailTemplate->setSubject($l->t('%s added a note to a file shared with you', [$initiatorDisplayName]));
			$emailTemplate->addHeader();
			$emailTemplate->addHeading($htmlHeading, $plainHeading);
			$emailTemplate->addBodyText(htmlspecialchars($note), $note);

			$link = $this->urlGenerator->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $share->getNode()->getId()]);
			$emailTemplate->addBodyButton(
				$l->t('Open %s', [$filename]),
				$link
			);


			// The "From" contains the sharers name
			$instanceName = $this->defaults->getName();
			$senderName = $l->t(
				'%1$s via %2$s',
				[
					$initiatorDisplayName,
					$instanceName
				]
			);
			$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $senderName]);
			if ($initiatorEmailAddress !== null) {
				$message->setReplyTo([$initiatorEmailAddress => $initiatorDisplayName]);
				$emailTemplate->addFooter($instanceName . ' - ' . $this->defaults->getSlogan());
			} else {
				$emailTemplate->addFooter();
			}

			if (count($toList) === 1) {
				$message->setTo($toList);
			} else {
				$message->setTo([]);
				$message->setBcc($toList);
			}
			$message->useTemplate($emailTemplate);
			$this->mailer->send($message);
		}
	}

	public function getAllShares(): iterable {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->select('*')
			->from('share')
			->where($qb->expr()->in('share_type', $qb->createNamedParameter([IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_LINK], IQueryBuilder::PARAM_INT_ARRAY)));

		$cursor = $qb->executeQuery();
		while ($data = $cursor->fetch()) {
			try {
				$share = $this->createShare($data);
			} catch (InvalidShare $e) {
				continue;
			}

			yield $share;
		}
		$cursor->closeCursor();
	}

	/**
	 * Load from database format (JSON string) to IAttributes
	 *
	 * @return IShare the modified share
	 */
	protected function updateShareAttributes(IShare $share, ?string $data): IShare {
		if ($data !== null && $data !== '') {
			$attributes = new ShareAttributes();
			$compressedAttributes = \json_decode($data, true);
			if ($compressedAttributes === false || $compressedAttributes === null) {
				return $share;
			}
			foreach ($compressedAttributes as $compressedAttribute) {
				$attributes->setAttribute(
					$compressedAttribute[0],
					$compressedAttribute[1],
					$compressedAttribute[2]
				);
			}
			$share->setAttributes($attributes);
		}

		return $share;
	}

	/**
	 * Format IAttributes to database format (JSON string)
	 */
	protected function formatShareAttributes(?IAttributes $attributes): ?string {
		if ($attributes === null || empty($attributes->toArray())) {
			return null;
		}

		$compressedAttributes = [];
		foreach ($attributes->toArray() as $attribute) {
			$compressedAttributes[] = [
				0 => $attribute['scope'],
				1 => $attribute['key'],
				2 => $attribute['value']
			];
		}
		return \json_encode($compressedAttributes);
	}
}

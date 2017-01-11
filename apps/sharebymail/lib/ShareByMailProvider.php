<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\ShareByMail;

use OC\HintException;
use OC\Share20\Exception\InvalidShare;
use OCP\Activity\IManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;
use OC\Share20\Share;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IShare;
use OCP\Share\IShareProvider;
use OCP\Template;

/**
 * Class ShareByMail
 *
 * @package OCA\ShareByMail
 */
class ShareByMailProvider implements IShareProvider {

	/** @var  IDBConnection */
	private $dbConnection;

	/** @var ILogger */
	private $logger;

	/** @var ISecureRandom */
	private $secureRandom;

	/** @var IUserManager */
	private $userManager;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IL10N */
	private $l;

	/** @var IMailer */
	private $mailer;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IManager  */
	private $activityManager;

	/**
	 * Return the identifier of this provider.
	 *
	 * @return string Containing only [a-zA-Z0-9]
	 */
	public function identifier() {
		return 'ocShareByMail';
	}

	/**
	 * DefaultShareProvider constructor.
	 *
	 * @param IDBConnection $connection
	 * @param ISecureRandom $secureRandom
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 * @param IL10N $l
	 * @param ILogger $logger
	 * @param IMailer $mailer
	 * @param IURLGenerator $urlGenerator
	 * @param IManager $activityManager
	 */
	public function __construct(
		IDBConnection $connection,
		ISecureRandom $secureRandom,
		IUserManager $userManager,
		IRootFolder $rootFolder,
		IL10N $l,
		ILogger $logger,
		IMailer $mailer,
		IURLGenerator $urlGenerator,
		IManager $activityManager
	) {
		$this->dbConnection = $connection;
		$this->secureRandom = $secureRandom;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->l = $l;
		$this->logger = $logger;
		$this->mailer = $mailer;
		$this->urlGenerator = $urlGenerator;
		$this->activityManager = $activityManager;
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

		$shareWith = $share->getSharedWith();
		/*
		 * Check if file is not already shared with the remote user
		 */
		$alreadyShared = $this->getSharedWith($shareWith, \OCP\Share::SHARE_TYPE_EMAIL, $share->getNode(), 1, 0);
		if (!empty($alreadyShared)) {
			$message = 'Sharing %s failed, this item is already shared with %s';
			$message_t = $this->l->t('Sharing %s failed, this item is already shared with %s', array($share->getNode()->getName(), $shareWith));
			$this->logger->debug(sprintf($message, $share->getNode()->getName(), $shareWith), ['app' => 'Federated File Sharing']);
			throw new \Exception($message_t);
		}

		$shareId = $this->createMailShare($share);
		$this->createActivity($share);
		$data = $this->getRawShare($shareId);
		return $this->createShareObject($data);

	}

	/**
	 * create activity if a file/folder was shared by mail
	 *
	 * @param IShare $share
	 */
	protected function createActivity(IShare $share) {

		$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());

		$this->publishActivity(
			Activity::SUBJECT_SHARED_EMAIL_SELF,
			[$userFolder->getRelativePath($share->getNode()->getPath()), $share->getSharedWith()],
			$share->getSharedBy(),
			$share->getNode()->getId(),
			$userFolder->getRelativePath($share->getNode()->getPath())
		);

		if ($share->getShareOwner() !== $share->getSharedBy()) {
			$ownerFolder = $this->rootFolder->getUserFolder($share->getShareOwner());
			$fileId = $share->getNode()->getId();
			$nodes = $ownerFolder->getById($fileId);
			$ownerPath = $nodes[0]->getPath();
			$this->publishActivity(
				Activity::SUBJECT_SHARED_EMAIL_BY,
				[$ownerFolder->getRelativePath($ownerPath), $share->getSharedWith(), $share->getSharedBy()],
				$share->getShareOwner(),
				$fileId,
				$ownerFolder->getRelativePath($ownerPath)
			);
		}

	}

	/**
	 * publish activity if a file/folder was shared by mail
	 *
	 * @param $subject
	 * @param $parameters
	 * @param $affectedUser
	 * @param $fileId
	 * @param $filePath
	 */
	protected function publishActivity($subject, $parameters, $affectedUser, $fileId, $filePath) {
		$event = $this->activityManager->generateEvent();
		$event->setApp('sharebymail')
			->setType('shared')
			->setSubject($subject, $parameters)
			->setAffectedUser($affectedUser)
			->setObject('files', $fileId, $filePath);
		$this->activityManager->publish($event);

	}

	/**
	 * @param IShare $share
	 * @return int
	 * @throws \Exception
	 */
	protected function createMailShare(IShare $share) {
		$share->setToken($this->generateToken());
		$shareId = $this->addShareToDB(
			$share->getNodeId(),
			$share->getNodeType(),
			$share->getSharedWith(),
			$share->getSharedBy(),
			$share->getShareOwner(),
			$share->getPermissions(),
			$share->getToken()
		);

		try {
			$link = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare',
				['token' => $share->getToken()]);
			$this->sendMailNotification($share->getNode()->getName(),
				$link,
				$share->getShareOwner(),
				$share->getSharedBy(), $share->getSharedWith());
		} catch (HintException $hintException) {
			$this->logger->error('Failed to send share by mail: ' . $hintException->getMessage());
			$this->removeShareFromTable($shareId);
			throw $hintException;
		} catch (\Exception $e) {
			$this->logger->error('Failed to send share by mail: ' . $e->getMessage());
			$this->removeShareFromTable($shareId);
			throw new HintException('Failed to send share by mail',
				$this->l->t('Failed to send share by E-mail'));
		}

		return $shareId;

	}

	protected function sendMailNotification($filename, $link, $owner, $initiator, $shareWith) {
		$ownerUser = $this->userManager->get($owner);
		$initiatorUser = $this->userManager->get($initiator);
		$ownerDisplayName = ($ownerUser instanceof IUser) ? $ownerUser->getDisplayName() : $owner;
		$initiatorDisplayName = ($initiatorUser instanceof IUser) ? $initiatorUser->getDisplayName() : $initiator;
		if ($owner === $initiator) {
			$subject = (string)$this->l->t('%s shared »%s« with you', array($ownerDisplayName, $filename));
		} else {
			$subject = (string)$this->l->t('%s shared »%s« with you on behalf of %s', array($ownerDisplayName, $filename, $initiatorDisplayName));
		}

		$message = $this->mailer->createMessage();
		$htmlBody = $this->createMailBody('mail', $filename, $link, $ownerDisplayName, $initiatorDisplayName);
		$textBody = $this->createMailBody('altmail', $filename, $link, $ownerDisplayName, $initiatorDisplayName);
		$message->setTo([$shareWith]);
		$message->setSubject($subject);
		$message->setBody($textBody, 'text/plain');
		$message->setHtmlBody($htmlBody);
		$this->mailer->send($message);

	}

	/**
	 * create mail body
	 *
	 * @param $filename
	 * @param $link
	 * @param $owner
	 * @param $initiator
	 * @return string plain text mail
	 * @throws HintException
	 */
	protected function createMailBody($template, $filename, $link, $owner, $initiator) {

		$mailBodyTemplate = new Template('sharebymail', $template, '');
		$mailBodyTemplate->assign ('filename', $filename);
		$mailBodyTemplate->assign ('link', $link);
		$mailBodyTemplate->assign ('owner', $owner);
		$mailBodyTemplate->assign ('initiator', $initiator);
		$mailBodyTemplate->assign ('onBehalfOf', $initiator !== $owner);
		$mailBody = $mailBodyTemplate->fetchPage();

		if (is_string($mailBody)) {
			return $mailBody;
		}

		throw new HintException('Failed to create the E-mail',
			$this->l->t('Failed to create the E-mail'));
	}

	/**
	 * generate share token
	 *
	 * @return string
	 */
	protected function generateToken() {
		$token = $this->secureRandom->generate(
			15, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_DIGITS);
		return $token;
	}

	/**
	 * Get all children of this share
	 *
	 * @param IShare $parent
	 * @return IShare[]
	 */
	public function getChildren(IShare $parent) {
		$children = [];

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('parent', $qb->createNamedParameter($parent->getId())))
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_EMAIL)))
			->orderBy('id');

		$cursor = $qb->execute();
		while($data = $cursor->fetch()) {
			$children[] = $this->createShareObject($data);
		}
		$cursor->closeCursor();

		return $children;
	}

	/**
	 * add share to the database and return the ID
	 *
	 * @param int $itemSource
	 * @param string $itemType
	 * @param string $shareWith
	 * @param string $sharedBy
	 * @param string $uidOwner
	 * @param int $permissions
	 * @param string $token
	 * @return int
	 */
	protected function addShareToDB($itemSource, $itemType, $shareWith, $sharedBy, $uidOwner, $permissions, $token) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert('share')
			->setValue('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_EMAIL))
			->setValue('item_type', $qb->createNamedParameter($itemType))
			->setValue('item_source', $qb->createNamedParameter($itemSource))
			->setValue('file_source', $qb->createNamedParameter($itemSource))
			->setValue('share_with', $qb->createNamedParameter($shareWith))
			->setValue('uid_owner', $qb->createNamedParameter($uidOwner))
			->setValue('uid_initiator', $qb->createNamedParameter($sharedBy))
			->setValue('permissions', $qb->createNamedParameter($permissions))
			->setValue('token', $qb->createNamedParameter($token))
			->setValue('stime', $qb->createNamedParameter(time()));

		/*
		 * Added to fix https://github.com/owncloud/core/issues/22215
		 * Can be removed once we get rid of ajax/share.php
		 */
		$qb->setValue('file_target', $qb->createNamedParameter(''));

		$qb->execute();
		$id = $qb->getLastInsertId();

		return (int)$id;
	}

	/**
	 * Update a share
	 *
	 * @param IShare $share
	 * @return IShare The share object
	 */
	public function update(IShare $share) {
		/*
		 * We allow updating the permissions of mail shares
		 */
		$qb = $this->dbConnection->getQueryBuilder();
			$qb->update('share')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
				->set('permissions', $qb->createNamedParameter($share->getPermissions()))
				->set('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
				->set('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
				->execute();

		return $share;
	}

	/**
	 * @inheritdoc
	 */
	public function move(IShare $share, $recipient) {
		/**
		 * nothing to do here, mail shares are only outgoing shares
		 */
		return $share;
	}

	/**
	 * Delete a share (owner unShares the file)
	 *
	 * @param IShare $share
	 */
	public function delete(IShare $share) {
		$this->removeShareFromTable($share->getId());
	}

	/**
	 * @inheritdoc
	 */
	public function deleteFromSelf(IShare $share, $recipient) {
		// nothing to do here, mail shares are only outgoing shares
		return;
	}

	/**
	 * @inheritdoc
	 */
	public function getSharesBy($userId, $shareType, $node, $reshares, $limit, $offset) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')
			->from('share');

		$qb->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_EMAIL)));

		/**
		 * Reshares for this user are shares where they are the owner.
		 */
		if ($reshares === false) {
			//Special case for old shares created via the web UI
			$or1 = $qb->expr()->andX(
				$qb->expr()->eq('uid_owner', $qb->createNamedParameter($userId)),
				$qb->expr()->isNull('uid_initiator')
			);

			$qb->andWhere(
				$qb->expr()->orX(
					$qb->expr()->eq('uid_initiator', $qb->createNamedParameter($userId)),
					$or1
				)
			);
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
			$shares[] = $this->createShareObject($data);
		}
		$cursor->closeCursor();

		return $shares;
	}

	/**
	 * @inheritdoc
	 */
	public function getShareById($id, $recipientId = null) {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_EMAIL)));

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new ShareNotFound();
		}

		try {
			$share = $this->createShareObject($data);
		} catch (InvalidShare $e) {
			throw new ShareNotFound();
		}

		return $share;
	}

	/**
	 * Get shares for a given path
	 *
	 * @param \OCP\Files\Node $path
	 * @return IShare[]
	 */
	public function getSharesByPath(Node $path) {
		$qb = $this->dbConnection->getQueryBuilder();

		$cursor = $qb->select('*')
			->from('share')
			->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($path->getId())))
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_EMAIL)))
			->execute();

		$shares = [];
		while($data = $cursor->fetch()) {
			$shares[] = $this->createShareObject($data);
		}
		$cursor->closeCursor();

		return $shares;
	}

	/**
	 * @inheritdoc
	 */
	public function getSharedWith($userId, $shareType, $node, $limit, $offset) {
		/** @var IShare[] $shares */
		$shares = [];

		//Get shares directly with this user
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')
			->from('share');

		// Order by id
		$qb->orderBy('id');

		// Set limit and offset
		if ($limit !== -1) {
			$qb->setMaxResults($limit);
		}
		$qb->setFirstResult($offset);

		$qb->where($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_EMAIL)));
		$qb->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($userId)));

		// Filter by node if provided
		if ($node !== null) {
			$qb->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($node->getId())));
		}

		$cursor = $qb->execute();

		while($data = $cursor->fetch()) {
			$shares[] = $this->createShareObject($data);
		}
		$cursor->closeCursor();


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
		$qb = $this->dbConnection->getQueryBuilder();

		$cursor = $qb->select('*')
			->from('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_EMAIL)))
			->andWhere($qb->expr()->eq('token', $qb->createNamedParameter($token)))
			->execute();

		$data = $cursor->fetch();

		if ($data === false) {
			throw new ShareNotFound('Share not found', $this->l->t('Could not find share'));
		}

		try {
			$share = $this->createShareObject($data);
		} catch (InvalidShare $e) {
			throw new ShareNotFound('Share not found', $this->l->t('Could not find share'));
		}

		return $share;
	}

	/**
	 * remove share from table
	 *
	 * @param string $shareId
	 */
	protected function removeShareFromTable($shareId) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($shareId)));
		$qb->execute();
	}

	/**
	 * Create a share object from an database row
	 *
	 * @param array $data
	 * @return IShare
	 * @throws InvalidShare
	 * @throws ShareNotFound
	 */
	protected function createShareObject($data) {

		$share = new Share($this->rootFolder, $this->userManager);
		$share->setId((int)$data['id'])
			->setShareType((int)$data['share_type'])
			->setPermissions((int)$data['permissions'])
			->setTarget($data['file_target'])
			->setMailSend((bool)$data['mail_send'])
			->setToken($data['token']);

		$shareTime = new \DateTime();
		$shareTime->setTimestamp((int)$data['stime']);
		$share->setShareTime($shareTime);
		$share->setSharedWith($data['share_with']);

		if ($data['uid_initiator'] !== null) {
			$share->setShareOwner($data['uid_owner']);
			$share->setSharedBy($data['uid_initiator']);
		} else {
			//OLD SHARE
			$share->setSharedBy($data['uid_owner']);
			$path = $this->getNode($share->getSharedBy(), (int)$data['file_source']);

			$owner = $path->getOwner();
			$share->setShareOwner($owner->getUID());
		}

		$share->setNodeId((int)$data['file_source']);
		$share->setNodeType($data['item_type']);

		$share->setProviderId($this->identifier());

		return $share;
	}

	/**
	 * Get the node with file $id for $user
	 *
	 * @param string $userId
	 * @param int $id
	 * @return \OCP\Files\File|\OCP\Files\Folder
	 * @throws InvalidShare
	 */
	private function getNode($userId, $id) {
		try {
			$userFolder = $this->rootFolder->getUserFolder($userId);
		} catch (NotFoundException $e) {
			throw new InvalidShare();
		}

		$nodes = $userFolder->getById($id);

		if (empty($nodes)) {
			throw new InvalidShare();
		}

		return $nodes[0];
	}

	/**
	 * A user is deleted from the system
	 * So clean up the relevant shares.
	 *
	 * @param string $uid
	 * @param int $shareType
	 */
	public function userDeleted($uid, $shareType) {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->delete('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_EMAIL)))
			->andWhere($qb->expr()->eq('uid_owner', $qb->createNamedParameter($uid)))
			->execute();
	}

	/**
	 * This provider does not support group shares
	 *
	 * @param string $gid
	 */
	public function groupDeleted($gid) {
		return;
	}

	/**
	 * This provider does not support group shares
	 *
	 * @param string $uid
	 * @param string $gid
	 */
	public function userDeletedFromGroup($uid, $gid) {
		return;
	}

	/**
	 * get database row of a give share
	 *
	 * @param $id
	 * @return array
	 * @throws ShareNotFound
	 */
	protected function getRawShare($id) {

		// Now fetch the inserted share and create a complete share object
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new ShareNotFound;
		}

		return $data;
	}

	public function getSharesInFolder($userId, Folder $node, $reshares) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')
			->from('share', 's')
			->andWhere($qb->expr()->orX(
				$qb->expr()->eq('item_type', $qb->createNamedParameter('file')),
				$qb->expr()->eq('item_type', $qb->createNamedParameter('folder'))
			))
			->andWhere(
				$qb->expr()->eq('share_type', $qb->createNamedParameter(\OCP\Share::SHARE_TYPE_EMAIL))
			);

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

		$qb->innerJoin('s', 'filecache' ,'f', 's.file_source = f.fileid');
		$qb->andWhere($qb->expr()->eq('f.parent', $qb->createNamedParameter($node->getId())));

		$qb->orderBy('id');

		$cursor = $qb->execute();
		$shares = [];
		while ($data = $cursor->fetch()) {
			$shares[$data['fileid']][] = $this->createShareObject($data);
		}
		$cursor->closeCursor();

		return $shares;
	}

}

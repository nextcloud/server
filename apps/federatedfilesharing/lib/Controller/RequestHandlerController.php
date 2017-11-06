<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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

namespace OCA\FederatedFileSharing\Controller;

use OCA\Files_Sharing\Activity\Providers\RemoteShares;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\Notifications;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Constants;
use OCP\Federation\ICloudIdManager;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Share;
use OCP\Share\IShare;

class RequestHandlerController extends OCSController {

	/** @var FederatedShareProvider */
	private $federatedShareProvider;

	/** @var IDBConnection */
	private $connection;

	/** @var Share\IManager */
	private $shareManager;

	/** @var Notifications */
	private $notifications;

	/** @var AddressHandler */
	private $addressHandler;

	/** @var  IUserManager */
	private $userManager;

	/** @var string */
	private $shareTable = 'share';

	/** @var ICloudIdManager */
	private $cloudIdManager;

	/**
	 * Server2Server constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param FederatedShareProvider $federatedShareProvider
	 * @param IDBConnection $connection
	 * @param Share\IManager $shareManager
	 * @param Notifications $notifications
	 * @param AddressHandler $addressHandler
	 * @param IUserManager $userManager
	 * @param ICloudIdManager $cloudIdManager
	 */
	public function __construct($appName,
								IRequest $request,
								FederatedShareProvider $federatedShareProvider,
								IDBConnection $connection,
								Share\IManager $shareManager,
								Notifications $notifications,
								AddressHandler $addressHandler,
								IUserManager $userManager,
								ICloudIdManager $cloudIdManager
	) {
		parent::__construct($appName, $request);

		$this->federatedShareProvider = $federatedShareProvider;
		$this->connection = $connection;
		$this->shareManager = $shareManager;
		$this->notifications = $notifications;
		$this->addressHandler = $addressHandler;
		$this->userManager = $userManager;
		$this->cloudIdManager = $cloudIdManager;
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * create a new share
	 *
	 * @return Http\DataResponse
	 * @throws OCSException
	 */
	public function createShare() {

		if (!$this->isS2SEnabled(true)) {
			throw new OCSException('Server does not support federated cloud sharing', 503);
		}

		$remote = isset($_POST['remote']) ? $_POST['remote'] : null;
		$token = isset($_POST['token']) ? $_POST['token'] : null;
		$name = isset($_POST['name']) ? $_POST['name'] : null;
		$owner = isset($_POST['owner']) ? $_POST['owner'] : null;
		$sharedBy = isset($_POST['sharedBy']) ? $_POST['sharedBy'] : null;
		$shareWith = isset($_POST['shareWith']) ? $_POST['shareWith'] : null;
		$remoteId = isset($_POST['remoteId']) ? (int)$_POST['remoteId'] : null;
		$sharedByFederatedId = isset($_POST['sharedByFederatedId']) ? $_POST['sharedByFederatedId'] : null;
		$ownerFederatedId = isset($_POST['ownerFederatedId']) ? $_POST['ownerFederatedId'] : null;

		if ($remote && $token && $name && $owner && $remoteId && $shareWith) {

			if (!\OCP\Util::isValidFileName($name)) {
				throw new OCSException('The mountpoint name contains invalid characters.', 400);
			}

			// FIXME this should be a method in the user management instead
			\OCP\Util::writeLog('files_sharing', 'shareWith before, ' . $shareWith, \OCP\Util::DEBUG);
			\OCP\Util::emitHook(
				'\OCA\Files_Sharing\API\Server2Server',
				'preLoginNameUsedAsUserName',
				array('uid' => &$shareWith)
			);
			\OCP\Util::writeLog('files_sharing', 'shareWith after, ' . $shareWith, \OCP\Util::DEBUG);

			if (!\OCP\User::userExists($shareWith)) {
				throw new OCSException('User does not exists', 400);
			}

			\OC_Util::setupFS($shareWith);

			$externalManager = new \OCA\Files_Sharing\External\Manager(
					\OC::$server->getDatabaseConnection(),
					\OC\Files\Filesystem::getMountManager(),
					\OC\Files\Filesystem::getLoader(),
					\OC::$server->getHTTPClientService(),
					\OC::$server->getNotificationManager(),
					\OC::$server->query(\OCP\OCS\IDiscoveryService::class),
					$shareWith
				);

			try {
				$externalManager->addShare($remote, $token, '', $name, $owner, false, $shareWith, $remoteId);
				$shareId = \OC::$server->getDatabaseConnection()->lastInsertId('*PREFIX*share_external');

				if ($ownerFederatedId === null) {
					$ownerFederatedId = $this->cloudIdManager->getCloudId($owner, $this->cleanupRemote($remote))->getId();
				}
				// if the owner of the share and the initiator are the same user
				// we also complete the federated share ID for the initiator
				if ($sharedByFederatedId === null && $owner === $sharedBy) {
					$sharedByFederatedId = $ownerFederatedId;
				}

				$event = \OC::$server->getActivityManager()->generateEvent();
				$event->setApp('files_sharing')
					->setType('remote_share')
					->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_RECEIVED, [$ownerFederatedId, trim($name, '/')])
					->setAffectedUser($shareWith)
					->setObject('remote_share', (int)$shareId, $name);
				\OC::$server->getActivityManager()->publish($event);

				$urlGenerator = \OC::$server->getURLGenerator();

				$notificationManager = \OC::$server->getNotificationManager();
				$notification = $notificationManager->createNotification();
				$notification->setApp('files_sharing')
					->setUser($shareWith)
					->setDateTime(new \DateTime())
					->setObject('remote_share', $shareId)
					->setSubject('remote_share', [$ownerFederatedId, $sharedByFederatedId, trim($name, '/')]);

				$declineAction = $notification->createAction();
				$declineAction->setLabel('decline')
					->setLink($urlGenerator->getAbsoluteURL($urlGenerator->linkTo('', 'ocs/v2.php/apps/files_sharing/api/v1/remote_shares/pending/' . $shareId)), 'DELETE');
				$notification->addAction($declineAction);

				$acceptAction = $notification->createAction();
				$acceptAction->setLabel('accept')
					->setLink($urlGenerator->getAbsoluteURL($urlGenerator->linkTo('', 'ocs/v2.php/apps/files_sharing/api/v1/remote_shares/pending/' . $shareId)), 'POST');
				$notification->addAction($acceptAction);

				$notificationManager->notify($notification);

				return new Http\DataResponse();
			} catch (\Exception $e) {
				\OCP\Util::writeLog('files_sharing', 'server can not add remote share, ' . $e->getMessage(), \OCP\Util::ERROR);
				throw new OCSException('internal server error, was not able to add share from ' . $remote, 500);
			}
		}

		throw new OCSException('server can not add remote share, missing parameter', 400);
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * create re-share on behalf of another user
	 *
	 * @param int $id
	 * @return Http\DataResponse
	 * @throws OCSBadRequestException
	 * @throws OCSForbiddenException
	 * @throws OCSNotFoundException
	 */
	public function reShare($id) {

		$token = $this->request->getParam('token', null);
		$shareWith = $this->request->getParam('shareWith', null);
		$permission = (int)$this->request->getParam('permission', null);
		$remoteId = (int)$this->request->getParam('remoteId', null);

		if ($id === null ||
			$token === null ||
			$shareWith === null ||
			$permission === null ||
			$remoteId === null
		) {
			throw new OCSBadRequestException();
		}

		try {
			$share = $this->federatedShareProvider->getShareById($id);
		} catch (Share\Exceptions\ShareNotFound $e) {
			throw new OCSNotFoundException();
		}

		// don't allow to share a file back to the owner
		list($user, $remote) = $this->addressHandler->splitUserRemote($shareWith);
		$owner = $share->getShareOwner();
		$currentServer = $this->addressHandler->generateRemoteURL();
		if ($this->addressHandler->compareAddresses($user, $remote, $owner, $currentServer)) {
			throw new OCSForbiddenException();
		}

		if ($this->verifyShare($share, $token)) {

			// check if re-sharing is allowed
			if ($share->getPermissions() | ~Constants::PERMISSION_SHARE) {
				$share->setPermissions($share->getPermissions() & $permission);
				// the recipient of the initial share is now the initiator for the re-share
				$share->setSharedBy($share->getSharedWith());
				$share->setSharedWith($shareWith);
				try {
					$result = $this->federatedShareProvider->create($share);
					$this->federatedShareProvider->storeRemoteId((int)$result->getId(), $remoteId);
					return new Http\DataResponse([
						'token' => $result->getToken(),
						'remoteId' => $result->getId()
					]);
				} catch (\Exception $e) {
					throw new OCSBadRequestException();
				}
			} else {
				throw new OCSForbiddenException();
			}
		}
		throw new OCSBadRequestException();
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * accept server-to-server share
	 *
	 * @param int $id
	 * @return Http\DataResponse
	 * @throws OCSException
	 */
	public function acceptShare($id) {

		if (!$this->isS2SEnabled()) {
			throw new OCSException('Server does not support federated cloud sharing', 503);
		}

		$token = isset($_POST['token']) ? $_POST['token'] : null;

		try {
			$share = $this->federatedShareProvider->getShareById($id);
		} catch (Share\Exceptions\ShareNotFound $e) {
			return new Http\DataResponse();
		}

		if ($this->verifyShare($share, $token)) {
			$this->executeAcceptShare($share);
			if ($share->getShareOwner() !== $share->getSharedBy()) {
				list(, $remote) = $this->addressHandler->splitUserRemote($share->getSharedBy());
				$remoteId = $this->federatedShareProvider->getRemoteId($share);
				$this->notifications->sendAcceptShare($remote, $remoteId, $share->getToken());
			}
		}

		return new Http\DataResponse();
	}

	protected function executeAcceptShare(Share\IShare $share) {
		$fileId = (int) $share->getNode()->getId();
		list($file, $link) = $this->getFile($this->getCorrectUid($share), $fileId);

		$event = \OC::$server->getActivityManager()->generateEvent();
		$event->setApp('files_sharing')
			->setType('remote_share')
			->setAffectedUser($this->getCorrectUid($share))
			->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_ACCEPTED, [$share->getSharedWith(), [$fileId => $file]])
			->setObject('files', $fileId, $file)
			->setLink($link);
		\OC::$server->getActivityManager()->publish($event);
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * decline server-to-server share
	 *
	 * @param int $id
	 * @return Http\DataResponse
	 * @throws OCSException
	 */
	public function declineShare($id) {

		if (!$this->isS2SEnabled()) {
			throw new OCSException('Server does not support federated cloud sharing', 503);
		}

		$token = isset($_POST['token']) ? $_POST['token'] : null;

		try {
			$share = $this->federatedShareProvider->getShareById($id);
		} catch (Share\Exceptions\ShareNotFound $e) {
			return new Http\DataResponse();
		}

		if ($this->verifyShare($share, $token)) {
			if ($share->getShareOwner() !== $share->getSharedBy()) {
				list(, $remote) = $this->addressHandler->splitUserRemote($share->getSharedBy());
				$remoteId = $this->federatedShareProvider->getRemoteId($share);
				$this->notifications->sendDeclineShare($remote, $remoteId, $share->getToken());
			}
			$this->executeDeclineShare($share);
		}

		return new Http\DataResponse();
	}

	/**
	 * delete declined share and create a activity
	 *
	 * @param Share\IShare $share
	 */
	protected function executeDeclineShare(Share\IShare $share) {
		$this->federatedShareProvider->removeShareFromTable($share);
		$fileId = (int) $share->getNode()->getId();
		list($file, $link) = $this->getFile($this->getCorrectUid($share), $fileId);

		$event = \OC::$server->getActivityManager()->generateEvent();
		$event->setApp('files_sharing')
			->setType('remote_share')
			->setAffectedUser($this->getCorrectUid($share))
			->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_DECLINED, [$share->getSharedWith(), [$fileId => $file]])
			->setObject('files', $fileId, $file)
			->setLink($link);
		\OC::$server->getActivityManager()->publish($event);

	}

	/**
	 * check if we are the initiator or the owner of a re-share and return the correct UID
	 *
	 * @param Share\IShare $share
	 * @return string
	 */
	protected function getCorrectUid(Share\IShare $share) {
		if ($this->userManager->userExists($share->getShareOwner())) {
			return $share->getShareOwner();
		}

		return $share->getSharedBy();
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * remove server-to-server share if it was unshared by the owner
	 *
	 * @param int $id
	 * @return Http\DataResponse
	 * @throws OCSException
	 */
	public function unshare($id) {

		if (!$this->isS2SEnabled()) {
			throw new OCSException('Server does not support federated cloud sharing', 503);
		}

		$token = isset($_POST['token']) ? $_POST['token'] : null;

		$query = \OCP\DB::prepare('SELECT * FROM `*PREFIX*share_external` WHERE `remote_id` = ? AND `share_token` = ?');
		$query->execute(array($id, $token));
		$share = $query->fetchRow();

		if ($token && $id && !empty($share)) {

			$remote = $this->cleanupRemote($share['remote']);

			$owner = $this->cloudIdManager->getCloudId($share['owner'], $remote);
			$mountpoint = $share['mountpoint'];
			$user = $share['user'];

			$query = \OCP\DB::prepare('DELETE FROM `*PREFIX*share_external` WHERE `remote_id` = ? AND `share_token` = ?');
			$query->execute(array($id, $token));

			if ($share['accepted']) {
				$path = trim($mountpoint, '/');
			} else {
				$path = trim($share['name'], '/');
			}

			$notificationManager = \OC::$server->getNotificationManager();
			$notification = $notificationManager->createNotification();
			$notification->setApp('files_sharing')
				->setUser($share['user'])
				->setObject('remote_share', (int)$share['id']);
			$notificationManager->markProcessed($notification);

			$event = \OC::$server->getActivityManager()->generateEvent();
			$event->setApp('files_sharing')
				->setType('remote_share')
				->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_UNSHARED, [$owner->getId(), $path])
				->setAffectedUser($user)
				->setObject('remote_share', (int)$share['id'], $path);
			\OC::$server->getActivityManager()->publish($event);
		}

		return new Http\DataResponse();
	}

	private function cleanupRemote($remote) {
		$remote = substr($remote, strpos($remote, '://') + 3);

		return rtrim($remote, '/');
	}


	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * federated share was revoked, either by the owner or the re-sharer
	 *
	 * @param int $id
	 * @return Http\DataResponse
	 * @throws OCSBadRequestException
	 */
	public function revoke($id) {
		$token = $this->request->getParam('token');

		$share = $this->federatedShareProvider->getShareById($id);

		if ($this->verifyShare($share, $token)) {
			$this->federatedShareProvider->removeShareFromTable($share);
			return new Http\DataResponse();
		}

		throw new OCSBadRequestException();
	}

	/**
	 * get share
	 *
	 * @param int $id
	 * @param string $token
	 * @return array|bool
	 */
	protected function getShare($id, $token) {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')->from($this->shareTable)
			->where($query->expr()->eq('token', $query->createNamedParameter($token)))
			->andWhere($query->expr()->eq('share_type', $query->createNamedParameter(FederatedShareProvider::SHARE_TYPE_REMOTE)))
			->andWhere($query->expr()->eq('id', $query->createNamedParameter($id)));

		$result = $query->execute()->fetchAll();

		if (!empty($result) && isset($result[0])) {
			return $result[0];
		}

		return false;
	}

	/**
	 * get file
	 *
	 * @param string $user
	 * @param int $fileSource
	 * @return array with internal path of the file and a absolute link to it
	 */
	private function getFile($user, $fileSource) {
		\OC_Util::setupFS($user);

		try {
			$file = \OC\Files\Filesystem::getPath($fileSource);
		} catch (NotFoundException $e) {
			$file = null;
		}
		$args = \OC\Files\Filesystem::is_dir($file) ? array('dir' => $file) : array('dir' => dirname($file), 'scrollto' => $file);
		$link = \OCP\Util::linkToAbsolute('files', 'index.php', $args);

		return array($file, $link);

	}

	/**
	 * check if server-to-server sharing is enabled
	 *
	 * @param bool $incoming
	 * @return bool
	 */
	private function isS2SEnabled($incoming = false) {

		$result = \OCP\App::isEnabled('files_sharing');

		if ($incoming) {
			$result = $result && $this->federatedShareProvider->isIncomingServer2serverShareEnabled();
		} else {
			$result = $result && $this->federatedShareProvider->isOutgoingServer2serverShareEnabled();
		}

		return $result;
	}

	/**
	 * check if we got the right share
	 *
	 * @param Share\IShare $share
	 * @param string $token
	 * @return bool
	 */
	protected function verifyShare(Share\IShare $share, $token) {
		if (
			$share->getShareType() === FederatedShareProvider::SHARE_TYPE_REMOTE &&
			$share->getToken() === $token
		) {
			return true;
		}

		return false;
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * update share information to keep federated re-shares in sync
	 *
	 * @param int $id
	 * @return Http\DataResponse
	 * @throws OCSBadRequestException
	 */
	public function updatePermissions($id) {
		$token = $this->request->getParam('token', null);
		$permissions = $this->request->getParam('permissions', null);

		try {
			$share = $this->federatedShareProvider->getShareById($id);
		} catch (Share\Exceptions\ShareNotFound $e) {
			throw new OCSBadRequestException();
		}

		$validPermission = ctype_digit($permissions);
		$validToken = $this->verifyShare($share, $token);
		if ($validPermission && $validToken) {
			$this->updatePermissionsInDatabase($share, (int)$permissions);
		} else {
			throw new OCSBadRequestException();
		}

		return new Http\DataResponse();
	}

	/**
	 * update permissions in database
	 *
	 * @param IShare $share
	 * @param int $permissions
	 */
	protected function updatePermissionsInDatabase(IShare $share, $permissions) {
		$query = $this->connection->getQueryBuilder();
		$query->update('share')
			->where($query->expr()->eq('id', $query->createNamedParameter($share->getId())))
			->set('permissions', $query->createNamedParameter($permissions))
			->execute();
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * change the owner of a server-to-server share
	 *
	 * @param int $id
	 * @return Http\DataResponse
	 * @throws \InvalidArgumentException
	 * @throws OCSException
	 */
	public function move($id) {

		if (!$this->isS2SEnabled()) {
			throw new OCSException('Server does not support federated cloud sharing', 503);
		}

		$token = $this->request->getParam('token');
		$remote = $this->request->getParam('remote');
		$newRemoteId = $this->request->getParam('remote_id', $id);
		$cloudId = $this->cloudIdManager->resolveCloudId($remote);

		$qb = $this->connection->getQueryBuilder();
		$query = $qb->update('share_external')
			->set('remote', $qb->createNamedParameter($cloudId->getRemote()))
			->set('owner', $qb->createNamedParameter($cloudId->getUser()))
			->set('remote_id', $qb->createNamedParameter($newRemoteId))
			->where($qb->expr()->eq('remote_id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('share_token', $qb->createNamedParameter($token)));
		$affected = $query->execute();

		if ($affected > 0) {
			return new Http\DataResponse(['remote' => $cloudId->getRemote(), 'owner' => $cloudId->getUser()]);
		} else {
			throw new OCSBadRequestException('Share not found or token invalid');
		}
	}
}

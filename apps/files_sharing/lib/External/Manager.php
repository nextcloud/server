<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing\External;

use OC\Files\Filesystem;
use OC\Files\SetupManager;
use OC\User\NoUserException;
use OCA\FederatedFileSharing\Events\FederatedShareAddedEvent;
use OCA\Files_Sharing\Helper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Files\Events\InvalidateMountCacheEvent;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\IStorageFactory;
use OCP\Http\Client\IClientService;
use OCP\ICertificateManager;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Notification\IManager;
use OCP\OCS\IDiscoveryService;
use OCP\Share\IShare;
use OCP\Snowflake\IGenerator;
use Psr\Log\LoggerInterface;

class Manager {
	private ?IUser $user;

	public function __construct(
		private IDBConnection $connection,
		private \OC\Files\Mount\Manager $mountManager,
		private IStorageFactory $storageLoader,
		private IClientService $clientService,
		private IManager $notificationManager,
		private IDiscoveryService $discoveryService,
		private ICloudFederationProviderManager $cloudFederationProviderManager,
		private ICloudFederationFactory $cloudFederationFactory,
		private IGroupManager $groupManager,
		IUserSession $userSession,
		private IEventDispatcher $eventDispatcher,
		private LoggerInterface $logger,
		private IRootFolder $rootFolder,
		private SetupManager $setupManager,
		private ICertificateManager $certificateManager,
		private ExternalShareMapper $externalShareMapper,
		private IGenerator $snowflakeGenerator,
	) {
		$this->user = $userSession->getUser();
	}

	/**
	 * Add new server-to-server share.
	 *
	 * @throws Exception
	 * @throws NotPermittedException
	 * @throws NoUserException
	 */
	public function addShare(ExternalShare $externalShare, IUser|IGroup|null $shareWith = null): ?Mount {
		$shareWith = $shareWith ?? $this->user;

		if ($externalShare->getAccepted() !== IShare::STATUS_ACCEPTED) {
			// To avoid conflicts with the mount point generation later,
			// we only use a temporary mount point name here. The real
			// mount point name will be generated when accepting the share,
			// using the original share item name.
			$tmpMountPointName = '{{TemporaryMountPointName#' . $externalShare->getName() . '}}';
			$externalShare->setMountpoint($tmpMountPointName);
			$externalShare->setShareWith($shareWith);

			$i = 1;
			while (true) {
				try {
					$this->externalShareMapper->insert($externalShare);
					break;
				} catch (Exception $e) {
					if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
						$externalShare->setMountpoint($tmpMountPointName . '-' . $i);
						$i++;
					} else {
						throw $e;
					}
				}
			}

			return null;
		}

		$user = $shareWith instanceof IUser ? $shareWith : $this->user;

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$mountPoint = $userFolder->getNonExistingName($externalShare->getName());

		$mountPoint = Filesystem::normalizePath('/' . $mountPoint);
		$externalShare->setMountpoint($mountPoint);
		$externalShare->setShareWith($user);
		$this->externalShareMapper->insert($externalShare);

		$options = [
			'remote' => $externalShare->getRemote(),
			'token' => $externalShare->getShareToken(),
			'password' => $externalShare->getPassword(),
			'mountpoint' => $externalShare->getMountpoint(),
			'owner' => $externalShare->getOwner(),
		];
		return $this->mountShare($options, $user);
	}

	public function getShare(string $id, ?IUser $user = null): ExternalShare|false {
		$user = $user ?? $this->user;
		try {
			$externalShare = $this->externalShareMapper->getById($id);
		} catch (DoesNotExistException $e) {
			return false;
		}

		// check if the user is allowed to access it
		if ($this->canAccessShare($externalShare, $user)) {
			return $externalShare;
		}

		return false;
	}

	private function canAccessShare(ExternalShare $share, IUser $user): bool {
		$isValid = $share->getShareType() === null;
		if ($isValid) {
			// Invalid share type
			return false;
		}

		// If the share is a user share, check if the user is the recipient
		if ($share->getShareType() === IShare::TYPE_USER && $share->getUser() === $user->getUID()) {
			return true;
		}

		// If the share is a group share, check if the user is in the group
		if ($share->getShareType() === IShare::TYPE_GROUP) {
			$parentId = $share->getParent();
			if ($parentId !== '-1') {
				// we just retrieved a sub-share, switch to the parent entry for verification
				$groupShare = $this->externalShareMapper->getById($parentId);
			} else {
				$groupShare = $share;
			}

			if ($this->groupManager->get($groupShare->getUser())->inGroup($user)) {
				return true;
			}
		}

		return false;
	}

	public function getShareByToken(string $token): ExternalShare|false {
		try {
			return $this->externalShareMapper->getShareByToken($token);
		} catch (DoesNotExistException) {
			return false;
		}
	}

	/**
	 * @throws Exception
	 */
	private function updateSubShare(ExternalShare $externalShare, IUser $user, ?string $mountPoint, int $accepted): ExternalShare {
		$parentId = $externalShare->getParent();
		if ($parentId !== '-1') {
			// this is the sub-share
			$subShare = $externalShare;
		} else {
			try {
				$subShare = $this->externalShareMapper->getUserShare($externalShare, $user);
			} catch (DoesNotExistException) {
				$subShare = new ExternalShare();
				$subShare->setId($this->snowflakeGenerator->nextId());
				$subShare->setRemote($externalShare->getRemote());
				$subShare->setPassword($externalShare->getPassword());
				$subShare->setName($externalShare->getName());
				$subShare->setOwner($externalShare->getOwner());
				$subShare->setUser($user->getUID());
				$subShare->setMountpoint($mountPoint ?? $externalShare->getMountpoint());
				$subShare->setAccepted($accepted);
				$subShare->setRemoteId($externalShare->getRemoteId());
				$subShare->setParent($externalShare->getId());
				$subShare->setShareType($externalShare->getShareType());
				$subShare->setShareToken($externalShare->getShareToken());
				$this->externalShareMapper->insert($subShare);
			}
		}

		if ($subShare->getAccepted() !== $accepted) {
			$subShare->setAccepted($accepted);
			if ($mountPoint !== null) {
				$subShare->setMountpoint($mountPoint);
			}
			$this->externalShareMapper->update($subShare);
		}

		return $subShare;
	}

	/**
	 * Accept server-to-server share.
	 *
	 * @return bool True if the share could be accepted, false otherwise
	 */
	public function acceptShare(ExternalShare $externalShare, ?IUser $user = null): bool {
		// If we're auto-accepting a share, we need to know the user id
		// as there is no session available while processing the share
		// from the remote server request.
		$user = $user ?? $this->user;
		if ($user === null) {
			$this->logger->error('No user specified for accepting share');
			return false;
		}

		$result = false;
		$this->setupManager->setupForUser($user);
		$folder = $this->rootFolder->getUserFolder($user->getUID());

		$shareFolder = Helper::getShareFolder(null, $user->getUID());
		$shareFolder = $folder->get($shareFolder);
		/** @var Folder $shareFolder */
		$mountPoint = $shareFolder->getNonExistingName($externalShare->getName());
		$mountPoint = Filesystem::normalizePath($mountPoint);
		$userShareAccepted = false;

		if ($externalShare->getShareType() === IShare::TYPE_USER) {
			if ($externalShare->getUser() === $user->getUID()) {
				$externalShare->setAccepted(IShare::STATUS_ACCEPTED);
				$externalShare->setMountpoint($mountPoint);
				$this->externalShareMapper->update($externalShare);
				$userShareAccepted = true;
			}
		} else {
			try {
				$this->updateSubShare($externalShare, $user, $mountPoint, IShare::STATUS_ACCEPTED);
				$result = true;
			} catch (Exception $e) {
				$this->logger->emergency('Could not create sub-share', ['exception' => $e]);
				$this->processNotification($externalShare, $user);
				return false;
			}
		}

		if ($userShareAccepted !== false) {
			$this->sendFeedbackToRemote($externalShare, 'accept');
			$event = new FederatedShareAddedEvent($externalShare->getRemote());
			$this->eventDispatcher->dispatchTyped($event);
			$this->eventDispatcher->dispatchTyped(new InvalidateMountCacheEvent($user));
			$result = true;
		}

		// Make sure the user has no notification for something that does not exist anymore.
		$this->processNotification($externalShare, $user);
		return $result;
	}

	/**
	 * Decline server-to-server share
	 *
	 * @return bool True if the share could be declined, false otherwise
	 */
	public function declineShare(ExternalShare $externalShare, ?Iuser $user = null): bool {
		$user = $user ?? $this->user;
		if ($user === null) {
			$this->logger->error('No user specified for declining share');
			return false;
		}

		$result = false;

		if ($externalShare->getShareType() === IShare::TYPE_USER) {
			if ($externalShare->getUser() === $user->getUID()) {
				$this->externalShareMapper->delete($externalShare);
				$this->sendFeedbackToRemote($externalShare, 'decline');
				$this->processNotification($externalShare, $user);
				$result = true;
			}
		} elseif ($externalShare->getShareType() === IShare::TYPE_GROUP) {
			try {
				$this->updateSubShare($externalShare, $user, null, IShare::STATUS_PENDING);
				$result = true;
			} catch (Exception $e) {
				$this->logger->emergency('Could not create sub-share', ['exception' => $e]);
				$this->processNotification($externalShare, $user);
				return false;
			}
		}

		// Make sure the user has no notification for something that does not exist anymore.
		$this->processNotification($externalShare, $user);
		return $result;
	}

	public function processNotification(ExternalShare $remoteShare, ?IUser $user = null): void {
		$user = $user ?? $this->user;
		if ($user === null) {
			$this->logger->error('No user specified for processing notification');
			return;
		}

		$filter = $this->notificationManager->createNotification();
		$filter->setApp('files_sharing')
			->setUser($user->getUID())
			->setObject('remote_share', $remoteShare->getId());
		$this->notificationManager->markProcessed($filter);
	}

	/**
	 * Inform remote server whether server-to-server share was accepted/declined
	 *
	 * @param 'accept'|'decline' $feedback
	 */
	private function sendFeedbackToRemote(ExternalShare $externalShare, string $feedback): bool {
		$result = $this->tryOCMEndPoint($externalShare, $feedback);
		if (is_array($result)) {
			return true;
		}

		$federationEndpoints = $this->discoveryService->discover($externalShare->getRemote(), 'FEDERATED_SHARING');
		$endpoint = $federationEndpoints['share'] ?? '/ocs/v2.php/cloud/shares';

		$url = rtrim($externalShare->getRemote(), '/') . $endpoint . '/' . $externalShare->getRemoteId() . '/' . $feedback . '?format=json';
		$fields = ['token' => $externalShare->getShareToken()];

		$client = $this->clientService->newClient();

		try {
			$response = $client->post(
				$url,
				[
					'body' => $fields,
					'connect_timeout' => 10,
				]
			);
		} catch (\Exception $e) {
			return false;
		}

		$status = json_decode($response->getBody(), true);

		return ($status['ocs']['meta']['statuscode'] === 100 || $status['ocs']['meta']['statuscode'] === 200);
	}

	/**
	 * Try to send accept message to ocm end-point
	 *
	 * @param 'accept'|'decline' $feedback
	 * @return array|false
	 */
	protected function tryOCMEndPoint(ExternalShare $externalShare, string $feedback) {
		switch ($feedback) {
			case 'accept':
				$notification = $this->cloudFederationFactory->getCloudFederationNotification();
				$notification->setMessage(
					'SHARE_ACCEPTED',
					'file',
					$externalShare->getRemoteId(),
					[
						'sharedSecret' => $externalShare->getShareToken(),
						'message' => 'Recipient accept the share'
					]

				);
				return $this->cloudFederationProviderManager->sendNotification($externalShare->getRemote(), $notification);
			case 'decline':
				$notification = $this->cloudFederationFactory->getCloudFederationNotification();
				$notification->setMessage(
					'SHARE_DECLINED',
					'file',
					$externalShare->getRemoteId(),
					[
						'sharedSecret' => $externalShare->getShareToken(),
						'message' => 'Recipient declined the share'
					]
				);
				return $this->cloudFederationProviderManager->sendNotification($externalShare->getRemote(), $notification);
		}
		return false;
	}

	/**
	 * remove '/user/files' from the path and trailing slashes
	 */
	protected function stripPath(string $path): string {
		$prefix = '/' . $this->user->getUID() . '/files';
		return rtrim(substr($path, strlen($prefix)), '/');
	}

	public function getMount(array $data, ?IUser $user = null): Mount {
		$user = $user ?? $this->user;
		$data['manager'] = $this;
		$mountPoint = '/' . $user->getUID() . '/files' . $data['mountpoint'];
		$data['mountpoint'] = $mountPoint;
		$data['certificateManager'] = $this->certificateManager;
		return new Mount(Storage::class, $mountPoint, $data, $this, $this->storageLoader);
	}

	protected function mountShare(array $data, ?IUser $user = null): Mount {
		$mount = $this->getMount($data, $user);
		$this->mountManager->addMount($mount);
		return $mount;
	}

	public function getMountManager(): \OC\Files\Mount\Manager {
		return $this->mountManager;
	}

	public function setMountPoint(string $source, string $target): bool {
		$source = $this->stripPath($source);
		$target = $this->stripPath($target);
		$sourceHash = md5($source);
		$targetHash = md5($target);

		$qb = $this->connection->getQueryBuilder();
		$qb->update('share_external')
			->set('mountpoint', $qb->createNamedParameter($target))
			->set('mountpoint_hash', $qb->createNamedParameter($targetHash))
			->where($qb->expr()->eq('mountpoint_hash', $qb->createNamedParameter($sourceHash)))
			->andWhere($qb->expr()->eq('user', $qb->createNamedParameter($this->user->getUID())));

		$result = (bool)$qb->executeStatement();

		$this->eventDispatcher->dispatchTyped(new InvalidateMountCacheEvent($this->user));

		return $result;
	}

	public function removeShare(string $mountPoint): bool {
		try {
			$mountPointObj = $this->mountManager->find($mountPoint);
		} catch (NotFoundException $e) {
			$this->logger->error('Mount point to remove share not found', ['mountPoint' => $mountPoint]);
			return false;
		}
		if (!$mountPointObj instanceof Mount) {
			$this->logger->error('Mount point to remove share is not an external share, share probably doesn\'t exist', ['mountPoint' => $mountPoint]);
			return false;
		}
		$id = $mountPointObj->getStorage()->getCache()->getId('');

		$mountPoint = $this->stripPath($mountPoint);

		try {
			try {
				$externalShare = $this->externalShareMapper->getByMountPointAndUser($mountPoint, $this->user);
			} catch (DoesNotExistException $e) {
				// ignore
				$this->removeReShares((string)$id);
				return true;
			}

			if ($externalShare->getShareType() === IShare::TYPE_USER) {
				try {
					$this->sendFeedbackToRemote($externalShare, 'decline');
				} catch (\Throwable $e) {
					// if we fail to notify the remote (probably cause the remote is down)
					// we still want the share to be gone to prevent undeletable remotes
				}

				$this->externalShareMapper->delete($externalShare);
			} elseif ($externalShare->getShareType() === IShare::TYPE_GROUP) {
				$externalShare->setAccepted(IShare::STATUS_PENDING);
				$this->externalShareMapper->update($externalShare);
			}

			$this->removeReShares((string)$id);
		} catch (Exception $ex) {
			$this->logger->emergency('Could not update share', ['exception' => $ex]);
			return false;
		}

		return true;
	}

	/**
	 * Remove re-shares from share table and mapping in the federated_reshares table
	 */
	protected function removeReShares(string $mountPointId): void {
		$selectQuery = $this->connection->getQueryBuilder();
		$query = $this->connection->getQueryBuilder();
		$selectQuery->select('id')->from('share')
			->where($selectQuery->expr()->eq('file_source', $query->createNamedParameter($mountPointId)));
		$select = $selectQuery->getSQL();

		$query->delete('federated_reshares')
			->where($query->expr()->in('share_id', $query->createFunction($select)));
		$query->executeStatement();

		$deleteReShares = $this->connection->getQueryBuilder();
		$deleteReShares->delete('share')
			->where($deleteReShares->expr()->eq('file_source', $deleteReShares->createNamedParameter($mountPointId)));
		$deleteReShares->executeStatement();
	}

	/**
	 * Remove all shares for user $uid if the user was deleted.
	 */
	public function removeUserShares(IUser $user): bool {
		try {
			$shares = $this->externalShareMapper->getUserShares($user);
			foreach ($shares as $share) {
				$this->sendFeedbackToRemote($share, 'decline');
			}

			$this->externalShareMapper->deleteUserShares($user);
		} catch (Exception $ex) {
			$this->logger->emergency('Could not delete user shares', ['exception' => $ex]);
			return false;
		}

		return true;
	}

	public function removeGroupShares(IGroup $group): bool {
		try {
			$this->externalShareMapper->deleteGroupShares($group);
		} catch (Exception $ex) {
			$this->logger->emergency('Could not delete user shares', ['exception' => $ex]);
			return false;
		}

		return true;
	}

	/**
	 * Return a list of shares which are not yet accepted by the user.
	 *
	 * @return list<ExternalShare> list of open server-to-server shares
	 */
	public function getOpenShares(): array {
		try {
			return $this->externalShareMapper->getShares($this->user, IShare::STATUS_PENDING);
		} catch (Exception $e) {
			$this->logger->emergency('Error when retrieving shares', ['exception' => $e]);
			return [];
		}
	}

	/**
	 * Return a list of shares which are accepted by the user.
	 *
	 * @return list<ExternalShare> list of accepted server-to-server shares
	 */
	public function getAcceptedShares(): array {
		try {
			return $this->externalShareMapper->getShares($this->user, IShare::STATUS_ACCEPTED);
		} catch (Exception $e) {
			$this->logger->emergency('Error when retrieving shares', ['exception' => $e]);
			return [];
		}
	}
}

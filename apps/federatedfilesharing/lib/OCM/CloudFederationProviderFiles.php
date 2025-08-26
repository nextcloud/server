<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FederatedFileSharing\OCM;

use NCU\Federation\ISignedCloudFederationProvider;
use OC\AppFramework\Http;
use OC\Files\Filesystem;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\Federation\TrustedServers;
use OCA\Files_Sharing\Activity\Providers\RemoteShares;
use OCA\Files_Sharing\External\Manager;
use OCA\GlobalSiteSelector\Service\SlaveService;
use OCP\Activity\IManager as IActivityManager;
use OCP\App\IAppManager;
use OCP\AppFramework\QueryException;
use OCP\Constants;
use OCP\Federation\Exceptions\ActionNotSupportedException;
use OCP\Federation\Exceptions\AuthenticationFailedException;
use OCP\Federation\Exceptions\BadRequestException;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudIdManager;
use OCP\Files\IFilenameValidator;
use OCP\Files\NotFoundException;
use OCP\HintException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Server;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;
use OCP\Util;
use Psr\Log\LoggerInterface;
use SensitiveParameter;

class CloudFederationProviderFiles implements ISignedCloudFederationProvider {
	/**
	 * CloudFederationProvider constructor.
	 */
	public function __construct(
		private IAppManager $appManager,
		private FederatedShareProvider $federatedShareProvider,
		private AddressHandler $addressHandler,
		private IUserManager $userManager,
		private IManager $shareManager,
		private ICloudIdManager $cloudIdManager,
		private IActivityManager $activityManager,
		private INotificationManager $notificationManager,
		private IURLGenerator $urlGenerator,
		private ICloudFederationFactory $cloudFederationFactory,
		private ICloudFederationProviderManager $cloudFederationProviderManager,
		private IDBConnection $connection,
		private IGroupManager $groupManager,
		private IConfig $config,
		private Manager $externalShareManager,
		private LoggerInterface $logger,
		private IFilenameValidator $filenameValidator,
		private readonly IProviderFactory $shareProviderFactory,
	) {
	}

	/**
	 * @return string
	 */
	public function getShareType() {
		return 'file';
	}

	/**
	 * share received from another server
	 *
	 * @param ICloudFederationShare $share
	 * @return string provider specific unique ID of the share
	 *
	 * @throws ProviderCouldNotAddShareException
	 * @throws QueryException
	 * @throws HintException
	 * @since 14.0.0
	 */
	public function shareReceived(ICloudFederationShare $share) {
		if (!$this->isS2SEnabled(true)) {
			throw new ProviderCouldNotAddShareException('Server does not support federated cloud sharing', '', Http::STATUS_SERVICE_UNAVAILABLE);
		}

		$protocol = $share->getProtocol();
		if ($protocol['name'] !== 'webdav') {
			throw new ProviderCouldNotAddShareException('Unsupported protocol for data exchange.', '', Http::STATUS_NOT_IMPLEMENTED);
		}

		[$ownerUid, $remote] = $this->addressHandler->splitUserRemote($share->getOwner());
		// for backward compatibility make sure that the remote url stored in the
		// database ends with a trailing slash
		if (!str_ends_with($remote, '/')) {
			$remote = $remote . '/';
		}

		$token = $share->getShareSecret();
		$name = $share->getResourceName();
		$owner = $share->getOwnerDisplayName();
		$sharedBy = $share->getSharedByDisplayName();
		$shareWith = $share->getShareWith();
		$remoteId = $share->getProviderId();
		$sharedByFederatedId = $share->getSharedBy();
		$ownerFederatedId = $share->getOwner();
		$shareType = $this->mapShareTypeToNextcloud($share->getShareType());

		// if no explicit information about the person who created the share was send
		// we assume that the share comes from the owner
		if ($sharedByFederatedId === null) {
			$sharedBy = $owner;
			$sharedByFederatedId = $ownerFederatedId;
		}

		if ($remote && $token && $name && $owner && $remoteId && $shareWith) {
			if (!$this->filenameValidator->isFilenameValid($name)) {
				throw new ProviderCouldNotAddShareException('The mountpoint name contains invalid characters.', '', Http::STATUS_BAD_REQUEST);
			}

			// FIXME this should be a method in the user management instead
			if ($shareType === IShare::TYPE_USER) {
				$this->logger->debug('shareWith before, ' . $shareWith, ['app' => 'files_sharing']);
				Util::emitHook(
					'\OCA\Files_Sharing\API\Server2Server',
					'preLoginNameUsedAsUserName',
					['uid' => &$shareWith]
				);
				$this->logger->debug('shareWith after, ' . $shareWith, ['app' => 'files_sharing']);

				if (!$this->userManager->userExists($shareWith)) {
					throw new ProviderCouldNotAddShareException('User does not exists', '', Http::STATUS_BAD_REQUEST);
				}

				\OC_Util::setupFS($shareWith);
			}

			if ($shareType === IShare::TYPE_GROUP && !$this->groupManager->groupExists($shareWith)) {
				throw new ProviderCouldNotAddShareException('Group does not exists', '', Http::STATUS_BAD_REQUEST);
			}

			try {
				$this->externalShareManager->addShare($remote, $token, '', $name, $owner, $shareType, false, $shareWith, $remoteId);
				$shareId = Server::get(IDBConnection::class)->lastInsertId('*PREFIX*share_external');

				// get DisplayName about the owner of the share
				$ownerDisplayName = $this->getUserDisplayName($ownerFederatedId);

				$trustedServers = null;
				if ($this->appManager->isEnabledForAnyone('federation')
					&& class_exists(TrustedServers::class)) {
					try {
						$trustedServers = Server::get(TrustedServers::class);
					} catch (\Throwable $e) {
						$this->logger->debug('Failed to create TrustedServers', ['exception' => $e]);
					}
				}


				if ($shareType === IShare::TYPE_USER) {
					$event = $this->activityManager->generateEvent();
					$event->setApp('files_sharing')
						->setType('remote_share')
						->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_RECEIVED, [$ownerFederatedId, trim($name, '/'), $ownerDisplayName])
						->setAffectedUser($shareWith)
						->setObject('remote_share', $shareId, $name);
					Server::get(IActivityManager::class)->publish($event);
					$this->notifyAboutNewShare($shareWith, $shareId, $ownerFederatedId, $sharedByFederatedId, $name, $ownerDisplayName);

					// If auto-accept is enabled, accept the share
					if ($this->federatedShareProvider->isFederatedTrustedShareAutoAccept() && $trustedServers?->isTrustedServer($remote) === true) {
						$this->externalShareManager->acceptShare($shareId, $shareWith);
					}
				} else {
					$groupMembers = $this->groupManager->get($shareWith)->getUsers();
					foreach ($groupMembers as $user) {
						$event = $this->activityManager->generateEvent();
						$event->setApp('files_sharing')
							->setType('remote_share')
							->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_RECEIVED, [$ownerFederatedId, trim($name, '/'), $ownerDisplayName])
							->setAffectedUser($user->getUID())
							->setObject('remote_share', $shareId, $name);
						Server::get(IActivityManager::class)->publish($event);
						$this->notifyAboutNewShare($user->getUID(), $shareId, $ownerFederatedId, $sharedByFederatedId, $name, $ownerDisplayName);

						// If auto-accept is enabled, accept the share
						if ($this->federatedShareProvider->isFederatedTrustedShareAutoAccept() && $trustedServers?->isTrustedServer($remote) === true) {
							$this->externalShareManager->acceptShare($shareId, $user->getUID());
						}
					}
				}

				return $shareId;
			} catch (\Exception $e) {
				$this->logger->error('Server can not add remote share.', [
					'app' => 'files_sharing',
					'exception' => $e,
				]);
				throw new ProviderCouldNotAddShareException('internal server error, was not able to add share from ' . $remote, '', HTTP::STATUS_INTERNAL_SERVER_ERROR);
			}
		}

		throw new ProviderCouldNotAddShareException('server can not add remote share, missing parameter', '', HTTP::STATUS_BAD_REQUEST);
	}

	/**
	 * notification received from another server
	 *
	 * @param string $notificationType (e.g. SHARE_ACCEPTED)
	 * @param string $providerId id of the share
	 * @param array $notification payload of the notification
	 * @return array<string> data send back to the sender
	 *
	 * @throws ActionNotSupportedException
	 * @throws AuthenticationFailedException
	 * @throws BadRequestException
	 * @throws HintException
	 * @since 14.0.0
	 */
	public function notificationReceived($notificationType, $providerId, array $notification) {
		switch ($notificationType) {
			case 'SHARE_ACCEPTED':
				return $this->shareAccepted($providerId, $notification);
			case 'SHARE_DECLINED':
				return $this->shareDeclined($providerId, $notification);
			case 'SHARE_UNSHARED':
				return $this->unshare($providerId, $notification);
			case 'REQUEST_RESHARE':
				return $this->reshareRequested($providerId, $notification);
			case 'RESHARE_UNDO':
				return $this->undoReshare($providerId, $notification);
			case 'RESHARE_CHANGE_PERMISSION':
				return $this->updateResharePermissions($providerId, $notification);
		}


		throw new BadRequestException([$notificationType]);
	}

	/**
	 * map OCM share type (strings) to Nextcloud internal share types (integer)
	 *
	 * @param string $shareType
	 * @return int
	 */
	private function mapShareTypeToNextcloud($shareType) {
		$result = IShare::TYPE_USER;
		if ($shareType === 'group') {
			$result = IShare::TYPE_GROUP;
		}

		return $result;
	}

	private function notifyAboutNewShare($shareWith, $shareId, $ownerFederatedId, $sharedByFederatedId, $name, $displayName): void {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp('files_sharing')
			->setUser($shareWith)
			->setDateTime(new \DateTime())
			->setObject('remote_share', $shareId)
			->setSubject('remote_share', [$ownerFederatedId, $sharedByFederatedId, trim($name, '/'), $displayName]);

		$declineAction = $notification->createAction();
		$declineAction->setLabel('decline')
			->setLink($this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkTo('', 'ocs/v2.php/apps/files_sharing/api/v1/remote_shares/pending/' . $shareId)), 'DELETE');
		$notification->addAction($declineAction);

		$acceptAction = $notification->createAction();
		$acceptAction->setLabel('accept')
			->setLink($this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkTo('', 'ocs/v2.php/apps/files_sharing/api/v1/remote_shares/pending/' . $shareId)), 'POST');
		$notification->addAction($acceptAction);

		$this->notificationManager->notify($notification);
	}

	/**
	 * process notification that the recipient accepted a share
	 *
	 * @param string $id
	 * @param array $notification
	 * @return array<string>
	 * @throws ActionNotSupportedException
	 * @throws AuthenticationFailedException
	 * @throws BadRequestException
	 * @throws HintException
	 */
	private function shareAccepted($id, array $notification) {
		if (!$this->isS2SEnabled()) {
			throw new ActionNotSupportedException('Server does not support federated cloud sharing');
		}

		if (!isset($notification['sharedSecret'])) {
			throw new BadRequestException(['sharedSecret']);
		}

		$token = $notification['sharedSecret'];

		$share = $this->federatedShareProvider->getShareById($id);

		$this->verifyShare($share, $token);
		$this->executeAcceptShare($share);

		if ($share->getShareOwner() !== $share->getSharedBy()
			&& !$this->userManager->userExists($share->getSharedBy())) {
			// only if share was initiated from another instance
			[, $remote] = $this->addressHandler->splitUserRemote($share->getSharedBy());
			$remoteId = $this->federatedShareProvider->getRemoteId($share);
			$notification = $this->cloudFederationFactory->getCloudFederationNotification();
			$notification->setMessage(
				'SHARE_ACCEPTED',
				'file',
				$remoteId,
				[
					'sharedSecret' => $token,
					'message' => 'Recipient accepted the re-share'
				]

			);
			$this->cloudFederationProviderManager->sendNotification($remote, $notification);
		}

		return [];
	}

	/**
	 * @param IShare $share
	 * @throws ShareNotFound
	 */
	protected function executeAcceptShare(IShare $share) {
		try {
			$fileId = (int)$share->getNode()->getId();
			[$file, $link] = $this->getFile($this->getCorrectUid($share), $fileId);
		} catch (\Exception $e) {
			throw new ShareNotFound();
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('files_sharing')
			->setType('remote_share')
			->setAffectedUser($this->getCorrectUid($share))
			->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_ACCEPTED, [$share->getSharedWith(), [$fileId => $file]])
			->setObject('files', $fileId, $file)
			->setLink($link);
		$this->activityManager->publish($event);
	}

	/**
	 * process notification that the recipient declined a share
	 *
	 * @param string $id
	 * @param array $notification
	 * @return array<string>
	 * @throws ActionNotSupportedException
	 * @throws AuthenticationFailedException
	 * @throws BadRequestException
	 * @throws ShareNotFound
	 * @throws HintException
	 *
	 */
	protected function shareDeclined($id, array $notification) {
		if (!$this->isS2SEnabled()) {
			throw new ActionNotSupportedException('Server does not support federated cloud sharing');
		}

		if (!isset($notification['sharedSecret'])) {
			throw new BadRequestException(['sharedSecret']);
		}

		$token = $notification['sharedSecret'];

		$share = $this->federatedShareProvider->getShareById($id);

		$this->verifyShare($share, $token);

		if ($share->getShareOwner() !== $share->getSharedBy()) {
			[, $remote] = $this->addressHandler->splitUserRemote($share->getSharedBy());
			$remoteId = $this->federatedShareProvider->getRemoteId($share);
			$notification = $this->cloudFederationFactory->getCloudFederationNotification();
			$notification->setMessage(
				'SHARE_DECLINED',
				'file',
				$remoteId,
				[
					'sharedSecret' => $token,
					'message' => 'Recipient declined the re-share'
				]

			);
			$this->cloudFederationProviderManager->sendNotification($remote, $notification);
		}

		$this->executeDeclineShare($share);

		return [];
	}

	/**
	 * delete declined share and create a activity
	 *
	 * @param IShare $share
	 * @throws ShareNotFound
	 */
	protected function executeDeclineShare(IShare $share) {
		$this->federatedShareProvider->removeShareFromTable($share);

		try {
			$fileId = (int)$share->getNode()->getId();
			[$file, $link] = $this->getFile($this->getCorrectUid($share), $fileId);
		} catch (\Exception $e) {
			throw new ShareNotFound();
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('files_sharing')
			->setType('remote_share')
			->setAffectedUser($this->getCorrectUid($share))
			->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_DECLINED, [$share->getSharedWith(), [$fileId => $file]])
			->setObject('files', $fileId, $file)
			->setLink($link);
		$this->activityManager->publish($event);
	}

	/**
	 * received the notification that the owner unshared a file from you
	 *
	 * @param string $id
	 * @param array $notification
	 * @return array<string>
	 * @throws AuthenticationFailedException
	 * @throws BadRequestException
	 */
	private function undoReshare($id, array $notification) {
		if (!isset($notification['sharedSecret'])) {
			throw new BadRequestException(['sharedSecret']);
		}
		$token = $notification['sharedSecret'];

		$share = $this->federatedShareProvider->getShareById($id);

		$this->verifyShare($share, $token);
		$this->federatedShareProvider->removeShareFromTable($share);
		return [];
	}

	/**
	 * unshare file from self
	 *
	 * @param string $id
	 * @param array $notification
	 * @return array<string>
	 * @throws ActionNotSupportedException
	 * @throws BadRequestException
	 */
	private function unshare($id, array $notification) {
		if (!$this->isS2SEnabled(true)) {
			throw new ActionNotSupportedException('incoming shares disabled!');
		}

		if (!isset($notification['sharedSecret'])) {
			throw new BadRequestException(['sharedSecret']);
		}
		$token = $notification['sharedSecret'];

		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from('share_external')
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('remote_id', $qb->createNamedParameter($id)),
					$qb->expr()->eq('share_token', $qb->createNamedParameter($token))
				)
			);

		$result = $qb->executeQuery();
		$share = $result->fetch();
		$result->closeCursor();

		if ($token && $id && !empty($share)) {
			$remote = $this->cleanupRemote($share['remote']);

			$owner = $this->cloudIdManager->getCloudId($share['owner'], $remote);
			$mountpoint = $share['mountpoint'];
			$user = $share['user'];

			$qb = $this->connection->getQueryBuilder();
			$qb->delete('share_external')
				->where(
					$qb->expr()->andX(
						$qb->expr()->eq('remote_id', $qb->createNamedParameter($id)),
						$qb->expr()->eq('share_token', $qb->createNamedParameter($token))
					)
				);

			$qb->executeStatement();

			// delete all child in case of a group share
			$qb = $this->connection->getQueryBuilder();
			$qb->delete('share_external')
				->where($qb->expr()->eq('parent', $qb->createNamedParameter((int)$share['id'])));
			$qb->executeStatement();

			$ownerDisplayName = $this->getUserDisplayName($owner->getId());

			if ((int)$share['share_type'] === IShare::TYPE_USER) {
				if ($share['accepted']) {
					$path = trim($mountpoint, '/');
				} else {
					$path = trim($share['name'], '/');
				}
				$notification = $this->notificationManager->createNotification();
				$notification->setApp('files_sharing')
					->setUser($share['user'])
					->setObject('remote_share', (string)$share['id']);
				$this->notificationManager->markProcessed($notification);

				$event = $this->activityManager->generateEvent();
				$event->setApp('files_sharing')
					->setType('remote_share')
					->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_UNSHARED, [$owner->getId(), $path, $ownerDisplayName])
					->setAffectedUser($user)
					->setObject('remote_share', (int)$share['id'], $path);
				Server::get(IActivityManager::class)->publish($event);
			}
		}

		return [];
	}

	private function cleanupRemote($remote) {
		$remote = substr($remote, strpos($remote, '://') + 3);

		return rtrim($remote, '/');
	}

	/**
	 * recipient of a share request to re-share the file with another user
	 *
	 * @param string $id
	 * @param array $notification
	 * @return array<string>
	 * @throws AuthenticationFailedException
	 * @throws BadRequestException
	 * @throws ProviderCouldNotAddShareException
	 * @throws ShareNotFound
	 */
	protected function reshareRequested($id, array $notification) {
		if (!isset($notification['sharedSecret'])) {
			throw new BadRequestException(['sharedSecret']);
		}
		$token = $notification['sharedSecret'];

		if (!isset($notification['shareWith'])) {
			throw new BadRequestException(['shareWith']);
		}
		$shareWith = $notification['shareWith'];

		if (!isset($notification['senderId'])) {
			throw new BadRequestException(['senderId']);
		}
		$senderId = $notification['senderId'];

		$share = $this->federatedShareProvider->getShareById($id);

		// We have to respect the default share permissions
		$permissions = $share->getPermissions() & (int)$this->config->getAppValue('core', 'shareapi_default_permissions', (string)Constants::PERMISSION_ALL);
		$share->setPermissions($permissions);

		// don't allow to share a file back to the owner
		try {
			[$user, $remote] = $this->addressHandler->splitUserRemote($shareWith);
			$owner = $share->getShareOwner();
			$currentServer = $this->addressHandler->generateRemoteURL();
			if ($this->addressHandler->compareAddresses($user, $remote, $owner, $currentServer)) {
				throw new ProviderCouldNotAddShareException('Resharing back to the owner is not allowed: ' . $id);
			}
		} catch (\Exception $e) {
			throw new ProviderCouldNotAddShareException($e->getMessage());
		}

		$this->verifyShare($share, $token);

		// check if re-sharing is allowed
		if ($share->getPermissions() & Constants::PERMISSION_SHARE) {
			// the recipient of the initial share is now the initiator for the re-share
			$share->setSharedBy($share->getSharedWith());
			$share->setSharedWith($shareWith);
			$result = $this->federatedShareProvider->create($share);
			$this->federatedShareProvider->storeRemoteId((int)$result->getId(), $senderId);
			return ['token' => $result->getToken(), 'providerId' => $result->getId()];
		} else {
			throw new ProviderCouldNotAddShareException('resharing not allowed for share: ' . $id);
		}
	}

	/**
	 * update permission of a re-share so that the share dialog shows the right
	 * permission if the owner or the sender changes the permission
	 *
	 * @param string $id
	 * @param array $notification
	 * @return array<string>
	 * @throws AuthenticationFailedException
	 * @throws BadRequestException
	 */
	protected function updateResharePermissions($id, array $notification) {
		throw new HintException('Updating reshares not allowed');
	}

	/**
	 * translate OCM Permissions to Nextcloud permissions
	 *
	 * @param array $ocmPermissions
	 * @return int
	 * @throws BadRequestException
	 */
	protected function ocmPermissions2ncPermissions(array $ocmPermissions) {
		$ncPermissions = 0;
		foreach ($ocmPermissions as $permission) {
			switch (strtolower($permission)) {
				case 'read':
					$ncPermissions += Constants::PERMISSION_READ;
					break;
				case 'write':
					$ncPermissions += Constants::PERMISSION_CREATE + Constants::PERMISSION_UPDATE;
					break;
				case 'share':
					$ncPermissions += Constants::PERMISSION_SHARE;
					break;
				default:
					throw new BadRequestException(['permission']);
			}
		}

		return $ncPermissions;
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
			->executeStatement();
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
			$file = Filesystem::getPath($fileSource);
		} catch (NotFoundException $e) {
			$file = null;
		}
		$args = Filesystem::is_dir($file) ? ['dir' => $file] : ['dir' => dirname($file), 'scrollto' => $file];
		$link = Util::linkToAbsolute('files', 'index.php', $args);

		return [$file, $link];
	}

	/**
	 * check if we are the initiator or the owner of a re-share and return the correct UID
	 *
	 * @param IShare $share
	 * @return string
	 */
	protected function getCorrectUid(IShare $share) {
		if ($this->userManager->userExists($share->getShareOwner())) {
			return $share->getShareOwner();
		}

		return $share->getSharedBy();
	}



	/**
	 * check if we got the right share
	 *
	 * @param IShare $share
	 * @param string $token
	 * @return bool
	 * @throws AuthenticationFailedException
	 */
	protected function verifyShare(IShare $share, $token) {
		if (
			$share->getShareType() === IShare::TYPE_REMOTE
			&& $share->getToken() === $token
		) {
			return true;
		}

		if ($share->getShareType() === IShare::TYPE_CIRCLE) {
			try {
				$knownShare = $this->shareManager->getShareByToken($token);
				if ($knownShare->getId() === $share->getId()) {
					return true;
				}
			} catch (ShareNotFound $e) {
			}
		}

		throw new AuthenticationFailedException();
	}



	/**
	 * check if server-to-server sharing is enabled
	 *
	 * @param bool $incoming
	 * @return bool
	 */
	private function isS2SEnabled($incoming = false) {
		$result = $this->appManager->isEnabledForUser('files_sharing');

		if ($incoming) {
			$result = $result && $this->federatedShareProvider->isIncomingServer2serverShareEnabled();
		} else {
			$result = $result && $this->federatedShareProvider->isOutgoingServer2serverShareEnabled();
		}

		return $result;
	}


	/**
	 * get the supported share types, e.g. "user", "group", etc.
	 *
	 * @return array
	 *
	 * @since 14.0.0
	 */
	public function getSupportedShareTypes() {
		return ['user', 'group'];
	}


	public function getUserDisplayName(string $userId): string {
		// check if gss is enabled and available
		if (!$this->appManager->isEnabledForAnyone('globalsiteselector')
			|| !class_exists('\OCA\GlobalSiteSelector\Service\SlaveService')) {
			return '';
		}

		try {
			$slaveService = Server::get(SlaveService::class);
		} catch (\Throwable $e) {
			Server::get(LoggerInterface::class)->error(
				$e->getMessage(),
				['exception' => $e]
			);
			return '';
		}

		return $slaveService->getUserDisplayName($this->cloudIdManager->removeProtocolFromUrl($userId), false);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $sharedSecret
	 * @param array $payload
	 * @return string
	 */
	public function getFederationIdFromSharedSecret(
		#[SensitiveParameter]
		string $sharedSecret,
		array $payload,
	): string {
		$provider = $this->shareProviderFactory->getProviderForType(IShare::TYPE_REMOTE);
		try {
			$share = $provider->getShareByToken($sharedSecret);
		} catch (ShareNotFound) {
			// Maybe we're dealing with a share federated from another server
			$share = $this->externalShareManager->getShareByToken($sharedSecret);
			if ($share === false) {
				return '';
			}

			return $share['user'] . '@' . $share['remote'];
		}

		// if uid_owner is a local account, the request comes from the recipient
		// if not, request comes from the instance that owns the share and recipient is the re-sharer
		if ($this->userManager->get($share->getShareOwner()) !== null) {
			return $share->getSharedWith();
		} else {
			return $share->getShareOwner();
		}
	}
}

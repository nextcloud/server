<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FederatedFileSharing\OCM;

use NCU\Federation\ISignedCloudFederationProvider;
use OC\AppFramework\Http;
use OC\Files\Filesystem;
use OC\Files\SetupManager;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\Federation\TrustedServers;
use OCA\Files_Sharing\Activity\Providers\RemoteShares;
use OCA\Files_Sharing\External\ExternalShare;
use OCA\Files_Sharing\External\ExternalShareMapper;
use OCA\Files_Sharing\External\Manager;
use OCA\GlobalSiteSelector\Service\SlaveService;
use OCA\Polls\Db\Share;
use OCP\Activity\IManager as IActivityManager;
use OCP\App\IAppManager;
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
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Server;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;
use OCP\Snowflake\IGenerator;
use OCP\Util;
use Override;
use Psr\Log\LoggerInterface;
use SensitiveParameter;

class CloudFederationProviderFiles implements ISignedCloudFederationProvider {
	public function __construct(
		private readonly IAppManager $appManager,
		private readonly FederatedShareProvider $federatedShareProvider,
		private readonly AddressHandler $addressHandler,
		private readonly IUserManager $userManager,
		private readonly IManager $shareManager,
		private readonly ICloudIdManager $cloudIdManager,
		private readonly IActivityManager $activityManager,
		private readonly INotificationManager $notificationManager,
		private readonly IURLGenerator $urlGenerator,
		private readonly ICloudFederationFactory $cloudFederationFactory,
		private readonly ICloudFederationProviderManager $cloudFederationProviderManager,
		private readonly IGroupManager $groupManager,
		private readonly IConfig $config,
		private readonly Manager $externalShareManager,
		private readonly LoggerInterface $logger,
		private readonly IFilenameValidator $filenameValidator,
		private readonly IProviderFactory $shareProviderFactory,
		private readonly SetupManager $setupManager,
		private readonly IGenerator $snowflakeGenerator,
		private readonly ExternalShareMapper $externalShareMapper,
	) {
	}

	#[Override]
	public function getShareType(): string {
		return 'file';
	}

	#[Override]
	public function shareReceived(ICloudFederationShare $share): string {
		if (!$this->isS2SEnabled(true)) {
			throw new ProviderCouldNotAddShareException('Server does not support federated cloud sharing', '', Http::STATUS_SERVICE_UNAVAILABLE);
		}

		$protocol = $share->getProtocol();
		if ($protocol['name'] !== 'webdav') {
			throw new ProviderCouldNotAddShareException('Unsupported protocol for data exchange.', '', Http::STATUS_NOT_IMPLEMENTED);
		}

		[, $remote] = $this->addressHandler->splitUserRemote($share->getOwner());

		// for backward compatibility make sure that the remote url stored in the
		// database ends with a trailing slash
		if (!str_ends_with($remote, '/')) {
			$remote = $remote . '/';
		}

		$token = $share->getShareSecret();
		$name = $share->getResourceName();
		$owner = $share->getOwnerDisplayName() ?: $share->getOwner();
		$shareWith = $share->getShareWith();
		$remoteId = $share->getProviderId();
		$sharedByFederatedId = $share->getSharedBy();
		$ownerFederatedId = $share->getOwner();
		$shareType = $this->mapShareTypeToNextcloud($share->getShareType());

		// if no explicit information about the person who created the share was sent
		// we assume that the share comes from the owner
		if ($sharedByFederatedId === null) {
			$sharedByFederatedId = $ownerFederatedId;
		}

		if ($remote && $token && $name && $owner && $remoteId && $shareWith) {
			if (!$this->filenameValidator->isFilenameValid($name)) {
				throw new ProviderCouldNotAddShareException('The mountpoint name contains invalid characters.', '', Http::STATUS_BAD_REQUEST);
			}

			$user = null;
			$group = null;

			if ($shareType === IShare::TYPE_USER) {
				$this->logger->debug('shareWith before, ' . $shareWith, ['app' => 'files_sharing']);
				Util::emitHook(
					'\OCA\Files_Sharing\API\Server2Server',
					'preLoginNameUsedAsUserName',
					['uid' => &$shareWith]
				);
				$this->logger->debug('shareWith after, ' . $shareWith, ['app' => 'files_sharing']);

				$user = $this->userManager->get($shareWith);
				if ($user === null) {
					throw new ProviderCouldNotAddShareException('User does not exists', '', Http::STATUS_BAD_REQUEST);
				}

				$this->setupManager->setupForUser($user);
			} else {
				$group = $this->groupManager->get($shareWith);
				if ($group === null) {
					throw new ProviderCouldNotAddShareException('Group does not exists', '', Http::STATUS_BAD_REQUEST);
				}
			}

			$externalShare = new ExternalShare();
			$externalShare->setId($this->snowflakeGenerator->nextId());
			$externalShare->setRemote($remote);
			$externalShare->setRemoteId($remoteId);
			$externalShare->setShareToken($token);
			$externalShare->setPassword('');
			$externalShare->setName($name);
			$externalShare->setOwner($owner);
			$externalShare->setShareType($shareType);
			$externalShare->setAccepted(IShare::STATUS_PENDING);

			try {
				$this->externalShareManager->addShare($externalShare, $user ?: $group);

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
						->setObject('remote_share', $externalShare->getId(), $name);
					Server::get(IActivityManager::class)->publish($event);
					$this->notifyAboutNewShare($shareWith, $externalShare->getId(), $ownerFederatedId, $sharedByFederatedId, $name, $ownerDisplayName);

					// If auto-accept is enabled, accept the share
					if ($this->federatedShareProvider->isFederatedTrustedShareAutoAccept() && $trustedServers?->isTrustedServer($remote) === true) {
						$this->externalShareManager->acceptShare($externalShare, $user);
					}
				} else {
					$groupMembers = $group->getUsers();
					foreach ($groupMembers as $user) {
						$event = $this->activityManager->generateEvent();
						$event->setApp('files_sharing')
							->setType('remote_share')
							->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_RECEIVED, [$ownerFederatedId, trim($name, '/'), $ownerDisplayName])
							->setAffectedUser($user->getUID())
							->setObject('remote_share', $externalShare->getId(), $name);
						Server::get(IActivityManager::class)->publish($event);
						$this->notifyAboutNewShare($user->getUID(), $externalShare->getId(), $ownerFederatedId, $sharedByFederatedId, $name, $ownerDisplayName);

						// If auto-accept is enabled, accept the share
						if ($this->federatedShareProvider->isFederatedTrustedShareAutoAccept() && $trustedServers?->isTrustedServer($remote) === true) {
							$this->externalShareManager->acceptShare($externalShare, $user);
						}
					}
				}

				return $externalShare->getId();
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

	#[Override]
	public function notificationReceived(string $notificationType, string $providerId, array $notification): array {
		return match ($notificationType) {
			'SHARE_ACCEPTED' => $this->shareAccepted($providerId, $notification),
			'SHARE_DECLINED' => $this->shareDeclined($providerId, $notification),
			'SHARE_UNSHARED' => $this->unshare($providerId, $notification),
			'REQUEST_RESHARE' => $this->reshareRequested($providerId, $notification),
			'RESHARE_UNDO' => $this->undoReshare($providerId, $notification),
			'RESHARE_CHANGE_PERMISSION' => $this->updateResharePermissions($providerId, $notification),
			default => throw new BadRequestException([$notificationType]),
		};
	}

	/**
	 * Map OCM share type (strings) to Nextcloud internal share types (integer)
	 * @return IShare::TYPE_GROUP|IShare::TYPE_USER
	 */
	private function mapShareTypeToNextcloud(string $shareType): int {
		return $shareType === 'group' ? IShare::TYPE_GROUP : IShare::TYPE_USER;
	}

	private function notifyAboutNewShare(string $shareWith, string $shareId, $ownerFederatedId, $sharedByFederatedId, string $name, string $displayName): void {
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
	 * @param array{sharedSecret?: string} $notification
	 * @return array<string>
	 * @throws ActionNotSupportedException
	 * @throws AuthenticationFailedException
	 * @throws BadRequestException
	 * @throws HintException
	 */
	private function shareAccepted(string $id, array $notification): array {
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
	 * @throws ShareNotFound
	 */
	protected function executeAcceptShare(IShare $share): void {
		$user = $this->getCorrectUser($share);

		try {
			$fileId = $share->getNode()->getId();
			[$file, $link] = $this->getFile($user, $fileId);
		} catch (\Exception) {
			throw new ShareNotFound();
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('files_sharing')
			->setType('remote_share')
			->setAffectedUser($user->getUID())
			->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_ACCEPTED, [$share->getSharedWith(), [$fileId => $file]])
			->setObject('files', $fileId, $file)
			->setLink($link);
		$this->activityManager->publish($event);
	}

	/**
	 * process notification that the recipient declined a share
	 *
	 * @param array{sharedSecret?: string} $notification
	 * @return array<string>
	 * @throws ActionNotSupportedException
	 * @throws AuthenticationFailedException
	 * @throws BadRequestException
	 * @throws ShareNotFound
	 * @throws HintException
	 *
	 */
	protected function shareDeclined(string $id, array $notification): array {
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
	 * @throws ShareNotFound
	 */
	protected function executeDeclineShare(IShare $share): void {
		$this->federatedShareProvider->removeShareFromTable($share);

		$user = $this->getCorrectUser($share);

		try {
			$fileId = $share->getNode()->getId();
			[$file, $link] = $this->getFile($user, $fileId);
		} catch (\Exception) {
			throw new ShareNotFound();
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('files_sharing')
			->setType('remote_share')
			->setAffectedUser($user->getUID())
			->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_DECLINED, [$share->getSharedWith(), [$fileId => $file]])
			->setObject('files', $fileId, $file)
			->setLink($link);
		$this->activityManager->publish($event);
	}

	/**
	 * received the notification that the owner unshared a file from you
	 *
	 * @param array{sharedSecret?: string} $notification
	 * @return array<string>
	 * @throws AuthenticationFailedException
	 * @throws BadRequestException
	 */
	private function undoReshare(string $id, array $notification): array {
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
	 * @param array{sharedSecret?: string} $notification
	 * @return array<string>
	 * @throws ActionNotSupportedException
	 * @throws BadRequestException
	 */
	private function unshare(string $id, array $notification): array {
		if (!$this->isS2SEnabled(true)) {
			throw new ActionNotSupportedException('incoming shares disabled!');
		}

		if (!isset($notification['sharedSecret'])) {
			throw new BadRequestException(['sharedSecret']);
		}
		$token = $notification['sharedSecret'];

		$share = $this->externalShareMapper->getShareByRemoteIdAndToken($id, $token);

		if ($token && $id && $share !== null) {
			$remote = $this->cleanupRemote($share->getRemote());

			$owner = $this->cloudIdManager->getCloudId($share->getOwner(), $remote);
			$mountpoint = $share->getMountpoint();
			$user = $share->getUser();

			$this->externalShareMapper->delete($share);

			$ownerDisplayName = $this->getUserDisplayName($owner->getId());

			if ($share->getShareType() === IShare::TYPE_USER) {
				if ($share->getAccepted()) {
					$path = trim($mountpoint, '/');
				} else {
					$path = trim($share->getName(), '/');
				}
				$notification = $this->notificationManager->createNotification();
				$notification->setApp('files_sharing')
					->setUser($share->getUser())
					->setObject('remote_share', $share->getId());
				$this->notificationManager->markProcessed($notification);

				$event = $this->activityManager->generateEvent();
				$event->setApp('files_sharing')
					->setType('remote_share')
					->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_UNSHARED, [$owner->getId(), $path, $ownerDisplayName])
					->setAffectedUser($user)
					->setObject('remote_share', $share->getId(), $path);
				Server::get(IActivityManager::class)->publish($event);
			}
		}

		return [];
	}

	private function cleanupRemote(string $remote): string {
		$remote = substr($remote, strpos($remote, '://') + 3);

		return rtrim($remote, '/');
	}

	/**
	 * recipient of a share request to re-share the file with another user
	 *
	 * @param array{sharedSecret?: string, shareWith?: string, senderId?: string} $notification
	 * @return array<string>
	 * @throws AuthenticationFailedException
	 * @throws BadRequestException
	 * @throws ProviderCouldNotAddShareException
	 * @throws ShareNotFound
	 */
	protected function reshareRequested(string $id, array $notification) {
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
			$this->federatedShareProvider->storeRemoteId($result->getId(), $senderId);
			return ['token' => $result->getToken(), 'providerId' => $result->getId()];
		} else {
			throw new ProviderCouldNotAddShareException('resharing not allowed for share: ' . $id);
		}
	}

	/**
	 * Update permission of a re-share so that the share dialog shows the right
	 * permission if the owner or the sender changes the permission
	 *
	 * @return string[]
	 * @throws AuthenticationFailedException
	 * @throws BadRequestException
	 */
	protected function updateResharePermissions(string $id, array $notification): array {
		throw new HintException('Updating reshares not allowed');
	}

	/**
	 * @return list{?string, string} with internal path of the file and a absolute link to it
	 */
	private function getFile(IUser $user, int $fileSource): array {
		$this->setupManager->setupForUser($user);

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
	 * Check if we are the initiator or the owner of a re-share and return the correct UID
	 */
	protected function getCorrectUser(IShare $share): IUser {
		if ($user = $this->userManager->get($share->getShareOwner())) {
			return $user;
		}

		$user = $this->userManager->get($share->getSharedBy());
		if ($user === null) {
			throw new \RuntimeException('Neither the share owner or the share initiator exist');
		}
		return $user;
	}

	/**
	 * check if we got the right share
	 *
	 * @throws AuthenticationFailedException
	 */
	protected function verifyShare(IShare $share, string $token): bool {
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
	 * Check if server-to-server sharing is enabled
	 */
	private function isS2SEnabled(bool $incoming = false): bool {
		$result = $this->appManager->isEnabledForUser('files_sharing');

		if ($incoming) {
			$result = $result && $this->federatedShareProvider->isIncomingServer2serverShareEnabled();
		} else {
			$result = $result && $this->federatedShareProvider->isOutgoingServer2serverShareEnabled();
		}

		return $result;
	}

	#[Override]
	public function getSupportedShareTypes(): array {
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
			$this->logger->error(
				$e->getMessage(),
				['exception' => $e]
			);
			return '';
		}

		return $slaveService->getUserDisplayName($this->cloudIdManager->removeProtocolFromUrl($userId), false);
	}

	#[Override]
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

			return $share->getUser() . '@' . $share->getRemote();
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

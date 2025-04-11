<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing\Controller;

use Exception;
use OC\Files\Storage\Wrapper\Wrapper;
use OCA\Circles\Api\v1\Circles;
use OCA\Files\Helper;
use OCA\Files_Sharing\Exceptions\SharingRightsException;
use OCA\Files_Sharing\External\Storage;
use OCA\Files_Sharing\ResponseDefinitions;
use OCA\Files_Sharing\SharedStorage;
use OCA\GlobalSiteSelector\Service\SlaveService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\QueryException;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IShareOwnerlessMount;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\HintException;
use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ITagManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Mail\IMailer;
use OCP\Server;
use OCP\Share\Exceptions\GenericShareException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\Exceptions\ShareTokenException;
use OCP\Share\IManager;
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;
use OCP\Share\IShareProviderWithNotification;
use OCP\UserStatus\IManager as IUserStatusManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @package OCA\Files_Sharing\API
 *
 * @psalm-import-type Files_SharingShare from ResponseDefinitions
 */
class ShareAPIController extends OCSController {

	private ?Node $lockedNode = null;

	/**
	 * Share20OCS constructor.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private IManager $shareManager,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
		private IURLGenerator $urlGenerator,
		private IL10N $l,
		private IConfig $config,
		private IAppManager $appManager,
		private ContainerInterface $serverContainer,
		private IUserStatusManager $userStatusManager,
		private IPreview $previewManager,
		private IDateTimeZone $dateTimeZone,
		private LoggerInterface $logger,
		private IProviderFactory $factory,
		private IMailer $mailer,
		private ?string $userId = null,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Convert an IShare to an array for OCS output
	 *
	 * @param IShare $share
	 * @param Node|null $recipientNode
	 * @return Files_SharingShare
	 * @throws NotFoundException In case the node can't be resolved.
	 *
	 * @suppress PhanUndeclaredClassMethod
	 */
	protected function formatShare(IShare $share, ?Node $recipientNode = null): array {
		$sharedBy = $this->userManager->get($share->getSharedBy());
		$shareOwner = $this->userManager->get($share->getShareOwner());

		$isOwnShare = false;
		if ($shareOwner !== null) {
			$isOwnShare = $shareOwner->getUID() === $this->userId;
		}

		$result = [
			'id' => $share->getId(),
			'share_type' => $share->getShareType(),
			'uid_owner' => $share->getSharedBy(),
			'displayname_owner' => $sharedBy !== null ? $sharedBy->getDisplayName() : $share->getSharedBy(),
			// recipient permissions
			'permissions' => $share->getPermissions(),
			// current user permissions on this share
			'can_edit' => $this->canEditShare($share),
			'can_delete' => $this->canDeleteShare($share),
			'stime' => $share->getShareTime()->getTimestamp(),
			'parent' => null,
			'expiration' => null,
			'token' => null,
			'uid_file_owner' => $share->getShareOwner(),
			'note' => $share->getNote(),
			'label' => $share->getLabel(),
			'displayname_file_owner' => $shareOwner !== null ? $shareOwner->getDisplayName() : $share->getShareOwner(),
		];

		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		if ($recipientNode) {
			$node = $recipientNode;
		} else {
			$node = $userFolder->getFirstNodeById($share->getNodeId());
			if (!$node) {
				// fallback to guessing the path
				$node = $userFolder->get($share->getTarget());
				if ($node === null || $share->getTarget() === '') {
					throw new NotFoundException();
				}
			}
		}

		$result['path'] = $userFolder->getRelativePath($node->getPath());
		if ($node instanceof Folder) {
			$result['item_type'] = 'folder';
		} else {
			$result['item_type'] = 'file';
		}

		// Get the original node permission if the share owner is the current user
		if ($isOwnShare) {
			$result['item_permissions'] = $node->getPermissions();
		}

		// If we're on the recipient side, the node permissions
		// are bound to the share permissions. So we need to
		// adjust the permissions to the share permissions if necessary.
		if (!$isOwnShare) {
			$result['item_permissions'] = $share->getPermissions();

			// For some reason, single files share are forbidden to have the delete permission
			// since we have custom methods to check those, let's adjust straight away.
			// DAV permissions does not have that issue though.
			if ($this->canDeleteShare($share) || $this->canDeleteShareFromSelf($share)) {
				$result['item_permissions'] |= Constants::PERMISSION_DELETE;
			}
			if ($this->canEditShare($share)) {
				$result['item_permissions'] |= Constants::PERMISSION_UPDATE;
			}
		}

		// See MOUNT_ROOT_PROPERTYNAME dav property
		$result['is-mount-root'] = $node->getInternalPath() === '';
		$result['mount-type'] = $node->getMountPoint()->getMountType();

		$result['mimetype'] = $node->getMimetype();
		$result['has_preview'] = $this->previewManager->isAvailable($node);
		$result['storage_id'] = $node->getStorage()->getId();
		$result['storage'] = $node->getStorage()->getCache()->getNumericStorageId();
		$result['item_source'] = $node->getId();
		$result['file_source'] = $node->getId();
		$result['file_parent'] = $node->getParent()->getId();
		$result['file_target'] = $share->getTarget();
		$result['item_size'] = $node->getSize();
		$result['item_mtime'] = $node->getMTime();

		$expiration = $share->getExpirationDate();
		if ($expiration !== null) {
			$expiration->setTimezone($this->dateTimeZone->getTimeZone());
			$result['expiration'] = $expiration->format('Y-m-d 00:00:00');
		}

		if ($share->getShareType() === IShare::TYPE_USER) {
			$sharedWith = $this->userManager->get($share->getSharedWith());
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = $sharedWith !== null ? $sharedWith->getDisplayName() : $share->getSharedWith();
			$result['share_with_displayname_unique'] = $sharedWith !== null ? (
				!empty($sharedWith->getSystemEMailAddress()) ? $sharedWith->getSystemEMailAddress() : $sharedWith->getUID()
			) : $share->getSharedWith();

			$userStatuses = $this->userStatusManager->getUserStatuses([$share->getSharedWith()]);
			$userStatus = array_shift($userStatuses);
			if ($userStatus) {
				$result['status'] = [
					'status' => $userStatus->getStatus(),
					'message' => $userStatus->getMessage(),
					'icon' => $userStatus->getIcon(),
					'clearAt' => $userStatus->getClearAt()
						? (int)$userStatus->getClearAt()->format('U')
						: null,
				];
			}
		} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
			$group = $this->groupManager->get($share->getSharedWith());
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = $group !== null ? $group->getDisplayName() : $share->getSharedWith();
		} elseif ($share->getShareType() === IShare::TYPE_LINK) {

			// "share_with" and "share_with_displayname" for passwords of link
			// shares was deprecated in Nextcloud 15, use "password" instead.
			$result['share_with'] = $share->getPassword();
			$result['share_with_displayname'] = '(' . $this->l->t('Shared link') . ')';

			$result['password'] = $share->getPassword();

			$result['send_password_by_talk'] = $share->getSendPasswordByTalk();

			$result['token'] = $share->getToken();
			$result['url'] = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', ['token' => $share->getToken()]);
		} elseif ($share->getShareType() === IShare::TYPE_REMOTE) {
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = $this->getCachedFederatedDisplayName($share->getSharedWith());
			$result['token'] = $share->getToken();
		} elseif ($share->getShareType() === IShare::TYPE_REMOTE_GROUP) {
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = $this->getDisplayNameFromAddressBook($share->getSharedWith(), 'CLOUD');
			$result['token'] = $share->getToken();
		} elseif ($share->getShareType() === IShare::TYPE_EMAIL) {
			$result['share_with'] = $share->getSharedWith();
			$result['password'] = $share->getPassword();
			$result['password_expiration_time'] = $share->getPasswordExpirationTime() !== null ? $share->getPasswordExpirationTime()->format(\DateTime::ATOM) : null;
			$result['send_password_by_talk'] = $share->getSendPasswordByTalk();
			$result['share_with_displayname'] = $this->getDisplayNameFromAddressBook($share->getSharedWith(), 'EMAIL');
			$result['token'] = $share->getToken();
		} elseif ($share->getShareType() === IShare::TYPE_CIRCLE) {
			// getSharedWith() returns either "name (type, owner)" or
			// "name (type, owner) [id]", depending on the Teams app version.
			$hasCircleId = (substr($share->getSharedWith(), -1) === ']');

			$result['share_with_displayname'] = $share->getSharedWithDisplayName();
			if (empty($result['share_with_displayname'])) {
				$displayNameLength = ($hasCircleId ? strrpos($share->getSharedWith(), ' ') : strlen($share->getSharedWith()));
				$result['share_with_displayname'] = substr($share->getSharedWith(), 0, $displayNameLength);
			}

			$result['share_with_avatar'] = $share->getSharedWithAvatar();

			$shareWithStart = ($hasCircleId ? strrpos($share->getSharedWith(), '[') + 1 : 0);
			$shareWithLength = ($hasCircleId ? -1 : strpos($share->getSharedWith(), ' '));
			if ($shareWithLength === false) {
				$result['share_with'] = substr($share->getSharedWith(), $shareWithStart);
			} else {
				$result['share_with'] = substr($share->getSharedWith(), $shareWithStart, $shareWithLength);
			}
		} elseif ($share->getShareType() === IShare::TYPE_ROOM) {
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = '';

			try {
				/** @var array{share_with_displayname: string, share_with_link: string, share_with?: string, token?: string} $roomShare */
				$roomShare = $this->getRoomShareHelper()->formatShare($share);
				$result = array_merge($result, $roomShare);
			} catch (ContainerExceptionInterface $e) {
			}
		} elseif ($share->getShareType() === IShare::TYPE_DECK) {
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = '';

			try {
				/** @var array{share_with: string, share_with_displayname: string, share_with_link: string} $deckShare */
				$deckShare = $this->getDeckShareHelper()->formatShare($share);
				$result = array_merge($result, $deckShare);
			} catch (ContainerExceptionInterface $e) {
			}
		} elseif ($share->getShareType() === IShare::TYPE_SCIENCEMESH) {
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = '';

			try {
				/** @var array{share_with: string, share_with_displayname: string, token: string} $scienceMeshShare */
				$scienceMeshShare = $this->getSciencemeshShareHelper()->formatShare($share);
				$result = array_merge($result, $scienceMeshShare);
			} catch (ContainerExceptionInterface $e) {
			}
		}


		$result['mail_send'] = $share->getMailSend() ? 1 : 0;
		$result['hide_download'] = $share->getHideDownload() ? 1 : 0;

		$result['attributes'] = null;
		if ($attributes = $share->getAttributes()) {
			$result['attributes'] = (string)\json_encode($attributes->toArray());
		}

		return $result;
	}

	/**
	 * Check if one of the users address books knows the exact property, if
	 * not we return the full name.
	 *
	 * @param string $query
	 * @param string $property
	 * @return string
	 */
	private function getDisplayNameFromAddressBook(string $query, string $property): string {
		// FIXME: If we inject the contacts manager it gets initialized before any address books are registered
		try {
			$result = Server::get(\OCP\Contacts\IManager::class)->search($query, [$property], [
				'limit' => 1,
				'enumeration' => false,
				'strict_search' => true,
			]);
		} catch (Exception $e) {
			$this->logger->error(
				$e->getMessage(),
				['exception' => $e]
			);
			return $query;
		}

		foreach ($result as $r) {
			foreach ($r[$property] as $value) {
				if ($value === $query && $r['FN']) {
					return $r['FN'];
				}
			}
		}

		return $query;
	}


	/**
	 * @param list<Files_SharingShare> $shares
	 * @param array<string, string>|null $updatedDisplayName
	 *
	 * @return list<Files_SharingShare>
	 */
	private function fixMissingDisplayName(array $shares, ?array $updatedDisplayName = null): array {
		$userIds = $updated = [];
		foreach ($shares as $share) {
			// share is federated and share have no display name yet
			if ($share['share_type'] === IShare::TYPE_REMOTE
				&& ($share['share_with'] ?? '') !== ''
				&& ($share['share_with_displayname'] ?? '') === '') {
				$userIds[] = $userId = $share['share_with'];

				if ($updatedDisplayName !== null && array_key_exists($userId, $updatedDisplayName)) {
					$share['share_with_displayname'] = $updatedDisplayName[$userId];
				}
			}

			// prepping userIds with displayName to be updated
			$updated[] = $share;
		}

		// if $updatedDisplayName is not null, it means we should have already fixed displayNames of the shares
		if ($updatedDisplayName !== null) {
			return $updated;
		}

		// get displayName for the generated list of userId with no displayName
		$displayNames = $this->retrieveFederatedDisplayName($userIds);

		// if no displayName are updated, we exit
		if (empty($displayNames)) {
			return $updated;
		}

		// let's fix missing display name and returns all shares
		return $this->fixMissingDisplayName($shares, $displayNames);
	}


	/**
	 * get displayName of a list of userIds from the lookup-server; through the globalsiteselector app.
	 * returns an array with userIds as keys and displayName as values.
	 *
	 * @param array $userIds
	 * @param bool $cacheOnly - do not reach LUS, get data from cache.
	 *
	 * @return array
	 * @throws ContainerExceptionInterface
	 */
	private function retrieveFederatedDisplayName(array $userIds, bool $cacheOnly = false): array {
		// check if gss is enabled and available
		if (count($userIds) === 0
			|| !$this->appManager->isEnabledForAnyone('globalsiteselector')
			|| !class_exists('\OCA\GlobalSiteSelector\Service\SlaveService')) {
			return [];
		}

		try {
			$slaveService = Server::get(SlaveService::class);
		} catch (\Throwable $e) {
			$this->logger->error(
				$e->getMessage(),
				['exception' => $e]
			);
			return [];
		}

		return $slaveService->getUsersDisplayName($userIds, $cacheOnly);
	}


	/**
	 * retrieve displayName from cache if available (should be used on federated shares)
	 * if not available in cache/lus, try for get from address-book, else returns empty string.
	 *
	 * @param string $userId
	 * @param bool $cacheOnly if true will not reach the lus but will only get data from cache
	 *
	 * @return string
	 */
	private function getCachedFederatedDisplayName(string $userId, bool $cacheOnly = true): string {
		$details = $this->retrieveFederatedDisplayName([$userId], $cacheOnly);
		if (array_key_exists($userId, $details)) {
			return $details[$userId];
		}

		$displayName = $this->getDisplayNameFromAddressBook($userId, 'CLOUD');
		return ($displayName === $userId) ? '' : $displayName;
	}



	/**
	 * Get a specific share by id
	 *
	 * @param string $id ID of the share
	 * @param bool $include_tags Include tags in the share
	 * @return DataResponse<Http::STATUS_OK, list<Files_SharingShare>, array{}>
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share returned
	 */
	#[NoAdminRequired]
	public function getShare(string $id, bool $include_tags = false): DataResponse {
		try {
			$share = $this->getShareById($id);
		} catch (ShareNotFound $e) {
			throw new OCSNotFoundException($this->l->t('Wrong share ID, share does not exist'));
		}

		try {
			if ($this->canAccessShare($share)) {
				$share = $this->formatShare($share);

				if ($include_tags) {
					$share = Helper::populateTags([$share], Server::get(ITagManager::class));
				} else {
					$share = [$share];
				}

				return new DataResponse($share);
			}
		} catch (NotFoundException $e) {
			// Fall through
		}

		throw new OCSNotFoundException($this->l->t('Wrong share ID, share does not exist'));
	}

	/**
	 * Delete a share
	 *
	 * @param string $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSNotFoundException Share not found
	 * @throws OCSForbiddenException Missing permissions to delete the share
	 *
	 * 200: Share deleted successfully
	 */
	#[NoAdminRequired]
	public function deleteShare(string $id): DataResponse {
		try {
			$share = $this->getShareById($id);
		} catch (ShareNotFound $e) {
			throw new OCSNotFoundException($this->l->t('Wrong share ID, share does not exist'));
		}

		try {
			$this->lock($share->getNode());
		} catch (LockedException $e) {
			throw new OCSNotFoundException($this->l->t('Could not delete share'));
		}

		if (!$this->canAccessShare($share)) {
			throw new OCSNotFoundException($this->l->t('Wrong share ID, share does not exist'));
		}

		// if it's a group share or a room share
		// we don't delete the share, but only the
		// mount point. Allowing it to be restored
		// from the deleted shares
		if ($this->canDeleteShareFromSelf($share)) {
			$this->shareManager->deleteFromSelf($share, $this->userId);
		} else {
			if (!$this->canDeleteShare($share)) {
				throw new OCSForbiddenException($this->l->t('Could not delete share'));
			}

			$this->shareManager->deleteShare($share);
		}

		return new DataResponse();
	}

	/**
	 * Create a share
	 *
	 * @param string|null $path Path of the share
	 * @param int|null $permissions Permissions for the share
	 * @param int $shareType Type of the share
	 * @param ?string $shareWith The entity this should be shared with
	 * @param 'true'|'false'|null $publicUpload If public uploading is allowed (deprecated)
	 * @param string $password Password for the share
	 * @param string|null $sendPasswordByTalk Send the password for the share over Talk
	 * @param ?string $expireDate The expiry date of the share in the user's timezone at 00:00.
	 *                            If $expireDate is not supplied or set to `null`, the system default will be used.
	 * @param string $note Note for the share
	 * @param string $label Label for the share (only used in link and email)
	 * @param string|null $attributes Additional attributes for the share
	 * @param 'false'|'true'|null $sendMail Send a mail to the recipient
	 *
	 * @return DataResponse<Http::STATUS_OK, Files_SharingShare, array{}>
	 * @throws OCSBadRequestException Unknown share type
	 * @throws OCSException
	 * @throws OCSForbiddenException Creating the share is not allowed
	 * @throws OCSNotFoundException Creating the share failed
	 * @suppress PhanUndeclaredClassMethod
	 *
	 * 200: Share created
	 */
	#[NoAdminRequired]
	public function createShare(
		?string $path = null,
		?int $permissions = null,
		int $shareType = -1,
		?string $shareWith = null,
		?string $publicUpload = null,
		string $password = '',
		?string $sendPasswordByTalk = null,
		?string $expireDate = null,
		string $note = '',
		string $label = '',
		?string $attributes = null,
		?string $sendMail = null,
	): DataResponse {
		assert($this->userId !== null);

		$share = $this->shareManager->newShare();
		$hasPublicUpload = $this->getLegacyPublicUpload($publicUpload);

		// Verify path
		if ($path === null) {
			throw new OCSNotFoundException($this->l->t('Please specify a file or folder path'));
		}

		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		try {
			/** @var \OC\Files\Node\Node $node */
			$node = $userFolder->get($path);
		} catch (NotFoundException $e) {
			throw new OCSNotFoundException($this->l->t('Wrong path, file/folder does not exist'));
		}

		// a user can have access to a file through different paths, with differing permissions
		// combine all permissions to determine if the user can share this file
		$nodes = $userFolder->getById($node->getId());
		foreach ($nodes as $nodeById) {
			/** @var \OC\Files\FileInfo $fileInfo */
			$fileInfo = $node->getFileInfo();
			$fileInfo['permissions'] |= $nodeById->getPermissions();
		}

		$share->setNode($node);

		try {
			$this->lock($share->getNode());
		} catch (LockedException $e) {
			throw new OCSNotFoundException($this->l->t('Could not create share'));
		}

		// Set permissions
		if ($shareType === IShare::TYPE_LINK || $shareType === IShare::TYPE_EMAIL) {
			$permissions = $this->getLinkSharePermissions($permissions, $hasPublicUpload);
			$this->validateLinkSharePermissions($node, $permissions, $hasPublicUpload);
		} else {
			// Use default permissions only for non-link shares to keep legacy behavior
			if ($permissions === null) {
				$permissions = (int)$this->config->getAppValue('core', 'shareapi_default_permissions', (string)Constants::PERMISSION_ALL);
			}
			// Non-link shares always require read permissions (link shares could be file drop)
			$permissions |= Constants::PERMISSION_READ;
		}

		// For legacy reasons the API allows to pass PERMISSIONS_ALL even for single file shares (I look at you Talk)
		if ($node instanceof File) {
			// if this is a single file share we remove the DELETE and CREATE permissions
			$permissions = $permissions & ~(Constants::PERMISSION_DELETE | Constants::PERMISSION_CREATE);
		}

		/**
		 * Hack for https://github.com/owncloud/core/issues/22587
		 * We check the permissions via webdav. But the permissions of the mount point
		 * do not equal the share permissions. Here we fix that for federated mounts.
		 */
		if ($node->getStorage()->instanceOfStorage(Storage::class)) {
			$permissions &= ~($permissions & ~$node->getPermissions());
		}

		if ($attributes !== null) {
			$share = $this->setShareAttributes($share, $attributes);
		}

		// Expire date checks
		// Normally, null means no expiration date but we still set the default for backwards compatibility
		// If the client sends an empty string, we set noExpirationDate to true
		if ($expireDate !== null) {
			if ($expireDate !== '') {
				try {
					$expireDateTime = $this->parseDate($expireDate);
					$share->setExpirationDate($expireDateTime);
				} catch (\Exception $e) {
					throw new OCSNotFoundException($e->getMessage(), $e);
				}
			} else {
				// Client sent empty string for expire date.
				// Set noExpirationDate to true so overwrite is prevented.
				$share->setNoExpirationDate(true);
			}
		}

		$share->setSharedBy($this->userId);

		// Handle mail send
		if (is_null($sendMail)) {
			$allowSendMail = $this->config->getSystemValueBool('sharing.enable_share_mail', true);
			if ($allowSendMail !== true || $shareType === IShare::TYPE_EMAIL) {
				// Define a default behavior when sendMail is not provided
				// For email shares with a valid recipient, the default is to send the mail
				// For all other share types, the default is to not send the mail
				$allowSendMail = ($shareType === IShare::TYPE_EMAIL && $shareWith !== null && $shareWith !== '');
			}
			$share->setMailSend($allowSendMail);
		} else {
			$share->setMailSend($sendMail === 'true');
		}

		if ($shareType === IShare::TYPE_USER) {
			// Valid user is required to share
			if ($shareWith === null || !$this->userManager->userExists($shareWith)) {
				throw new OCSNotFoundException($this->l->t('Please specify a valid account to share with'));
			}
			$share->setSharedWith($shareWith);
			$share->setPermissions($permissions);
		} elseif ($shareType === IShare::TYPE_GROUP) {
			if (!$this->shareManager->allowGroupSharing()) {
				throw new OCSNotFoundException($this->l->t('Group sharing is disabled by the administrator'));
			}

			// Valid group is required to share
			if ($shareWith === null || !$this->groupManager->groupExists($shareWith)) {
				throw new OCSNotFoundException($this->l->t('Please specify a valid group'));
			}
			$share->setSharedWith($shareWith);
			$share->setPermissions($permissions);
		} elseif ($shareType === IShare::TYPE_LINK
			|| $shareType === IShare::TYPE_EMAIL) {

			// Can we even share links?
			if (!$this->shareManager->shareApiAllowLinks()) {
				throw new OCSNotFoundException($this->l->t('Public link sharing is disabled by the administrator'));
			}

			$this->validateLinkSharePermissions($node, $permissions, $hasPublicUpload);
			$share->setPermissions($permissions);

			// Set password
			if ($password !== '') {
				$share->setPassword($password);
			}

			// Only share by mail have a recipient
			if (is_string($shareWith) && $shareType === IShare::TYPE_EMAIL) {
				// If sending a mail have been requested, validate the mail address
				if ($share->getMailSend() && !$this->mailer->validateMailAddress($shareWith)) {
					throw new OCSNotFoundException($this->l->t('Please specify a valid email address'));
				}
				$share->setSharedWith($shareWith);
			}

			// If we have a label, use it
			if ($label !== '') {
				if (strlen($label) > 255) {
					throw new OCSBadRequestException('Maximum label length is 255');
				}
				$share->setLabel($label);
			}

			if ($sendPasswordByTalk === 'true') {
				if (!$this->appManager->isEnabledForUser('spreed')) {
					throw new OCSForbiddenException($this->l->t('Sharing %s sending the password by Nextcloud Talk failed because Nextcloud Talk is not enabled', [$node->getPath()]));
				}

				$share->setSendPasswordByTalk(true);
			}
		} elseif ($shareType === IShare::TYPE_REMOTE) {
			if (!$this->shareManager->outgoingServer2ServerSharesAllowed()) {
				throw new OCSForbiddenException($this->l->t('Sharing %1$s failed because the back end does not allow shares from type %2$s', [$node->getPath(), $shareType]));
			}

			if ($shareWith === null) {
				throw new OCSNotFoundException($this->l->t('Please specify a valid federated account ID'));
			}

			$share->setSharedWith($shareWith);
			$share->setPermissions($permissions);
			$share->setSharedWithDisplayName($this->getCachedFederatedDisplayName($shareWith, false));
		} elseif ($shareType === IShare::TYPE_REMOTE_GROUP) {
			if (!$this->shareManager->outgoingServer2ServerGroupSharesAllowed()) {
				throw new OCSForbiddenException($this->l->t('Sharing %1$s failed because the back end does not allow shares from type %2$s', [$node->getPath(), $shareType]));
			}

			if ($shareWith === null) {
				throw new OCSNotFoundException($this->l->t('Please specify a valid federated group ID'));
			}

			$share->setSharedWith($shareWith);
			$share->setPermissions($permissions);
		} elseif ($shareType === IShare::TYPE_CIRCLE) {
			if (!Server::get(IAppManager::class)->isEnabledForUser('circles') || !class_exists('\OCA\Circles\ShareByCircleProvider')) {
				throw new OCSNotFoundException($this->l->t('You cannot share to a Team if the app is not enabled'));
			}

			$circle = Circles::detailsCircle($shareWith);

			// Valid team is required to share
			if ($circle === null) {
				throw new OCSNotFoundException($this->l->t('Please specify a valid team'));
			}
			$share->setSharedWith($shareWith);
			$share->setPermissions($permissions);
		} elseif ($shareType === IShare::TYPE_ROOM) {
			try {
				$this->getRoomShareHelper()->createShare($share, $shareWith, $permissions, $expireDate ?? '');
			} catch (ContainerExceptionInterface $e) {
				throw new OCSForbiddenException($this->l->t('Sharing %s failed because the back end does not support room shares', [$node->getPath()]));
			}
		} elseif ($shareType === IShare::TYPE_DECK) {
			try {
				$this->getDeckShareHelper()->createShare($share, $shareWith, $permissions, $expireDate ?? '');
			} catch (ContainerExceptionInterface $e) {
				throw new OCSForbiddenException($this->l->t('Sharing %s failed because the back end does not support room shares', [$node->getPath()]));
			}
		} elseif ($shareType === IShare::TYPE_SCIENCEMESH) {
			try {
				$this->getSciencemeshShareHelper()->createShare($share, $shareWith, $permissions, $expireDate ?? '');
			} catch (ContainerExceptionInterface $e) {
				throw new OCSForbiddenException($this->l->t('Sharing %s failed because the back end does not support ScienceMesh shares', [$node->getPath()]));
			}
		} else {
			throw new OCSBadRequestException($this->l->t('Unknown share type'));
		}

		$share->setShareType($shareType);
		$this->checkInheritedAttributes($share);

		if ($note !== '') {
			$share->setNote($note);
		}

		try {
			$share = $this->shareManager->createShare($share);
		} catch (HintException $e) {
			$code = $e->getCode() === 0 ? 403 : $e->getCode();
			throw new OCSException($e->getHint(), $code);
		} catch (GenericShareException|\InvalidArgumentException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSForbiddenException($e->getMessage(), $e);
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSForbiddenException('Failed to create share.', $e);
		}

		$output = $this->formatShare($share);

		return new DataResponse($output);
	}

	/**
	 * @param null|Node $node
	 * @param boolean $includeTags
	 *
	 * @return list<Files_SharingShare>
	 */
	private function getSharedWithMe($node, bool $includeTags): array {
		$userShares = $this->shareManager->getSharedWith($this->userId, IShare::TYPE_USER, $node, -1, 0);
		$groupShares = $this->shareManager->getSharedWith($this->userId, IShare::TYPE_GROUP, $node, -1, 0);
		$circleShares = $this->shareManager->getSharedWith($this->userId, IShare::TYPE_CIRCLE, $node, -1, 0);
		$roomShares = $this->shareManager->getSharedWith($this->userId, IShare::TYPE_ROOM, $node, -1, 0);
		$deckShares = $this->shareManager->getSharedWith($this->userId, IShare::TYPE_DECK, $node, -1, 0);
		$sciencemeshShares = $this->shareManager->getSharedWith($this->userId, IShare::TYPE_SCIENCEMESH, $node, -1, 0);

		$shares = array_merge($userShares, $groupShares, $circleShares, $roomShares, $deckShares, $sciencemeshShares);

		$filteredShares = array_filter($shares, function (IShare $share) {
			return $share->getShareOwner() !== $this->userId;
		});

		$formatted = [];
		foreach ($filteredShares as $share) {
			if ($this->canAccessShare($share)) {
				try {
					$formatted[] = $this->formatShare($share);
				} catch (NotFoundException $e) {
					// Ignore this share
				}
			}
		}

		if ($includeTags) {
			$formatted = Helper::populateTags($formatted, Server::get(ITagManager::class));
		}

		return $formatted;
	}

	/**
	 * @param Node $folder
	 *
	 * @return list<Files_SharingShare>
	 * @throws OCSBadRequestException
	 * @throws NotFoundException
	 */
	private function getSharesInDir(Node $folder): array {
		if (!($folder instanceof Folder)) {
			throw new OCSBadRequestException($this->l->t('Not a directory'));
		}

		$nodes = $folder->getDirectoryListing();

		/** @var IShare[] $shares */
		$shares = array_reduce($nodes, function ($carry, $node) {
			$carry = array_merge($carry, $this->getAllShares($node, true));
			return $carry;
		}, []);

		// filter out duplicate shares
		$known = [];

		$formatted = $miniFormatted = [];
		$resharingRight = false;
		$known = [];
		foreach ($shares as $share) {
			if (in_array($share->getId(), $known) || $share->getSharedWith() === $this->userId) {
				continue;
			}

			try {
				$format = $this->formatShare($share);

				$known[] = $share->getId();
				$formatted[] = $format;
				if ($share->getSharedBy() === $this->userId) {
					$miniFormatted[] = $format;
				}
				if (!$resharingRight && $this->shareProviderResharingRights($this->userId, $share, $folder)) {
					$resharingRight = true;
				}
			} catch (\Exception $e) {
				//Ignore this share
			}
		}

		if (!$resharingRight) {
			$formatted = $miniFormatted;
		}

		return $formatted;
	}

	/**
	 * Get shares of the current user
	 *
	 * @param string $shared_with_me Only get shares with the current user
	 * @param string $reshares Only get shares by the current user and reshares
	 * @param string $subfiles Only get all shares in a folder
	 * @param string $path Get shares for a specific path
	 * @param string $include_tags Include tags in the share
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Files_SharingShare>, array{}>
	 * @throws OCSNotFoundException The folder was not found or is inaccessible
	 *
	 * 200: Shares returned
	 */
	#[NoAdminRequired]
	public function getShares(
		string $shared_with_me = 'false',
		string $reshares = 'false',
		string $subfiles = 'false',
		string $path = '',
		string $include_tags = 'false',
	): DataResponse {
		$node = null;
		if ($path !== '') {
			$userFolder = $this->rootFolder->getUserFolder($this->userId);
			try {
				$node = $userFolder->get($path);
				$this->lock($node);
			} catch (NotFoundException $e) {
				throw new OCSNotFoundException(
					$this->l->t('Wrong path, file/folder does not exist')
				);
			} catch (LockedException $e) {
				throw new OCSNotFoundException($this->l->t('Could not lock node'));
			}
		}

		$shares = $this->getFormattedShares(
			$this->userId,
			$node,
			($shared_with_me === 'true'),
			($reshares === 'true'),
			($subfiles === 'true'),
			($include_tags === 'true')
		);

		return new DataResponse($shares);
	}

	private function getLinkSharePermissions(?int $permissions, ?bool $legacyPublicUpload): int {
		$permissions = $permissions ?? Constants::PERMISSION_READ;

		// Legacy option handling
		if ($legacyPublicUpload !== null) {
			$permissions = $legacyPublicUpload
				? (Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE)
				: Constants::PERMISSION_READ;
		}

		// TODO: It might make sense to have a dedicated setting to allow/deny converting link shares into federated ones
		if ($this->hasPermission($permissions, Constants::PERMISSION_READ)
			&& $this->shareManager->outgoingServer2ServerSharesAllowed()) {
			$permissions |= Constants::PERMISSION_SHARE;
		}

		return $permissions;
	}

	/**
	 * Helper to check for legacy "publicUpload" handling.
	 * If the value is set to `true` or `false` then true or false are returned.
	 * Otherwise null is returned to indicate that the option was not (or wrong) set.
	 *
	 * @param null|string $legacyPublicUpload The value of `publicUpload`
	 */
	private function getLegacyPublicUpload(?string $legacyPublicUpload): ?bool {
		if ($legacyPublicUpload === 'true') {
			return true;
		} elseif ($legacyPublicUpload === 'false') {
			return false;
		}
		// Not set at all
		return null;
	}

	/**
	 * For link and email shares validate that only allowed combinations are set.
	 *
	 * @throw OCSBadRequestException If permission combination is invalid.
	 * @throw OCSForbiddenException If public upload was forbidden by the administrator.
	 */
	private function validateLinkSharePermissions(Node $node, int $permissions, ?bool $legacyPublicUpload): void {
		if ($legacyPublicUpload && ($node instanceof File)) {
			throw new OCSBadRequestException($this->l->t('Public upload is only possible for publicly shared folders'));
		}

		// We need at least READ or CREATE (file drop)
		if (!$this->hasPermission($permissions, Constants::PERMISSION_READ)
			&& !$this->hasPermission($permissions, Constants::PERMISSION_CREATE)) {
			throw new OCSBadRequestException($this->l->t('Share must at least have READ or CREATE permissions'));
		}

		// UPDATE and DELETE require a READ permission
		if (!$this->hasPermission($permissions, Constants::PERMISSION_READ)
			&& ($this->hasPermission($permissions, Constants::PERMISSION_UPDATE) || $this->hasPermission($permissions, Constants::PERMISSION_DELETE))) {
			throw new OCSBadRequestException($this->l->t('Share must have READ permission if UPDATE or DELETE permission is set'));
		}

		// Check if public uploading was disabled
		if ($this->hasPermission($permissions, Constants::PERMISSION_CREATE)
			&& !$this->shareManager->shareApiLinkAllowPublicUpload()) {
			throw new OCSForbiddenException($this->l->t('Public upload disabled by the administrator'));
		}
	}

	/**
	 * @param string $viewer
	 * @param Node $node
	 * @param bool $sharedWithMe
	 * @param bool $reShares
	 * @param bool $subFiles
	 * @param bool $includeTags
	 *
	 * @return list<Files_SharingShare>
	 * @throws NotFoundException
	 * @throws OCSBadRequestException
	 */
	private function getFormattedShares(
		string $viewer,
		$node = null,
		bool $sharedWithMe = false,
		bool $reShares = false,
		bool $subFiles = false,
		bool $includeTags = false,
	): array {
		if ($sharedWithMe) {
			return $this->getSharedWithMe($node, $includeTags);
		}

		if ($subFiles) {
			return $this->getSharesInDir($node);
		}

		$shares = $this->getSharesFromNode($viewer, $node, $reShares);

		$known = $formatted = $miniFormatted = [];
		$resharingRight = false;
		foreach ($shares as $share) {
			try {
				$share->getNode();
			} catch (NotFoundException $e) {
				/*
				 * Ignore shares where we can't get the node
				 * For example deleted shares
				 */
				continue;
			}

			if (in_array($share->getId(), $known)
				|| ($share->getSharedWith() === $this->userId && $share->getShareType() === IShare::TYPE_USER)) {
				continue;
			}

			$known[] = $share->getId();
			try {
				/** @var IShare $share */
				$format = $this->formatShare($share, $node);
				$formatted[] = $format;

				// let's also build a list of shares created
				// by the current user only, in case
				// there is no resharing rights
				if ($share->getSharedBy() === $this->userId) {
					$miniFormatted[] = $format;
				}

				// check if one of those share is shared with me
				// and if I have resharing rights on it
				if (!$resharingRight && $this->shareProviderResharingRights($this->userId, $share, $node)) {
					$resharingRight = true;
				}
			} catch (InvalidPathException|NotFoundException $e) {
			}
		}

		if (!$resharingRight) {
			$formatted = $miniFormatted;
		}

		// fix eventual missing display name from federated shares
		$formatted = $this->fixMissingDisplayName($formatted);

		if ($includeTags) {
			$formatted =
				Helper::populateTags($formatted, Server::get(ITagManager::class));
		}

		return $formatted;
	}


	/**
	 * Get all shares relative to a file, including parent folders shares rights
	 *
	 * @param string $path Path all shares will be relative to
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Files_SharingShare>, array{}>
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws OCSNotFoundException The given path is invalid
	 * @throws SharingRightsException
	 *
	 * 200: Shares returned
	 */
	#[NoAdminRequired]
	public function getInheritedShares(string $path): DataResponse {
		// get Node from (string) path.
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		try {
			$node = $userFolder->get($path);
			$this->lock($node);
		} catch (NotFoundException $e) {
			throw new OCSNotFoundException($this->l->t('Wrong path, file/folder does not exist'));
		} catch (LockedException $e) {
			throw new OCSNotFoundException($this->l->t('Could not lock path'));
		}

		if (!($node->getPermissions() & Constants::PERMISSION_SHARE)) {
			throw new SharingRightsException($this->l->t('no sharing rights on this item'));
		}

		// The current top parent we have access to
		$parent = $node;

		// initiate real owner.
		$owner = $node->getOwner()
			->getUID();
		if (!$this->userManager->userExists($owner)) {
			return new DataResponse([]);
		}

		// get node based on the owner, fix owner in case of external storage
		$userFolder = $this->rootFolder->getUserFolder($owner);
		if ($node->getId() !== $userFolder->getId() && !$userFolder->isSubNode($node)) {
			$owner = $node->getOwner()
				->getUID();
			$userFolder = $this->rootFolder->getUserFolder($owner);
			$node = $userFolder->getFirstNodeById($node->getId());
		}
		$basePath = $userFolder->getPath();

		// generate node list for each parent folders
		/** @var Node[] $nodes */
		$nodes = [];
		while (true) {
			$node = $node->getParent();
			if ($node->getPath() === $basePath) {
				break;
			}
			$nodes[] = $node;
		}

		// The user that is requesting this list
		$currentUserFolder = $this->rootFolder->getUserFolder($this->userId);

		// for each nodes, retrieve shares.
		$shares = [];

		foreach ($nodes as $node) {
			$getShares = $this->getFormattedShares($owner, $node, false, true);

			$currentUserNode = $currentUserFolder->getFirstNodeById($node->getId());
			if ($currentUserNode) {
				$parent = $currentUserNode;
			}

			$subPath = $currentUserFolder->getRelativePath($parent->getPath());
			foreach ($getShares as &$share) {
				$share['via_fileid'] = $parent->getId();
				$share['via_path'] = $subPath;
			}
			$this->mergeFormattedShares($shares, $getShares);
		}

		return new DataResponse(array_values($shares));
	}

	/**
	 * Check whether a set of permissions contains the permissions to check.
	 */
	private function hasPermission(int $permissionsSet, int $permissionsToCheck): bool {
		return ($permissionsSet & $permissionsToCheck) === $permissionsToCheck;
	}

	/**
	 * Update a share
	 *
	 * @param string $id ID of the share
	 * @param int|null $permissions New permissions
	 * @param string|null $password New password
	 * @param string|null $sendPasswordByTalk New condition if the password should be send over Talk
	 * @param string|null $publicUpload New condition if public uploading is allowed
	 * @param string|null $expireDate New expiry date
	 * @param string|null $note New note
	 * @param string|null $label New label
	 * @param string|null $hideDownload New condition if the download should be hidden
	 * @param string|null $attributes New additional attributes
	 * @param string|null $sendMail if the share should be send by mail.
	 *                              Considering the share already exists, no mail will be send after the share is updated.
	 *                              You will have to use the sendMail action to send the mail.
	 * @param string|null $shareWith New recipient for email shares
	 * @param string|null $token New token
	 * @return DataResponse<Http::STATUS_OK, Files_SharingShare, array{}>
	 * @throws OCSBadRequestException Share could not be updated because the requested changes are invalid
	 * @throws OCSForbiddenException Missing permissions to update the share
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share updated successfully
	 */
	#[NoAdminRequired]
	public function updateShare(
		string $id,
		?int $permissions = null,
		?string $password = null,
		?string $sendPasswordByTalk = null,
		?string $publicUpload = null,
		?string $expireDate = null,
		?string $note = null,
		?string $label = null,
		?string $hideDownload = null,
		?string $attributes = null,
		?string $sendMail = null,
		?string $token = null,
	): DataResponse {
		try {
			$share = $this->getShareById($id);
		} catch (ShareNotFound $e) {
			throw new OCSNotFoundException($this->l->t('Wrong share ID, share does not exist'));
		}

		$this->lock($share->getNode());

		if (!$this->canAccessShare($share, false)) {
			throw new OCSNotFoundException($this->l->t('Wrong share ID, share does not exist'));
		}

		if (!$this->canEditShare($share)) {
			throw new OCSForbiddenException($this->l->t('You are not allowed to edit incoming shares'));
		}

		if (
			$permissions === null &&
			$password === null &&
			$sendPasswordByTalk === null &&
			$publicUpload === null &&
			$expireDate === null &&
			$note === null &&
			$label === null &&
			$hideDownload === null &&
			$attributes === null &&
			$sendMail === null &&
			$token === null
		) {
			throw new OCSBadRequestException($this->l->t('Wrong or no update parameter given'));
		}

		if ($note !== null) {
			$share->setNote($note);
		}

		if ($attributes !== null) {
			$share = $this->setShareAttributes($share, $attributes);
		}

		// Handle mail send
		if ($sendMail === 'true' || $sendMail === 'false') {
			$share->setMailSend($sendMail === 'true');
		}

		/**
		 * expiration date, password and publicUpload only make sense for link shares
		 */
		if ($share->getShareType() === IShare::TYPE_LINK
			|| $share->getShareType() === IShare::TYPE_EMAIL) {

			// Update hide download state
			$attributes = $share->getAttributes() ?? $share->newAttributes();
			if ($hideDownload === 'true') {
				$share->setHideDownload(true);
				$attributes->setAttribute('permissions', 'download', false);
			} elseif ($hideDownload === 'false') {
				$share->setHideDownload(false);
				$attributes->setAttribute('permissions', 'download', true);
			}
			$share->setAttributes($attributes);


			// If either manual permissions are specified or publicUpload
			// then we need to also update the permissions of the share
			if ($permissions !== null || $publicUpload !== null) {
				$hasPublicUpload = $this->getLegacyPublicUpload($publicUpload);
				$permissions = $this->getLinkSharePermissions($permissions ?? Constants::PERMISSION_READ, $hasPublicUpload);
				$this->validateLinkSharePermissions($share->getNode(), $permissions, $hasPublicUpload);
				$share->setPermissions($permissions);
			}

			if ($password === '') {
				$share->setPassword(null);
			} elseif ($password !== null) {
				$share->setPassword($password);
			}

			if ($label !== null) {
				if (strlen($label) > 255) {
					throw new OCSBadRequestException('Maximum label length is 255');
				}
				$share->setLabel($label);
			}

			if ($sendPasswordByTalk === 'true') {
				if (!$this->appManager->isEnabledForUser('spreed')) {
					throw new OCSForbiddenException($this->l->t('"Sending the password by Nextcloud Talk" for sharing a file or folder failed because Nextcloud Talk is not enabled.'));
				}

				$share->setSendPasswordByTalk(true);
			} elseif ($sendPasswordByTalk !== null) {
				$share->setSendPasswordByTalk(false);
			}

			if ($token !== null) {
				if (!$this->shareManager->allowCustomTokens()) {
					throw new OCSForbiddenException($this->l->t('Custom share link tokens have been disabled by the administrator'));
				}
				if (!$this->validateToken($token)) {
					throw new OCSBadRequestException($this->l->t('Tokens must contain at least 1 character and may only contain letters, numbers, or a hyphen'));
				}
				$share->setToken($token);
			}
		}

		// NOT A LINK SHARE
		else {
			if ($permissions !== null) {
				$share->setPermissions($permissions);
			}
		}

		if ($expireDate === '') {
			$share->setExpirationDate(null);
		} elseif ($expireDate !== null) {
			try {
				$expireDateTime = $this->parseDate($expireDate);
				$share->setExpirationDate($expireDateTime);
			} catch (\Exception $e) {
				throw new OCSBadRequestException($e->getMessage(), $e);
			}
		}

		try {
			$this->checkInheritedAttributes($share);
			$share = $this->shareManager->updateShare($share);
		} catch (HintException $e) {
			$code = $e->getCode() === 0 ? 403 : $e->getCode();
			throw new OCSException($e->getHint(), (int)$code);
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSBadRequestException('Failed to update share.', $e);
		}

		return new DataResponse($this->formatShare($share));
	}

	private function validateToken(string $token): bool {
		if (mb_strlen($token) === 0) {
			return false;
		}
		if (!preg_match('/^[a-z0-9-]+$/i', $token)) {
			return false;
		}
		return true;
	}

	/**
	 * Get all shares that are still pending
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Files_SharingShare>, array{}>
	 *
	 * 200: Pending shares returned
	 */
	#[NoAdminRequired]
	public function pendingShares(): DataResponse {
		$pendingShares = [];

		$shareTypes = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP
		];

		foreach ($shareTypes as $shareType) {
			$shares = $this->shareManager->getSharedWith($this->userId, $shareType, null, -1, 0);

			foreach ($shares as $share) {
				if ($share->getStatus() === IShare::STATUS_PENDING || $share->getStatus() === IShare::STATUS_REJECTED) {
					$pendingShares[] = $share;
				}
			}
		}

		$result = array_values(array_filter(array_map(function (IShare $share) {
			$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());
			$node = $userFolder->getFirstNodeById($share->getNodeId());
			if (!$node) {
				// fallback to guessing the path
				$node = $userFolder->get($share->getTarget());
				if ($node === null || $share->getTarget() === '') {
					return null;
				}
			}

			try {
				$formattedShare = $this->formatShare($share, $node);
				$formattedShare['path'] = '/' . $share->getNode()->getName();
				$formattedShare['permissions'] = 0;
				return $formattedShare;
			} catch (NotFoundException $e) {
				return null;
			}
		}, $pendingShares), function ($entry) {
			return $entry !== null;
		}));

		return new DataResponse($result);
	}

	/**
	 * Accept a share
	 *
	 * @param string $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSNotFoundException Share not found
	 * @throws OCSException
	 * @throws OCSBadRequestException Share could not be accepted
	 *
	 * 200: Share accepted successfully
	 */
	#[NoAdminRequired]
	public function acceptShare(string $id): DataResponse {
		try {
			$share = $this->getShareById($id);
		} catch (ShareNotFound $e) {
			throw new OCSNotFoundException($this->l->t('Wrong share ID, share does not exist'));
		}

		if (!$this->canAccessShare($share)) {
			throw new OCSNotFoundException($this->l->t('Wrong share ID, share does not exist'));
		}

		try {
			$this->shareManager->acceptShare($share, $this->userId);
		} catch (HintException $e) {
			$code = $e->getCode() === 0 ? 403 : $e->getCode();
			throw new OCSException($e->getHint(), (int)$code);
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSBadRequestException('Failed to accept share.', $e);
		}

		return new DataResponse();
	}

	/**
	 * Does the user have read permission on the share
	 *
	 * @param IShare $share the share to check
	 * @param boolean $checkGroups check groups as well?
	 * @return boolean
	 * @throws NotFoundException
	 *
	 * @suppress PhanUndeclaredClassMethod
	 */
	protected function canAccessShare(IShare $share, bool $checkGroups = true): bool {
		// A file with permissions 0 can't be accessed by us. So Don't show it
		if ($share->getPermissions() === 0) {
			return false;
		}

		// Owner of the file and the sharer of the file can always get share
		if ($share->getShareOwner() === $this->userId
			|| $share->getSharedBy() === $this->userId) {
			return true;
		}

		// If the share is shared with you, you can access it!
		if ($share->getShareType() === IShare::TYPE_USER
			&& $share->getSharedWith() === $this->userId) {
			return true;
		}

		// Have reshare rights on the shared file/folder ?
		// Does the currentUser have access to the shared file?
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$file = $userFolder->getFirstNodeById($share->getNodeId());
		if ($file && $this->shareProviderResharingRights($this->userId, $share, $file)) {
			return true;
		}

		// If in the recipient group, you can see the share
		if ($checkGroups && $share->getShareType() === IShare::TYPE_GROUP) {
			$sharedWith = $this->groupManager->get($share->getSharedWith());
			$user = $this->userManager->get($this->userId);
			if ($user !== null && $sharedWith !== null && $sharedWith->inGroup($user)) {
				return true;
			}
		}

		if ($share->getShareType() === IShare::TYPE_CIRCLE) {
			// TODO: have a sanity check like above?
			return true;
		}

		if ($share->getShareType() === IShare::TYPE_ROOM) {
			try {
				return $this->getRoomShareHelper()->canAccessShare($share, $this->userId);
			} catch (ContainerExceptionInterface $e) {
				return false;
			}
		}

		if ($share->getShareType() === IShare::TYPE_DECK) {
			try {
				return $this->getDeckShareHelper()->canAccessShare($share, $this->userId);
			} catch (ContainerExceptionInterface $e) {
				return false;
			}
		}

		if ($share->getShareType() === IShare::TYPE_SCIENCEMESH) {
			try {
				return $this->getSciencemeshShareHelper()->canAccessShare($share, $this->userId);
			} catch (ContainerExceptionInterface $e) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Does the user have edit permission on the share
	 *
	 * @param IShare $share the share to check
	 * @return boolean
	 */
	protected function canEditShare(IShare $share): bool {
		// A file with permissions 0 can't be accessed by us. So Don't show it
		if ($share->getPermissions() === 0) {
			return false;
		}

		// The owner of the file and the creator of the share
		// can always edit the share
		if ($share->getShareOwner() === $this->userId ||
			$share->getSharedBy() === $this->userId
		) {
			return true;
		}

		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$file = $userFolder->getFirstNodeById($share->getNodeId());
		if ($file?->getMountPoint() instanceof IShareOwnerlessMount && $this->shareProviderResharingRights($this->userId, $share, $file)) {
			return true;
		}

		//! we do NOT support some kind of `admin` in groups.
		//! You cannot edit shares shared to a group you're
		//! a member of if you're not the share owner or the file owner!

		return false;
	}

	/**
	 * Does the user have delete permission on the share
	 *
	 * @param IShare $share the share to check
	 * @return boolean
	 */
	protected function canDeleteShare(IShare $share): bool {
		// A file with permissions 0 can't be accessed by us. So Don't show it
		if ($share->getPermissions() === 0) {
			return false;
		}

		// if the user is the recipient, i can unshare
		// the share with self
		if ($share->getShareType() === IShare::TYPE_USER &&
			$share->getSharedWith() === $this->userId
		) {
			return true;
		}

		// The owner of the file and the creator of the share
		// can always delete the share
		if ($share->getShareOwner() === $this->userId ||
			$share->getSharedBy() === $this->userId
		) {
			return true;
		}

		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$file = $userFolder->getFirstNodeById($share->getNodeId());
		if ($file?->getMountPoint() instanceof IShareOwnerlessMount && $this->shareProviderResharingRights($this->userId, $share, $file)) {
			return true;
		}

		return false;
	}

	/**
	 * Does the user have delete permission on the share
	 * This differs from the canDeleteShare function as it only
	 * remove the share for the current user. It does NOT
	 * completely delete the share but only the mount point.
	 * It can then be restored from the deleted shares section.
	 *
	 * @param IShare $share the share to check
	 * @return boolean
	 *
	 * @suppress PhanUndeclaredClassMethod
	 */
	protected function canDeleteShareFromSelf(IShare $share): bool {
		if ($share->getShareType() !== IShare::TYPE_GROUP &&
			$share->getShareType() !== IShare::TYPE_ROOM &&
			$share->getShareType() !== IShare::TYPE_DECK &&
			$share->getShareType() !== IShare::TYPE_SCIENCEMESH
		) {
			return false;
		}

		if ($share->getShareOwner() === $this->userId ||
			$share->getSharedBy() === $this->userId
		) {
			// Delete the whole share, not just for self
			return false;
		}

		// If in the recipient group, you can delete the share from self
		if ($share->getShareType() === IShare::TYPE_GROUP) {
			$sharedWith = $this->groupManager->get($share->getSharedWith());
			$user = $this->userManager->get($this->userId);
			if ($user !== null && $sharedWith !== null && $sharedWith->inGroup($user)) {
				return true;
			}
		}

		if ($share->getShareType() === IShare::TYPE_ROOM) {
			try {
				return $this->getRoomShareHelper()->canAccessShare($share, $this->userId);
			} catch (ContainerExceptionInterface $e) {
				return false;
			}
		}

		if ($share->getShareType() === IShare::TYPE_DECK) {
			try {
				return $this->getDeckShareHelper()->canAccessShare($share, $this->userId);
			} catch (ContainerExceptionInterface $e) {
				return false;
			}
		}

		if ($share->getShareType() === IShare::TYPE_SCIENCEMESH) {
			try {
				return $this->getSciencemeshShareHelper()->canAccessShare($share, $this->userId);
			} catch (ContainerExceptionInterface $e) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Make sure that the passed date is valid ISO 8601
	 * So YYYY-MM-DD
	 * If not throw an exception
	 *
	 * @param string $expireDate
	 *
	 * @throws \Exception
	 * @return \DateTime
	 */
	private function parseDate(string $expireDate): \DateTime {
		try {
			$date = new \DateTime(trim($expireDate, '"'), $this->dateTimeZone->getTimeZone());
			// Make sure it expires at midnight in owner timezone
			$date->setTime(0, 0, 0);
		} catch (\Exception $e) {
			throw new \Exception($this->l->t('Invalid date. Format must be YYYY-MM-DD'));
		}

		return $date;
	}

	/**
	 * Since we have multiple providers but the OCS Share API v1 does
	 * not support this we need to check all backends.
	 *
	 * @param string $id
	 * @return IShare
	 * @throws ShareNotFound
	 */
	private function getShareById(string $id): IShare {
		$share = null;

		// First check if it is an internal share.
		try {
			$share = $this->shareManager->getShareById('ocinternal:' . $id, $this->userId);
			return $share;
		} catch (ShareNotFound $e) {
			// Do nothing, just try the other share type
		}


		try {
			if ($this->shareManager->shareProviderExists(IShare::TYPE_CIRCLE)) {
				$share = $this->shareManager->getShareById('ocCircleShare:' . $id, $this->userId);
				return $share;
			}
		} catch (ShareNotFound $e) {
			// Do nothing, just try the other share type
		}

		try {
			if ($this->shareManager->shareProviderExists(IShare::TYPE_EMAIL)) {
				$share = $this->shareManager->getShareById('ocMailShare:' . $id, $this->userId);
				return $share;
			}
		} catch (ShareNotFound $e) {
			// Do nothing, just try the other share type
		}

		try {
			$share = $this->shareManager->getShareById('ocRoomShare:' . $id, $this->userId);
			return $share;
		} catch (ShareNotFound $e) {
			// Do nothing, just try the other share type
		}

		try {
			if ($this->shareManager->shareProviderExists(IShare::TYPE_DECK)) {
				$share = $this->shareManager->getShareById('deck:' . $id, $this->userId);
				return $share;
			}
		} catch (ShareNotFound $e) {
			// Do nothing, just try the other share type
		}

		try {
			if ($this->shareManager->shareProviderExists(IShare::TYPE_SCIENCEMESH)) {
				$share = $this->shareManager->getShareById('sciencemesh:' . $id, $this->userId);
				return $share;
			}
		} catch (ShareNotFound $e) {
			// Do nothing, just try the other share type
		}

		if (!$this->shareManager->outgoingServer2ServerSharesAllowed()) {
			throw new ShareNotFound();
		}
		$share = $this->shareManager->getShareById('ocFederatedSharing:' . $id, $this->userId);

		return $share;
	}

	/**
	 * Lock a Node
	 *
	 * @param Node $node
	 * @throws LockedException
	 */
	private function lock(Node $node) {
		$node->lock(ILockingProvider::LOCK_SHARED);
		$this->lockedNode = $node;
	}

	/**
	 * Cleanup the remaining locks
	 * @throws LockedException
	 */
	public function cleanup() {
		if ($this->lockedNode !== null) {
			$this->lockedNode->unlock(ILockingProvider::LOCK_SHARED);
		}
	}

	/**
	 * Returns the helper of ShareAPIController for room shares.
	 *
	 * If the Talk application is not enabled or the helper is not available
	 * a ContainerExceptionInterface is thrown instead.
	 *
	 * @return \OCA\Talk\Share\Helper\ShareAPIController
	 * @throws ContainerExceptionInterface
	 */
	private function getRoomShareHelper() {
		if (!$this->appManager->isEnabledForUser('spreed')) {
			throw new QueryException();
		}

		return $this->serverContainer->get('\OCA\Talk\Share\Helper\ShareAPIController');
	}

	/**
	 * Returns the helper of ShareAPIHelper for deck shares.
	 *
	 * If the Deck application is not enabled or the helper is not available
	 * a ContainerExceptionInterface is thrown instead.
	 *
	 * @return \OCA\Deck\Sharing\ShareAPIHelper
	 * @throws ContainerExceptionInterface
	 */
	private function getDeckShareHelper() {
		if (!$this->appManager->isEnabledForUser('deck')) {
			throw new QueryException();
		}

		return $this->serverContainer->get('\OCA\Deck\Sharing\ShareAPIHelper');
	}

	/**
	 * Returns the helper of ShareAPIHelper for sciencemesh shares.
	 *
	 * If the sciencemesh application is not enabled or the helper is not available
	 * a ContainerExceptionInterface is thrown instead.
	 *
	 * @return \OCA\Deck\Sharing\ShareAPIHelper
	 * @throws ContainerExceptionInterface
	 */
	private function getSciencemeshShareHelper() {
		if (!$this->appManager->isEnabledForUser('sciencemesh')) {
			throw new QueryException();
		}

		return $this->serverContainer->get('\OCA\ScienceMesh\Sharing\ShareAPIHelper');
	}

	/**
	 * @param string $viewer
	 * @param Node $node
	 * @param bool $reShares
	 *
	 * @return IShare[]
	 */
	private function getSharesFromNode(string $viewer, $node, bool $reShares): array {
		$providers = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP,
			IShare::TYPE_LINK,
			IShare::TYPE_EMAIL,
			IShare::TYPE_CIRCLE,
			IShare::TYPE_ROOM,
			IShare::TYPE_DECK,
			IShare::TYPE_SCIENCEMESH
		];

		// Should we assume that the (currentUser) viewer is the owner of the node !?
		$shares = [];
		foreach ($providers as $provider) {
			if (!$this->shareManager->shareProviderExists($provider)) {
				continue;
			}

			$providerShares =
				$this->shareManager->getSharesBy($viewer, $provider, $node, $reShares, -1, 0);
			$shares = array_merge($shares, $providerShares);
		}

		if ($this->shareManager->outgoingServer2ServerSharesAllowed()) {
			$federatedShares = $this->shareManager->getSharesBy(
				$this->userId, IShare::TYPE_REMOTE, $node, $reShares, -1, 0
			);
			$shares = array_merge($shares, $federatedShares);
		}

		if ($this->shareManager->outgoingServer2ServerGroupSharesAllowed()) {
			$federatedShares = $this->shareManager->getSharesBy(
				$this->userId, IShare::TYPE_REMOTE_GROUP, $node, $reShares, -1, 0
			);
			$shares = array_merge($shares, $federatedShares);
		}

		return $shares;
	}


	/**
	 * @param Node $node
	 *
	 * @throws SharingRightsException
	 */
	private function confirmSharingRights(Node $node): void {
		if (!$this->hasResharingRights($this->userId, $node)) {
			throw new SharingRightsException($this->l->t('No sharing rights on this item'));
		}
	}


	/**
	 * @param string $viewer
	 * @param Node $node
	 *
	 * @return bool
	 */
	private function hasResharingRights($viewer, $node): bool {
		if ($viewer === $node->getOwner()->getUID()) {
			return true;
		}

		foreach ([$node, $node->getParent()] as $node) {
			$shares = $this->getSharesFromNode($viewer, $node, true);
			foreach ($shares as $share) {
				try {
					if ($this->shareProviderResharingRights($viewer, $share, $node)) {
						return true;
					}
				} catch (InvalidPathException|NotFoundException $e) {
				}
			}
		}

		return false;
	}


	/**
	 * Returns if we can find resharing rights in an IShare object for a specific user.
	 *
	 * @suppress PhanUndeclaredClassMethod
	 *
	 * @param string $userId
	 * @param IShare $share
	 * @param Node $node
	 *
	 * @return bool
	 * @throws NotFoundException
	 * @throws InvalidPathException
	 */
	private function shareProviderResharingRights(string $userId, IShare $share, $node): bool {
		if ($share->getShareOwner() === $userId) {
			return true;
		}

		// we check that current user have parent resharing rights on the current file
		if ($node !== null && ($node->getPermissions() & Constants::PERMISSION_SHARE) !== 0) {
			return true;
		}

		if ((Constants::PERMISSION_SHARE & $share->getPermissions()) === 0) {
			return false;
		}

		if ($share->getShareType() === IShare::TYPE_USER && $share->getSharedWith() === $userId) {
			return true;
		}

		if ($share->getShareType() === IShare::TYPE_GROUP && $this->groupManager->isInGroup($userId, $share->getSharedWith())) {
			return true;
		}

		if ($share->getShareType() === IShare::TYPE_CIRCLE && Server::get(IAppManager::class)->isEnabledForUser('circles')
			&& class_exists('\OCA\Circles\Api\v1\Circles')) {
			$hasCircleId = (str_ends_with($share->getSharedWith(), ']'));
			$shareWithStart = ($hasCircleId ? strrpos($share->getSharedWith(), '[') + 1 : 0);
			$shareWithLength = ($hasCircleId ? -1 : strpos($share->getSharedWith(), ' '));
			if ($shareWithLength === false) {
				$sharedWith = substr($share->getSharedWith(), $shareWithStart);
			} else {
				$sharedWith = substr($share->getSharedWith(), $shareWithStart, $shareWithLength);
			}
			try {
				$member = Circles::getMember($sharedWith, $userId, 1);
				if ($member->getLevel() >= 4) {
					return true;
				}
				return false;
			} catch (ContainerExceptionInterface $e) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Get all the shares for the current user
	 *
	 * @param Node|null $path
	 * @param boolean $reshares
	 * @return IShare[]
	 */
	private function getAllShares(?Node $path = null, bool $reshares = false) {
		// Get all shares
		$userShares = $this->shareManager->getSharesBy($this->userId, IShare::TYPE_USER, $path, $reshares, -1, 0);
		$groupShares = $this->shareManager->getSharesBy($this->userId, IShare::TYPE_GROUP, $path, $reshares, -1, 0);
		$linkShares = $this->shareManager->getSharesBy($this->userId, IShare::TYPE_LINK, $path, $reshares, -1, 0);

		// EMAIL SHARES
		$mailShares = $this->shareManager->getSharesBy($this->userId, IShare::TYPE_EMAIL, $path, $reshares, -1, 0);

		// TEAM SHARES
		$circleShares = $this->shareManager->getSharesBy($this->userId, IShare::TYPE_CIRCLE, $path, $reshares, -1, 0);

		// TALK SHARES
		$roomShares = $this->shareManager->getSharesBy($this->userId, IShare::TYPE_ROOM, $path, $reshares, -1, 0);

		// DECK SHARES
		$deckShares = $this->shareManager->getSharesBy($this->userId, IShare::TYPE_DECK, $path, $reshares, -1, 0);

		// SCIENCEMESH SHARES
		$sciencemeshShares = $this->shareManager->getSharesBy($this->userId, IShare::TYPE_SCIENCEMESH, $path, $reshares, -1, 0);

		// FEDERATION
		if ($this->shareManager->outgoingServer2ServerSharesAllowed()) {
			$federatedShares = $this->shareManager->getSharesBy($this->userId, IShare::TYPE_REMOTE, $path, $reshares, -1, 0);
		} else {
			$federatedShares = [];
		}
		if ($this->shareManager->outgoingServer2ServerGroupSharesAllowed()) {
			$federatedGroupShares = $this->shareManager->getSharesBy($this->userId, IShare::TYPE_REMOTE_GROUP, $path, $reshares, -1, 0);
		} else {
			$federatedGroupShares = [];
		}

		return array_merge($userShares, $groupShares, $linkShares, $mailShares, $circleShares, $roomShares, $deckShares, $sciencemeshShares, $federatedShares, $federatedGroupShares);
	}


	/**
	 * merging already formatted shares.
	 * We'll make an associative array to easily detect duplicate Ids.
	 * Keys _needs_ to be removed after all shares are retrieved and merged.
	 *
	 * @param array $shares
	 * @param array $newShares
	 */
	private function mergeFormattedShares(array &$shares, array $newShares) {
		foreach ($newShares as $newShare) {
			if (!array_key_exists($newShare['id'], $shares)) {
				$shares[$newShare['id']] = $newShare;
			}
		}
	}

	/**
	 * @param IShare $share
	 * @param string|null $attributesString
	 * @return IShare modified share
	 */
	private function setShareAttributes(IShare $share, ?string $attributesString) {
		$newShareAttributes = null;
		if ($attributesString !== null) {
			$newShareAttributes = $this->shareManager->newShare()->newAttributes();
			$formattedShareAttributes = \json_decode($attributesString, true);
			if (is_array($formattedShareAttributes)) {
				foreach ($formattedShareAttributes as $formattedAttr) {
					$newShareAttributes->setAttribute(
						$formattedAttr['scope'],
						$formattedAttr['key'],
						$formattedAttr['value'],
					);
				}
			} else {
				throw new OCSBadRequestException($this->l->t('Invalid share attributes provided: "%s"', [$attributesString]));
			}
		}
		$share->setAttributes($newShareAttributes);

		return $share;
	}

	private function checkInheritedAttributes(IShare $share): void {
		if (!$share->getSharedBy()) {
			return; // Probably in a test
		}

		$canDownload = false;
		$hideDownload = true;

		$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());
		$nodes = $userFolder->getById($share->getNodeId());
		foreach ($nodes as $node) {
			// Owner always can download it - so allow it and break
			if ($node->getOwner()?->getUID() === $share->getSharedBy()) {
				$canDownload = true;
				$hideDownload = false;
				break;
			}

			if ($node->getStorage()->instanceOfStorage(SharedStorage::class)) {
				$storage = $node->getStorage();
				if ($storage instanceof Wrapper) {
					$storage = $storage->getInstanceOfStorage(SharedStorage::class);
					if ($storage === null) {
						throw new \RuntimeException('Should not happen, instanceOfStorage but getInstanceOfStorage return null');
					}
				} else {
					throw new \RuntimeException('Should not happen, instanceOfStorage but not a wrapper');
				}

				/** @var SharedStorage $storage */
				$originalShare = $storage->getShare();
				$inheritedAttributes = $originalShare->getAttributes();
				// hide if hidden and also the current share enforces hide (can only be false if one share is false or user is owner)
				$hideDownload = $hideDownload && $originalShare->getHideDownload();
				// allow download if already allowed by previous share or when the current share allows downloading
				$canDownload = $canDownload || $inheritedAttributes === null || $inheritedAttributes->getAttribute('permissions', 'download') !== false;
			}
		}

		if ($hideDownload || !$canDownload) {
			$share->setHideDownload(true);

			if (!$canDownload) {
				$attributes = $share->getAttributes() ?? $share->newAttributes();
				$attributes->setAttribute('permissions', 'download', false);
				$share->setAttributes($attributes);
			}
		}
	}

	/**
	 * Send a mail notification again for a share.
	 * The mail_send option must be enabled for the given share.
	 * @param string $id the share ID
	 * @param string $password the password to check against. Necessary for password protected shares.
	 * @throws OCSNotFoundException Share not found
	 * @throws OCSForbiddenException You are not allowed to send mail notifications
	 * @throws OCSBadRequestException Invalid request or wrong password
	 * @throws OCSException Error while sending mail notification
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 *
	 * 200: The email notification was sent successfully
	 */
	#[NoAdminRequired]
	#[UserRateLimit(limit: 5, period: 120)]
	public function sendShareEmail(string $id, $password = ''): DataResponse {
		try {
			$share = $this->getShareById($id);

			if (!$this->canAccessShare($share, false)) {
				throw new OCSNotFoundException($this->l->t('Wrong share ID, share does not exist'));
			}

			if (!$this->canEditShare($share)) {
				throw new OCSForbiddenException($this->l->t('You are not allowed to send mail notifications'));
			}

			// For mail and link shares, the user must be
			// the owner of the share, not only the file owner.
			if ($share->getShareType() === IShare::TYPE_EMAIL
				|| $share->getShareType() === IShare::TYPE_LINK) {
				if ($share->getSharedBy() !== $this->userId) {
					throw new OCSForbiddenException($this->l->t('You are not allowed to send mail notifications'));
				}
			}

			try {
				$provider = $this->factory->getProviderForType($share->getShareType());
				if (!($provider instanceof IShareProviderWithNotification)) {
					throw new OCSBadRequestException($this->l->t('No mail notification configured for this share type'));
				}

				// Circumvent the password encrypted data by
				// setting the password clear. We're not storing
				// the password clear, it is just a temporary
				// object manipulation. The password will stay
				// encrypted in the database.
				if ($share->getPassword() !== null && $share->getPassword() !== $password) {
					if (!$this->shareManager->checkPassword($share, $password)) {
						throw new OCSBadRequestException($this->l->t('Wrong password'));
					}
					$share = $share->setPassword($password);
				}

				$provider->sendMailNotification($share);
				return new DataResponse();
			} catch (Exception $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
				throw new OCSException($this->l->t('Error while sending mail notification'));
			}

		} catch (ShareNotFound $e) {
			throw new OCSNotFoundException($this->l->t('Wrong share ID, share does not exist'));
		}
	}

	/**
	 * Get a unique share token
	 *
	 * @throws OCSException Failed to generate a unique token
	 *
	 * @return DataResponse<Http::STATUS_OK, array{token: string}, array{}>
	 *
	 * 200: Token generated successfully
	 */
	#[ApiRoute(verb: 'GET', url: '/api/v1/token')]
	#[NoAdminRequired]
	public function generateToken(): DataResponse {
		try {
			$token = $this->shareManager->generateToken();
			return new DataResponse([
				'token' => $token,
			]);
		} catch (ShareTokenException $e) {
			throw new OCSException($this->l->t('Failed to generate a unique token'));
		}
	}
}

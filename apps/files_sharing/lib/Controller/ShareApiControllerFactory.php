<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Controller;

use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\ResponseDefinitions;
use OCP\App\IAppManager;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\QueryException;
use OCP\Constants;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IDateTimeZone;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Server;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IShare;
use OCP\UserStatus\IManager as UserStatusManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type Files_SharingShare from ResponseDefinitions
 */
class ShareApiControllerFactory extends OCSController {

	protected string $currentUser;
	public bool $isDeletedShareController = false;

	public function __construct(
		IRequest $request,
		protected ShareManager $shareManager,
		protected string $userId,
		protected IUserManager $userManager,
		protected IGroupManager $groupManager,
		protected IRootFolder $rootFolder,
		protected IAppManager $appManager,
		protected ContainerInterface $serverContainer,
		protected UserStatusManager $userStatusManager,
		protected IPreview $previewManager,
		protected IDateTimeZone $dateTimeZone,
		protected IURLGenerator $urlGenerator,
		protected IL10N $l,
		protected LoggerInterface $logger,
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->currentUser = $userId;
	}

	/**
	 * Convert an IShare to an array for OCS output
	 *
	 * @param \OCP\Share\IShare $share
	 * @param Node|null $recipientNode
	 * @return Files_SharingShare
	 * @throws NotFoundException In case the node can't be resolved.
	 *
	 * @suppress PhanUndeclaredClassMethod
	 */
	public function formatShare(IShare $share, ?Node $recipientNode = null): array {
		$sharedBy = $this->userManager->get($share->getSharedBy());
		$shareOwner = $this->userManager->get($share->getShareOwner());

		$isOwnShare = false;
		if ($shareOwner !== null) {
			$isOwnShare = $shareOwner->getUID() === $this->currentUser;
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

		$userFolder = $this->rootFolder->getUserFolder($this->isDeletedShareController ? $share->getSharedBy() : $this->currentUser);
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
				$roomShareHelper = $this->isDeletedShareController
					? $this->getDeletedRoomShareHelper()
					: $this->getRoomShareHelper();

				/** @var array{share_with_displayname: string, share_with_link: string, share_with?: string, token?: string} $roomShare */
				$roomShare = $roomShareHelper->formatShare($share);
				$result = array_merge($result, $roomShare);
			} catch (QueryException $e) {
			}
		} elseif ($share->getShareType() === IShare::TYPE_DECK) {
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = '';

			try {
				/** @var array{share_with: string, share_with_displayname: string, share_with_link: string} $deckShare */
				$deckShare = $this->getDeckShareHelper()->formatShare($share);
				$result = array_merge($result, $deckShare);
			} catch (QueryException $e) {
			}
		} elseif ($share->getShareType() === IShare::TYPE_SCIENCEMESH) {
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = '';

			try {
				/** @var array{share_with: string, share_with_displayname: string, token: string} $scienceMeshShare */
				$scienceMeshShare = $this->getSciencemeshShareHelper()->formatShare($share);
				$result = array_merge($result, $scienceMeshShare);
			} catch (QueryException $e) {
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
	 * Returns the helper of ShareAPIController for room shares.
	 *
	 * If the Talk application is not enabled or the helper is not available
	 * a QueryException is thrown instead.
	 *
	 * @return \OCA\Talk\Share\Helper\ShareAPIController
	 * @throws QueryException
	 */
	public function getRoomShareHelper() {
		if (!$this->appManager->isEnabledForUser('spreed')) {
			throw new QueryException();
		}

		return $this->serverContainer->get('\OCA\Talk\Share\Helper\ShareAPIController');
	}

	/**
	 * Returns the helper of DeletedShareAPIController for room shares.
	 *
	 * If the Talk application is not enabled or the helper is not available
	 * a QueryException is thrown instead.
	 *
	 * @return \OCA\Talk\Share\Helper\DeletedShareAPIController
	 * @throws QueryException
	 */
	public function getDeletedRoomShareHelper() {
		if (!$this->appManager->isEnabledForUser('spreed')) {
			throw new QueryException();
		}

		return $this->serverContainer->get('\OCA\Talk\Share\Helper\DeletedShareAPIController');
	}

	/**
	 * Returns the helper of DeletedShareAPIHelper for deck shares.
	 *
	 * If the Deck application is not enabled or the helper is not available
	 * a QueryException is thrown instead.
	 *
	 * @return \OCA\Deck\Sharing\ShareAPIHelper
	 * @throws QueryException
	 */
	public function getDeckShareHelper() {
		if (!$this->appManager->isEnabledForUser('deck')) {
			throw new QueryException();
		}

		return $this->serverContainer->get('\OCA\Deck\Sharing\ShareAPIHelper');
	}

	/**
	 * Returns the helper of DeletedShareAPIHelper for sciencemesh shares.
	 *
	 * If the sciencemesh application is not enabled or the helper is not available
	 * a QueryException is thrown instead.
	 *
	 * @return \OCA\Deck\Sharing\ShareAPIHelper
	 * @throws QueryException
	 */
	public function getSciencemeshShareHelper() {
		if (!$this->appManager->isEnabledForUser('sciencemesh')) {
			throw new QueryException();
		}

		return $this->serverContainer->get('\OCA\ScienceMesh\Sharing\ShareAPIHelper');
	}

	/**
	 * Does the user have edit permission on the share
	 *
	 * @param \OCP\Share\IShare $share the share to check
	 * @return boolean
	 */
	public function canEditShare(\OCP\Share\IShare $share): bool {
		// A file with permissions 0 can't be accessed by us. So Don't show it
		if ($share->getPermissions() === 0) {
			return false;
		}

		// The owner of the file and the creator of the share
		// can always edit the share
		if ($share->getShareOwner() === $this->currentUser ||
			$share->getSharedBy() === $this->currentUser
		) {
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
	 * @param \OCP\Share\IShare $share the share to check
	 * @return boolean
	 */
	public function canDeleteShare(\OCP\Share\IShare $share): bool {
		// A file with permissions 0 can't be accessed by us. So Don't show it
		if ($share->getPermissions() === 0) {
			return false;
		}

		// if the user is the recipient, i can unshare
		// the share with self
		if ($share->getShareType() === IShare::TYPE_USER &&
			$share->getSharedWith() === $this->currentUser
		) {
			return true;
		}

		// The owner of the file and the creator of the share
		// can always delete the share
		if ($share->getShareOwner() === $this->currentUser ||
			$share->getSharedBy() === $this->currentUser
		) {
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
	 * @param \OCP\Share\IShare $share the share to check
	 * @return boolean
	 *
	 * @suppress PhanUndeclaredClassMethod
	 */
	public function canDeleteShareFromSelf(\OCP\Share\IShare $share): bool {
		if ($share->getShareType() !== IShare::TYPE_GROUP &&
			$share->getShareType() !== IShare::TYPE_ROOM &&
			$share->getShareType() !== IShare::TYPE_DECK &&
			$share->getShareType() !== IShare::TYPE_SCIENCEMESH
		) {
			return false;
		}

		if ($share->getShareOwner() === $this->currentUser ||
			$share->getSharedBy() === $this->currentUser
		) {
			// Delete the whole share, not just for self
			return false;
		}

		// If in the recipient group, you can delete the share from self
		if ($share->getShareType() === IShare::TYPE_GROUP) {
			$sharedWith = $this->groupManager->get($share->getSharedWith());
			$user = $this->userManager->get($this->currentUser);
			if ($user !== null && $sharedWith !== null && $sharedWith->inGroup($user)) {
				return true;
			}
		}

		if ($share->getShareType() === IShare::TYPE_ROOM) {
			try {
				return $this->getRoomShareHelper()->canAccessShare($share, $this->currentUser);
			} catch (QueryException $e) {
				return false;
			}
		}

		if ($share->getShareType() === IShare::TYPE_DECK) {
			try {
				return $this->getDeckShareHelper()->canAccessShare($share, $this->currentUser);
			} catch (QueryException $e) {
				return false;
			}
		}

		if ($share->getShareType() === IShare::TYPE_SCIENCEMESH) {
			try {
				return $this->getSciencemeshShareHelper()->canAccessShare($share, $this->currentUser);
			} catch (QueryException $e) {
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
	public function parseDate(string $expireDate): \DateTime {
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
	 * retrieve displayName from cache if available (should be used on federated shares)
	 * if not available in cache/lus, try for get from address-book, else returns empty string.
	 *
	 * @param string $userId
	 * @param bool $cacheOnly if true will not reach the lus but will only get data from cache
	 *
	 * @return string
	 */
	public function getCachedFederatedDisplayName(string $userId, bool $cacheOnly = true): string {
		$details = $this->retrieveFederatedDisplayName([$userId], $cacheOnly);
		if (array_key_exists($userId, $details)) {
			return $details[$userId];
		}

		$displayName = $this->getDisplayNameFromAddressBook($userId, 'CLOUD');
		return ($displayName === $userId) ? '' : $displayName;
	}

	/**
	 * Check if one of the users address books knows the exact property, if
	 * not we return the full name.
	 *
	 * @param string $query
	 * @param string $property
	 * @return string
	 */
	public function getDisplayNameFromAddressBook(string $query, string $property): string {
		// FIXME: If we inject the contacts manager it gets initialized before any address books are registered
		try {
			$result = \OC::$server->getContactsManager()->search($query, [$property], [
				'limit' => 1,
				'enumeration' => false,
				'strict_search' => true,
			]);
		} catch (\Exception $e) {
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
	 * @param array $shares
	 * @param array|null $updatedDisplayName
	 *
	 * @return array
	 */
	public function fixMissingDisplayName(array $shares, ?array $updatedDisplayName = null): array {
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
	public function retrieveFederatedDisplayName(array $userIds, bool $cacheOnly = false): array {
		// check if gss is enabled and available
		if (count($userIds) === 0
			|| !$this->appManager->isInstalled('globalsiteselector')
			|| !$this->appManager->isEnabledForUser('globalsiteselector')
			|| !class_exists('\OCA\GlobalSiteSelector\Service\SlaveService')) {
			return [];
		}

		try {
			/** @var \OCA\GlobalSiteSelector\Service\SlaveService $slaveService */
			$slaveService = $this->serverContainer->get('\OCA\GlobalSiteSelector\Service\SlaveService');
		} catch (\Throwable $e) {
			$this->logger->error(
				$e->getMessage(),
				['exception' => $e]
			);
			return [];
		}

		return $slaveService->getUsersDisplayName($userIds, $cacheOnly);
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Controller;

use OCA\Deck\Sharing\ShareAPIHelper;
use OCA\Files_Sharing\ResponseDefinitions;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\QueryException;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Server;
use OCP\Share\Exceptions\GenericShareException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IShare;

/**
 * @psalm-import-type Files_SharingDeletedShare from ResponseDefinitions
 */
class DeletedShareAPIController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private ShareManager $shareManager,
		private ?string $userId,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private IRootFolder $rootFolder,
		private IAppManager $appManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @suppress PhanUndeclaredClassMethod
	 *
	 * @return Files_SharingDeletedShare
	 */
	private function formatShare(IShare $share): array {
		$result = [
			'id' => $share->getFullId(),
			'share_type' => $share->getShareType(),
			'uid_owner' => $share->getSharedBy(),
			'displayname_owner' => $this->userManager->get($share->getSharedBy())->getDisplayName(),
			'permissions' => 0,
			'stime' => $share->getShareTime()->getTimestamp(),
			'parent' => null,
			'expiration' => null,
			'token' => null,
			'uid_file_owner' => $share->getShareOwner(),
			'displayname_file_owner' => $this->userManager->get($share->getShareOwner())->getDisplayName(),
			'path' => $share->getTarget(),
		];
		$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());
		$node = $userFolder->getFirstNodeById($share->getNodeId());
		if (!$node) {
			// fallback to guessing the path
			$node = $userFolder->get($share->getTarget());
			if ($node === null || $share->getTarget() === '') {
				throw new NotFoundException();
			}
		}

		$result['path'] = $userFolder->getRelativePath($node->getPath());
		if ($node instanceof Folder) {
			$result['item_type'] = 'folder';
		} else {
			$result['item_type'] = 'file';
		}
		$result['mimetype'] = $node->getMimetype();
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
			$result['expiration'] = $expiration->format('Y-m-d 00:00:00');
		}

		if ($share->getShareType() === IShare::TYPE_GROUP) {
			$group = $this->groupManager->get($share->getSharedWith());
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = $group !== null ? $group->getDisplayName() : $share->getSharedWith();
		} elseif ($share->getShareType() === IShare::TYPE_ROOM) {
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = '';

			try {
				$result = array_merge($result, $this->getRoomShareHelper()->formatShare($share));
			} catch (QueryException $e) {
			}
		} elseif ($share->getShareType() === IShare::TYPE_DECK) {
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = '';

			try {
				$result = array_merge($result, $this->getDeckShareHelper()->formatShare($share));
			} catch (QueryException $e) {
			}
		} elseif ($share->getShareType() === IShare::TYPE_SCIENCEMESH) {
			$result['share_with'] = $share->getSharedWith();
			$result['share_with_displayname'] = '';

			try {
				$result = array_merge($result, $this->getSciencemeshShareHelper()->formatShare($share));
			} catch (QueryException $e) {
			}
		}

		return $result;
	}

	/**
	 * Get a list of all deleted shares
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Files_SharingDeletedShare>, array{}>
	 *
	 * 200: Deleted shares returned
	 */
	#[NoAdminRequired]
	public function index(): DataResponse {
		$groupShares = $this->shareManager->getDeletedSharedWith($this->userId, IShare::TYPE_GROUP, null, -1, 0);
		$teamShares = $this->shareManager->getDeletedSharedWith($this->userId, IShare::TYPE_CIRCLE, null, -1, 0);
		$roomShares = $this->shareManager->getDeletedSharedWith($this->userId, IShare::TYPE_ROOM, null, -1, 0);
		$deckShares = $this->shareManager->getDeletedSharedWith($this->userId, IShare::TYPE_DECK, null, -1, 0);
		$sciencemeshShares = $this->shareManager->getDeletedSharedWith($this->userId, IShare::TYPE_SCIENCEMESH, null, -1, 0);

		$shares = array_merge($groupShares, $teamShares, $roomShares, $deckShares, $sciencemeshShares);

		$shares = array_values(array_map(function (IShare $share) {
			return $this->formatShare($share);
		}, $shares));

		return new DataResponse($shares);
	}

	/**
	 * Undelete a deleted share
	 *
	 * @param string $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSException
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share undeleted successfully
	 */
	#[NoAdminRequired]
	public function undelete(string $id): DataResponse {
		try {
			$share = $this->shareManager->getShareById($id, $this->userId);
		} catch (ShareNotFound $e) {
			throw new OCSNotFoundException('Share not found');
		}

		if ($share->getPermissions() !== 0) {
			throw new OCSNotFoundException('No deleted share found');
		}

		try {
			$this->shareManager->restoreShare($share, $this->userId);
		} catch (GenericShareException $e) {
			throw new OCSException('Something went wrong');
		}

		return new DataResponse([]);
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
	private function getRoomShareHelper() {
		if (!$this->appManager->isEnabledForUser('spreed')) {
			throw new QueryException();
		}

		return Server::get('\OCA\Talk\Share\Helper\DeletedShareAPIController');
	}

	/**
	 * Returns the helper of DeletedShareAPIHelper for deck shares.
	 *
	 * If the Deck application is not enabled or the helper is not available
	 * a QueryException is thrown instead.
	 *
	 * @return ShareAPIHelper
	 * @throws QueryException
	 */
	private function getDeckShareHelper() {
		if (!$this->appManager->isEnabledForUser('deck')) {
			throw new QueryException();
		}

		return Server::get('\OCA\Deck\Sharing\ShareAPIHelper');
	}

	/**
	 * Returns the helper of DeletedShareAPIHelper for sciencemesh shares.
	 *
	 * If the sciencemesh application is not enabled or the helper is not available
	 * a QueryException is thrown instead.
	 *
	 * @return ShareAPIHelper
	 * @throws QueryException
	 */
	private function getSciencemeshShareHelper() {
		if (!$this->appManager->isEnabledForUser('sciencemesh')) {
			throw new QueryException();
		}

		return Server::get('\OCA\ScienceMesh\Sharing\ShareAPIHelper');
	}
}

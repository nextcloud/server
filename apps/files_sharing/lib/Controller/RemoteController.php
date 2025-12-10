<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Controller;

use OCA\Files_Sharing\External\ExternalShare;
use OCA\Files_Sharing\External\Manager;
use OCA\Files_Sharing\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type Files_SharingRemoteShare from ResponseDefinitions
 * @api
 */
class RemoteController extends OCSController {
	/**
	 * Remote controller constructor.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly Manager $externalManager,
		private readonly LoggerInterface $logger,
		private readonly ?string $userId,
		private readonly IRootFolder $rootFolder,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get list of pending remote shares
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Files_SharingRemoteShare>, array{}>
	 *
	 * 200: Pending remote shares returned
	 */
	#[NoAdminRequired]
	public function getOpenShares(): DataResponse {
		$shares = $this->externalManager->getOpenShares();
		$shares = array_map($this->extendShareInfo(...), $shares);
		return new DataResponse($shares);
	}

	/**
	 * Accept a remote share.
	 *
	 * @param string $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share accepted successfully
	 */
	#[NoAdminRequired]
	public function acceptShare(string $id): DataResponse {
		$externalShare = $this->externalManager->getShare($id);
		if ($externalShare === false) {
			$this->logger->error('Could not accept federated share with id: ' . $id . ' Share not found.', ['app' => 'files_sharing']);
			throw new OCSNotFoundException('Wrong share ID, share does not exist.');
		}

		if (!$this->externalManager->acceptShare($externalShare)) {
			$this->logger->error('Could not accept federated share with id: ' . $id, ['app' => 'files_sharing']);
			throw new OCSNotFoundException('Wrong share ID, share does not exist.');
		}

		return new DataResponse();
	}

	/**
	 * Decline a remote share.
	 *
	 * @param string $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share declined successfully
	 */
	#[NoAdminRequired]
	public function declineShare(string $id): DataResponse {
		$externalShare = $this->externalManager->getShare($id);
		if ($externalShare === false) {
			$this->logger->error('Could not decline federated share with id: ' . $id . ' Share not found.', ['app' => 'files_sharing']);
			throw new OCSNotFoundException('Wrong share ID, share does not exist.');
		}

		if (!$this->externalManager->declineShare($externalShare)) {
			$this->logger->error('Could not decline federated share with id: ' . $id, ['app' => 'files_sharing']);
			throw new OCSNotFoundException('Wrong share ID, share does not exist.');
		}

		return new DataResponse();
	}

	/**
	 * @param ExternalShare $share Share with info from the share_external table
	 * @return Files_SharingRemoteShare Enriched share info with data from the filecache
	 */
	private function extendShareInfo(ExternalShare $share): array {
		$shareData = $share->jsonSerialize();

		$shareData['parent'] = $shareData['parent'] !== '-1' ? $shareData['parent'] : null;
		$userFolder = $this->rootFolder->getUserFolder($this->userId);

		try {
			$mountPointNode = $userFolder->get($share->getMountpoint());
		} catch (\Exception) {
			return $shareData;
		}

		$shareData['mimetype'] = $mountPointNode->getMimetype();
		$shareData['mtime'] = $mountPointNode->getMTime();
		$shareData['permissions'] = $mountPointNode->getPermissions();
		$shareData['type'] = $mountPointNode->getType();
		$shareData['file_id'] = $mountPointNode->getId();

		return $shareData;
	}

	/**
	 * Get a list of accepted remote shares
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Files_SharingRemoteShare>, array{}>
	 *
	 * 200: Accepted remote shares returned
	 */
	#[NoAdminRequired]
	public function getShares(): DataResponse {
		$shares = $this->externalManager->getAcceptedShares();
		$shares = array_map(fn (ExternalShare $share) => $this->extendShareInfo($share), $shares);
		return new DataResponse($shares);
	}

	/**
	 * Get info of a remote share
	 *
	 * @param string $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, Files_SharingRemoteShare, array{}>
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share returned
	 */
	#[NoAdminRequired]
	public function getShare(string $id): DataResponse {
		$shareInfo = $this->externalManager->getShare($id);

		if ($shareInfo === false) {
			throw new OCSNotFoundException('share does not exist');
		} else {
			$shareInfo = $this->extendShareInfo($shareInfo);
			return new DataResponse($shareInfo);
		}
	}

	/**
	 * Unshare a remote share
	 *
	 * @param string $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSNotFoundException Share not found
	 * @throws OCSForbiddenException Unsharing is not possible
	 *
	 * 200: Share unshared successfully
	 */
	#[NoAdminRequired]
	public function unshare(string $id): DataResponse {
		$shareInfo = $this->externalManager->getShare($id);

		if ($shareInfo === false) {
			throw new OCSNotFoundException('Share does not exist');
		}

		$mountPoint = '/' . $this->userId . '/files' . $shareInfo->getMountpoint();

		if ($this->externalManager->removeShare($mountPoint) === true) {
			return new DataResponse();
		} else {
			throw new OCSForbiddenException('Could not unshare');
		}
	}
}

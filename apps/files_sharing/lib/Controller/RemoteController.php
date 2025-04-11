<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Controller;

use OC\Files\View;
use OCA\Files_Sharing\External\Manager;
use OCA\Files_Sharing\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type Files_SharingRemoteShare from ResponseDefinitions
 */
class RemoteController extends OCSController {
	/**
	 * Remote constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param Manager $externalManager
	 */
	public function __construct(
		$appName,
		IRequest $request,
		private Manager $externalManager,
		private LoggerInterface $logger,
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
	public function getOpenShares() {
		return new DataResponse($this->externalManager->getOpenShares());
	}

	/**
	 * Accept a remote share
	 *
	 * @param int $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share accepted successfully
	 */
	#[NoAdminRequired]
	public function acceptShare($id) {
		if ($this->externalManager->acceptShare($id)) {
			return new DataResponse();
		}

		$this->logger->error('Could not accept federated share with id: ' . $id,
			['app' => 'files_sharing']);

		throw new OCSNotFoundException('wrong share ID, share does not exist.');
	}

	/**
	 * Decline a remote share
	 *
	 * @param int $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share declined successfully
	 */
	#[NoAdminRequired]
	public function declineShare($id) {
		if ($this->externalManager->declineShare($id)) {
			return new DataResponse();
		}

		// Make sure the user has no notification for something that does not exist anymore.
		$this->externalManager->processNotification($id);

		throw new OCSNotFoundException('wrong share ID, share does not exist.');
	}

	/**
	 * @param array $share Share with info from the share_external table
	 * @return array enriched share info with data from the filecache
	 */
	private static function extendShareInfo($share) {
		$view = new View('/' . \OC_User::getUser() . '/files/');
		$info = $view->getFileInfo($share['mountpoint']);

		if ($info === false) {
			return $share;
		}

		$share['mimetype'] = $info->getMimetype();
		$share['mtime'] = $info->getMTime();
		$share['permissions'] = $info->getPermissions();
		$share['type'] = $info->getType();
		$share['file_id'] = $info->getId();

		return $share;
	}

	/**
	 * Get a list of accepted remote shares
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Files_SharingRemoteShare>, array{}>
	 *
	 * 200: Accepted remote shares returned
	 */
	#[NoAdminRequired]
	public function getShares() {
		$shares = $this->externalManager->getAcceptedShares();
		$shares = array_map(self::extendShareInfo(...), $shares);

		return new DataResponse($shares);
	}

	/**
	 * Get info of a remote share
	 *
	 * @param int $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, Files_SharingRemoteShare, array{}>
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share returned
	 */
	#[NoAdminRequired]
	public function getShare($id) {
		$shareInfo = $this->externalManager->getShare($id);

		if ($shareInfo === false) {
			throw new OCSNotFoundException('share does not exist');
		} else {
			$shareInfo = self::extendShareInfo($shareInfo);
			return new DataResponse($shareInfo);
		}
	}

	/**
	 * Unshare a remote share
	 *
	 * @param int $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSNotFoundException Share not found
	 * @throws OCSForbiddenException Unsharing is not possible
	 *
	 * 200: Share unshared successfully
	 */
	#[NoAdminRequired]
	public function unshare($id) {
		$shareInfo = $this->externalManager->getShare($id);

		if ($shareInfo === false) {
			throw new OCSNotFoundException('Share does not exist');
		}

		$mountPoint = '/' . \OC_User::getUser() . '/files' . $shareInfo['mountpoint'];

		if ($this->externalManager->removeShare($mountPoint) === true) {
			return new DataResponse();
		} else {
			throw new OCSForbiddenException('Could not unshare');
		}
	}
}

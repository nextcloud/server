<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Sharing\Controller;

use OCA\Files_Sharing\External\Manager;
use OCA\Files_Sharing\ResponseDefinitions;
use OCP\AppFramework\Http;
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
	 * @NoAdminRequired
	 *
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
	 * @NoAdminRequired
	 *
	 * Get list of pending remote shares
	 *
	 * @return DataResponse<Http::STATUS_OK, Files_SharingRemoteShare[], array{}>
	 *
	 * 200: Pending remote shares returned
	 */
	public function getOpenShares() {
		return new DataResponse($this->externalManager->getOpenShares());
	}

	/**
	 * @NoAdminRequired
	 *
	 * Accept a remote share
	 *
	 * @param int $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share accepted successfully
	 */
	public function acceptShare($id) {
		if ($this->externalManager->acceptShare($id)) {
			return new DataResponse();
		}

		$this->logger->error('Could not accept federated share with id: ' . $id,
			['app' => 'files_sharing']);

		throw new OCSNotFoundException('wrong share ID, share does not exist.');
	}

	/**
	 * @NoAdminRequired
	 *
	 * Decline a remote share
	 *
	 * @param int $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share declined successfully
	 */
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
		$view = new \OC\Files\View('/' . \OC_User::getUser() . '/files/');
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
	 * @NoAdminRequired
	 *
	 * Get a list of accepted remote shares
	 *
	 * @return DataResponse<Http::STATUS_OK, Files_SharingRemoteShare[], array{}>
	 *
	 * 200: Accepted remote shares returned
	 */
	public function getShares() {
		$shares = $this->externalManager->getAcceptedShares();
		$shares = array_map('self::extendShareInfo', $shares);

		return new DataResponse($shares);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get info of a remote share
	 *
	 * @param int $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, Files_SharingRemoteShare, array{}>
	 * @throws OCSNotFoundException Share not found
	 *
	 * 200: Share returned
	 */
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
	 * @NoAdminRequired
	 *
	 * Unshare a remote share
	 *
	 * @param int $id ID of the share
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws OCSNotFoundException Share not found
	 * @throws OCSForbiddenException Unsharing is not possible
	 *
	 * 200: Share unshared successfully
	 */
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

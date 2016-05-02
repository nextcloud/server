<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Files_Sharing\Controllers;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\ILogger;
use OCP\IRequest;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

/**
 * Class ShareInfo
 *
 * @package OCA\Files_Sharing\Controllers
 */
class ShareInfo extends Controller {
	/** @var ILogger */
	private $logger;
	/** @var IManager */
	private $shareManager;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param IManager $shareManager
	 */
	public function __construct($appName,
								IRequest $request,
								ILogger $logger,
								IManager $shareManager) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->shareManager = $shareManager;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $t The token of the share
	 * @param string $password The password of the share
	 * @param string $dir The dir in the share
	 * @return Response
	 */
	public function shareInfo($t, $password = null, $dir = null) {
		\OC_User::setIncognitoMode(true);

		// Try to get the share else 404
		try {
			$share = $this->shareManager->getShareByToken($t);
		} catch (ShareNotFound $e) {
			$response = new Response();
			$response->setStatus(Http::STATUS_NOT_FOUND);
			return $response;
		}

		// If this is a link share with password validate.
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_LINK && $share->getPassword()) {
			// 403 if password is invalid
			if (!$this->shareManager->checkPassword($share, $password)) {
				$response = new Response();
				$response->setStatus(Http::STATUS_FORBIDDEN);
				return $response;
			}
		}

		try {
			$root = $share->getNode()->get($dir);
		} catch (NotFoundException $e) {
			$response = new Response();
			$response->setStatus(Http::STATUS_NOT_FOUND);
			return $response;
		}

		$result = \OCA\Files\Helper::formatFileInfo($root->getFileInfo());
		$result['mtime'] = $result['mtime'] / 1000;
		$result['permissions'] = (int)$result['permissions'] & $share->getPermissions();

		if ($root instanceof Folder) {
			/** @var Folder $root */
			$result['children'] = $this->getChildInfo($root, $share->getPermissions());
		}

		return new Http\JSONResponse(['data' => $result, 'status' => 'success']);
	}

	/**
	 * @param Folder $folder
	 * @param int $sharePermissions
	 * @return array
	 */
	private function getChildInfo($folder, $sharePermissions) {
		$children = $folder->getDirectoryListing();
		$result = [];

		foreach ($children as $child) {
			$formatted = \OCA\Files\Helper::formatFileInfo($child->getFileInfo());
			if ($child instanceof Folder) {
				/** @var Folder $child */
				$formatted['children'] = $this->getChildInfo($child, $sharePermissions);
			}
			$formatted['mtime'] = $formatted['mtime'] / 1000;
			$formatted['permissions'] = $sharePermissions & (int)$formatted['permissions'];
			$result[] = $formatted;
		}

		return $result;
	}
}

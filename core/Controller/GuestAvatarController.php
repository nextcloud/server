<?php
/**
 * @copyright Copyright (c) 2019, Michael Weimann <mail@michael-weimann.eu>
 *
 * @author Michael Weimann <mail@michael-weimann.eu>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\IAvatarManager;
use OCP\ILogger;
use OCP\IRequest;

/**
 * This controller handles guest avatar requests.
 */
class GuestAvatarController extends Controller {

	/**
	 * @var ILogger
	 */
	private $logger;

	/**
	 * @var IAvatarManager
	 */
	private $avatarManager;

	/**
	 * GuestAvatarController constructor.
	 *
	 * @param $appName
	 * @param IRequest $request
	 * @param IAvatarManager $avatarManager
	 * @param ILogger $logger
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IAvatarManager $avatarManager,
		ILogger $logger
	) {
		parent::__construct($appName, $request);
		$this->avatarManager = $avatarManager;
		$this->logger = $logger;
	}

	/**
	 * Returns a guest avatar image response.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $guestName The guest name, e.g. "Albert"
	 * @param string $size The desired avatar size, e.g. 64 for 64x64px
	 * @return FileDisplayResponse|Http\Response
	 */
	public function getAvatar($guestName, $size) {
		$size = (int) $size;

		// min/max size
		if ($size > 2048) {
			$size = 2048;
		} elseif ($size <= 0) {
			$size = 64;
		}

		try {
			$avatar = $this->avatarManager->getGuestAvatar($guestName);
			$avatarFile = $avatar->getFile($size);

			$resp = new FileDisplayResponse(
				$avatarFile,
				$avatar->isCustomAvatar() ? Http::STATUS_OK : Http::STATUS_CREATED,
				['Content-Type' => $avatarFile->getMimeType()]
			);
		} catch (\Exception $e) {
			$this->logger->error('error while creating guest avatar', [
				'err' => $e,
			]);
			$resp = new Http\Response();
			$resp->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
			return $resp;
		}

		// Cache for 30 minutes
		$resp->cacheFor(1800);
		return $resp;
	}
}

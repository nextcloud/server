<?php
/**
 * @copyright Copyright (c) 2019, Michael Weimann <mail@michael-weimann.eu>
 *
 * @author Michael Weimann <mail@michael-weimann.eu>
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\Response;
use OCP\IAvatarManager;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * This controller handles guest avatar requests.
 */
class GuestAvatarController extends Controller {
	/**
	 * GuestAvatarController constructor.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private IAvatarManager $avatarManager,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Returns a guest avatar image response
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $guestName The guest name, e.g. "Albert"
	 * @param string $size The desired avatar size, e.g. 64 for 64x64px
	 * @param bool|null $darkTheme Return dark avatar
	 * @return FileDisplayResponse<Http::STATUS_OK|Http::STATUS_CREATED, array{Content-Type: string, X-NC-IsCustomAvatar: int}>|Response<Http::STATUS_INTERNAL_SERVER_ERROR, array{}>
	 *
	 * 200: Custom avatar returned
	 * 201: Avatar returned
	 */
	public function getAvatar(string $guestName, string $size, ?bool $darkTheme = false) {
		$size = (int) $size;
		$darkTheme = $darkTheme ?? false;

		if ($size <= 64) {
			if ($size !== 64) {
				$this->logger->debug('Avatar requested in deprecated size ' . $size);
			}
			$size = 64;
		} else {
			if ($size !== 512) {
				$this->logger->debug('Avatar requested in deprecated size ' . $size);
			}
			$size = 512;
		}

		try {
			$avatar = $this->avatarManager->getGuestAvatar($guestName);
			$avatarFile = $avatar->getFile($size, $darkTheme);

			$resp = new FileDisplayResponse(
				$avatarFile,
				$avatar->isCustomAvatar() ? Http::STATUS_OK : Http::STATUS_CREATED,
				['Content-Type' => $avatarFile->getMimeType(), 'X-NC-IsCustomAvatar' => (int)$avatar->isCustomAvatar()]
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
		$resp->cacheFor(1800, false, true);
		return $resp;
	}

	/**
	 * Returns a dark guest avatar image response
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $guestName The guest name, e.g. "Albert"
	 * @param string $size The desired avatar size, e.g. 64 for 64x64px
	 * @return FileDisplayResponse<Http::STATUS_OK|Http::STATUS_CREATED, array{Content-Type: string, X-NC-IsCustomAvatar: int}>|Response<Http::STATUS_INTERNAL_SERVER_ERROR, array{}>
	 *
	 * 200: Custom avatar returned
	 * 201: Avatar returned
	 */
	public function getAvatarDark(string $guestName, string $size) {
		return $this->getAvatar($guestName, $size, true);
	}
}

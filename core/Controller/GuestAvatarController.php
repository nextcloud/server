<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
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
	 * @param string $guestName The guest name, e.g. "Albert"
	 * @param 64|512 $size The desired avatar size, e.g. 64 for 64x64px
	 * @param bool|null $darkTheme Return dark avatar
	 * @return FileDisplayResponse<Http::STATUS_OK|Http::STATUS_CREATED, array{Content-Type: string, X-NC-IsCustomAvatar: int}>|Response<Http::STATUS_INTERNAL_SERVER_ERROR, array{}>
	 *
	 * 200: Custom avatar returned
	 * 201: Avatar returned
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/avatar/guest/{guestName}/{size}')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getAvatar(string $guestName, int $size, ?bool $darkTheme = false) {
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
	 * @param string $guestName The guest name, e.g. "Albert"
	 * @param 64|512 $size The desired avatar size, e.g. 64 for 64x64px
	 * @return FileDisplayResponse<Http::STATUS_OK|Http::STATUS_CREATED, array{Content-Type: string, X-NC-IsCustomAvatar: int}>|Response<Http::STATUS_INTERNAL_SERVER_ERROR, array{}>
	 *
	 * 200: Custom avatar returned
	 * 201: Avatar returned
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/avatar/guest/{guestName}/{size}/dark')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getAvatarDark(string $guestName, int $size) {
		return $this->getAvatar($guestName, $size, true);
	}
}

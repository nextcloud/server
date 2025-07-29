<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Controller;

use OCA\Files_Versions\Versions\IVersionManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Preview\IMimeIconProvider;

#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
class PreviewController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IRootFolder $rootFolder,
		private IUserSession $userSession,
		private IVersionManager $versionManager,
		private IPreview $previewManager,
		private IMimeIconProvider $mimeIconProvider,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get the preview for a file version
	 *
	 * @param string $file Path of the file
	 * @param int $x Width of the preview
	 * @param int $y Height of the preview
	 * @param string $version Version of the file to get the preview for
	 * @param bool $mimeFallback Whether to fallback to the mime icon if no preview is available
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND, list<empty>, array{}>|RedirectResponse<Http::STATUS_SEE_OTHER, array{}>
	 *
	 * 200: Preview returned
	 * 303: Redirect to the mime icon url if mimeFallback is true
	 * 400: Getting preview is not possible
	 * 404: Preview not found
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getPreview(
		string $file = '',
		int $x = 44,
		int $y = 44,
		string $version = '',
		bool $mimeFallback = false,
	) {
		if ($file === '' || $version === '' || $x === 0 || $y === 0) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$versionFile = null;
		try {
			$user = $this->userSession->getUser();
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$file = $userFolder->get($file);
			$versionFile = $this->versionManager->getVersionFile($user, $file, $version);
			$preview = $this->previewManager->getPreview($versionFile, $x, $y, true, IPreview::MODE_FILL, $versionFile->getMimetype());
			$response = new FileDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => $preview->getMimeType()]);
			$response->cacheFor(3600 * 24, false, true);
			return $response;
		} catch (NotFoundException $e) {
			// If we have no preview enabled, we can redirect to the mime icon if any
			if ($mimeFallback && $versionFile !== null) {
				$url = $this->mimeIconProvider->getMimeIconUrl($versionFile->getMimeType());
				if ($url !== null) {
					return new RedirectResponse($url);
				}
			}

			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
	}
}

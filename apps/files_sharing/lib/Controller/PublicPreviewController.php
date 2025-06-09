<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\PublicShareController;
use OCP\Constants;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ISession;
use OCP\Preview\IMimeIconProvider;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IShare;

class PublicPreviewController extends PublicShareController {

	/** @var IShare */
	private $share;

	public function __construct(
		string $appName,
		IRequest $request,
		private ShareManager $shareManager,
		ISession $session,
		private IPreview $previewManager,
		private IMimeIconProvider $mimeIconProvider,
	) {
		parent::__construct($appName, $request, $session);
	}

	protected function getPasswordHash(): ?string {
		return $this->share->getPassword();
	}

	public function isValidToken(): bool {
		try {
			$this->share = $this->shareManager->getShareByToken($this->getToken());
			return true;
		} catch (ShareNotFound $e) {
			return false;
		}
	}

	protected function isPasswordProtected(): bool {
		return $this->share->getPassword() !== null;
	}


	/**
	 * Get a preview for a shared file
	 *
	 * @param string $token Token of the share
	 * @param string $file File in the share
	 * @param int $x Width of the preview
	 * @param int $y Height of the preview
	 * @param bool $a Whether to not crop the preview
	 * @param bool $mimeFallback Whether to fallback to the mime icon if no preview is available
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, list<empty>, array{}>|RedirectResponse<Http::STATUS_SEE_OTHER, array{}>
	 *
	 * 200: Preview returned
	 * 303: Redirect to the mime icon url if mimeFallback is true
	 * 400: Getting preview is not possible
	 * 403: Getting preview is not allowed
	 * 404: Share or preview not found
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getPreview(
		string $token,
		string $file = '',
		int $x = 32,
		int $y = 32,
		$a = false,
		bool $mimeFallback = false,
	) {
		$cacheForSeconds = 60 * 60 * 24; // 1 day

		if ($token === '' || $x === 0 || $y === 0) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		try {
			$share = $this->shareManager->getShareByToken($token);
		} catch (ShareNotFound $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		if (($share->getPermissions() & Constants::PERMISSION_READ) === 0) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$attributes = $share->getAttributes();
		// Only explicitly set to false will forbid the download!
		$downloadForbidden = $attributes?->getAttribute('permissions', 'download') === false;
		// Is this header is set it means our UI is doing a preview for no-download shares
		// we check a header so we at least prevent people from using the link directly (obfuscation)
		$isPublicPreview = $this->request->getHeader('x-nc-preview') === 'true';

		if ($isPublicPreview && $downloadForbidden) {
			// Only cache for 15 minutes on public preview requests to quickly remove from cache
			$cacheForSeconds = 15 * 60;
		} elseif ($downloadForbidden) {
			// This is not a public share preview so we only allow a preview if download permissions are granted
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		try {
			$node = $share->getNode();
			if ($node instanceof Folder) {
				$file = $node->get($file);
			} else {
				$file = $node;
			}

			$f = $this->previewManager->getPreview($file, $x, $y, !$a);
			$response = new FileDisplayResponse($f, Http::STATUS_OK, ['Content-Type' => $f->getMimeType()]);
			$response->cacheFor($cacheForSeconds);
			return $response;
		} catch (NotFoundException $e) {
			// If we have no preview enabled, we can redirect to the mime icon if any
			if ($mimeFallback) {
				if ($url = $this->mimeIconProvider->getMimeIconUrl($file->getMimeType())) {
					return new RedirectResponse($url);
				}
			}
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * @NoSameSiteCookieRequired
	 *
	 * Get a direct link preview for a shared file
	 *
	 * @param string $token Token of the share
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, list<empty>, array{}>
	 *
	 * 200: Preview returned
	 * 400: Getting preview is not possible
	 * 403: Getting preview is not allowed
	 * 404: Share or preview not found
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function directLink(string $token) {
		// No token no image
		if ($token === '') {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		// No share no image
		try {
			$share = $this->shareManager->getShareByToken($token);
		} catch (ShareNotFound $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		// No permissions no image
		if (($share->getPermissions() & Constants::PERMISSION_READ) === 0) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		// Password protected shares have no direct link!
		if ($share->getPassword() !== null) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$attributes = $share->getAttributes();
		if ($attributes !== null && $attributes->getAttribute('permissions', 'download') === false) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		try {
			$node = $share->getNode();
			if ($node instanceof Folder) {
				// Direct link only works for single files
				return new DataResponse([], Http::STATUS_BAD_REQUEST);
			}

			$f = $this->previewManager->getPreview($node, -1, -1, false);
			$response = new FileDisplayResponse($f, Http::STATUS_OK, ['Content-Type' => $f->getMimeType()]);
			$response->cacheFor(3600 * 24);
			return $response;
		} catch (NotFoundException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
	}
}

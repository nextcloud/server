<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OCA\Files_Sharing\SharedStorage;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IPreview;
use OCP\IRequest;
use OCP\Preview\IMimeIconProvider;

class PreviewController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IPreview $preview,
		private IRootFolder $root,
		private ?string $userId,
		private IMimeIconProvider $mimeIconProvider,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get a preview by file path
	 *
	 * @param string $file Path of the file
	 * @param int $x Width of the preview. A width of -1 will use the original image width.
	 * @param int $y Height of the preview. A height of -1 will use the original image height.
	 * @param bool $a Preserve the aspect ratio
	 * @param bool $forceIcon Force returning an icon
	 * @param 'fill'|'cover' $mode How to crop the image
	 * @param bool $mimeFallback Whether to fallback to the mime icon if no preview is available
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, array<empty>, array{}>|RedirectResponse<Http::STATUS_SEE_OTHER, array{}>
	 *
	 * 200: Preview returned
	 * 303: Redirect to the mime icon url if mimeFallback is true
	 * 400: Getting preview is not possible
	 * 403: Getting preview is not allowed
	 * 404: Preview not found
	 */
	#[FrontpageRoute(verb: 'GET', url: '/core/preview.png')]
	public function getPreview(
		string $file = '',
		int $x = 32,
		int $y = 32,
		bool $a = false,
		bool $forceIcon = true,
		string $mode = 'fill',
		bool $mimeFallback = false): Http\Response {
		if ($file === '' || $x === 0 || $y === 0) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		try {
			$userFolder = $this->root->getUserFolder($this->userId);
			$node = $userFolder->get($file);
		} catch (NotFoundException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return $this->fetchPreview($node, $x, $y, $a, $forceIcon, $mode, $mimeFallback);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get a preview by file ID
	 *
	 * @param int $fileId ID of the file
	 * @param int $x Width of the preview. A width of -1 will use the original image width.
	 * @param int $y Height of the preview. A height of -1 will use the original image height.
	 * @param bool $a Preserve the aspect ratio
	 * @param bool $forceIcon Force returning an icon
	 * @param 'fill'|'cover' $mode How to crop the image
	 * @param bool $mimeFallback Whether to fallback to the mime icon if no preview is available
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, array<empty>, array{}>|RedirectResponse<Http::STATUS_SEE_OTHER, array{}>
	 *
	 * 200: Preview returned
	 * 303: Redirect to the mime icon url if mimeFallback is true
	 * 400: Getting preview is not possible
	 * 403: Getting preview is not allowed
	 * 404: Preview not found
	 */
	#[FrontpageRoute(verb: 'GET', url: '/core/preview')]
	public function getPreviewByFileId(
		int $fileId = -1,
		int $x = 32,
		int $y = 32,
		bool $a = false,
		bool $forceIcon = true,
		string $mode = 'fill',
		bool $mimeFallback = false) {
		if ($fileId === -1 || $x === 0 || $y === 0) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$userFolder = $this->root->getUserFolder($this->userId);
		$node = $userFolder->getFirstNodeById($fileId);

		if (!$node) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return $this->fetchPreview($node, $x, $y, $a, $forceIcon, $mode, $mimeFallback);
	}

	/**
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, array<empty>, array{}>|RedirectResponse<Http::STATUS_SEE_OTHER, array{}>
	 */
	private function fetchPreview(
		Node $node,
		int $x,
		int $y,
		bool $a,
		bool $forceIcon,
		string $mode,
		bool $mimeFallback = false) : Http\Response {
		if (!($node instanceof File) || (!$forceIcon && !$this->preview->isAvailable($node))) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		if (!$node->isReadable()) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$storage = $node->getStorage();
		if ($storage->instanceOfStorage(SharedStorage::class)) {
			/** @var SharedStorage $storage */
			$share = $storage->getShare();
			$attributes = $share->getAttributes();
			if ($attributes !== null && $attributes->getAttribute('permissions', 'download') === false) {
				return new DataResponse([], Http::STATUS_FORBIDDEN);
			}
		}

		try {
			$f = $this->preview->getPreview($node, $x, $y, !$a, $mode);
			$response = new FileDisplayResponse($f, Http::STATUS_OK, [
				'Content-Type' => $f->getMimeType(),
			]);
			$response->cacheFor(3600 * 24, false, true);
			return $response;
		} catch (NotFoundException $e) {
			// If we have no preview enabled, we can redirect to the mime icon if any
			if ($mimeFallback) {
				if ($url = $this->mimeIconProvider->getMimeIconUrl($node->getMimeType())) {
					return new RedirectResponse($url);
				}
			}

			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
	}
}

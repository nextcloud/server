<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OCA\Files_Sharing\SharedStorage;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
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
	 * @param int $x Width of the preview
	 * @param int $y Height of the preview
	 * @param bool $a Whether to not crop the preview
	 * @param bool $forceIcon Force returning an icon
	 * @param string $mode How to crop the image
	 * @param bool $mimeFallback Whether to fallback to the mime icon if no preview is available
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, array<empty>, array{}>|RedirectResponse<Http::STATUS_SEE_OTHER, array{}>
	 *
	 * 200: Preview returned
	 * 303: Redirect to the mime icon url if mimeFallback is true
	 * 400: Getting preview is not possible
	 * 403: Getting preview is not allowed
	 * 404: Preview not found
	 */
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
	 * @param int $x Width of the preview
	 * @param int $y Height of the preview
	 * @param bool $a Whether to not crop the preview
	 * @param bool $forceIcon Force returning an icon
	 * @param string $mode How to crop the image
	 * @param bool $mimeFallback Whether to fallback to the mime icon if no preview is available
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, array<empty>, array{}>|RedirectResponse<Http::STATUS_SEE_OTHER, array{}>
	 *
	 * 200: Preview returned
	 * 303: Redirect to the mime icon url if mimeFallback is true
	 * 400: Getting preview is not possible
	 * 403: Getting preview is not allowed
	 * 404: Preview not found
	 */
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
		$nodes = $userFolder->getById($fileId);

		if (\count($nodes) === 0) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$node = array_pop($nodes);

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

		if ($node->getId() <= 0) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
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

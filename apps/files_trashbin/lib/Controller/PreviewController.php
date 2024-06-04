<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Controller;

use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IUserSession;

class PreviewController extends Controller {
	/** @var IRootFolder */
	private $rootFolder;

	/** @var ITrashManager */
	private $trashManager;

	/** @var IUserSession */
	private $userSession;

	/** @var IMimeTypeDetector */
	private $mimeTypeDetector;

	/** @var IPreview */
	private $previewManager;

	/** @var ITimeFactory */
	private $time;

	public function __construct(
		string $appName,
		IRequest $request,
		IRootFolder $rootFolder,
		ITrashManager $trashManager,
		IUserSession $userSession,
		IMimeTypeDetector $mimeTypeDetector,
		IPreview $previewManager,
		ITimeFactory $time
	) {
		parent::__construct($appName, $request);

		$this->trashManager = $trashManager;
		$this->rootFolder = $rootFolder;
		$this->userSession = $userSession;
		$this->mimeTypeDetector = $mimeTypeDetector;
		$this->previewManager = $previewManager;
		$this->time = $time;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get the preview for a file
	 *
	 * @param int $fileId ID of the file
	 * @param int $x Width of the preview
	 * @param int $y Height of the preview
	 * @param bool $a Whether to not crop the preview
	 *
	 * @return Http\FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND, array<empty>, array{}>
	 *
	 * 200: Preview returned
	 * 400: Getting preview is not possible
	 * 404: Preview not found
	 */
	public function getPreview(
		int $fileId = -1,
		int $x = 32,
		int $y = 32,
		bool $a = false,
	) {
		if ($fileId === -1 || $x === 0 || $y === 0) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		try {
			$file = $this->trashManager->getTrashNodeById($this->userSession->getUser(), $fileId);
			if ($file === null) {
				return new DataResponse([], Http::STATUS_NOT_FOUND);
			}
			if ($file instanceof Folder) {
				return new DataResponse([], Http::STATUS_BAD_REQUEST);
			}

			$pathParts = pathinfo($file->getName());
			$extension = $pathParts['extension'] ?? '';
			$fileName = $pathParts['filename'];
			/*
			 * Files in the root of the trashbin are timetamped.
			 * So we have to strip that in order to properly detect the mimetype of the file.
			 */
			if (preg_match('/d\d+/', $extension)) {
				$mimeType = $this->mimeTypeDetector->detectPath($fileName);
			} else {
				$mimeType = $this->mimeTypeDetector->detectPath($file->getName());
			}

			$f = $this->previewManager->getPreview($file, $x, $y, !$a, IPreview::MODE_FILL, $mimeType);
			$response = new Http\FileDisplayResponse($f, Http::STATUS_OK, ['Content-Type' => $f->getMimeType()]);

			// Cache previews for 24H
			$response->cacheFor(3600 * 24);
			return $response;
		} catch (NotFoundException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
	}
}

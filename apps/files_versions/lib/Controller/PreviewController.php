<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Controller;

use OCA\Files_Versions\Versions\IVersionManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IUserSession;

class PreviewController extends Controller {

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IUserSession */
	private $userSession;

	/** @var IVersionManager */
	private $versionManager;

	/** @var IPreview */
	private $previewManager;

	public function __construct(
		string $appName,
		IRequest $request,
		IRootFolder $rootFolder,
		IUserSession $userSession,
		IVersionManager $versionManager,
		IPreview $previewManager
	) {
		parent::__construct($appName, $request);

		$this->rootFolder = $rootFolder;
		$this->userSession = $userSession;
		$this->versionManager = $versionManager;
		$this->previewManager = $previewManager;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get the preview for a file version
	 *
	 * @param string $file Path of the file
	 * @param int $x Width of the preview
	 * @param int $y Height of the preview
	 * @param string $version Version of the file to get the preview for
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND, array<empty>, array{}>
	 *
	 * 200: Preview returned
	 * 400: Getting preview is not possible
	 * 404: Preview not found
	 */
	public function getPreview(
		string $file = '',
		int $x = 44,
		int $y = 44,
		string $version = ''
	) {
		if ($file === '' || $version === '' || $x === 0 || $y === 0) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		try {
			$user = $this->userSession->getUser();
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$file = $userFolder->get($file);
			$versionFile = $this->versionManager->getVersionFile($user, $file, $version);
			$preview = $this->previewManager->getPreview($versionFile, $x, $y, true, IPreview::MODE_FILL, $versionFile->getMimetype());
			return new FileDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => $preview->getMimeType()]);
		} catch (NotFoundException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
	}
}

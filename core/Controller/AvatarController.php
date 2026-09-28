<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Controller;

use OC\AppFramework\Utility\TimeFactory;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IAvatarManager;
use OCP\IL10N;
use OCP\Image;
use OCP\IRequest;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

/**
 * Class AvatarController
 *
 * @package OC\Core\Controller
 */
class AvatarController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		protected IAvatarManager $avatarManager,
		protected IL10N $l10n,
		protected IUserManager $userManager,
		protected IRootFolder $rootFolder,
		protected LoggerInterface $logger,
		protected ?string $userId,
		protected TimeFactory $timeFactory,
		protected GuestAvatarController $guestAvatarController,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoSameSiteCookieRequired
	 *
	 * Get the dark avatar
	 *
	 * @param string $userId ID of the user
	 * @param 64|512 $size Size of the avatar
	 * @param bool $guestFallback Fallback to guest avatar if not found
	 * @return FileDisplayResponse<Http::STATUS_OK|Http::STATUS_CREATED, array{Content-Type: string, X-NC-IsCustomAvatar: int}>|JSONResponse<Http::STATUS_NOT_FOUND, list<empty>, array{}>|Response<Http::STATUS_INTERNAL_SERVER_ERROR, array{}>
	 *
	 * 200: Avatar returned
	 * 201: Avatar returned
	 * 404: Avatar not found
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[FrontpageRoute(verb: 'GET', url: '/avatar/{userId}/{size}/dark')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getAvatarDark(string $userId, int $size, bool $guestFallback = false) {
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
			$avatar = $this->avatarManager->getAvatar($userId);
			$avatarFile = $avatar->getFile($size, true);
			$response = new FileDisplayResponse(
				$avatarFile,
				Http::STATUS_OK,
				['Content-Type' => $avatarFile->getMimeType(), 'X-NC-IsCustomAvatar' => (int)$avatar->isCustomAvatar()]
			);
		} catch (\Exception $e) {
			if ($guestFallback) {
				return $this->guestAvatarController->getAvatarDark($userId, $size);
			}
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		// Cache for 1 day
		$response->cacheFor(60 * 60 * 24, false, true);
		return $response;
	}


	/**
	 * @NoSameSiteCookieRequired
	 *
	 * Get the avatar
	 *
	 * @param string $userId ID of the user
	 * @param 64|512 $size Size of the avatar
	 * @param bool $guestFallback Fallback to guest avatar if not found
	 * @return FileDisplayResponse<Http::STATUS_OK|Http::STATUS_CREATED, array{Content-Type: string, X-NC-IsCustomAvatar: int}>|JSONResponse<Http::STATUS_NOT_FOUND, list<empty>, array{}>|Response<Http::STATUS_INTERNAL_SERVER_ERROR, array{}>
	 *
	 * 200: Avatar returned
	 * 201: Avatar returned
	 * 404: Avatar not found
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[FrontpageRoute(verb: 'GET', url: '/avatar/{userId}/{size}')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getAvatar(string $userId, int $size, bool $guestFallback = false) {
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
			$avatar = $this->avatarManager->getAvatar($userId);
			$avatarFile = $avatar->getFile($size);
			$response = new FileDisplayResponse(
				$avatarFile,
				Http::STATUS_OK,
				['Content-Type' => $avatarFile->getMimeType(), 'X-NC-IsCustomAvatar' => (int)$avatar->isCustomAvatar()]
			);
		} catch (\Exception $e) {
			if ($guestFallback) {
				return $this->guestAvatarController->getAvatar($userId, $size);
			}
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		// Cache for 1 day
		$response->cacheFor(60 * 60 * 24, false, true);
		return $response;
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/avatar/')]
	public function postAvatar(?string $path = null): JSONResponse {
		$files = $this->request->getUploadedFile('files');

		if (isset($path)) {
			$path = stripslashes($path);
			$userFolder = $this->rootFolder->getUserFolder($this->userId);
			/** @var File $node */
			$node = $userFolder->get($path);
			if (!($node instanceof File)) {
				return new JSONResponse(['data' => ['message' => $this->l10n->t('Please select a file.')]]);
			}
			if ($node->getSize() > 20 * 1024 * 1024) {
				return new JSONResponse(
					['data' => ['message' => $this->l10n->t('File is too big')]],
					Http::STATUS_BAD_REQUEST
				);
			}

			if ($node->getMimeType() !== 'image/jpeg' && $node->getMimeType() !== 'image/png') {
				return new JSONResponse(
					['data' => ['message' => $this->l10n->t('The selected file is not an image.')]],
					Http::STATUS_BAD_REQUEST
				);
			}

			try {
				$content = $node->getContent();
			} catch (NotPermittedException $e) {
				return new JSONResponse(
					['data' => ['message' => $this->l10n->t('The selected file cannot be read.')]],
					Http::STATUS_BAD_REQUEST
				);
			}
		} elseif (!is_null($files)) {
			if (
				$files['error'][0] === 0
				 && is_uploaded_file($files['tmp_name'][0])
			) {
				if ($files['size'][0] > 20 * 1024 * 1024) {
					return new JSONResponse(
						['data' => ['message' => $this->l10n->t('File is too big')]],
						Http::STATUS_BAD_REQUEST
					);
				}
				$content = file_get_contents($files['tmp_name'][0]);
				unlink($files['tmp_name'][0]);
			} else {
				$phpFileUploadErrors = [
					UPLOAD_ERR_OK => $this->l10n->t('The file was uploaded'),
					UPLOAD_ERR_INI_SIZE => $this->l10n->t('The uploaded file exceeds the upload_max_filesize directive in php.ini'),
					UPLOAD_ERR_FORM_SIZE => $this->l10n->t('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'),
					UPLOAD_ERR_PARTIAL => $this->l10n->t('The file was only partially uploaded'),
					UPLOAD_ERR_NO_FILE => $this->l10n->t('No file was uploaded'),
					UPLOAD_ERR_NO_TMP_DIR => $this->l10n->t('Missing a temporary folder'),
					UPLOAD_ERR_CANT_WRITE => $this->l10n->t('Could not write file to disk'),
					UPLOAD_ERR_EXTENSION => $this->l10n->t('A PHP extension stopped the file upload'),
				];
				$message = $phpFileUploadErrors[$files['error'][0]] ?? $this->l10n->t('Invalid file provided');
				$this->logger->warning($message, ['app' => 'core']);
				return new JSONResponse(
					['data' => ['message' => $message]],
					Http::STATUS_BAD_REQUEST
				);
			}
		} else {
			//Add imgfile
			return new JSONResponse(
				['data' => ['message' => $this->l10n->t('No image or file provided')]],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$image = new Image();
			$image->loadFromData($content);
			$image->readExif($content);
			$image->fixOrientation();

			if ($image->valid()) {
				$mimeType = $image->mimeType();
				if ($mimeType !== 'image/jpeg' && $mimeType !== 'image/png') {
					return new JSONResponse(
						['data' => ['message' => $this->l10n->t('Unknown filetype')]],
						Http::STATUS_OK
					);
				}

				if ($image->width() === $image->height()) {
					try {
						$avatar = $this->avatarManager->getAvatar($this->userId);
						$avatar->set($image);
						return new JSONResponse(['status' => 'success']);
					} catch (\Throwable $e) {
						$this->logger->error($e->getMessage(), ['exception' => $e, 'app' => 'core']);
						return new JSONResponse(['data' => ['message' => $this->l10n->t('An error occurred. Please contact your admin.')]], Http::STATUS_BAD_REQUEST);
					}
				}

				return new JSONResponse(
					['data' => 'notsquare', 'image' => 'data:' . $mimeType . ';base64,' . base64_encode($image->data())],
					Http::STATUS_OK
				);
			} else {
				return new JSONResponse(
					['data' => ['message' => $this->l10n->t('Invalid image')]],
					Http::STATUS_OK
				);
			}
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e, 'app' => 'core']);
			return new JSONResponse(['data' => ['message' => $this->l10n->t('An error occurred. Please contact your admin.')]], Http::STATUS_OK);
		}
	}

	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/avatar/')]
	public function deleteAvatar(): JSONResponse {
		try {
			$avatar = $this->avatarManager->getAvatar($this->userId);
			$avatar->remove();
			return new JSONResponse();
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e, 'app' => 'core']);
			return new JSONResponse(['data' => ['message' => $this->l10n->t('An error occurred. Please contact your admin.')]], Http::STATUS_BAD_REQUEST);
		}
	}
}

<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Controller;

use OC\AppFramework\Utility\TimeFactory;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IAvatarManager;
use OCP\ICache;
use OCP\IL10N;
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
		protected ICache $cache,
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
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @NoSameSiteCookieRequired
	 * @PublicPage
	 *
	 * Get the dark avatar
	 *
	 * @param string $userId ID of the user
	 * @param int $size Size of the avatar
	 * @param bool $guestFallback Fallback to guest avatar if not found
	 * @return FileDisplayResponse<Http::STATUS_OK|Http::STATUS_CREATED, array{Content-Type: string, X-NC-IsCustomAvatar: int}>|JSONResponse<Http::STATUS_NOT_FOUND, array<empty>, array{}>|Response<Http::STATUS_INTERNAL_SERVER_ERROR, array{}>
	 *
	 * 200: Avatar returned
	 * 201: Avatar returned
	 * 404: Avatar not found
	 */
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
				return $this->guestAvatarController->getAvatarDark($userId, (string)$size);
			}
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		// Cache for 1 day
		$response->cacheFor(60 * 60 * 24, false, true);
		return $response;
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @NoSameSiteCookieRequired
	 * @PublicPage
	 *
	 * Get the avatar
	 *
	 * @param string $userId ID of the user
	 * @param int $size Size of the avatar
	 * @param bool $guestFallback Fallback to guest avatar if not found
	 * @return FileDisplayResponse<Http::STATUS_OK|Http::STATUS_CREATED, array{Content-Type: string, X-NC-IsCustomAvatar: int}>|JSONResponse<Http::STATUS_NOT_FOUND, array<empty>, array{}>|Response<Http::STATUS_INTERNAL_SERVER_ERROR, array{}>
	 *
	 * 200: Avatar returned
	 * 201: Avatar returned
	 * 404: Avatar not found
	 */
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
				return $this->guestAvatarController->getAvatar($userId, (string)$size);
			}
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		// Cache for 1 day
		$response->cacheFor(60 * 60 * 24, false, true);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
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
			} catch (\OCP\Files\NotPermittedException $e) {
				return new JSONResponse(
					['data' => ['message' => $this->l10n->t('The selected file cannot be read.')]],
					Http::STATUS_BAD_REQUEST
				);
			}
		} elseif (!is_null($files)) {
			if (
				$files['error'][0] === 0 &&
				 is_uploaded_file($files['tmp_name'][0]) &&
				!\OC\Files\Filesystem::isFileBlacklisted($files['tmp_name'][0])
			) {
				if ($files['size'][0] > 20 * 1024 * 1024) {
					return new JSONResponse(
						['data' => ['message' => $this->l10n->t('File is too big')]],
						Http::STATUS_BAD_REQUEST
					);
				}
				$this->cache->set('avatar_upload', file_get_contents($files['tmp_name'][0]), 7200);
				$content = $this->cache->get('avatar_upload');
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
			$image = new \OCP\Image();
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
						// Clean up
						$this->cache->remove('tmpAvatar');
						return new JSONResponse(['status' => 'success']);
					} catch (\Throwable $e) {
						$this->logger->error($e->getMessage(), ['exception' => $e, 'app' => 'core']);
						return new JSONResponse(['data' => ['message' => $this->l10n->t('An error occurred. Please contact your admin.')]], Http::STATUS_BAD_REQUEST);
					}
				}

				$this->cache->set('tmpAvatar', $image->data(), 7200);
				return new JSONResponse(
					['data' => 'notsquare'],
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

	/**
	 * @NoAdminRequired
	 */
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

	/**
	 * @NoAdminRequired
	 *
	 * @return JSONResponse|DataDisplayResponse
	 */
	public function getTmpAvatar() {
		$tmpAvatar = $this->cache->get('tmpAvatar');
		if (is_null($tmpAvatar)) {
			return new JSONResponse(['data' => [
				'message' => $this->l10n->t("No temporary profile picture available, try again")
			]],
				Http::STATUS_NOT_FOUND);
		}

		$image = new \OCP\Image();
		$image->loadFromData($tmpAvatar);

		$resp = new DataDisplayResponse(
			$image->data() ?? '',
			Http::STATUS_OK,
			['Content-Type' => $image->mimeType()]);

		$resp->setETag((string)crc32($image->data() ?? ''));
		$resp->cacheFor(0);
		$resp->setLastModified(new \DateTime('now', new \DateTimeZone('GMT')));
		return $resp;
	}

	/**
	 * @NoAdminRequired
	 */
	public function postCroppedAvatar(?array $crop = null): JSONResponse {
		if (is_null($crop)) {
			return new JSONResponse(['data' => ['message' => $this->l10n->t("No crop data provided")]],
				Http::STATUS_BAD_REQUEST);
		}

		if (!isset($crop['x'], $crop['y'], $crop['w'], $crop['h'])) {
			return new JSONResponse(['data' => ['message' => $this->l10n->t("No valid crop data provided")]],
				Http::STATUS_BAD_REQUEST);
		}

		$tmpAvatar = $this->cache->get('tmpAvatar');
		if (is_null($tmpAvatar)) {
			return new JSONResponse(['data' => [
				'message' => $this->l10n->t("No temporary profile picture available, try again")
			]],
				Http::STATUS_BAD_REQUEST);
		}

		$image = new \OCP\Image();
		$image->loadFromData($tmpAvatar);
		$image->crop($crop['x'], $crop['y'], (int)round($crop['w']), (int)round($crop['h']));
		try {
			$avatar = $this->avatarManager->getAvatar($this->userId);
			$avatar->set($image);
			// Clean up
			$this->cache->remove('tmpAvatar');
			return new JSONResponse(['status' => 'success']);
		} catch (\OC\NotSquareException $e) {
			return new JSONResponse(['data' => ['message' => $this->l10n->t('Crop is not square')]],
				Http::STATUS_BAD_REQUEST);
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e, 'app' => 'core']);
			return new JSONResponse(['data' => ['message' => $this->l10n->t('An error occurred. Please contact your admin.')]], Http::STATUS_BAD_REQUEST);
		}
	}
}

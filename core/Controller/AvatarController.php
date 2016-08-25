<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IAvatarManager;
use OCP\ICache;
use OCP\ILogger;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;

/**
 * Class AvatarController
 *
 * @package OC\Core\Controller
 */
class AvatarController extends Controller {

	/** @var IAvatarManager */
	protected $avatarManager;

	/** @var ICache */
	protected $cache;

	/** @var IL10N */
	protected $l;

	/** @var IUserManager */
	protected $userManager;

	/** @var IUserSession */
	protected $userSession;

	/** @var IRootFolder */
	protected $rootFolder;

	/** @var ILogger */
	protected $logger;

	/** @var string */
	protected $userId;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IAvatarManager $avatarManager
	 * @param ICache $cache
	 * @param IL10N $l10n
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 * @param ILogger $logger
	 * @param string $userId
	 */
	public function __construct($appName,
								IRequest $request,
								IAvatarManager $avatarManager,
								ICache $cache,
								IL10N $l10n,
								IUserManager $userManager,
								IRootFolder $rootFolder,
								ILogger $logger,
								$userId) {
		parent::__construct($appName, $request);

		$this->avatarManager = $avatarManager;
		$this->cache = $cache;
		$this->l = $l10n;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->logger = $logger;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param string $userId
	 * @param int $size
	 * @return DataResponse|DataDisplayResponse
	 */
	public function getAvatar($userId, $size) {
		if ($size > 2048) {
			$size = 2048;
		} elseif ($size <= 0) {
			$size = 64;
		}

		try {
			$avatar = $this->avatarManager->getAvatar($userId)->getFile($size);
			$resp = new DataDisplayResponse($avatar->getContent(),
				Http::STATUS_OK,
				['Content-Type' => $avatar->getMimeType()]);
			$resp->setETag($avatar->getEtag());
		} catch (NotFoundException $e) {
			$user = $this->userManager->get($userId);
			$resp = new DataResponse([
				'data' => [
					'displayname' => $user->getDisplayName(),
				],
			]);
		} catch (\Exception $e) {
			$resp = new DataResponse([
				'data' => [
					'displayname' => '',
				],
			]);
		}

		$resp->addHeader('Pragma', 'public');
		$resp->cacheFor(0);
		$resp->setLastModified(new \DateTime('now', new \DateTimeZone('GMT')));

		return $resp;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $path
	 * @return DataResponse
	 */
	public function postAvatar($path) {
		$files = $this->request->getUploadedFile('files');

		$headers = [];

		if (isset($path)) {
			$path = stripslashes($path);
			$userFolder = $this->rootFolder->getUserFolder($this->userId);
			$node = $userFolder->get($path);
			if (!($node instanceof File)) {
				return new DataResponse(['data' => ['message' => $this->l->t('Please select a file.')]], Http::STATUS_OK, $headers);
			}
			if ($node->getSize() > 20*1024*1024) {
				return new DataResponse(
					['data' => ['message' => $this->l->t('File is too big')]],
					Http::STATUS_BAD_REQUEST,
					$headers
				);
			}
			$content = $node->getContent();
		} elseif (!is_null($files)) {
			if (
				$files['error'][0] === 0 &&
				 is_uploaded_file($files['tmp_name'][0]) &&
				!\OC\Files\Filesystem::isFileBlacklisted($files['tmp_name'][0])
			) {
				if ($files['size'][0] > 20*1024*1024) {
					return new DataResponse(
						['data' => ['message' => $this->l->t('File is too big')]],
						Http::STATUS_BAD_REQUEST,
						$headers
					);
				}
				$this->cache->set('avatar_upload', file_get_contents($files['tmp_name'][0]), 7200);
				$content = $this->cache->get('avatar_upload');
				unlink($files['tmp_name'][0]);
			} else {
				return new DataResponse(
					['data' => ['message' => $this->l->t('Invalid file provided')]],
					Http::STATUS_BAD_REQUEST,
					$headers
				);
			}
		} else {
			//Add imgfile
			return new DataResponse(
				['data' => ['message' => $this->l->t('No image or file provided')]],
				Http::STATUS_BAD_REQUEST,
				$headers
			);
		}

		try {
			$image = new \OC_Image();
			$image->loadFromData($content);
			$image->fixOrientation();

			if ($image->valid()) {
				$mimeType = $image->mimeType();
				if ($mimeType !== 'image/jpeg' && $mimeType !== 'image/png') {
					return new DataResponse(
						['data' => ['message' => $this->l->t('Unknown filetype')]],
						Http::STATUS_OK,
						$headers
					);
				}

				$this->cache->set('tmpAvatar', $image->data(), 7200);
				return new DataResponse(
					['data' => 'notsquare'],
					Http::STATUS_OK,
					$headers
				);
			} else {
				return new DataResponse(
					['data' => ['message' => $this->l->t('Invalid image')]],
					Http::STATUS_OK,
					$headers
				);
			}
		} catch (\Exception $e) {
			$this->logger->logException($e, ['app' => 'core']);
			return new DataResponse(['data' => ['message' => $this->l->t('An error occurred. Please contact your admin.')]], Http::STATUS_OK, $headers);
		}
	}

	/**
	 * @NoAdminRequired
     *
	 * @return DataResponse
	 */
	public function deleteAvatar() {
		try {
			$avatar = $this->avatarManager->getAvatar($this->userId);
			$avatar->remove();
			return new DataResponse();
		} catch (\Exception $e) {
			$this->logger->logException($e, ['app' => 'core']);
			return new DataResponse(['data' => ['message' => $this->l->t('An error occurred. Please contact your admin.')]], Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse|DataDisplayResponse
	 */
	public function getTmpAvatar() {
		$tmpAvatar = $this->cache->get('tmpAvatar');
		if (is_null($tmpAvatar)) {
			return new DataResponse(['data' => [
										'message' => $this->l->t("No temporary profile picture available, try again")
									]],
									Http::STATUS_NOT_FOUND);
		}

		$image = new \OC_Image($tmpAvatar);

		$resp = new DataDisplayResponse($image->data(),
				Http::STATUS_OK,
				['Content-Type' => $image->mimeType()]);

		$resp->setETag(crc32($image->data()));
		$resp->cacheFor(0);
		$resp->setLastModified(new \DateTime('now', new \DateTimeZone('GMT')));
		return $resp;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param array $crop
	 * @return DataResponse
	 */
	public function postCroppedAvatar($crop) {
		if (is_null($crop)) {
			return new DataResponse(['data' => ['message' => $this->l->t("No crop data provided")]],
									Http::STATUS_BAD_REQUEST);
		}

		if (!isset($crop['x'], $crop['y'], $crop['w'], $crop['h'])) {
			return new DataResponse(['data' => ['message' => $this->l->t("No valid crop data provided")]],
									Http::STATUS_BAD_REQUEST);
		}

		$tmpAvatar = $this->cache->get('tmpAvatar');
		if (is_null($tmpAvatar)) {
			return new DataResponse(['data' => [
										'message' => $this->l->t("No temporary profile picture available, try again")
									]],
									Http::STATUS_BAD_REQUEST);
		}

		$image = new \OC_Image($tmpAvatar);
		$image->crop($crop['x'], $crop['y'], round($crop['w']), round($crop['h']));
		try {
			$avatar = $this->avatarManager->getAvatar($this->userId);
			$avatar->set($image);
			// Clean up
			$this->cache->remove('tmpAvatar');
			return new DataResponse(['status' => 'success']);
		} catch (\OC\NotSquareException $e) {
			return new DataResponse(['data' => ['message' => $this->l->t('Crop is not square')]],
									Http::STATUS_BAD_REQUEST);
		} catch (\Exception $e) {
			$this->logger->logException($e, ['app' => 'core']);
			return new DataResponse(['data' => ['message' => $this->l->t('An error occurred. Please contact your admin.')]], Http::STATUS_BAD_REQUEST);
		}
	}
}

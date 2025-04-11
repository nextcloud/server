<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Controller;

use OCA\DAV\Db\Direct;
use OCA\DAV\Db\DirectMapper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\BeforeDirectFileDownloadEvent;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;

class DirectController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private IRootFolder $rootFolder,
		private string $userId,
		private DirectMapper $mapper,
		private ISecureRandom $random,
		private ITimeFactory $timeFactory,
		private IURLGenerator $urlGenerator,
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get a direct link to a file
	 *
	 * @param int $fileId ID of the file
	 * @param int $expirationTime Duration until the link expires
	 * @return DataResponse<Http::STATUS_OK, array{url: string}, array{}>
	 * @throws OCSNotFoundException File not found
	 * @throws OCSBadRequestException Getting direct link is not possible
	 * @throws OCSForbiddenException Missing permissions to get direct link
	 *
	 * 200: Direct link returned
	 */
	#[NoAdminRequired]
	public function getUrl(int $fileId, int $expirationTime = 60 * 60 * 8): DataResponse {
		$userFolder = $this->rootFolder->getUserFolder($this->userId);

		$file = $userFolder->getFirstNodeById($fileId);

		if (!$file) {
			throw new OCSNotFoundException();
		}

		if ($expirationTime <= 0 || $expirationTime > (60 * 60 * 24)) {
			throw new OCSBadRequestException('Expiration time should be greater than 0 and less than or equal to ' . (60 * 60 * 24));
		}

		if (!($file instanceof File)) {
			throw new OCSBadRequestException('Direct download only works for files');
		}

		$event = new BeforeDirectFileDownloadEvent($userFolder->getRelativePath($file->getPath()));
		$this->eventDispatcher->dispatchTyped($event);

		if ($event->isSuccessful() === false) {
			throw new OCSForbiddenException('Permission denied to download file');
		}

		//TODO: at some point we should use the directdownlaod function of storages
		$direct = new Direct();
		$direct->setUserId($this->userId);
		$direct->setFileId($fileId);

		$token = $this->random->generate(60, ISecureRandom::CHAR_ALPHANUMERIC);
		$direct->setToken($token);
		$direct->setExpiration($this->timeFactory->getTime() + $expirationTime);

		$this->mapper->insert($direct);

		$url = $this->urlGenerator->getAbsoluteURL('remote.php/direct/' . $token);

		return new DataResponse([
			'url' => $url,
		]);
	}
}

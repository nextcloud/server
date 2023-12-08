<?php

declare(strict_types=1);

/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Iscle <albertiscle9@gmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
namespace OCA\DAV\Controller;

use OCA\DAV\Db\Direct;
use OCA\DAV\Db\DirectMapper;
use OCP\AppFramework\Http;
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

	/** @var IRootFolder */
	private $rootFolder;

	/** @var string */
	private $userId;

	/** @var DirectMapper */
	private $mapper;

	/** @var ISecureRandom */
	private $random;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	public function __construct(string $appName,
		IRequest $request,
		IRootFolder $rootFolder,
		string $userId,
		DirectMapper $mapper,
		ISecureRandom $random,
		ITimeFactory $timeFactory,
		IURLGenerator $urlGenerator,
		IEventDispatcher $eventDispatcher) {
		parent::__construct($appName, $request);

		$this->rootFolder = $rootFolder;
		$this->userId = $userId;
		$this->mapper = $mapper;
		$this->random = $random;
		$this->timeFactory = $timeFactory;
		$this->urlGenerator = $urlGenerator;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * @NoAdminRequired
	 *
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
	public function getUrl(int $fileId, int $expirationTime = 60 * 60 * 8): DataResponse {
		$userFolder = $this->rootFolder->getUserFolder($this->userId);

		$files = $userFolder->getById($fileId);

		if ($files === []) {
			throw new OCSNotFoundException();
		}

		if ($expirationTime <= 0 || $expirationTime > (60 * 60 * 24)) {
			throw new OCSBadRequestException('Expiration time should be greater than 0 and less than or equal to ' . (60 * 60 * 24));
		}

		$file = array_shift($files);
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

		$url = $this->urlGenerator->getAbsoluteURL('remote.php/direct/'.$token);

		return new DataResponse([
			'url' => $url,
		]);
	}
}

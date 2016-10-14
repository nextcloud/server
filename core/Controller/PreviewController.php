<?php
/**
 * @copyright Copyright (c) 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Controller;

use OC\PreviewManager;
use OCP\AppFramework\Controller;
use OCP\Files\File;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IRequest;

class PreviewController extends Controller {

	/** @var string */
	private $userId;

	/** @var IRootFolder */
	private $root;

	/** @var IConfig */
	private $config;

	/** @var PreviewManager */
	private $previewManager;

	/** @var IAppData */
	private $appData;

	public function __construct($appName,
								IRequest $request,
								IRootFolder $root,
								IConfig $config,
								PreviewManager $previewManager,
								IAppData $appData,
								$userId
	) {
		parent::__construct($appName, $request);

		$this->previewManager = $previewManager;
		$this->root = $root;
		$this->config = $config;
		$this->appData = $appData;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $file
	 * @param int $x
	 * @param int $y
	 * @param bool $a
	 * @param bool $forceIcon
	 * @param string $mode
	 * @return DataResponse|Http\FileDisplayResponse
	 */
	public function getPreview(
		$file = '',
		$x = 32,
		$y = 32,
		$a = false,
		$forceIcon = true,
		$mode = 'fill') {

		if ($file === '') {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		if ($x === 0 || $y === 0) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		try {
			$userFolder = $this->root->getUserFolder($this->userId);
			$file = $userFolder->get($file);
		} catch (NotFoundException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		if (!($file instanceof File) || (!$forceIcon && !$this->previewManager->isAvailable($file))) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} else if (!$file->isReadable()) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$preview = new \OC\Preview2(
			$this->root,
			$this->config,
			$this->previewManager,
			$file,
			$this->appData
		);

		$f = $preview->getPreview($x, $y, !$a, $mode);
		return new Http\FileDisplayResponse($f, Http::STATUS_OK, ['Content-Type' => $f->getMimeType()]);
	}
}

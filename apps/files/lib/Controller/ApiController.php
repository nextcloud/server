<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tobias Kaminsky <tobias@kaminsky.me>
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

namespace OCA\Files\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\Response;
use OCA\Files\Service\TagService;
use OCP\IPreview;
use OCP\Share\IManager;
use OCP\Files\Node;
use OCP\IUserSession;

/**
 * Class ApiController
 *
 * @package OCA\Files\Controller
 */
class ApiController extends Controller {
	/** @var TagService */
	private $tagService;
	/** @var IManager **/
	private $shareManager;
	/** @var IPreview */
	private $previewManager;
	/** IUserSession */
	private $userSession;
	/** IConfig */
	private $config;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param TagService $tagService
	 * @param IPreview $previewManager
	 */
	public function __construct($appName,
								IRequest $request,
								IUserSession $userSession,
								TagService $tagService,
								IPreview $previewManager,
								IManager $shareManager,
								IConfig $config) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->tagService = $tagService;
		$this->previewManager = $previewManager;
		$this->shareManager = $shareManager;
		$this->config = $config;
	}

	/**
	 * Gets a thumbnail of the specified file
	 *
	 * @since API version 1.0
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @StrictCookieRequired
	 *
	 * @param int $x
	 * @param int $y
	 * @param string $file URL-encoded filename
	 * @return DataResponse|DataDisplayResponse
	 */
	public function getThumbnail($x, $y, $file) {
		if($x < 1 || $y < 1) {
			return new DataResponse(['message' => 'Requested size must be numeric and a positive value.'], Http::STATUS_BAD_REQUEST);
		}

		$preview = $this->previewManager->createPreview('files/'.$file, $x, $y, true);
		if ($preview->valid()) {
			return new DataDisplayResponse($preview->data(), Http::STATUS_OK, ['Content-Type' => 'image/png']);
		} else {
			return new DataResponse(['message' => 'File not found.'], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Updates the info of the specified file path
	 * The passed tags are absolute, which means they will
	 * replace the actual tag selection.
	 *
	 * @NoAdminRequired
	 *
	 * @param string $path path
	 * @param array|string $tags array of tags
	 * @return DataResponse
	 */
	public function updateFileTags($path, $tags = null) {
		$result = [];
		// if tags specified or empty array, update tags
		if (!is_null($tags)) {
			try {
				$this->tagService->updateFileTags($path, $tags);
			} catch (\OCP\Files\NotFoundException $e) {
				return new DataResponse([
					'message' => $e->getMessage()
				], Http::STATUS_NOT_FOUND);
			} catch (\OCP\Files\StorageNotAvailableException $e) {
				return new DataResponse([
					'message' => $e->getMessage()
				], Http::STATUS_SERVICE_UNAVAILABLE);
			} catch (\Exception $e) {
				return new DataResponse([
					'message' => $e->getMessage()
				], Http::STATUS_NOT_FOUND);
			}
			$result['tags'] = $tags;
		}
		return new DataResponse($result);
	}

	/**
	 * Returns a list of all files tagged with the given tag.
	 *
	 * @NoAdminRequired
	 *
	 * @param string $tagName tag name to filter by
	 * @return DataResponse
	 */
	public function getFilesByTag($tagName) {
		$files = array();
		$nodes = $this->tagService->getFilesByTag($tagName);
		foreach ($nodes as &$node) {
			$shareTypes = $this->getShareTypes($node);
			$fileInfo = $node->getFileInfo();
			$file = \OCA\Files\Helper::formatFileInfo($fileInfo);
			$parts = explode('/', dirname($fileInfo->getPath()), 4);
			if(isset($parts[3])) {
				$file['path'] = '/' . $parts[3];
			} else {
				$file['path'] = '/';
			}
			$file['tags'] = [$tagName];
			if (!empty($shareTypes)) {
				$file['shareTypes'] = $shareTypes;
			}
			$files[] = $file;
		}
		return new DataResponse(['files' => $files]);
	}

	/**
	 * Return a list of share types for outgoing shares
	 *
	 * @param Node $node file node
	 *
	 * @return int[] array of share types
	 */
	private function getShareTypes(Node $node) {
		$userId = $this->userSession->getUser()->getUID();
		$shareTypes = [];
		$requestedShareTypes = [
			\OCP\Share::SHARE_TYPE_USER,
			\OCP\Share::SHARE_TYPE_GROUP,
			\OCP\Share::SHARE_TYPE_LINK,
			\OCP\Share::SHARE_TYPE_REMOTE
		];
		foreach ($requestedShareTypes as $requestedShareType) {
			// one of each type is enough to find out about the types
			$shares = $this->shareManager->getSharesBy(
				$userId,
				$requestedShareType,
				$node,
				false,
				1
			);
			if (!empty($shares)) {
				$shareTypes[] = $requestedShareType;
			}
		}
		return $shareTypes;
	}

	/**
	 * Change the default sort mode
	 *
	 * @NoAdminRequired
	 *
	 * @param string $mode
	 * @param string $direction
	 * @return Response
	 */
	public function updateFileSorting($mode, $direction) {
		$allowedMode = ['name', 'size', 'mtime'];
		$allowedDirection = ['asc', 'desc'];
		if (!in_array($mode, $allowedMode) || !in_array($direction, $allowedDirection)) {
			$response = new Response();
			$response->setStatus(Http::STATUS_UNPROCESSABLE_ENTITY);
			return $response;
		}
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'file_sorting', $mode);
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'file_sorting_direction', $direction);
		return new Response();
	}

	/**
	 * Toggle default for showing/hiding hidden files
	 *
	 * @NoAdminRequired
	 *
	 * @param bool $show
	 */
	public function showHiddenFiles($show) {
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'show_hidden', (int) $show);
		return new Response();
	}

}

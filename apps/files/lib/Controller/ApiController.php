<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tobias Kaminsky <tobias@kaminsky.me>
 * @author Vincent Petry <pvince81@owncloud.com>
 * @author Felix Nüsse <felix.nuesse@t-online.de>
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
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\Response;
use OCA\Files\Service\TagService;
use OCP\IPreview;
use OCP\Share\IManager;
use OC\Files\Node\Node;
use OCP\IUserSession;
use Sabre\VObject\Property\Boolean;

/**
 * Class ApiController
 *
 * @package OCA\Files\Controller
 */
class ApiController extends Controller {
	/** @var TagService */
	private $tagService;
	/** @var IManager * */
	private $shareManager;
	/** @var IPreview */
	private $previewManager;
	/** IUserSession */
	private $userSession;
	/** IConfig */
	private $config;
	/** @var Folder */
	private $userFolder;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserSession $userSession
	 * @param TagService $tagService
	 * @param IPreview $previewManager
	 * @param IManager $shareManager
	 * @param IConfig $config
	 * @param Folder $userFolder
	 */
	public function __construct($appName,
								IRequest $request,
								IUserSession $userSession,
								TagService $tagService,
								IPreview $previewManager,
								IManager $shareManager,
								IConfig $config,
								Folder $userFolder) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->tagService = $tagService;
		$this->previewManager = $previewManager;
		$this->shareManager = $shareManager;
		$this->config = $config;
		$this->userFolder = $userFolder;
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
	 * @return DataResponse|FileDisplayResponse
	 */
	public function getThumbnail($x, $y, $file) {
		if ($x < 1 || $y < 1) {
			return new DataResponse(['message' => 'Requested size must be numeric and a positive value.'], Http::STATUS_BAD_REQUEST);
		}

		try {
			$file = $this->userFolder->get($file);
			if ($file instanceof Folder) {
				throw new NotFoundException();
			}

			/** @var File $file */
			$preview = $this->previewManager->getPreview($file, $x, $y, true);

			return new FileDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => $preview->getMimeType()]);
		} catch (NotFoundException $e) {
			return new DataResponse(['message' => 'File not found.'], Http::STATUS_NOT_FOUND);
		} catch (\Exception $e) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
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
	 * @param \OCP\Files\Node[] $nodes
	 * @return array
	 */
	private function formatNodes(array $nodes) {
		return array_values(array_map(function (Node $node) {
			/** @var \OC\Files\Node\Node $shareTypes */
			$shareTypes = $this->getShareTypes($node);
			$file = \OCA\Files\Helper::formatFileInfo($node->getFileInfo());
			$parts = explode('/', dirname($node->getPath()), 4);
			if (isset($parts[3])) {
				$file['path'] = '/' . $parts[3];
			} else {
				$file['path'] = '/';
			}
			if (!empty($shareTypes)) {
				$file['shareTypes'] = $shareTypes;
			}
			return $file;
		}, $nodes));
	}

	/**
	 * Returns a list of recently modifed files.
	 *
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function getRecentFiles() {
		$nodes = $this->userFolder->getRecent(100);
		$files = $this->formatNodes($nodes);
		return new DataResponse(['files' => $files]);
	}

	/**
	 * Returns a list of favorites modifed folder.
	 *
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function getFavoritesFolder() {
		$nodes = $this->userFolder->searchByTag('_$!<Favorite>!$_', $this->userSession->getUser()->getUID());

		$favorites = [];
		$i = 0;
		foreach ($nodes as &$node) {

			$favorites[$i]['id'] = $node->getId();
			$favorites[$i]['name'] = $node->getName();
			$favorites[$i]['mtime'] = $node->getMTime();
			$i++;
		}

		return new DataResponse(['favoriteFolders' => $favorites]);
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
			\OCP\Share::SHARE_TYPE_REMOTE,
			\OCP\Share::SHARE_TYPE_EMAIL
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
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'show_hidden', (int)$show);
		return new Response();
	}

	/**
	 * Toggle default for showing/hiding QuickAccess folder
	 *
	 * @NoAdminRequired
	 *
	 * @param bool $show
	 *
	 * @return Response
	 */
	public function showQuickAccess($show) {
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'show_Quick_Access', (int)$show);
		return new Response();
	}

	/**
	 * Toggle default for showing/hiding QuickAccess folder
	 *
	 * @NoAdminRequired
	 *
	 * @return String
	 */
	public function getShowQuickAccess() {

		return $this->config->getUserValue($this->userSession->getUser()->getUID(), 'files', 'show_Quick_Access', 1);
	}

	/**
	 * quickaccess-sorting-strategy
	 *
	 * @NoAdminRequired
	 *
	 * @param string $strategy
	 * @return Response
	 */
	public function setSortingStrategy($strategy) {
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'quickaccess_sorting_strategy', (String)$strategy);
		return new Response();
	}

	/**
	 * Get reverse-state for quickaccess-list
	 *
	 * @NoAdminRequired
	 *
	 * @return String
	 */
	public function getSortingStrategy() {
		return $this->config->getUserValue($this->userSession->getUser()->getUID(), 'files', 'quickaccess_sorting_strategy', 'date');
	}

	/**
	 * Toggle for reverse quickaccess-list
	 *
	 * @NoAdminRequired
	 *
	 * @param bool $reverse
	 * @return Response
	 */
	public function setReverseQuickaccess($reverse) {
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'quickaccess_reverse_list', (int)$reverse);
		return new Response();
	}

	/**
	 * Get reverse-state for quickaccess-list
	 *
	 * @NoAdminRequired
	 *
	 * @return bool
	 */
	public function getReverseQuickaccess() {
		if ($this->config->getUserValue($this->userSession->getUser()->getUID(), 'files', 'quickaccess_reverse_list', false)) {
			return true;
		}
		return false;
	}

	/**
	 * Set state for show sorting menu
	 *
	 * @NoAdminRequired
	 *
	 * @param bool $show
	 * @return Response
	 */
	public function setShowQuickaccessSettings($show) {
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'quickaccess_show_settings', (int)$show);
		return new Response();
	}

	/**
	 * Get state for show sorting menu
	 *
	 * @NoAdminRequired
	 *
	 * @return bool
	 */
	public function getShowQuickaccessSettings() {
		if ($this->config->getUserValue($this->userSession->getUser()->getUID(), 'files', 'quickaccess_show_settings', false)) {
			return true;
		}
		return false;
	}

	/**
	 * Set sorting-order for custom sorting
	 *
	 * @NoAdminRequired
	 *
	 * @param String $order
	 * @return Response
	 */
	public function setSortingOrder($order) {
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'quickaccess_custom_sorting_order', (String)$order);
		return new Response();
	}

	/**
	 * Get sorting-order for custom sorting
	 *
	 * @NoAdminRequired
	 *
	 * @param String
	 * @return String
	 */
	public function getSortingOrder() {
		return $this->config->getUserValue($this->userSession->getUser()->getUID(), 'files', 'quickaccess_custom_sorting_order', "");
	}

}

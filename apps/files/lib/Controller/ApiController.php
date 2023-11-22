<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Felix Nüsse <Felix.nuesse@t-online.de>
 * @author fnuesse <felix.nuesse@t-online.de>
 * @author fnuesse <fnuesse@techfak.uni-bielefeld.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Max Kovalenko <mxss1998@yandex.ru>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Nina Pypchenko <22447785+nina-py@users.noreply.github.com>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tobias Kaminsky <tobias@kaminsky.me>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OCA\Files\Controller;

use OC\Files\Node\Node;
use OCA\Files\Service\TagService;
use OCA\Files\Service\UserConfig;
use OCA\Files\Service\ViewConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\IgnoreOpenAPI;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StreamResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;

/**
 * @package OCA\Files\Controller
 */
class ApiController extends Controller {
	private TagService $tagService;
	private IManager $shareManager;
	private IPreview $previewManager;
	private IUserSession $userSession;
	private IConfig $config;
	private ?Folder $userFolder;
	private UserConfig $userConfig;
	private ViewConfig $viewConfig;

	public function __construct(string $appName,
								IRequest $request,
								IUserSession $userSession,
								TagService $tagService,
								IPreview $previewManager,
								IManager $shareManager,
								IConfig $config,
								?Folder $userFolder,
								UserConfig $userConfig,
								ViewConfig $viewConfig) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->tagService = $tagService;
		$this->previewManager = $previewManager;
		$this->shareManager = $shareManager;
		$this->config = $config;
		$this->userFolder = $userFolder;
		$this->userConfig = $userConfig;
		$this->viewConfig = $viewConfig;
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
	 * @param int $x Width of the thumbnail
	 * @param int $y Height of the thumbnail
	 * @param string $file URL-encoded filename
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND, array{message?: string}, array{}>
	 *
	 * 200: Thumbnail returned
	 * 400: Getting thumbnail is not possible
	 * 404: File not found
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
		$shareTypesForNodes = $this->getShareTypesForNodes($nodes);
		return array_values(array_map(function (Node $node) use ($shareTypesForNodes) {
			$shareTypes = $shareTypesForNodes[$node->getId()] ?? [];
			$file = \OCA\Files\Helper::formatFileInfo($node->getFileInfo());
			$file['hasPreview'] = $this->previewManager->isAvailable($node);
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
	 * Get the share types for each node
	 *
	 * @param \OCP\Files\Node[] $nodes
	 * @return array<int, int[]> list of share types for each fileid
	 */
	private function getShareTypesForNodes(array $nodes): array {
		$userId = $this->userSession->getUser()->getUID();
		$requestedShareTypes = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP,
			IShare::TYPE_LINK,
			IShare::TYPE_REMOTE,
			IShare::TYPE_EMAIL,
			IShare::TYPE_ROOM,
			IShare::TYPE_DECK,
			IShare::TYPE_SCIENCEMESH,
		];
		$shareTypes = [];

		$nodeIds = array_map(function (Node $node) {
			return $node->getId();
		}, $nodes);

		foreach ($requestedShareTypes as $shareType) {
			$nodesLeft = array_combine($nodeIds, array_fill(0, count($nodeIds), true));
			$offset = 0;

			// fetch shares until we've either found shares for all nodes or there are no more shares left
			while (count($nodesLeft) > 0) {
				$shares = $this->shareManager->getSharesBy($userId, $shareType, null, false, 100, $offset);
				foreach ($shares as $share) {
					$fileId = $share->getNodeId();
					if (isset($nodesLeft[$fileId])) {
						if (!isset($shareTypes[$fileId])) {
							$shareTypes[$fileId] = [];
						}
						$shareTypes[$fileId][] = $shareType;
						unset($nodesLeft[$fileId]);
					}
				}

				if (count($shares) < 100) {
					break;
				} else {
					$offset += count($shares);
				}
			}
		}
		return $shareTypes;
	}

	/**
	 * Returns a list of recently modified files.
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
	 * Returns the current logged-in user's storage stats.
	 *
	 * @NoAdminRequired
	 *
	 * @param ?string $dir the directory to get the storage stats from
	 * @return JSONResponse
	 */
	public function getStorageStats($dir = '/'): JSONResponse {
		$storageInfo = \OC_Helper::getStorageInfo($dir ?: '/');
		return new JSONResponse(['message' => 'ok', 'data' => $storageInfo]);
	}

	/**
	 * Set a user view config
	 *
	 * @NoAdminRequired
	 *
	 * @param string $view
	 * @param string $key
	 * @param string|bool $value
	 * @return JSONResponse
	 */
	public function setViewConfig(string $view, string $key, $value): JSONResponse {
		try {
			$this->viewConfig->setConfig($view, $key, (string)$value);
		} catch (\InvalidArgumentException $e) {
			return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse(['message' => 'ok', 'data' => $this->viewConfig->getConfig($view)]);
	}


	/**
	 * Get the user view config
	 *
	 * @NoAdminRequired
	 *
	 * @return JSONResponse
	 */
	public function getViewConfigs(): JSONResponse {
		return new JSONResponse(['message' => 'ok', 'data' => $this->viewConfig->getConfigs()]);
	}

	/**
	 * Set a user config
	 *
	 * @NoAdminRequired
	 *
	 * @param string $key
	 * @param string|bool $value
	 * @return JSONResponse
	 */
	public function setConfig(string $key, $value): JSONResponse {
		try {
			$this->userConfig->setConfig($key, (string)$value);
		} catch (\InvalidArgumentException $e) {
			return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse(['message' => 'ok', 'data' => ['key' => $key, 'value' => $value]]);
	}


	/**
	 * Get the user config
	 *
	 * @NoAdminRequired
	 *
	 * @return JSONResponse
	 */
	public function getConfigs(): JSONResponse {
		return new JSONResponse(['message' => 'ok', 'data' => $this->userConfig->getConfigs()]);
	}

	/**
	 * Toggle default for showing/hiding hidden files
	 *
	 * @NoAdminRequired
	 *
	 * @param bool $value
	 * @return Response
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function showHiddenFiles(bool $value): Response {
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'show_hidden', $value ? '1' : '0');
		return new Response();
	}

	/**
	 * Toggle default for cropping preview images
	 *
	 * @NoAdminRequired
	 *
	 * @param bool $value
	 * @return Response
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function cropImagePreviews(bool $value): Response {
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'crop_image_previews', $value ? '1' : '0');
		return new Response();
	}

	/**
	 * Toggle default for files grid view
	 *
	 * @NoAdminRequired
	 *
	 * @param bool $show
	 * @return Response
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function showGridView(bool $show): Response {
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'show_grid', $show ? '1' : '0');
		return new Response();
	}

	/**
	 * Get default settings for the grid view
	 *
	 * @NoAdminRequired
	 */
	public function getGridView() {
		$status = $this->config->getUserValue($this->userSession->getUser()->getUID(), 'files', 'show_grid', '0') === '1';
		return new JSONResponse(['gridview' => $status]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	#[IgnoreOpenAPI]
	public function serviceWorker(): StreamResponse {
		$response = new StreamResponse(__DIR__ . '/../../../../dist/preview-service-worker.js');
		$response->setHeaders([
			'Content-Type' => 'application/javascript',
			'Service-Worker-Allowed' => '/'
		]);
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedWorkerSrcDomain("'self'");
		$policy->addAllowedScriptDomain("'self'");
		$policy->addAllowedConnectDomain("'self'");
		$response->setContentSecurityPolicy($policy);
		return $response;
	}
}

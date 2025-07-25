<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Controller;

use OC\Files\Node\Node;
use OCA\Files\Helper;
use OCA\Files\ResponseDefinitions;
use OCA\Files\Service\TagService;
use OCA\Files\Service\UserConfig;
use OCA\Files\Service\ViewConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\StrictCookiesRequired;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StreamResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\StorageNotAvailableException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\PreConditionNotMetException;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @psalm-import-type FilesFolderTree from ResponseDefinitions
 *
 * @package OCA\Files\Controller
 */
class ApiController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IUserSession $userSession,
		private TagService $tagService,
		private IPreview $previewManager,
		private IManager $shareManager,
		private IConfig $config,
		private ?Folder $userFolder,
		private UserConfig $userConfig,
		private ViewConfig $viewConfig,
		private IL10N $l10n,
		private IRootFolder $rootFolder,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Gets a thumbnail of the specified file
	 *
	 * @since API version 1.0
	 * @deprecated 32.0.0 Use the preview endpoint provided by core instead
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
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[StrictCookiesRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getThumbnail($x, $y, $file) {
		if ($x < 1 || $y < 1) {
			return new DataResponse(['message' => 'Requested size must be numeric and a positive value.'], Http::STATUS_BAD_REQUEST);
		}

		try {
			$file = $this->userFolder?->get($file);
			if ($file === null
				|| !($file instanceof File)
				|| ($file->getId() <= 0)
			) {
				throw new NotFoundException();
			}

			// Validate the user is allowed to download the file (preview is some kind of download)
			/** @var ISharedStorage $storage */
			$storage = $file->getStorage();
			if ($storage->instanceOfStorage(ISharedStorage::class)) {
				/** @var IShare $share */
				$share = $storage->getShare();
				if (!$share->canSeeContent()) {
					throw new NotFoundException();
				}
			}

			$preview = $this->previewManager->getPreview($file, $x, $y, true);

			return new FileDisplayResponse($preview, Http::STATUS_OK, ['Content-Type' => $preview->getMimeType()]);
		} catch (NotFoundException|NotPermittedException|InvalidPathException) {
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
	 * @param string $path path
	 * @param array|string $tags array of tags
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function updateFileTags($path, $tags = null) {
		$result = [];
		// if tags specified or empty array, update tags
		if (!is_null($tags)) {
			try {
				$this->tagService->updateFileTags($path, $tags);
			} catch (NotFoundException $e) {
				return new DataResponse([
					'message' => $e->getMessage()
				], Http::STATUS_NOT_FOUND);
			} catch (StorageNotAvailableException $e) {
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
			$file = Helper::formatFileInfo($node->getFileInfo());
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
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function getRecentFiles() {
		$nodes = $this->userFolder->getRecent(100);
		$files = $this->formatNodes($nodes);
		return new DataResponse(['files' => $files]);
	}

	/**
	 * @param \OCP\Files\Node[] $nodes
	 * @param int $depth The depth to traverse into the contents of each node
	 */
	private function getChildren(array $nodes, int $depth = 1, int $currentDepth = 0): array {
		if ($currentDepth >= $depth) {
			return [];
		}

		$children = [];
		foreach ($nodes as $node) {
			if (!($node instanceof Folder)) {
				continue;
			}

			$basename = basename($node->getPath());
			$entry = [
				'id' => $node->getId(),
				'basename' => $basename,
				'children' => $this->getChildren($node->getDirectoryListing(), $depth, $currentDepth + 1),
			];
			$displayName = $node->getName();
			if ($basename !== $displayName) {
				$entry['displayName'] = $displayName;
			}
			$children[] = $entry;
		}
		return $children;
	}

	/**
	 * Returns the folder tree of the user
	 *
	 * @param string $path The path relative to the user folder
	 * @param int $depth The depth of the tree
	 *
	 * @return JSONResponse<Http::STATUS_OK, FilesFolderTree, array{}>|JSONResponse<Http::STATUS_UNAUTHORIZED|Http::STATUS_BAD_REQUEST|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 * 200: Folder tree returned successfully
	 * 400: Invalid folder path
	 * 401: Unauthorized
	 * 404: Folder not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/folder-tree')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getFolderTree(string $path = '/', int $depth = 1): JSONResponse {
		$user = $this->userSession->getUser();
		if (!($user instanceof IUser)) {
			return new JSONResponse([
				'message' => $this->l10n->t('Failed to authorize'),
			], Http::STATUS_UNAUTHORIZED);
		}
		try {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$userFolderPath = $userFolder->getPath();
			$fullPath = implode('/', [$userFolderPath, trim($path, '/')]);
			$node = $this->rootFolder->get($fullPath);
			if (!($node instanceof Folder)) {
				return new JSONResponse([
					'message' => $this->l10n->t('Invalid folder path'),
				], Http::STATUS_BAD_REQUEST);
			}
			$nodes = $node->getDirectoryListing();
			$tree = $this->getChildren($nodes, $depth);
		} catch (NotFoundException $e) {
			return new JSONResponse([
				'message' => $this->l10n->t('Folder not found'),
			], Http::STATUS_NOT_FOUND);
		} catch (Throwable $th) {
			$this->logger->error($th->getMessage(), ['exception' => $th]);
			$tree = [];
		}
		return new JSONResponse($tree);
	}

	/**
	 * Returns the current logged-in user's storage stats.
	 *
	 * @param ?string $dir the directory to get the storage stats from
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function getStorageStats($dir = '/'): JSONResponse {
		$storageInfo = \OC_Helper::getStorageInfo($dir ?: '/');
		$response = new JSONResponse(['message' => 'ok', 'data' => $storageInfo]);
		$response->cacheFor(5 * 60);
		return $response;
	}

	/**
	 * Set a user view config
	 *
	 * @param string $view
	 * @param string $key
	 * @param string|bool $value
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
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
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function getViewConfigs(): JSONResponse {
		return new JSONResponse(['message' => 'ok', 'data' => $this->viewConfig->getConfigs()]);
	}

	/**
	 * Set a user config
	 *
	 * @param string $key
	 * @param string|bool $value
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
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
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function getConfigs(): JSONResponse {
		return new JSONResponse(['message' => 'ok', 'data' => $this->userConfig->getConfigs()]);
	}

	/**
	 * Toggle default for showing/hiding hidden files
	 *
	 * @param bool $value
	 * @return Response
	 * @throws PreConditionNotMetException
	 */
	#[NoAdminRequired]
	public function showHiddenFiles(bool $value): Response {
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'show_hidden', $value ? '1' : '0');
		return new Response();
	}

	/**
	 * Toggle default for cropping preview images
	 *
	 * @param bool $value
	 * @return Response
	 * @throws PreConditionNotMetException
	 */
	#[NoAdminRequired]
	public function cropImagePreviews(bool $value): Response {
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'crop_image_previews', $value ? '1' : '0');
		return new Response();
	}

	/**
	 * Toggle default for files grid view
	 *
	 * @param bool $show
	 * @return Response
	 * @throws PreConditionNotMetException
	 */
	#[NoAdminRequired]
	public function showGridView(bool $show): Response {
		$this->config->setUserValue($this->userSession->getUser()->getUID(), 'files', 'show_grid', $show ? '1' : '0');
		return new Response();
	}

	/**
	 * Get default settings for the grid view
	 */
	#[NoAdminRequired]
	public function getGridView() {
		$status = $this->config->getUserValue($this->userSession->getUser()->getUID(), 'files', 'show_grid', '0') === '1';
		return new JSONResponse(['gridview' => $status]);
	}

	#[PublicPage]
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
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

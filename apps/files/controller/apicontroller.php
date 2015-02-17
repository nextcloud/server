<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\DownloadResponse;
use OC\Preview;
use OCA\Files\Service\TagService;

class ApiController extends Controller {

	/**
	 * @var TagService $tagService
	 */
	private $tagService;

	public function __construct($appName, IRequest $request, TagService $tagService){
		parent::__construct($appName, $request);
		$this->tagService = $tagService;
	}


	/**
	 * Gets a thumbnail of the specified file
	 *
	 * @since API version 1.0
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $x
	 * @param int $y
	 * @param string $file URL-encoded filename
	 * @return JSONResponse|DownloadResponse
	 */
	public function getThumbnail($x, $y, $file) {
		if($x < 1 || $y < 1) {
			return new JSONResponse('Requested size must be numeric and a positive value.', Http::STATUS_BAD_REQUEST);
		}

		try {
			$preview = new Preview('', 'files', urldecode($file), $x, $y, true);
			echo($preview->showPreview('image/png'));
			return new DownloadResponse(urldecode($file).'.png', 'image/png');
		} catch (\Exception $e) {
			return new JSONResponse('File not found.', Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Updates the info of the specified file path
	 * The passed tags are absolute, which means they will
	 * replace the actual tag selection.
	 *
	 * @NoAdminRequired
	 * @CORS
	 *
	 * @param string $path path
	 * @param array  $tags array of tags
	 * @return DataResponse
	 */
	public function updateFileTags($path, $tags = null) {
		$result = array();
		// if tags specified or empty array, update tags
		if (!is_null($tags)) {
			try {
				$this->tagService->updateFileTags($path, $tags);
			} catch (\OCP\Files\NotFoundException $e) {
				return new DataResponse($e->getMessage(), Http::STATUS_NOT_FOUND);
			} catch (\OCP\Files\StorageNotAvailableException $e) {
				return new DataResponse($e->getMessage(), Http::STATUS_SERVICE_UNAVAILABLE);
			} catch (\Exception $e) {
				return new DataResponse($e->getMessage(), Http::STATUS_NOT_FOUND);
			}
			$result['tags'] = $tags;
		}
		return new DataResponse($result, Http::STATUS_OK);
	}

	/**
	 * Returns a list of all files tagged with the given tag.
	 *
	 * @NoAdminRequired
	 * @CORS
	 *
	 * @param array $tagName tag name to filter by
	 * @return DataResponse
	 */
	public function getFilesByTag($tagName) {
		$files = array();
		$fileInfos = $this->tagService->getFilesByTag($tagName);
		foreach ($fileInfos as &$fileInfo) {
			$file = \OCA\Files\Helper::formatFileInfo($fileInfo);
			$parts = explode('/', dirname($fileInfo->getPath()), 4);
			if(isset($parts[3])) {
				$file['path'] = '/' . $parts[3];
			} else {
				$file['path'] = '/';
			}
			$file['tags'] = array($tagName);
			$files[] = $file;
		}
		return new DataResponse(array('files' => $files), Http::STATUS_OK);
	}

}

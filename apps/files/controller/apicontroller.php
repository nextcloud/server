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
use OCP\AppFramework\Http\DownloadResponse;
use OC\Preview;

class ApiController extends Controller {

	public function __construct($appName, IRequest $request){
		parent::__construct($appName, $request);
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

}

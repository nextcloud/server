<?php
/**
 *
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
namespace OCA\Files\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Util;

class SettingsController extends Controller {
	public function __construct($appName, IRequest $request) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param string $maxUploadSize
	 * @return JSONResponse
	 */
	public function setMaxUploadSize($maxUploadSize) {
		$setMaxSize = \OC_Files::setUploadLimit(Util::computerFileSize($maxUploadSize));

		if ($setMaxSize === false) {
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		} else {
			return new JSONResponse([
				'maxUploadSize' => Util::humanFileSize($setMaxSize)
			]);
		}
	}
}

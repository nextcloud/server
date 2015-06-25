<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\Files_Sharing\Controllers;

use OC;
use OCP;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;

/**
 * Class ExternalSharesController
 *
 * @package OCA\Files_Sharing\Controllers
 */
class ExternalSharesController extends Controller {

	/** @var bool */
	private $incomingShareEnabled;
	/** @var \OCA\Files_Sharing\External\Manager */
	private $externalManager;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param bool $incomingShareEnabled
	 * @param \OCA\Files_Sharing\External\Manager $externalManager
	 */
	public function __construct($appName,
								IRequest $request,
								$incomingShareEnabled,
								\OCA\Files_Sharing\External\Manager $externalManager) {
		parent::__construct($appName, $request);
		$this->incomingShareEnabled = $incomingShareEnabled;
		$this->externalManager = $externalManager;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JSONResponse
	 */
	public function index() {
		$shares = [];
		if ($this->incomingShareEnabled) {
			$shares = $this->externalManager->getOpenShares();
		}
		return new JSONResponse($shares);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	public function create($id) {
		if ($this->incomingShareEnabled) {
			$this->externalManager->acceptShare($id);
		}

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param $id
	 * @return JSONResponse
	 */
	public function destroy($id) {
		if ($this->incomingShareEnabled) {
			$this->externalManager->declineShare($id);
		}

		return new JSONResponse();
	}

}

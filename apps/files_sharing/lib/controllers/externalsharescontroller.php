<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @copyright 2014 Lukas Reschke
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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

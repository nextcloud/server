<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Files_Sharing\Middleware;

use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\Share\IManager;

/**
 * Checks whether the "sharing check" is enabled
 *
 * @package OCA\Files_Sharing\Middleware
 */
class ShareInfoMiddleware extends Middleware {

	/** @var string */
	protected $appName;
	/** @var IAppManager */
	protected $appManager;
	/** @var IManager */
	protected $shareManager;

	/***
	 * @param string $appName
	 * @param IAppManager $appManager
	 * @param IManager $shareManager
	 */
	public function __construct($appName,
								IAppManager $appManager,
								IManager $shareManager
	) {
		$this->appName = $appName;
		$this->appManager = $appManager;
		$this->shareManager = $shareManager;
	}

	/**
	 * Check if sharing is enabled before the controllers is executed
	 *
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @throws NotFoundException
	 */
	public function beforeController($controller, $methodName) {
		if ($controller instanceof \OCA\Files_Sharing\Controllers\ShareInfo && (
				!$this->appManager->isEnabledForUser('federatedfilesharing') ||
				!$this->shareManager->outgoingServer2ServerSharesAllowed()
			)) {
			throw new NotFoundException('Link sharing is disabled');
		}
	}

	/**
	 * Return 404 page in case of a not found exception
	 *
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @return NotFoundResponse
	 * @throws \Exception
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if ($exception instanceof NotFoundException) {
			$resp = new Response();
			$resp->setStatus(Http::STATUS_NOT_FOUND);
			return $resp;
		}

		throw $exception;
	}



}

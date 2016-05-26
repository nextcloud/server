<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Federation\Middleware;

use OC\HintException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\IL10N;
use OCP\ILogger;

class AddServerMiddleware extends Middleware {

	/** @var  string */
	protected $appName;

	/** @var  IL10N */
	protected $l;

	/** @var  ILogger */
	protected $logger;

	public function __construct($appName, IL10N $l, ILogger $logger) {
		$this->appName = $appName;
		$this->l = $l;
		$this->logger = $logger;
	}

	/**
	 * Log error message and return a response which can be displayed to the user
	 *
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @return JSONResponse
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		$this->logger->error($exception->getMessage(), ['app' => $this->appName]);
		if ($exception instanceof HintException) {
			$message = $exception->getHint();
		} else {
			$message = $exception->getMessage();
		}

		return new JSONResponse(
			['message' => $message],
			Http::STATUS_BAD_REQUEST
		);

	}

}

<?php
/**

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
namespace OC\AppFramework\Middleware;

use OC\AppFramework\Http;
use OCP\API;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\OCSResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\AppFramework\Middleware;

class OCSMiddleware extends Middleware {

	/** @var IRequest */
	private $request;

	/**
	 * @param IRequest $request
	 */
	public function __construct(IRequest $request) {
		$this->request = $request;
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @throws \Exception
	 * @return OCSResponse
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if ($controller instanceof OCSController && $exception instanceof OCSException) {
			$format = $this->getFormat($controller);

			$code = $exception->getCode();
			if ($code === 0) {
				$code = API::RESPOND_UNKNOWN_ERROR;
			}

			// Build the response
			$response = new OCSResponse($format, $code, $exception->getMessage());

			// Forbidden always sets 401 (even on v1.php)
			if ($exception instanceof OCSForbiddenException || $code === API::RESPOND_UNAUTHORISED) {
				$response->setStatus(Http::STATUS_UNAUTHORIZED);
			}

			// On v2.php we set actual HTTP error codes
			if (substr_compare($this->request->getScriptName(), '/ocs/v2.php', -strlen('/ocs/v2.php')) === 0) {
				if ($code === API::RESPOND_NOT_FOUND) {
					$response->setStatus(Http::STATUS_NOT_FOUND);
				} else if ($code === API::RESPOND_SERVER_ERROR) {
					$response->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
				} else if ($code === API::RESPOND_UNKNOWN_ERROR) {
					$response->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
				} else if ($code === API::RESPOND_UNAUTHORISED) {
					// Already set
				}
				// 4xx and 5xx codes are forwarded as is.
				else if ($code >= 400 && $code < 600) {
					$response->setStatus($code);
				} else {
					// All other codes get a bad request
					$response->setStatus(Http::STATUS_BAD_REQUEST);
				}
			}
			return $response;
		}

		throw $exception;
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @param Response $response
	 * @return \OCP\AppFramework\Http\Response
	 */
	public function afterController($controller, $methodName, Response $response) {
		/*
		 * If a different middleware has detected that a request unauthorized or forbidden
		 * we need to catch the response and convert it to a proper OCS response.
		 */
		if ($controller instanceof OCSController && !($response instanceof OCSResponse)) {
			if ($response->getStatus() === Http::STATUS_UNAUTHORIZED ||
			    $response->getStatus() === Http::STATUS_FORBIDDEN) {
				$format = $this->getFormat($controller);

				$message = '';
				if ($response instanceof JSONResponse) {
					/** @var DataResponse $response */
					$message = $response->getData()['message'];
				}
				$response = new OCSResponse($format, \OCP\API::RESPOND_UNAUTHORISED, $message);
				$response->setStatus(Http::STATUS_UNAUTHORIZED);
			}
		}

		return $response;
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @return string
	 */
	private function getFormat($controller) {
		// get format from the url format or request format parameter
		$format = $this->request->getParam('format');

		// if none is given try the first Accept header
		if($format === null) {
			$headers = $this->request->getHeader('Accept');
			$format = $controller->getResponderByHTTPHeader($headers, 'xml');
		}

		return $format;
	}
}

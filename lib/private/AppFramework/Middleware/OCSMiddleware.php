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
use OCP\AppFramework\Http\OCSResponse;
use OCP\AppFramework\OCS\OCSException;
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
				$code = Http::STATUS_INTERNAL_SERVER_ERROR;
			}

			$response = new OCSResponse($format, $code, $exception->getMessage());

			if (substr_compare($this->request->getScriptName(), '/ocs/v2.php', -strlen('/ocs/v2.php')) === 0) {
				$response->setStatus($code);
			}
			return $response;
		}

		throw $exception;
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

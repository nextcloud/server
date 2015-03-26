<?php
/**
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
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

namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\IRequest;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;

/**
 * This middleware sets the correct CORS headers on a response if the 
 * controller has the @CORS annotation. This is needed for webapps that want
 * to access an API and dont run on the same domain, see 
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
 */
class CORSMiddleware extends Middleware {

	private $request;
	private $reflector;

	/**
	 * @param IRequest $request
	 * @param ControllerMethodReflector $reflector
	 */
	public function __construct(IRequest $request, 
	                            ControllerMethodReflector $reflector) {
		$this->request = $request;
		$this->reflector = $reflector;
	}


	/**
	 * This is being run after a successful controllermethod call and allows
	 * the manipulation of a Response object. The middleware is run in reverse order
	 *
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param Response $response the generated response from the controller
	 * @return Response a Response object
	 */
	public function afterController($controller, $methodName, Response $response){
		// only react if its a CORS request and if the request sends origin and

		if(isset($this->request->server['HTTP_ORIGIN']) &&
			$this->reflector->hasAnnotation('CORS')) {

			// allow credentials headers must not be true or CSRF is possible 
			// otherwise
			foreach($response->getHeaders() as $header => $value ) {
				if(strtolower($header) === 'access-control-allow-credentials' &&
				   strtolower(trim($value)) === 'true') {
					$msg = 'Access-Control-Allow-Credentials must not be '.
						   'set to true in order to prevent CSRF';
					throw new SecurityException($msg);
				}
			}

			$origin = $this->request->server['HTTP_ORIGIN'];
			$response->addHeader('Access-Control-Allow-Origin', $origin);
		}
		return $response;
	}


}

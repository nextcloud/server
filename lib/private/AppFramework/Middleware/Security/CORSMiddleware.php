<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author korelstar <korelstar@users.noreply.github.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Stefan Weil <sw@weilnetz.de>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Security\Bruteforce\IThrottler;
use ReflectionMethod;

/**
 * This middleware sets the correct CORS headers on a response if the
 * controller has the @CORS annotation. This is needed for webapps that want
 * to access an API and don't run on the same domain, see
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
 */
class CORSMiddleware extends Middleware {
	public function __construct(private IRequest $request,
								private ControllerMethodReflector $reflector,
								private IUserSession $session,
								private IThrottler $throttler,
								private IConfig $config,
								) {
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
	 * @throws SecurityException
	 */
	public function afterController($controller, $methodName, Response $response) {
		$userId = !is_null($this->session->getUser()) ? $this->session->getUser()->getUID() : null;

		// only react if it's a CORS request and if the request sends origin and
		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		if ($this->request->getHeader("Origin") !== null
			&& $this->hasAnnotationOrAttribute($reflectionMethod, 'CORS', CORS::class)
			&& !is_null($userId)) {
			$requesterDomain = $this->request->getHeader("Origin");
			\OC_Response::setCorsHeaders($userId, $requesterDomain, $this->config);

			// allow credentials headers must not be true or CSRF is possible
			// otherwise
			foreach ($response->getHeaders() as $header => $value) {
				if (strtolower($header) === 'access-control-allow-credentials' &&
					strtolower(trim($value)) === 'true') {
					$msg = 'Access-Control-Allow-Credentials must not be '.
							'set to true in order to prevent CSRF';
					throw new SecurityException($msg);
				}
			}
		}
		return $response;
	}

	/**
	 * If an SecurityException is being caught return a JSON error response
	 *
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param \Exception $exception the thrown exception
	 * @throws \Exception the passed in exception if it can't handle it
	 * @return Response a Response object or null in case that the exception could not be handled
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if ($exception instanceof SecurityException) {
			$response = new JSONResponse(['message' => $exception->getMessage()]);
			if ($exception->getCode() !== 0) {
				$response->setStatus($exception->getCode());
			} else {
				$response->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
			}
			return $response;
		}

		throw $exception;
	}

	/**
	 * @template T
	 *
	 * @param ReflectionMethod $reflectionMethod
	 * @param string $annotationName
	 * @param class-string<T> $attributeClass
	 * @return boolean
	 */
	protected function hasAnnotationOrAttribute(ReflectionMethod $reflectionMethod, string $annotationName, string $attributeClass): bool {
		if ($this->reflector->hasAnnotation($annotationName)) {
			return true;
		}

		if (!empty($reflectionMethod->getAttributes($attributeClass))) {
			return true;
		}

		return false;
	}
}

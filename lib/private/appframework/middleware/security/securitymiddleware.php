<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Controller\Controller;
use OC\AppFramework\Http\Http;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Http\RedirectResponse;
use OC\AppFramework\Utility\MethodAnnotationReader;
use OC\AppFramework\Core\API;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\JSONResponse;


/**
 * Used to do all the authentication and checking stuff for a controller method
 * It reads out the annotations of a controller method and checks which if
 * security things should be checked and also handles errors in case a security
 * check fails
 */
class SecurityMiddleware extends Middleware {

	private $api;

	/**
	 * @var \OC\AppFramework\Http\Request
	 */
	private $request;

	/**
	 * @param API $api an instance of the api
	 */
	public function __construct(API $api, Request $request){
		$this->api = $api;
		$this->request = $request;
	}


	/**
	 * This runs all the security checks before a method call. The
	 * security checks are determined by inspecting the controller method
	 * annotations
	 * @param string/Controller $controller the controllername or string
	 * @param string $methodName the name of the method
	 * @throws SecurityException when a security check fails
	 */
	public function beforeController($controller, $methodName){

		// get annotations from comments
		$annotationReader = new MethodAnnotationReader($controller, $methodName);

		// this will set the current navigation entry of the app, use this only
		// for normal HTML requests and not for AJAX requests
		$this->api->activateNavigationEntry();

		// security checks
		$isPublicPage = $annotationReader->hasAnnotation('PublicPage');
		if(!$isPublicPage) {
			if(!$this->api->isLoggedIn()) {
				throw new SecurityException('Current user is not logged in', Http::STATUS_UNAUTHORIZED);
			}

			if(!$annotationReader->hasAnnotation('NoAdminRequired')) {
				if(!$this->api->isAdminUser($this->api->getUserId())) {
					throw new SecurityException('Logged in user must be an admin', Http::STATUS_FORBIDDEN);
				}
			}
		}

		if(!$annotationReader->hasAnnotation('NoCSRFRequired')) {
			if(!$this->api->passesCSRFCheck()) {
				throw new SecurityException('CSRF check failed', Http::STATUS_PRECONDITION_FAILED);
			}
		}

	}


	/**
	 * If an SecurityException is being caught, ajax requests return a JSON error
	 * response and non ajax requests redirect to the index
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param \Exception $exception the thrown exception
	 * @throws \Exception the passed in exception if it cant handle it
	 * @return Response a Response object or null in case that the exception could not be handled
	 */
	public function afterException($controller, $methodName, \Exception $exception){
		if($exception instanceof SecurityException){

			if (stripos($this->request->getHeader('Accept'),'html')===false) {

				$response = new JSONResponse(
					array('message' => $exception->getMessage()),
					$exception->getCode()
				);
				$this->api->log($exception->getMessage(), 'debug');
			} else {

				$url = $this->api->linkToAbsolute('index.php', ''); // TODO: replace with link to route
				$response = new RedirectResponse($url);
				$this->api->log($exception->getMessage(), 'debug');
			}

			return $response;

		}

		throw $exception;
	}

}

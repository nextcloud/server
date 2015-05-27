<?php
/**
 * ownCloud - App Framework
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2014
 */

namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;

/**
 * This middleware sets the correct CORS headers on a response if the
 * controller has the @CORS annotation. This is needed for webapps that want
 * to access an API and dont run on the same domain, see
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
 */
class CORSMiddleware extends Middleware {

	/**
	 * @var IRequest
	 */
	private $request;
	/**
	 * @var ControllerMethodReflector
	 */
	private $reflector;
	/**
	 * @var IUserSession
	 */
	private $session;

	/**
	 * @param IRequest $request
	 * @param ControllerMethodReflector $reflector
	 * @param IUserSession $session
	 */
	public function __construct(IRequest $request,
	                            ControllerMethodReflector $reflector,
	                            IUserSession $session) {
		$this->request = $request;
		$this->reflector = $reflector;
		$this->session = $session;
	}

	/**
	 * This is being run in normal order before the controller is being
	 * called which allows several modifications and checks
	 *
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @since 7.0.0
	 */
	public function beforeController($controller, $methodName){
		// ensure that @CORS annotated API routes are not used in conjunction
		// with session authentication since this enables CSRF attack vectors
		if ($this->reflector->hasAnnotation('CORS') &&
			!$this->reflector->hasAnnotation('PublicPage'))  {
			$user = $this->request->server['PHP_AUTH_USER'];
			$pass = $this->request->server['PHP_AUTH_PW'];
			$this->session->logout();
			if(!$this->session->login($user, $pass)) {
				throw new SecurityException('CORS requires basic auth');
			}
		}
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

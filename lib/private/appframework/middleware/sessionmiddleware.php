<?php
/**
 * ownCloud - App Framework
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @copyright Thomas Müller 2014
 */

namespace OC\AppFramework\Middleware;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\IRequest;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\ISession;

class SessionMiddleware extends Middleware {

	/**
	 * @var IRequest
	 */
	private $request;

	/**
	 * @var ControllerMethodReflector
	 */
	private $reflector;

	/**
	 * @param IRequest $request
	 * @param ControllerMethodReflector $reflector
	 */
	public function __construct(IRequest $request,
								ControllerMethodReflector $reflector,
								ISession $session
) {
		$this->request = $request;
		$this->reflector = $reflector;
		$this->session = $session;
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 */
	public function beforeController($controller, $methodName) {
		$useSession = $this->reflector->hasAnnotation('UseSession');
		if (!$useSession) {
			$this->session->close();
		}
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @param Response $response
	 * @return Response
	 */
	public function afterController($controller, $methodName, Response $response){
		$useSession = $this->reflector->hasAnnotation('UseSession');
		if ($useSession) {
			$this->session->close();
		}
		return $response;
	}

}

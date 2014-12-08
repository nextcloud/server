<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Settings\Middleware;

use OC\AppFramework\Http;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;

/**
 * Verifies whether an user has at least subadmin rights.
 * To bypass use the `@NoSubadminRequired` annotation
 *
 * @package OC\Settings\Middleware
 */
class SubadminMiddleware extends Middleware {
	/** @var bool */
	protected $isSubAdmin;
	/** @var ControllerMethodReflector */
	protected $reflector;

	/**
	 * @param ControllerMethodReflector $reflector
	 * @param bool $isSubAdmin
	 */
	public function __construct(ControllerMethodReflector $reflector,
								$isSubAdmin) {
		$this->reflector = $reflector;
		$this->isSubAdmin = $isSubAdmin;
	}

	/**
	 * Check if sharing is enabled before the controllers is executed
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @throws \Exception
	 */
	public function beforeController($controller, $methodName) {
		if(!$this->reflector->hasAnnotation('NoSubadminRequired')) {
			if(!$this->isSubAdmin) {
				throw new \Exception('Logged in user must be a subadmin');
			}
		}
	}

	/**
	 * Return 403 page in case of an exception
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @return TemplateResponse
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		return new TemplateResponse('core', '403', array(), 'guest');
	}

}

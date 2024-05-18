<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OC\AppFramework\Middleware;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\ISession;
use ReflectionMethod;

class SessionMiddleware extends Middleware {
	/** @var ControllerMethodReflector */
	private $reflector;

	/** @var ISession */
	private $session;

	public function __construct(ControllerMethodReflector $reflector,
		ISession $session) {
		$this->reflector = $reflector;
		$this->session = $session;
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 */
	public function beforeController($controller, $methodName) {
		/**
		 * Annotation deprecated with Nextcloud 26
		 */
		$hasAnnotation = $this->reflector->hasAnnotation('UseSession');
		if ($hasAnnotation) {
			$this->session->reopen();
			return;
		}

		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		$hasAttribute = !empty($reflectionMethod->getAttributes(UseSession::class));
		if ($hasAttribute) {
			$this->session->reopen();
		}
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param Response $response
	 * @return Response
	 */
	public function afterController($controller, $methodName, Response $response) {
		/**
		 * Annotation deprecated with Nextcloud 26
		 */
		$hasAnnotation = $this->reflector->hasAnnotation('UseSession');
		if ($hasAnnotation) {
			$this->session->close();
			return $response;
		}

		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		$hasAttribute = !empty($reflectionMethod->getAttributes(UseSession::class));
		if ($hasAttribute) {
			$this->session->close();
		}

		return $response;
	}
}

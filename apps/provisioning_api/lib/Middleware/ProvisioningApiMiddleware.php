<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Provisioning_API\Middleware;

use OCA\Provisioning_API\Middleware\Exceptions\NotSubAdminException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\Utility\IControllerMethodReflector;

class ProvisioningApiMiddleware extends Middleware {

	/**
	 * ProvisioningApiMiddleware constructor.
	 *
	 * @param IControllerMethodReflector $reflector
	 * @param bool $isAdmin
	 * @param bool $isSubAdmin
	 */
	public function __construct(
		private IControllerMethodReflector $reflector,
		private bool $isAdmin,
		private bool $isSubAdmin,
	) {
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 *
	 * @throws NotSubAdminException
	 */
	public function beforeController($controller, $methodName) {
		// If AuthorizedAdminSetting, the check will be done in the SecurityMiddleware
		if (!$this->isAdmin && !$this->reflector->hasAnnotation('NoSubAdminRequired') && !$this->isSubAdmin && !$this->reflector->hasAnnotation('AuthorizedAdminSetting')) {
			throw new NotSubAdminException();
		}
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @throws \Exception
	 * @return Response
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if ($exception instanceof NotSubAdminException) {
			throw new OCSException($exception->getMessage(), Http::STATUS_FORBIDDEN);
		}

		throw $exception;
	}
}

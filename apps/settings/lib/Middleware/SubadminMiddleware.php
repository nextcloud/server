<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Settings\Middleware;

use OC\AppFramework\Http;
use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\Group\ISubAdmin;
use OCP\IL10N;
use OCP\IUserSession;

/**
 * Verifies whether an user has at least subadmin rights.
 * To bypass use the `@NoSubAdminRequired` annotation
 */
class SubadminMiddleware extends Middleware {
	public function __construct(
		protected ControllerMethodReflector $reflector,
		protected IUserSession $userSession,
		protected ISubAdmin $subAdminManager,
		private IL10N $l10n,
	) {
	}

	private function isSubAdmin(): bool {
		$userObject = $this->userSession->getUser();
		if ($userObject === null) {
			return false;
		}
		return $this->subAdminManager->isSubAdmin($userObject);
	}

	/**
	 * Check if sharing is enabled before the controllers is executed
	 * @param Controller $controller
	 * @param string $methodName
	 * @throws \Exception
	 */
	public function beforeController($controller, $methodName) {
		if (!$this->reflector->hasAnnotation('NoSubAdminRequired') && !$this->reflector->hasAnnotation('AuthorizedAdminSetting')) {
			if (!$this->isSubAdmin()) {
				throw new NotAdminException($this->l10n->t('Logged in account must be a sub admin'));
			}
		}
	}

	/**
	 * Return 403 page in case of an exception
	 * @param Controller $controller
	 * @param string $methodName
	 * @param \Exception $exception
	 * @return TemplateResponse
	 * @throws \Exception
	 */
	public function afterException($controller, $methodName, \Exception $exception) {
		if ($exception instanceof NotAdminException) {
			$response = new TemplateResponse('core', '403', [], 'guest');
			$response->setStatus(Http::STATUS_FORBIDDEN);
			return $response;
		}

		throw $exception;
	}
}

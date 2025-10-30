<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\Exceptions\ReloadExecutionException;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Middleware;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;

/**
 * Simple middleware to handle the clearing of the execution context. This will trigger
 * a reload but if the session variable is set we properly redirect to the login page.
 */
class ReloadExecutionMiddleware extends Middleware {

	public function __construct(private ISession $session,
		private IURLGenerator $urlGenerator,
		private IRequest $request) {
		$this->session = $session;
		$this->urlGenerator = $urlGenerator;
	}

	public function beforeController($controller, $methodName) {
		if ($this->session->exists('clearingExecutionContexts')) {
			throw new ReloadExecutionException();
		}
	}

	public function afterException($controller, $methodName, \Exception $exception) {
		if ($exception instanceof ReloadExecutionException) {
			$this->session->remove('clearingExecutionContexts');

			return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute(
				'core.login.showLoginForm',
				[
					'clear' => true, // this param the code in login.js may be removed when the "Clear-Site-Data" is working in the browsers
					'redirect_url' => $this->request->getParam('redirect_url'),
				],
			));
		}

		return parent::afterException($controller, $methodName, $exception);
	}
}

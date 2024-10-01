<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\PublicShare;

use OC\AppFramework\Middleware\PublicShare\Exceptions\NeedAuthenticationException;
use OCA\Files_Sharing\AppInfo\Application;
use OCP\AppFramework\AuthPublicShareController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\PublicShareController;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\Security\Bruteforce\IThrottler;

class PublicShareMiddleware extends Middleware {

	public function __construct(
		private IRequest $request,
		private ISession $session,
		private IConfig $config,
		private IThrottler $throttler,
	) {
	}

	public function beforeController($controller, $methodName) {
		if (!($controller instanceof PublicShareController)) {
			return;
		}

		$controllerClassPath = explode('\\', get_class($controller));
		$controllerShortClass = end($controllerClassPath);
		$bruteforceProtectionAction = $controllerShortClass . '::' . $methodName;
		$this->throttler->sleepDelayOrThrowOnMax($this->request->getRemoteAddress(), $bruteforceProtectionAction);

		if (!$this->isLinkSharingEnabled()) {
			throw new NotFoundException('Link sharing is disabled');
		}

		// We require the token parameter to be set
		$token = $this->request->getParam('token');
		if ($token === null) {
			throw new NotFoundException();
		}

		// Set the token
		$controller->setToken($token);

		if (!$controller->isValidToken()) {
			$this->throttle($bruteforceProtectionAction, $token);

			$controller->shareNotFound();
			throw new NotFoundException();
		}

		// No need to check for authentication when we try to authenticate
		if ($methodName === 'authenticate' || $methodName === 'showAuthenticate') {
			return;
		}

		// If authentication succeeds just continue
		if ($controller->isAuthenticated()) {
			return;
		}

		// If we can authenticate to this controller do it else we throw a 404 to not leak any info
		if ($controller instanceof AuthPublicShareController) {
			$this->session->set('public_link_authenticate_redirect', json_encode($this->request->getParams()));
			throw new NeedAuthenticationException();
		}

		$this->throttle($bruteforceProtectionAction, $token);
		throw new NotFoundException();
	}

	public function afterException($controller, $methodName, \Exception $exception) {
		if (!($controller instanceof PublicShareController)) {
			throw $exception;
		}

		if ($exception instanceof NotFoundException) {
			return new TemplateResponse(Application::APP_ID, 'sharenotfound', [
				'message' => $exception->getMessage(),
			], 'guest', Http::STATUS_NOT_FOUND);
		}

		if ($controller instanceof AuthPublicShareController && $exception instanceof NeedAuthenticationException) {
			return $controller->getAuthenticationRedirect($this->getFunctionForRoute($this->request->getParam('_route')));
		}

		throw $exception;
	}

	private function getFunctionForRoute(string $route): string {
		$tmp = explode('.', $route);
		return array_pop($tmp);
	}

	/**
	 * Check if link sharing is allowed
	 */
	private function isLinkSharingEnabled(): bool {
		// Check if the shareAPI is enabled
		if ($this->config->getAppValue('core', 'shareapi_enabled', 'yes') !== 'yes') {
			return false;
		}

		// Check whether public sharing is enabled
		if ($this->config->getAppValue('core', 'shareapi_allow_links', 'yes') !== 'yes') {
			return false;
		}

		return true;
	}

	private function throttle($bruteforceProtectionAction, $token): void {
		$ip = $this->request->getRemoteAddress();
		$this->throttler->sleepDelay($ip, $bruteforceProtectionAction);
		$this->throttler->registerAttempt($bruteforceProtectionAction, $ip, ['token' => $token]);
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Middleware;

use Exception;
use OC\Authentication\Exceptions\TwoFactorAuthRequiredException;
use OC\Authentication\Exceptions\UserAlreadyLoggedInException;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Core\Controller\LoginController;
use OC\Core\Controller\TwoFactorChallengeController;
use OC\User\Session;
use OCA\TwoFactorNextcloudNotification\Controller\APIController;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\Authentication\TwoFactorAuth\ALoginSetupController;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;

/**
 * Two-factor authentication enforcement middleware
 */
class TwoFactorMiddleware extends Middleware {
	public function __construct(
		private Manager $twoFactorManager,
		private Session $userSession,
		private ISession $session,
		private IURLGenerator $urlGenerator,
		private IControllerMethodReflector $reflector,
		private IRequest $request,
	) {
	}

	/**
	 * Enforces two-factor authentication during controller dispatch as required.
	 *
	 * Allows requests to proceed only if two-factor authentication is not required, is already completed,
	 * or the route is explicitly exempt. Blocks access to protected controllers and routes until the user
	 * completes two-factor authentication.
	 *
	 * @param Controller $controller The active controller instance.
	 * @param string $methodName The name of the method being dispatched.
	 * @throws TwoFactorAuthRequiredException if 2FA must be completed before proceeding.
	 * @throws UserAlreadyLoggedInException if attempting to access a 2FA challenge after completing 2FA.
	 */
	public function beforeController(Controller $controller, string $methodName) {
		$isChallengeController = $controller instanceof TwoFactorChallengeController;
		$isSetupController = $controller instanceof ALoginSetupController;

		// Allow bypass for routes that explicitly do not require 2FA.
		if ($this->reflector->hasAnnotation('NoTwoFactorRequired')) {
			return;
		}

		// Allow bypass when polling for 2FA notification state ((could probably use NoTwoFactorRequired instead, but explicit policy doesn't hurt).
		if ($controller instanceof APIController && $methodName === 'poll') {
			return;
		}

		// Allow bypass for logging out (could probably use NoTwoFactorRequired instead, but explicit policy doesn't hurt).
		if ($controller instanceof LoginController && $methodName === 'logout') {
			return;
		}

		// Allow bypass if there is no user session to enforce 2FA for.
		if (!$this->userSession->isLoggedIn()) {
			return;
		}

		$user = $this->userSession->getUser();

		// Allow bypass if session is already 2FA-complete or 2FA exempt.
		if ($this->twoFactorManager->isTwoFactorAuthenticated($user)) {
			return;
		}

		// Allow bypass if session is using app/api tokens.
		if ($this->session->exists('app_password') || $this->session->exists('app_api')) {
			// TODO: Check duplicate code in OC\Authentication\TwoFactorAuth::needsSecondFactor() (and see #1031)
			return;
		}

		$needsSecondFactor = $this->twoFactorManager->needsSecondFactor($user);

		// Access control logic for all 2FA setup routes and most 2FA challenge routes
		if (
			// a challenge route that doesn't require a completed 2FA setup
			($isChallengeController && !$this->reflector->hasAnnotation('TwoFactorSetUpDoneRequired'))
			// a setup route when the user needs to go through 2FA
			|| ($isSetupController && $needsSecondFactor)
		) {
			$providers = $this->twoFactorManager->getProviderSet($user);
			$primaryProviders = $providers->getPrimaryProviders();
			$providerMissing = $providers->isProviderMissing();

			// Allow bypass if user has no configured providers and none are required by policy.
			if (count($primaryProviders) === 0 && !$providerMissing) {
				return;
			}

			// Enforce 2FA:
			// - If a provider exists, user will be redirected to the appropriate 2FA challenge.
			// - If a required provider is missing, this locks the user out until admin intervention.
			// TODO: Consider calling out a missing provider (i.e. logging for admin, using a different exception/handling differently)
			throw new TwoFactorAuthRequiredException();
		}
	
		// Block access if user requests a challenge route, but doesn't need 2FA.
		if ($isChallengeController && !$needsSecondFactor) {
			throw new UserAlreadyLoggedInException();
		}

		// Enforce 2FA for all other controllers/routes if 2FA is still required.
		if ($needsSecondFactor && !$isChallengeController) {
			// Ensures users cannot interact with normal login routes while 2FA is still required.
			throw new TwoFactorAuthRequiredException();
		}
	}

	/**
	 * Handles exceptions related to two-factor authentication during controller execution.
	 *
	 * - Redirects to the 2FA challenge selection page if a TwoFactorAuthRequiredException is thrown,
	 *   passing along the current or requested URL for redirect after challenge completion.
	 * - Redirects to the file index view if a UserAlreadyLoggedInException is thrown, 
	 *   indicating the user tried to access a 2FA route after already completing authentication.
	 * - Rethrows all other exceptions for standard handling.
	 *
	 * @param Controller $controller The active controller instance.
	 * @param string $methodName The invoked method name.
	 * @param Exception $exception The exception that was thrown.
	 * @return RedirectResponse
	 * @throws Exception For anything not related to 2FA flow.
	 */
	public function afterException(Controller $controller, string $methodName, Exception $exception) {
		if ($exception instanceof TwoFactorAuthRequiredException) {
			$params = [
				'redirect_url' => $this->request->getParam('redirect_url'),
			];
			if (!isset($params['redirect_url']) && isset($this->request->server['REQUEST_URI'])) {
				$params['redirect_url'] = $this->request->server['REQUEST_URI'];
			}
			return new RedirectResponse($this->urlGenerator->linkToRoute('core.TwoFactorChallenge.selectChallenge', $params));
		}
		if ($exception instanceof UserAlreadyLoggedInException) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.index'));
		}

		throw $exception;
	}
}

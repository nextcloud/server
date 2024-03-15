<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
	 * @param Controller $controller
	 * @param string $methodName
	 */
	public function beforeController($controller, $methodName) {
		if ($this->reflector->hasAnnotation('NoTwoFactorRequired')) {
			// Route handler explicitly marked to work without finished 2FA are
			// not blocked
			return;
		}

		if ($controller instanceof APIController && $methodName === 'poll') {
			// Allow polling the twofactor nextcloud notifications state
			return;
		}

		if ($controller instanceof TwoFactorChallengeController
			&& $this->userSession->getUser() !== null
			&& !$this->reflector->hasAnnotation('TwoFactorSetUpDoneRequired')) {
			$providers = $this->twoFactorManager->getProviderSet($this->userSession->getUser());

			if (!($providers->getPrimaryProviders() === [] && !$providers->isProviderMissing())) {
				throw new TwoFactorAuthRequiredException();
			}
		}

		if ($controller instanceof ALoginSetupController
			&& $this->userSession->getUser() !== null
			&& $this->twoFactorManager->needsSecondFactor($this->userSession->getUser())) {
			$providers = $this->twoFactorManager->getProviderSet($this->userSession->getUser());

			if ($providers->getPrimaryProviders() === [] && !$providers->isProviderMissing()) {
				return;
			}
		}

		if ($controller instanceof LoginController && $methodName === 'logout') {
			// Don't block the logout page, to allow canceling the 2FA
			return;
		}

		if ($this->userSession->isLoggedIn()) {
			$user = $this->userSession->getUser();

			if ($this->session->exists('app_password')  // authenticated using an app password
				|| $this->session->exists('app_api')  // authenticated using an AppAPI Auth
				|| $this->twoFactorManager->isTwoFactorAuthenticated($user)) {

				$this->checkTwoFactor($controller, $methodName, $user);
			} elseif ($controller instanceof TwoFactorChallengeController) {
				// Allow access to the two-factor controllers only if two-factor authentication
				// is in progress.
				throw new UserAlreadyLoggedInException();
			}
		}
		// TODO: dont check/enforce 2FA if a auth token is used
	}

	private function checkTwoFactor(Controller $controller, $methodName, IUser $user) {
		// If two-factor auth is in progress disallow access to any controllers
		// defined within "LoginController".
		$needsSecondFactor = $this->twoFactorManager->needsSecondFactor($user);
		$twoFactor = $controller instanceof TwoFactorChallengeController;

		// Disallow access to any controller if 2FA needs to be checked
		if ($needsSecondFactor && !$twoFactor) {
			throw new TwoFactorAuthRequiredException();
		}

		// Allow access to the two-factor controllers only if two-factor authentication
		// is in progress.
		if (!$needsSecondFactor && $twoFactor) {
			throw new UserAlreadyLoggedInException();
		}
	}

	public function afterException($controller, $methodName, Exception $exception) {
		if ($exception instanceof TwoFactorAuthRequiredException) {
			$params = [];
			if (isset($this->request->server['REQUEST_URI'])) {
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

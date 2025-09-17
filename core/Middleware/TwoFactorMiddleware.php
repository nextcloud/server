<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Middleware;

use Exception;
use OC\AppFramework\Http\Attributes\TwoFactorSetUpDoneRequired;
use OC\Authentication\Exceptions\TwoFactorAuthRequiredException;
use OC\Authentication\Exceptions\UserAlreadyLoggedInException;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Core\Controller\LoginController;
use OC\Core\Controller\TwoFactorChallengeController;
use OC\User\Session;
use OCA\TwoFactorNextcloudNotification\Controller\APIController;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoTwoFactorRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\Authentication\TwoFactorAuth\ALoginSetupController;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

class TwoFactorMiddleware extends Middleware {
	public function __construct(
		private Manager $twoFactorManager,
		private Session $userSession,
		private ISession $session,
		private IURLGenerator $urlGenerator,
		private IControllerMethodReflector $reflector,
		private IRequest $request,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 */
	public function beforeController($controller, $methodName) {
		$reflectionMethod = new ReflectionMethod($controller, $methodName);

		if ($this->hasAnnotationOrAttribute($reflectionMethod, 'NoTwoFactorRequired', NoTwoFactorRequired::class)) {
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
			&& !$reflectionMethod->getAttributes(TwoFactorSetUpDoneRequired::class)) {
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

				$this->checkTwoFactor($controller, $user);
			} elseif ($controller instanceof TwoFactorChallengeController) {
				// Allow access to the two-factor controllers only if two-factor authentication
				// is in progress.
				throw new UserAlreadyLoggedInException();
			}
		}
		// TODO: dont check/enforce 2FA if a auth token is used
	}

	private function checkTwoFactor(Controller $controller, IUser $user) {
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


	/**
	 * @template T
	 *
	 * @param ReflectionMethod $reflectionMethod
	 * @param ?string $annotationName
	 * @param class-string<T> $attributeClass
	 * @return boolean
	 */
	protected function hasAnnotationOrAttribute(ReflectionMethod $reflectionMethod, ?string $annotationName, string $attributeClass): bool {
		if (!empty($reflectionMethod->getAttributes($attributeClass))) {
			return true;
		}

		if ($annotationName && $this->reflector->hasAnnotation($annotationName)) {
			$this->logger->debug($reflectionMethod->getDeclaringClass()->getName() . '::' . $reflectionMethod->getName() . ' uses the @' . $annotationName . ' annotation and should use the #[' . $attributeClass . '] attribute instead');
			return true;
		}

		return false;
	}
}

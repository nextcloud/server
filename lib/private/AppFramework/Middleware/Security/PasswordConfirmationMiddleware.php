<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\Exceptions\NotConfirmedException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Authentication\Token\IProvider;
use OC\User\Manager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Exceptions\ExpiredTokenException;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Exceptions\WipeTokenException;
use OCP\Authentication\Token\IToken;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Session\Exceptions\SessionNotAvailableException;
use OCP\User\Backend\IPasswordConfirmationBackend;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

class PasswordConfirmationMiddleware extends Middleware {
	private array $excludedUserBackEnds = ['user_saml' => true, 'user_globalsiteselector' => true];

	public function __construct(
		private ControllerMethodReflector $reflector,
		private ISession $session,
		private IUserSession $userSession,
		private ITimeFactory $timeFactory,
		private IProvider $tokenProvider,
		private readonly LoggerInterface $logger,
		private readonly IRequest $request,
		private readonly Manager $userManager,
	) {
	}

	/**
	 * @throws NotConfirmedException
	 */
	public function beforeController(Controller $controller, string $methodName) {
		$reflectionMethod = new ReflectionMethod($controller, $methodName);

		if (!$this->needsPasswordConfirmation($reflectionMethod)) {
			return;
		}

		$user = $this->userSession->getUser();
		$backendClassName = '';
		if ($user !== null) {
			$backend = $user->getBackend();
			if ($backend instanceof IPasswordConfirmationBackend) {
				if (!$backend->canConfirmPassword($user->getUID())) {
					return;
				}
			}

			$backendClassName = $user->getBackendClassName();
		}

		try {
			$sessionId = $this->session->getId();
			$token = $this->tokenProvider->getToken($sessionId);
		} catch (SessionNotAvailableException|InvalidTokenException|WipeTokenException|ExpiredTokenException) {
			// States we do not deal with here.
			return;
		}

		$scope = $token->getScopeAsArray();
		if (isset($scope[IToken::SCOPE_SKIP_PASSWORD_VALIDATION]) && $scope[IToken::SCOPE_SKIP_PASSWORD_VALIDATION] === true) {
			// Users logging in from SSO backends cannot confirm their password by design
			return;
		}

		if ($this->isPasswordConfirmationStrict($reflectionMethod)) {
			$authHeader = $this->request->getHeader('Authorization');
			[, $password] = explode(':', base64_decode(substr($authHeader, 6)), 2);
			$loginName = $this->session->get('loginname');
			$loginResult = $this->userManager->checkPassword($loginName, $password);
			if ($loginResult === false) {
				throw new NotConfirmedException();
			}

			$this->session->set('last-password-confirm', $this->timeFactory->getTime());
		} else {
			$lastConfirm = (int)$this->session->get('last-password-confirm');
			// TODO: confirm excludedUserBackEnds can go away and remove it
			if (!isset($this->excludedUserBackEnds[$backendClassName]) && $lastConfirm < ($this->timeFactory->getTime() - (30 * 60 + 15))) { // allow 15 seconds delay
				throw new NotConfirmedException();
			}
		}
	}

	private function needsPasswordConfirmation(ReflectionMethod $reflectionMethod): bool {
		$attributes = $reflectionMethod->getAttributes(PasswordConfirmationRequired::class);
		if (!empty($attributes)) {
			return true;
		}

		if ($this->reflector->hasAnnotation('PasswordConfirmationRequired')) {
			$this->logger->debug($reflectionMethod->getDeclaringClass()->getName() . '::' . $reflectionMethod->getName() . ' uses the @' . 'PasswordConfirmationRequired' . ' annotation and should use the #[PasswordConfirmationRequired] attribute instead');
			return true;
		}

		return false;
	}

	private function isPasswordConfirmationStrict(ReflectionMethod $reflectionMethod): bool {
		$attributes = $reflectionMethod->getAttributes(PasswordConfirmationRequired::class);
		return !empty($attributes) && ($attributes[0]->newInstance()->getStrict());
	}
}

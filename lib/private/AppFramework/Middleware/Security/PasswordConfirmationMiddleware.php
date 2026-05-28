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
use ReflectionAttribute;
use ReflectionMethod;

class PasswordConfirmationMiddleware extends Middleware {
	private const PASSWORD_CONFIRMATION_TIMEOUT = 30 * 60;
	private const PASSWORD_CONFIRMATION_GRACE_SECONDS = 15;

	/**
	 * Legacy compatibility allowlist for backends that do not participate in the
	 * non-strict recent-confirmation flow. New backends should prefer implementing
	 * IPasswordConfirmationBackend instead of being added here.
	 *
	 * @var array<string, true>
	 */
	private array $excludedUserBackEnds = [
		'user_saml' => true,
		'user_globalsiteselector' => true,
	];

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
	#[\Override]
	public function beforeController(Controller $controller, string $methodName) {
		if (!$this->needsPasswordConfirmation()) {
			return;
		}

		$user = $this->userSession->getUser();

		if ($this->isBackendExemptFromPasswordConfirmation($user)) {
			return;
		}

		try {
			$sessionId = $this->session->getId();
			$token = $this->tokenProvider->getToken($sessionId);
		} catch (SessionNotAvailableException|InvalidTokenException|WipeTokenException|ExpiredTokenException) {
			// Only valid interactive session tokens participate in password confirmation. Requests without such a
			// token are left to be rejected or otherwise handled by the normal authentication/session handling.
			return;
		}

		if ($this->isTokenExemptFromPasswordConfirmation($token)) {
			// Users logging in from SSO backends cannot confirm their password by design
			return;
		}

		$now = $this->timeFactory->getTime();
		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		if ($this->isPasswordConfirmationStrict($reflectionMethod)) {
			$this->confirmPasswordFromAuthorizationHeader();
			$this->session->set('last-password-confirm', $now);
			return;
		}
		
		$lastConfirm = (int)$this->session->get('last-password-confirm');
		$minimumRequiredConfirmTime = $now
			- (self::PASSWORD_CONFIRMATION_TIMEOUT + self::PASSWORD_CONFIRMATION_GRACE_SECONDS);

		// TODO: confirm excludedUserBackEnds can go away and remove it
		if (
			!$this->isLegacyBackendExcludedFromRecentConfirmation($user)
			&& $lastConfirm < $minimumRequiredConfirmTime
		) {
			throw new NotConfirmedException();
		}
	}

	private function needsPasswordConfirmation(): bool {
		return $this->reflector->hasAnnotationOrAttribute(
			'PasswordConfirmationRequired',
			PasswordConfirmationRequired::class
		);
	}

	private function isPasswordConfirmationStrict(ReflectionMethod $reflectionMethod): bool {
		/** @var ReflectionAttribute<PasswordConfirmationRequired>[] $attributes */
		$attributes = $reflectionMethod->getAttributes(PasswordConfirmationRequired::class);
		return !empty($attributes) && ($attributes[0]->newInstance()->getStrict());
	}

	private function isBackendExemptFromPasswordConfirmation(?IUser $user): bool {
		if ($user === null) {
			return false;
		}

		$backend = $user->getBackend();
		return $backend instanceof IPasswordConfirmationBackend
			&& !$backend->canConfirmPassword($user->getUID());
	}

	private function isLegacyBackendExcludedFromRecentConfirmation(?IUser $user): bool {
		$backendClassName = $user?->getBackendClassName() ?? '';
		return isset($this->excludedUserBackEnds[$backendClassName]);
	}

	private function isTokenExemptFromPasswordConfirmation(IToken $token): bool {
		$scope = $token->getScopeAsArray();
		return isset($scope[IToken::SCOPE_SKIP_PASSWORD_VALIDATION])
			&& $scope[IToken::SCOPE_SKIP_PASSWORD_VALIDATION] === true;
	}

	/**
	 * @throws NotConfirmedException
	 */
	private function confirmPasswordFromAuthorizationHeader(): void {
		$authHeader = strtolower($this->request->getHeader('Authorization'));

		if (!str_starts_with($authHeader, 'basic ')) {
			throw new NotConfirmedException('Required authorization header missing');
		}

		$decodedCredentials = base64_decode(substr($authHeader, 6), true);

		if ($decodedCredentials === false || !str_contains($decodedCredentials, ':')) {
			throw new NotConfirmedException('Malformed authorization header');
		}

		[$ignoredUser, $password] = explode(':', $decodedCredentials, 2);

		$loginName = $this->session->get('loginname');
		$loginResult = $this->userManager->checkPassword($loginName, $password);

		if ($loginResult === false) {
			throw new NotConfirmedException();
		}
	}
}

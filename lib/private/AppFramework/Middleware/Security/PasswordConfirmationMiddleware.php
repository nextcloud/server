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

/**
 * Middleware that enforces password reconfirmation for sensitive controller actions.
 *
 * When a controller method is annotated/attributed with PasswordConfirmationRequired this
 * middleware ensures a recent password reconfirmation or performs a strict HTTP Basic
 * re-check. Skip conditions include tokens with IToken::SCOPE_SKIP_PASSWORD_VALIDATION and
 * backends that implement IPasswordConfirmationBackend and opt out of confirmation.
 *
 * Implementation notes:
 *  - Malformed headers or failed checks raise NotConfirmedException.
 *  - Credentials are never logged; token retrieval failures are logged and result in no enforcement.
 */
class PasswordConfirmationMiddleware extends Middleware {
	// TODO: confirm excludedUserBackEnds can go away and remove it
	private array $excludedUserBackEnds = [
		'user_saml' => true,
		'user_globalsiteselector' => true
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
	 * Enforce password confirmation before invoking the controller method.
	 *
	 * If confirmation is required (see class doc) this performs either:
	 *  - Strict mode: validate "Authorization: Basic <base64(user:pass)>", verify via Manager::checkPassword(),
	 *    and update session 'last-password-confirm' on success.
	 *  - Non-strict mode: verify session 'last-password-confirm' falls within the allowed window.
	 *
	 * Does not log credential material. On malformed input or failed verification a NotConfirmedException is thrown.
	 *
	 * @param Controller $controller The controller instance to be executed.
	 * @param string $methodName The controller method name to be invoked.
	 * @throws NotConfirmedException When confirmation is required but not satisfied.
	 * @internal
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
			// NOTE: returning here may skip enforcement in certain edge cases where token retrieval fails.
			$this->logger->info('PasswordConfirmationMiddleware: could not retrieve sessionId or token; continuing anyway');
			return;
		}

		$scope = $token->getScopeAsArray();
		if (isset($scope[IToken::SCOPE_SKIP_PASSWORD_VALIDATION])
			&& $scope[IToken::SCOPE_SKIP_PASSWORD_VALIDATION] === true
		) {
			// Users logging in from SSO backends cannot confirm their password by design
			return;
		}

		if ($this->isPasswordConfirmationStrict($reflectionMethod)) {
			$authHeader = $this->request->getHeader('Authorization');

			// Validate header is present and a string
			/** @psalm-suppress TypeDoesNotContainType */
			if (!is_string($authHeader) || $authHeader === '') {
				throw new NotConfirmedException('Required authorization header missing');
			}

			// Extract base64 token from "Basic <b64>" in a robust, case-insensitive way
			if (!preg_match('/^\s*Basic\s+([A-Za-z0-9+\/=]+)\s*$/i', $authHeader, $matches)) {
				// Accept only "Basic <token>" and nothing else (allow trailing whitespace)
				throw new NotConfirmedException('Required authorization header missing or malformed');
			}
			$b64 = trim($matches[1]);

			// Enforce reasonable max length to reduce risk of abuse/DoS
			if (strlen($b64) > 4096) {
				throw new NotConfirmedException('Authorization token too long');
			}
			// Strictly decode base64; false means invalid base64
			$decoded = base64_decode($b64, true);
			if ($decoded === false || $decoded === '') {
				throw new NotConfirmedException('Invalid authorization header encoding');
			}

			// Detect non-UTF-8 payloads for monitoring/telemetry while preserving
			// current behavior (we continue to use the raw bytes as before).
			// Do NOT log the decoded payload or any credential material.
			if (function_exists('mb_check_encoding')) {
				$isUtf8 = mb_check_encoding($decoded, 'UTF-8');
			} else {
				// Fallback if mbstring isn't available: preg_match with /u will
				// return 1 for valid UTF-8, 0 for invalid. Suppress warnings.
				$isUtf8 = @preg_match('//u', $decoded) === 1;
			}
			if (!$isUtf8) {
				$this->logger->info('Non-UTF-8 Authorization Basic payload detected; continuing with raw bytes for compatibility');
			}

			// Expect exactly "username:password" (password may contain colons; we split into max 2 parts)
			$parts = explode(':', $decoded, 2);
			if (!isset($parts[1])) { // password is missing
				throw new NotConfirmedException('Authorization payload malformed');
			}
			$password = $parts[1];

			$loginName = $this->session->get('loginname'); // Current behavior, but would $user->getUID() be more robust?
			if (!is_string($loginName) || $loginName === '') {
				$this->logger->warning('PasswordConfirmationMiddleware: session loginname missing for strict confirmation');
				throw new NotConfirmedException('Unable to confirm password');
			}

			$loginResult = $this->userManager->checkPassword($loginName, $password);
			if ($loginResult === false) {
				throw new NotConfirmedException('Unable to confirm password');
			}

			$this->session->set('last-password-confirm', $this->timeFactory->getTime());
		} else {
			$lastConfirm = (int)$this->session->get('last-password-confirm');
			// TODO: confirm excludedUserBackEnds can go away and remove it
			if (!isset($this->excludedUserBackEnds[$backendClassName])
				&& $lastConfirm < ($this->timeFactory->getTime() - (30 * 60 + 15)) // allow 15 seconds delay
			) {
				throw new NotConfirmedException();
			}
		}
	}

	/**
	 * Determine whether the given controller method requires password reconfirmation.
	 *
	 * Checks for the PasswordConfirmationRequired attribute (preferred). If no attribute
	 * is present the legacy @PasswordConfirmationRequired annotation is queried; when
	 * the legacy annotation is used a debug log is emitted recommending the attribute.
	 *
	 * @param ReflectionMethod $reflectionMethod The controller method to inspect.
	 * @return bool True when password confirmation is required, false otherwise.
	 * @internal
	 */
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

	/**
	 * Determine whether the PasswordConfirmationRequired attribute requests strict confirmation.
	 *
	 * Inspects the PasswordConfirmationRequired attribute on the provided controller method
	 * and returns its `strict` flag. Only attributes are considered (legacy annotations are
	 * not evaluated by this helper).
	 *
	 * @param ReflectionMethod $reflectionMethod The controller method to inspect.
	 * @return bool True if strict (HTTP Basic re-check) is required, false otherwise.
	 * @internal
	 */
	private function isPasswordConfirmationStrict(ReflectionMethod $reflectionMethod): bool {
		/** @var ReflectionAttribute<PasswordConfirmationRequired>[] $attributes */
		$attributes = $reflectionMethod->getAttributes(PasswordConfirmationRequired::class);
		return !empty($attributes) && ($attributes[0]->newInstance()->getStrict());
	}
}

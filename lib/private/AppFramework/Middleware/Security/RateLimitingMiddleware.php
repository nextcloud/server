<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Security\Ip\BruteforceAllowList;
use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OC\Security\RateLimiting\Limiter;
use OC\User\Session;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\ARateLimit;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;
use ReflectionMethod;

/**
 * Class RateLimitingMiddleware is the middleware responsible for implementing the
 * ratelimiting in Nextcloud.
 *
 * It parses annotations such as:
 *
 * @UserRateThrottle(limit=5, period=100)
 * @AnonRateThrottle(limit=1, period=100)
 *
 * Or attributes such as:
 *
 * #[UserRateLimit(limit: 5, period: 100)]
 * #[AnonRateLimit(limit: 1, period: 100)]
 *
 * Both sets would mean that logged-in users can access the page 5
 * times within 100 seconds, and anonymous users 1 time within 100 seconds. If
 * only an AnonRateThrottle is specified that one will also be applied to logged-in
 * users.
 *
 * @package OC\AppFramework\Middleware\Security
 */
class RateLimitingMiddleware extends Middleware {
	public function __construct(
		protected IRequest $request,
		protected IUserSession $userSession,
		protected ControllerMethodReflector $reflector,
		protected Limiter $limiter,
		protected ISession $session,
		protected IAppConfig $appConfig,
		protected BruteforceAllowList $bruteForceAllowList,
	) {
	}

	/**
	 * {@inheritDoc}
	 * @throws RateLimitExceededException
	 */
	public function beforeController(Controller $controller, string $methodName): void {
		parent::beforeController($controller, $methodName);
		$rateLimitIdentifier = get_class($controller) . '::' . $methodName;

		if ($this->userSession instanceof Session && $this->userSession->getSession()->get('app_api') === true && $this->userSession->getUser() === null) {
			// if userId is not specified and the request is authenticated by AppAPI, we skip the rate limit
			return;
		}

		if ($this->userSession->isLoggedIn()) {
			$rateLimit = $this->readLimitFromAnnotationOrAttribute($controller, $methodName, 'UserRateThrottle', UserRateLimit::class);

			if ($rateLimit !== null) {
				if ($this->appConfig->getValueBool('bruteforcesettings', 'apply_allowlist_to_ratelimit')
					&& $this->bruteForceAllowList->isBypassListed($this->request->getRemoteAddress())) {
					return;
				}

				$this->limiter->registerUserRequest(
					$rateLimitIdentifier,
					$rateLimit->getLimit(),
					$rateLimit->getPeriod(),
					$this->userSession->getUser()
				);
				return;
			}

			// If not user specific rate limit is found the Anon rate limit applies!
		}

		$rateLimit = $this->readLimitFromAnnotationOrAttribute($controller, $methodName, 'AnonRateThrottle', AnonRateLimit::class);

		if ($rateLimit !== null) {
			$this->limiter->registerAnonRequest(
				$rateLimitIdentifier,
				$rateLimit->getLimit(),
				$rateLimit->getPeriod(),
				$this->request->getRemoteAddress()
			);
		}
	}

	/**
	 * @template T of ARateLimit
	 *
	 * @param Controller $controller
	 * @param string $methodName
	 * @param string $annotationName
	 * @param class-string<T> $attributeClass
	 * @return ?ARateLimit
	 */
	protected function readLimitFromAnnotationOrAttribute(Controller $controller, string $methodName, string $annotationName, string $attributeClass): ?ARateLimit {
		$annotationLimit = $this->reflector->getAnnotationParameter($annotationName, 'limit');
		$annotationPeriod = $this->reflector->getAnnotationParameter($annotationName, 'period');

		if ($annotationLimit !== '' && $annotationPeriod !== '') {
			return new $attributeClass(
				(int)$annotationLimit,
				(int)$annotationPeriod,
			);
		}

		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		$attributes = $reflectionMethod->getAttributes($attributeClass);
		$attribute = current($attributes);

		if ($attribute !== false) {
			return $attribute->newInstance();
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function afterException(Controller $controller, string $methodName, \Exception $exception): Response {
		if ($exception instanceof RateLimitExceededException) {
			if (stripos($this->request->getHeader('Accept'), 'html') === false) {
				$response = new DataResponse([], $exception->getCode());
			} else {
				$response = new TemplateResponse(
					'core',
					'429',
					[],
					TemplateResponse::RENDER_AS_GUEST
				);
				$response->setStatus($exception->getCode());
			}

			return $response;
		}

		throw $exception;
	}
}

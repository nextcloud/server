<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\MiddlewareUtils;
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OC\Authentication\Exceptions\PasswordLoginForbiddenException;
use OC\User\Session;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\IRequest;
use OCP\ISession;
use OCP\Security\Bruteforce\IThrottler;
use Override;
use ReflectionMethod;

/**
 * This middleware sets the correct CORS headers on a response if the
 * controller has the @CORS annotation. This is needed for webapps that want
 * to access an API and don't run on the same domain, see
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
 */
class CORSMiddleware extends Middleware {

	public function __construct(
		private readonly IRequest $request,
		private readonly MiddlewareUtils $middlewareUtils,
		private readonly Session $session,
		private readonly IThrottler $throttler,
	) {
	}

	#[Override]
	public function beforeController(Controller $controller, string $methodName): void {
		$reflectionMethod = new ReflectionMethod($controller, $methodName);

		// ensure that @CORS annotated API routes are not used in conjunction
		// with session authentication since this enables CSRF attack vectors
		if ($this->middlewareUtils->hasAnnotationOrAttribute($reflectionMethod, 'CORS', CORS::class)
			&& (!$this->middlewareUtils->hasAnnotationOrAttribute($reflectionMethod, 'PublicPage', PublicPage::class) || $this->session->isLoggedIn())) {
			$user = array_key_exists('PHP_AUTH_USER', $this->request->server) ? $this->request->server['PHP_AUTH_USER'] : null;
			$pass = array_key_exists('PHP_AUTH_PW', $this->request->server) ? $this->request->server['PHP_AUTH_PW'] : null;

			// Allow Bearer token authentication for CORS requests
			// Bearer tokens are stateless and don't require CSRF protection
			$authorizationHeader = $this->request->getHeader('Authorization');
			if (!empty($authorizationHeader) && str_starts_with($authorizationHeader, 'Bearer ')) {
				return;
			}

			// Allow to use the current session if a CSRF token is provided
			if ($this->request->passesCSRFCheck()) {
				return;
			}
			// Skip CORS check for requests with AppAPI auth.
			if ($this->session->getSession() instanceof ISession && $this->session->getSession()->get('app_api') === true) {
				return;
			}
			$this->session->logout();
			try {
				if ($user === null || $pass === null || !$this->session->logClientIn($user, $pass, $this->request, $this->throttler)) {
					throw new SecurityException('CORS requires basic auth', Http::STATUS_UNAUTHORIZED);
				}
			} catch (PasswordLoginForbiddenException $ex) {
				throw new SecurityException('Password login forbidden, use token instead', Http::STATUS_UNAUTHORIZED);
			}
		}
	}

	#[Override]
	public function afterController(Controller $controller, string $methodName, Response $response): Response {
		// only react if it's a CORS request and if the request sends origin and

		if (isset($this->request->server['HTTP_ORIGIN'])) {
			$reflectionMethod = new ReflectionMethod($controller, $methodName);
			if ($this->middlewareUtils->hasAnnotationOrAttribute($reflectionMethod, 'CORS', CORS::class)) {
				// allow credentials headers must not be true or CSRF is possible
				// otherwise
				foreach ($response->getHeaders() as $header => $value) {
					if (strtolower($header) === 'access-control-allow-credentials'
					   && strtolower(trim($value)) === 'true') {
						$msg = 'Access-Control-Allow-Credentials must not be '
							   . 'set to true in order to prevent CSRF';
						throw new SecurityException($msg);
					}
				}

				$origin = $this->request->server['HTTP_ORIGIN'];
				$response->addHeader('Access-Control-Allow-Origin', $origin);
			}
		}
		return $response;
	}

	/**
	 * If an SecurityException is being caught return a JSON error response
	 */
	#[Override]
	public function afterException(Controller $controller, string $methodName, \Exception $exception): Response {
		if ($exception instanceof SecurityException) {
			$response = new JSONResponse(['message' => $exception->getMessage()]);
			if ($exception->getCode() !== 0) {
				$response->setStatus($exception->getCode());
			} else {
				$response->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
			}
			return $response;
		}

		throw $exception;
	}
}
